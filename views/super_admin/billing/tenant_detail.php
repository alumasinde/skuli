<?php
declare(strict_types=1);

/**
 * @var array $tenant @var array|null $subscription @var array $invoices
 * @var array $payments @var array $plans @var array $errors
 */
$tenant       ??= [];
$subscription ??= null;
$invoices     ??= [];
$payments     ??= [];
$plans        ??= [];
$errors       ??= [];

$tenantId = (int) ($tenant['id'] ?? 0);

$statusBadge = static fn (string $s) => match ($s) {
    'active', 'paid', 'succeeded' => 'bg-success-subtle text-success-emphasis',
    'trialing', 'pending'         => 'bg-info-subtle text-info-emphasis',
    'past_due', 'open'            => 'bg-warning-subtle text-warning-emphasis',
    'cancelled', 'failed', 'void' => 'bg-danger-subtle text-danger-emphasis',
    default                       => 'bg-light text-dark border',
};
?>

<nav class="mb-1"><a href="/super-admin/billing" class="small text-decoration-none">&larr; Billing</a></nav>
<h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i><?= htmlspecialchars($tenant['name'] ?? '') ?></h5>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3 mb-3">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small">Subscription</div>
      <div class="card-body">
        <?php if ($subscription): ?>
          <div class="d-flex justify-content-between align-items-start mb-2">
            <div>
              <div class="fw-bold"><?= htmlspecialchars($subscription['plan_name']) ?></div>
              <div class="text-muted small text-capitalize"><?= htmlspecialchars($subscription['billing_cycle']) ?> billing</div>
            </div>
            <span class="badge <?= $statusBadge($subscription['status']) ?> text-capitalize"><?= htmlspecialchars($subscription['status']) ?></span>
          </div>
          <dl class="row small mb-3">
            <dt class="col-6 text-muted">Current period</dt>
            <dd class="col-6"><?= \Core\Session::formatDate($subscription['current_period_start'] ?? null) ?> &ndash; <?= \Core\Session::formatDate($subscription['current_period_end'] ?? null) ?></dd>
            <?php if (!empty($subscription['trial_ends_at'])): ?>
              <dt class="col-6 text-muted">Trial ends</dt>
              <dd class="col-6"><?= \Core\Session::formatDate($subscription['trial_ends_at']) ?></dd>
            <?php endif; ?>
          </dl>
          <form method="POST" action="/super-admin/billing/subscriptions/<?= (int) $subscription['id'] ?>/cancel"
                onsubmit="return confirm('Cancel this subscription?');">
            <?= csrf_field() ?>
            <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
            <input type="text" name="reason" class="form-control form-control-sm mb-2" placeholder="Reason (optional)">
            <button type="submit" class="btn btn-sm btn-outline-danger">Cancel Subscription</button>
          </form>
        <?php else: ?>
          <p class="text-muted small mb-3">No active subscription.</p>
          <form method="POST" action="/super-admin/billing/tenants/<?= $tenantId ?>/subscribe">
            <?= csrf_field() ?>
            <div class="mb-2">
              <label class="form-label small mb-1">Plan</label>
              <select name="plan_code" class="form-select form-select-sm" required>
                <?php foreach ($plans as $p): ?>
                  <option value="<?= htmlspecialchars($p['code']) ?>">
                    <?= htmlspecialchars($p['name']) ?> — <?= htmlspecialchars($p['currency']) ?> <?= number_format((float) $p['price_monthly'], 0) ?>/mo
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small mb-1">Cycle</label>
                <select name="billing_cycle" class="form-select form-select-sm">
                  <option value="monthly">Monthly</option>
                  <option value="yearly">Yearly</option>
                </select>
              </div>
              <div class="col-6">
                <label class="form-label small mb-1">Trial (days)</label>
                <input type="number" name="trial_days" class="form-control form-control-sm" value="14" min="0">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100">Start Subscription</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small">Invoices</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted"><th class="ps-3">Invoice</th><th>Amount</th><th>Status</th><th>Due</th><th class="text-end pe-3"></th></tr>
          </thead>
          <tbody>
            <?php if (empty($invoices)): ?>
              <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
            <?php else: foreach ($invoices as $inv): ?>
              <tr>
                <td class="ps-3 font-monospace small"><?= htmlspecialchars($inv['invoice_no']) ?></td>
                <td class="small"><?= htmlspecialchars($inv['currency']) ?> <?= number_format((float) $inv['amount'], 2) ?></td>
                <td><span class="badge <?= $statusBadge($inv['status']) ?> text-capitalize"><?= htmlspecialchars($inv['status']) ?></span></td>
                <td class="small"><?= \Core\Session::formatDate($inv['due_date'] ?? null) ?></td>
                <td class="text-end pe-3">
                  <?php if ($inv['status'] === 'open'): ?>
                    <button type="button" class="btn btn-sm btn-outline-success"
                            data-bs-toggle="modal" data-bs-target="#payModal"
                            data-invoice-id="<?= (int) $inv['id'] ?>"
                            data-amount="<?= (float) $inv['amount'] ?>">
                      Record Payment
                    </button>
                  <?php endif; ?>
                </td>
              </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold small">Payment History</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted"><th class="ps-3">Date</th><th>Amount</th><th>Gateway</th><th>Status</th><th>Notes</th></tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No payments recorded.</td></tr>
        <?php else: foreach ($payments as $p): ?>
          <tr>
            <td class="ps-3 small"><?= \Core\Session::formatDate(substr($p['paid_at'] ?? '', 0, 10)) ?></td>
            <td class="small fw-semibold"><?= htmlspecialchars($p['currency']) ?> <?= number_format((float) $p['amount'], 2) ?></td>
            <td><span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($p['gateway']) ?></span></td>
            <td><span class="badge <?= $statusBadge($p['status']) ?> text-capitalize"><?= htmlspecialchars($p['status']) ?></span></td>
            <td class="small text-muted"><?= htmlspecialchars($p['notes'] ?? '—') ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Record Payment modal -->
<div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <div class="modal-header">
      <h6 class="modal-title">Record Payment</h6>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <form id="payForm" method="POST" action="">
      <div class="modal-body">
        <?= csrf_field() ?>
        <input type="hidden" name="tenant_id" value="<?= $tenantId ?>">
        <div class="mb-2">
          <label class="form-label small mb-1">Amount</label>
          <input type="number" name="amount" id="payAmount" class="form-control" step="0.01" min="0.01" required>
        </div>
        <div class="mb-2">
          <label class="form-label small mb-1">Notes <span class="text-muted">(optional)</span></label>
          <input type="text" name="notes" class="form-control" placeholder="e.g. Bank transfer ref 001234">
        </div>
        <p class="small text-muted mb-0">Recorded as a manual payment (bank transfer, cash, cheque).</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success btn-sm">Record Payment</button>
      </div>
    </form>
  </div></div>
</div>

<script src="/assets/js/billing-tenant-detail.js"></script>
