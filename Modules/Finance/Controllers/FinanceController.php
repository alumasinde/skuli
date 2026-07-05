<?php
declare(strict_types=1);

namespace Modules\Finance\Controllers;

use Core\Ownership;
use Core\RequestContext;
use Core\Response;
use Modules\Finance\Repositories\FinanceRepository;
use Modules\Finance\Services\FinanceService;

final class FinanceController
{
    private FinanceService $service;

    public function __construct()
    {
        $this->service = new FinanceService(new FinanceRepository());
    }

    public function createFeeType(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        if (empty($body['name']) || !isset($body['amount'])) {
            Response::badRequest('name and amount are required.'); return;
        }
        $body['school_id'] = $schoolId;

        try {
            Response::created($this->service->createFeeType($body), 'fee type created');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function listFeeTypes(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        Response::success($schoolId ? $this->service->listFeeTypes($schoolId) : []);
    }

    public function generateInvoices(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $count = $this->service->generateInvoices($body);
            Response::success(['invoices_created' => $count], 'invoices generated');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function studentStatement(array $params): void
    {
        $studentId = (int)($params['studentId'] ?? 0);
        if (!Ownership::canAccessStudent($studentId)) {
            Response::forbidden('you do not have access to this student'); return;
        }
        try {
            Response::success($this->service->getStudentStatement($studentId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function recordPayment(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $this->service->recordPayment($body, $schoolId);
            Response::success(null, 'payment recorded');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function invoicePayments(array $params): void
    {
        $invoiceId = (int)($params['invoiceId'] ?? 0);
        try {
            Response::success($this->service->getInvoicePayments($invoiceId));
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function createDiscount(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            $d = $this->service->createDiscount($body, $schoolId, RequestContext::userId());
            Response::created($d, 'discount created');
        } catch (\Throwable $e) { Response::serverError($e); }
    }

    public function listDiscounts(array $params): void
    {
        $studentId = (int)($params['studentId'] ?? 0);
        Response::success($this->service->listDiscounts($studentId));
    }

    public function mpesaStkPush(array $params): void
    {
        $schoolId = RequestContext::schoolId();
        if ($schoolId === null) { Response::forbidden('no school context'); return; }

        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        try {
            Response::success($this->service->initiateStkPush($body, $schoolId), 'STK push initiated');
        } catch (\InvalidArgumentException $e) {
            Response::badRequest($e->getMessage());
        } catch (\Throwable $e) { Response::serverError($e); }
    }
}
