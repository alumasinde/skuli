<?php
declare(strict_types=1);

namespace Modules\Finance\Services;

use Modules\Finance\Repositories\FinanceRepository;

final class FinanceService
{
    public function __construct(private readonly FinanceRepository $repo) {}

    public function createFeeType(array $data): array
    {
        if (trim((string) ($data['name'] ?? '')) === '') {
            throw new \InvalidArgumentException('Fee type name is required.');
        }
        if (!isset($data['amount']) || (float) $data['amount'] <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than zero.');
        }
        $id = $this->repo->createFeeType($data);
        return $this->repo->findFeeTypeById($id);
    }

    public function listFeeTypes(int $schoolId): array
    {
        return $this->repo->listFeeTypes($schoolId);
    }

    /**
     * Bulk-generate invoices. Now takes $schoolId explicitly and validates
     * every class_id belongs to it before touching a single student —
     * previously this method had no school scoping at all, so a class_id
     * from a different tenant would silently generate invoices for
     * students that don't belong to the requesting school.
     */
    public function generateInvoices(array $data, int $schoolId): int
    {
        $feeType = $this->repo->findFeeTypeById((int) $data['fee_type_id']);
        if (!$feeType || (int) $feeType['school_id'] !== $schoolId) {
            throw new \InvalidArgumentException('Fee type not found.');
        }
        if (empty($data['due_date'])) {
            throw new \InvalidArgumentException('Due date is required.');
        }
        if (empty($data['term_id'])) {
            throw new \InvalidArgumentException('Term is required.');
        }

        $count    = 0;
        $classIds = (array) ($data['class_ids'] ?? []);

        if (empty($classIds)) {
            throw new \InvalidArgumentException('Select at least one class.');
        }

        foreach ($classIds as $classId) {
            $classId = (int) $classId;
            if (!$this->repo->classBelongsToSchool($classId, $schoolId)) {
                continue; // silently skip a class that isn't this school's — not an error the admin needs surfaced individually
            }

            $students = $this->repo->listStudentsByClass($classId, $schoolId);
            foreach ($students as $s) {
                try {
                    $this->repo->createInvoice([
                        'student_id'  => $s['id'],
                        'fee_type_id' => $feeType['id'],
                        'term_id'     => $data['term_id'],
                        'amount'      => $data['amount'] ?? $feeType['amount'],
                        'due_date'    => $data['due_date'],
                    ]);
                    $count++;
                } catch (\Throwable) {
                    // Skip duplicate invoices (UNIQUE constraint: student+fee_type+term)
                }
            }
        }
        return $count;
    }

    public function getStudentStatement(int $studentId): array
    {
        $invoices = $this->repo->listStudentInvoicesDetailed($studentId);
        $paidMap  = $this->repo->totalPaidByInvoiceMap($studentId);
        $payments = $this->repo->getPaymentsByStudent($studentId);
        $discounts = $this->repo->listDiscountsByStudent($studentId);

        $totalBilled = 0.0;
        $totalPaid   = 0.0;
        foreach ($invoices as $inv) {
            $totalBilled += (float) $inv['amount'];
            $totalPaid   += $paidMap[(int) $inv['id']] ?? 0.0;
        }

        return [
            'invoices'     => $invoices,
            'payments'     => $payments,
            'discounts'    => $discounts,
            'total_billed' => $totalBilled,
            'total_paid'   => $totalPaid,
            'balance'      => $totalBilled - $totalPaid,
        ];
    }

    /**
     * Now validates: the invoice belongs to the requesting school, the
     * amount is positive, and the payment doesn't exceed the remaining
     * balance (a real gap before — nothing stopped an accidental overpay
     * being recorded and marking an invoice "paid" at more than its amount).
     */
    public function recordPayment(array $data, int $schoolId): void
    {
        $invoiceId = (int) ($data['invoice_id'] ?? 0);

        if (!$this->repo->invoiceBelongsToSchool($invoiceId, $schoolId)) {
            throw new \InvalidArgumentException('Invoice not found.');
        }
        $inv = $this->repo->getInvoiceById($invoiceId);

        $amount = (float) ($data['amount_paid'] ?? 0);
        if ($amount <= 0) {
            throw new \InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $alreadyPaid = $this->repo->totalPaidForInvoice($invoiceId);
        $remaining   = (float) $inv['amount'] - $alreadyPaid;
        if ($amount > $remaining + 0.01) { // small epsilon for float rounding
            throw new \InvalidArgumentException(sprintf('Payment exceeds the remaining balance of %.2f.', $remaining));
        }

        $this->repo->recordPayment($data, $schoolId);

        $totalPaid = $this->repo->totalPaidForInvoice($invoiceId);
        $status    = $totalPaid >= (float) $inv['amount'] ? 'paid' : 'partial';
        $this->repo->updateInvoiceStatus($invoiceId, $status);
    }

    public function getInvoicePayments(int $invoiceId): array
    {
        return $this->repo->getPaymentsByInvoice($invoiceId);
    }

    /** Now validates the student belongs to the requesting school before creating a discount for them. */
    public function createDiscount(array $data, int $schoolId, int $approvedBy): array
    {
        $studentId = (int) ($data['student_id'] ?? 0);
        if (!$this->repo->studentBelongsToSchool($studentId, $schoolId)) {
            throw new \InvalidArgumentException('Student not found.');
        }
        if (trim((string) ($data['label'] ?? '')) === '') {
            throw new \InvalidArgumentException('A label describing the discount is required.');
        }
        $pct = $data['discount_pct'] ?? null;
        $amt = $data['discount_amt'] ?? null;
        if ($pct === null && $amt === null) {
            throw new \InvalidArgumentException('Provide either a percentage or a fixed amount.');
        }
        if ($pct !== null && ((float) $pct <= 0 || (float) $pct > 100)) {
            throw new \InvalidArgumentException('Discount percentage must be between 0 and 100.');
        }
        if ($amt !== null && (float) $amt <= 0) {
            throw new \InvalidArgumentException('Discount amount must be greater than zero.');
        }

        $data['school_id']   = $schoolId;
        $data['approved_by'] = $approvedBy;
        $id = $this->repo->createDiscount($data);
        return ['id' => $id] + $data;
    }

    public function listDiscounts(int $studentId): array
    {
        return $this->repo->listDiscountsByStudent($studentId);
    }

    public function initiateStkPush(array $data, int $schoolId): array
    {
        $inv = $this->repo->getInvoiceById((int) $data['invoice_id']);
        if (!$inv || !$this->repo->invoiceBelongsToSchool((int) $inv['id'], $schoolId)) {
            throw new \InvalidArgumentException('Invoice not found.');
        }
        $balance = (float) $inv['amount'] - $this->repo->totalPaidForInvoice((int) $inv['id']);
        if ($balance <= 0) {
            throw new \InvalidArgumentException('Invoice already fully paid.');
        }
        return [
            'invoice_id' => $inv['id'],
            'phone'      => $data['phone'],
            'amount'     => $balance,
            'status'     => 'pending',
            'message'    => 'Configure MPESA_* env vars and implement the Daraja HTTP call to go live.',
        ];
    }
}