<?php
declare(strict_types=1);

namespace Modules\Finance\Controllers\Web;

use Core\Ownership;
use Core\RequestContext;
use Core\Session;
use Core\WebController;
use Modules\Classes\Services\ClassService;
use Modules\Finance\Services\FinanceService;
use Modules\Students\Services\StudentService;
use Modules\Terms\Services\TermService;

final class FinanceWebController extends WebController
{
    public function __construct(
        private FinanceService $service,
        private ClassService $classes,
        private TermService $terms,
        private StudentService $students
    ) {
        parent::__construct();
    }

    /** GET /finance — admin-only overview (fee types, invoice generation). */
    public function index(array $params): void
    {
        $this->requirePermission('finance.view');
        $schoolId = RequestContext::schoolId();

        $this->view('finance/index', [
            'title'    => 'Finance',
            'feeTypes' => $schoolId ? $this->service->listFeeTypes($schoolId) : [],
            'terms'    => $schoolId ? $this->terms->listBySchool($schoolId) : [],
            'classes'  => $schoolId ? $this->classes->list($schoolId) : [],
            'errors'   => Session::flash('errors') ?: [],
        ]);
    }

    /** GET /finance/fee-types/create */
    public function createFeeType(array $params): void
    {
        $this->requirePermission('finance.create');
        $this->view('finance/fee_type_create', [
            'title'  => 'Add Fee Type',
            'errors' => Session::flash('errors') ?: [],
            'old'    => Session::flash('old') ?: [],
        ]);
    }

    /** POST /finance/fee-types */
    public function storeFeeType(array $params): void
    {
        $this->requirePermission('finance.create');
        $schoolId = RequestContext::schoolId();

        $body = [
            'school_id'    => $schoolId,
            'name'         => trim($_POST['name'] ?? ''),
            'amount'       => $_POST['amount'] ?? '',
            'frequency'    => $_POST['frequency'] ?? 'termly',
            'is_mandatory' => isset($_POST['is_mandatory']) ? 1 : 0,
        ];

        try {
            $this->service->createFeeType($body);
            $this->redirect('/finance', 'Fee type created.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            Session::flash('old', $body);
            $this->redirect('/finance/fee-types/create');
        }
    }

    /** POST /finance/invoices/generate */
    public function generateInvoices(array $params): void
    {
        $this->requirePermission('finance.create');
        $schoolId = RequestContext::schoolId();

        $body = [
            'fee_type_id' => (int) ($_POST['fee_type_id'] ?? 0),
            'term_id'     => (int) ($_POST['term_id'] ?? 0),
            'class_ids'   => (array) ($_POST['class_ids'] ?? []),
            'due_date'    => $_POST['due_date'] ?? '',
            'amount'      => $_POST['amount'] !== '' ? $_POST['amount'] : null,
        ];

        try {
            $count = $this->service->generateInvoices($body, $schoolId);
            $this->redirect('/finance', "{$count} invoice(s) generated.");
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect('/finance');
        }
    }

    /**
     * GET /finance/statement/{studentId}
     *
     * FIXED: was gated by requirePermission('finance.view') — an admin-only
     * permission, which is exactly why parents could never reach this page
     * at all. Swapped for Ownership::canAccessStudent(), the same guard
     * StudentWebController::show() already uses: admins pass through
     * automatically, and a parent or teacher only passes if they're
     * genuinely linked to THIS student — not just "is a parent of someone."
     * This closes both problems at once: parents can now view their own
     * children's statements, and nobody (parent or otherwise) can view a
     * statement for a student they have no real relationship to, even if
     * they know/guess the student ID.
     */
    public function statement(array $params): void
    {
        $studentId = (int) ($params['studentId'] ?? 0);

        if (!Ownership::canAccessStudent($studentId)) {
            $this->redirect('/dashboard', 'You do not have access to this student.', 'error');
            return;
        }

        $student = $this->students->getById($studentId);
        if (!$student) {
            $this->redirect('/finance', 'Student not found.', 'error');
            return;
        }

        $this->view('finance/statement', [
            'title'      => 'Fee Statement',
            'student'    => $student,
            'statement'  => $this->service->getStudentStatement($studentId),
            'canManage'  => Session::can('finance.create'), // controls whether payment/discount actions render
            'errors'     => Session::flash('errors') ?: [],
        ]);
    }

    /** POST /finance/payments — admin-only, unchanged. */
    public function recordPayment(array $params): void
    {
        $this->requirePermission('finance.create');
        $schoolId  = RequestContext::schoolId();
        $studentId = (int) ($_POST['student_id'] ?? 0);

        $body = [
            'invoice_id'  => (int) ($_POST['invoice_id'] ?? 0),
            'amount_paid' => $_POST['amount_paid'] ?? '',
            'method'      => $_POST['method'] ?? 'cash',
            'ref_no'      => trim($_POST['ref_no'] ?? '') ?: null,
            'mpesa_code'  => trim($_POST['mpesa_code'] ?? '') ?: null,
            'receipt_no'  => trim($_POST['receipt_no'] ?? '') ?: null,
        ];

        try {
            $this->service->recordPayment($body, $schoolId);
            $this->redirect("/finance/statement/{$studentId}", 'Payment recorded.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/finance/statement/{$studentId}");
        }
    }

    /** POST /finance/discounts — admin-only, unchanged. */
    public function createDiscount(array $params): void
    {
        $this->requirePermission('finance.create');
        $schoolId  = RequestContext::schoolId();
        $studentId = (int) ($_POST['student_id'] ?? 0);

        $body = [
            'student_id'   => $studentId,
            'fee_type_id'  => $_POST['fee_type_id'] !== '' ? (int) $_POST['fee_type_id'] : null,
            'term_id'      => $_POST['term_id'] !== '' ? (int) $_POST['term_id'] : null,
            'label'        => trim($_POST['label'] ?? ''),
            'discount_pct' => $_POST['discount_pct'] !== '' ? $_POST['discount_pct'] : null,
            'discount_amt' => $_POST['discount_amt'] !== '' ? $_POST['discount_amt'] : null,
        ];

        try {
            $this->service->createDiscount($body, $schoolId, RequestContext::userId());
            $this->redirect("/finance/statement/{$studentId}", 'Discount added.');
        } catch (\Throwable $e) {
            Session::flash('errors', [$e->getMessage()]);
            $this->redirect("/finance/statement/{$studentId}");
        }
    }
}
