<?php
declare(strict_types=1);

/** @var array $student @var array $statement @var array $errors @var bool $canManage */
$student   ??= [];
$statement ??= [];
$errors    ??= [];
$canManage ??= false;

$invoices    = $statement['invoices']    ?? [];
$payments    = $statement['payments']    ?? [];
$discounts   = $statement['discounts']   ?? [];
$balance     = (float) ($statement['balance']      ?? 0);
$totalPaid   = (float) ($statement['total_paid']   ?? 0);
$totalBilled = (float) ($statement['total_billed'] ?? 0);

$studentId = (int) ($student['id'] ?? 0);
$statusBadge = static fn (string $s) => match ($s) {
    'paid'    => 'bg-success-subtle text-success-emphasis',
    'partial' => 'bg-warning-subtle text-warning-emphasis',
    default   => 'bg-danger-subtle text-danger-emphasis',
};

// A parent viewing their own child's statement doesn't have a "back to
// student profile" link that makes sense for them (they may not have
// access to /students/{id} as an admin-style page) — point back
// somewhere sensible for whoever's looking at this.
$backHref = $canManage ? "/students/{$studentId}" : '/profile';
$backLabel = $canManage
    ? htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? '')))
    : 'My Profile';
?>

<nav class="mb-1"><a href="<?= $backHref ?>" class="small text-decoration-none">&larr; <?= $backLabel ?></a></nav>
<div class="d-flex justify-content-between align-items-center mb-3 mt-1">
  <h5 class="fw-bold mb-0"><i class="bi bi-cash-coin me-2 text-success"></i>Fee Statement
    <?php if (!$canManage): ?><span class="text-muted fw-normal fs-6">— <?= htmlspecialchars(trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''))) ?></span><?php endif; ?>
  </h5>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-md-4"><div class="card border-0 shadow-sm bg-light"><div class="card-body">
    <div class="text-muted small">Total Billed</div><div class="fs-4 fw-bold">KES <?= number_format($totalBilled, 2) ?></div>
  </div></div></div>
  <div class="col-md-4"><div class="card border-0 shadow-sm bg-success-subtle"><div class="card-body">
    <div class="text-muted small">Total Paid</div><div class="fs-4 fw-bold text-success">KES <?= number_format($totalPaid, 2) ?></div>
  </div></div></div>
  <div class="col-md-4"><div class="card border-0 shadow-sm <?= $balance > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' ?>"><div class="card-body">
    <div class="text-muted small">Outstanding Balance</div>
    <div class="fs-4 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">KES <?= number_format($balance, 2) ?></div>
  </div></div></div>
</div>

<?php if (!$canManage && $balance > 0): ?>
  <div class="alert alert-warning small">
    <i class="bi bi-info-circle me-1"></i>
    There's an outstanding balance of <strong>KES <?= number_format($balance, 2) ?></strong>.
    Please contact the school office to arrange payment.
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white d-flex justify-content-between align-items-center">
    <span class="fw-semibold small"><i class="bi bi-receipt me-1"></i>Invoices</span>
  </div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted">
          <th class="ps-3">Fee Type</th><th>Term</th><th>Amount</th><th>Status</th><th>Due</th>
          <?php if ($canManage): ?><th class="text-end pe-3"></th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr><td colspan="<?= $canManage ? 6 : 5 ?>" class="text-center text-muted py-4">No invoices yet.</td></tr>
        <?php else: foreach ($invoices as $inv): ?>
          <tr>
            <td class="ps-3 small"><?= htmlspecialchars($inv['fee_type_name'] ?? '') ?></td>
            <td class="small text-muted"><?= htmlspecialchars($inv['term_name'] ?? '') ?></td>
            <td class="fw-semibold small">KES <?= number_format((float) $inv['amount'], 2) ?></td>
            <td><span class="badge <?= $statusBadge($inv['status']) ?> text-capitalize"><?= htmlspecialchars($inv['status']) ?></span></td>
            <td class="small text-muted"><?= \Core\Session::formatDate($inv['due_date'] ?? null) ?></td>
            <?php if ($canManage): ?>
              <td class="text-end pe-3">
                <?php if ($inv['status'] !== 'paid'): ?>
                  <button type="button" class="btn btn-sm btn-outline-success"
                          data-bs-toggle="modal" data-bs-target="#payModal"
                          data-invoice-id="<?= (int) $inv['id'] ?>"
                          data-amount="<?= (float) $inv['amount'] ?>">Record Payment</button>
                <?php endif; ?>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small"><i class="bi bi-clock-history me-1"></i>Payment History</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light"><tr class="small text-muted"><th class="ps-3">Date</th><th>Amount</th><th>Method</th><th class="pe-3">Reference</th></tr></thead>
          <tbody>
            <?php if (empty($payments)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No payments recorded yet.</td></tr>
            <?php else: foreach ($payments as $p): ?>
              <tr>
                <td class="ps-3 small"><?= \Core\Session::formatDate(substr($p['paid_at'] ?? '', 0, 10)) ?></td>
                <td class="fw-semibold text-success small">KES <?= number_format((float) $p['amount_paid'], 2) ?></td>
                <td><span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($p['method'] ?? '') ?></span></td>
                <td class="pe-3 text-muted small"><?= htmlspecialchars($p['mpesa_code'] ?? $p['ref_no'] ?? $p['receipt_no'] ?? '—') ?></td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-tag me-1"></i>Discounts</span>
        <?php if ($canManage): ?>
          <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#discountModal">
            <i class="bi bi-plus-lg"></i>
          </button>
        <?php endif; ?>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($discounts)): ?>
          <div class="list-group-item text-muted small py-3">No discounts applied.</div>
        <?php else: foreach ($discounts as $d): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <span class="small"><?= htmlspecialchars($d['label']) ?></span>
            <span class="badge bg-primary-subtle text-primary-emphasis">
              <?= $d['discount_pct'] ? htmlspecialchars($d['discount_pct']) . '%' : 'KES ' . number_format((float) $d['discount_amt'], 2) ?>
            </span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<?php if ($canManage): ?>
  <!-- Record Payment modal -->
  <dialog class="modal" id="payModal">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Record Payment</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button></div>
      <form method="POST" action="/finance/payments">
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" name="student_id" value="<?= $studentId ?>">
          <input type="hidden" name="invoice_id" id="payInvoiceId">
          <div class="mb-2">
            <label class="form-label small fw-semibold">Amount (KES)</label>
            <input type="number" name="amount_paid" id="payAmount" class="form-control" step="0.01" min="0.01" required>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Method</label>
            <select name="method" class="form-select">
              <option value="cash">Cash</option><option value="mpesa">M-Pesa</option>
              <option value="bank">Bank</option><option value="cheque">Cheque</option>
            </select>
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">M-Pesa Code / Reference <span class="text-muted">(optional)</span></label>
            <input type="text" name="mpesa_code" class="form-control" placeholder="QK12ABC001">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-success btn-sm">Record Payment</button>
        </div>
      </form>
    </div></div>
  </dialog>

  <!-- Add Discount modal -->
  <dialog class="modal" id="discountModal">
    <div class="modal-dialog modal-dialog-centered"><div class="modal-content">
      <div class="modal-header"><h6 class="modal-title">Add Discount</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button></div>
      <form method="POST" action="/finance/discounts">
        <div class="modal-body">
          <?= csrf_field() ?>
          <input type="hidden" name="student_id" value="<?= $studentId ?>">
          <div class="mb-2">
            <label class="form-label small fw-semibold">Label <span class="text-danger">*</span></label>
            <input type="text" name="label" class="form-control" required placeholder="e.g. Sibling Discount">
          </div>
          <div class="row g-2">
            <div class="col-6">
              <label class="form-label small fw-semibold">Percentage</label>
              <input type="number" name="discount_pct" class="form-control" min="0" max="100" step="0.01" placeholder="e.g. 10">
            </div>
            <div class="col-6">
              <label class="form-label small fw-semibold">Or Fixed Amount</label>
              <input type="number" name="discount_amt" class="form-control" min="0" step="0.01" placeholder="e.g. 2000">
            </div>
          </div>
          <small class="text-muted d-block mt-2">Provide either a percentage or a fixed amount, not both.</small>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
          <button type="submit" class="btn btn-primary btn-sm">Add Discount</button>
        </div>
      </form>
    </div></div>
  </dialog>

  <script src="/assets/js/finance-statement.js"></script>
<?php endif; ?>
