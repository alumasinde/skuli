<?php
declare(strict_types=1);

namespace Modules\Finance\Repositories;

use Core\Repository;

final class FinanceRepository extends Repository
{
    // ── Fee Types ────────────────────────────────────────────────────────────

    public function createFeeType(array $data): int
    {
        return $this->insert('
            INSERT INTO fee_types (school_id, name, amount, frequency, is_mandatory)
            VALUES (?, ?, ?, ?, ?)
        ', [$data['school_id'], $data['name'], $data['amount'], $data['frequency'], (int)($data['is_mandatory'] ?? 1)]);
    }

    public function listFeeTypes(int $schoolId): array
    {
        return $this->fetchAll(
            'SELECT * FROM fee_types WHERE school_id = ? AND deleted_at IS NULL ORDER BY name',
            [$schoolId]
        );
    }

    /** Guard added: was missing deleted_at IS NULL, could return a soft-deleted fee type. */
    public function findFeeTypeById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM fee_types WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    // ── Invoices ─────────────────────────────────────────────────────────────

    public function createInvoice(array $data): int
    {
        return $this->insert("
            INSERT INTO fee_invoices (student_id, fee_type_id, term_id, amount, status, due_date)
            VALUES (?, ?, ?, ?, 'unpaid', ?)
        ", [$data['student_id'], $data['fee_type_id'], $data['term_id'], $data['amount'], $data['due_date']]);
    }

    /** Guard added: only students belonging to $schoolId — was previously
     *  unscoped, so a class_id from another tenant would happily generate
     *  invoices for students that don't belong to the requesting school. */
    public function listStudentsByClass(int $classId, int $schoolId): array
    {
        return $this->fetchAll(
            'SELECT id FROM students WHERE class_id = ? AND school_id = ? AND is_active = 1 AND deleted_at IS NULL',
            [$classId, $schoolId]
        );
    }

    /** New: lets the service reject a class_id that isn't even in this school before touching students at all. */
    public function classBelongsToSchool(int $classId, int $schoolId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM classes WHERE id = ? AND school_id = ? AND deleted_at IS NULL',
            [$classId, $schoolId]
        ) !== null;
    }

    public function listStudentInvoicesDetailed(int $studentId): array
    {
        return $this->fetchAll('
            SELECT fi.id, fi.student_id,
                   fi.fee_type_id, ft.name AS fee_type_name,
                   fi.term_id,     t.name  AS term_name,
                   fi.amount, fi.status, fi.due_date
            FROM fee_invoices fi
            JOIN fee_types ft ON ft.id = fi.fee_type_id
            JOIN terms     t  ON t.id  = fi.term_id
            WHERE fi.student_id = ? AND fi.deleted_at IS NULL
            ORDER BY fi.due_date DESC
        ', [$studentId]);
    }

    public function getInvoiceById(int $id): ?array
    {
        return $this->fetchOne('SELECT * FROM fee_invoices WHERE id = ? AND deleted_at IS NULL', [$id]);
    }

    /** New: was completely absent — nothing verified an invoice_id in
     *  recordPayment() actually belongs to the school making the request.
     *  Joins through students, since fee_invoices has no direct school_id. */
    public function invoiceBelongsToSchool(int $invoiceId, int $schoolId): bool
    {
        return $this->fetchOne('
            SELECT fi.id FROM fee_invoices fi
            JOIN students s ON s.id = fi.student_id
            WHERE fi.id = ? AND s.school_id = ? AND fi.deleted_at IS NULL
        ', [$invoiceId, $schoolId]) !== null;
    }

    public function updateInvoiceStatus(int $id, string $status): void
    {
        $this->execute('UPDATE fee_invoices SET status = ? WHERE id = ?', [$status, $id]);
    }

    // ── Payments ─────────────────────────────────────────────────────────────

    public function recordPayment(array $data, int $schoolId): int
    {
        return $this->insert('
            INSERT INTO fee_payments
                (invoice_id, school_id, amount_paid, method, ref_no, mpesa_code, receipt_no, paid_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ', [
            $data['invoice_id'], $schoolId,
            $data['amount_paid'], $data['method'],
            $data['ref_no'] ?? null, $data['mpesa_code'] ?? null,
            $data['receipt_no'] ?? null,
        ]);
    }

    public function getPaymentsByInvoice(int $invoiceId): array
    {
        return $this->fetchAll(
            'SELECT * FROM fee_payments WHERE invoice_id = ? ORDER BY paid_at',
            [$invoiceId]
        );
    }

    public function getPaymentsByStudent(int $studentId): array
    {
        return $this->fetchAll('
            SELECT fp.*
            FROM fee_payments fp
            JOIN fee_invoices fi ON fi.id = fp.invoice_id
            WHERE fi.student_id = ?
            ORDER BY fp.paid_at DESC
        ', [$studentId]);
    }

    public function totalPaidByInvoiceMap(int $studentId): array
    {
        $rows = $this->fetchAll('
            SELECT fi.id AS invoice_id, COALESCE(SUM(fp.amount_paid), 0) AS total
            FROM fee_invoices fi
            LEFT JOIN fee_payments fp ON fp.invoice_id = fi.id
            WHERE fi.student_id = ?
            GROUP BY fi.id
        ', [$studentId]);

        $map = [];
        foreach ($rows as $r) {
            $map[(int)$r['invoice_id']] = (float)$r['total'];
        }
        return $map;
    }

    public function totalPaidForInvoice(int $invoiceId): float
    {
        return (float)$this->fetchColumn(
            'SELECT COALESCE(SUM(amount_paid), 0) FROM fee_payments WHERE invoice_id = ?',
            [$invoiceId]
        );
    }

    // ── Discounts ────────────────────────────────────────────────────────────

    public function createDiscount(array $data): int
    {
        return $this->insert('
            INSERT INTO fee_discounts
                (school_id, student_id, fee_type_id, term_id, label,
                 discount_pct, discount_amt, approved_by, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
        ', [
            $data['school_id'], $data['student_id'],
            $data['fee_type_id'] ?? null, $data['term_id'] ?? null,
            $data['label'],
            $data['discount_pct'] ?? null, $data['discount_amt'] ?? null,
            $data['approved_by'],
        ]);
    }

    public function listDiscountsByStudent(int $studentId): array
    {
        return $this->fetchAll(
            'SELECT * FROM fee_discounts WHERE student_id = ? AND is_active = 1 AND deleted_at IS NULL',
            [$studentId]
        );
    }

    /** New: needed so the service can confirm a student belongs to the
     *  requesting school before creating a discount or an invoice for them. */
    public function studentBelongsToSchool(int $studentId, int $schoolId): bool
    {
        return $this->fetchOne(
            'SELECT id FROM students WHERE id = ? AND school_id = ? AND deleted_at IS NULL',
            [$studentId, $schoolId]
        ) !== null;
    }
}