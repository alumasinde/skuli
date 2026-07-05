<?php
declare(strict_types=1);

/** @var array|null $subscription @var array $invoices @var array $payments */
$subscription ??= null;
$invoices     ??= [];
$payments     ??= [];

$statusBadge = static fn (string $s) => match ($s) {
    'active', 'paid', 'succeeded' => 'bg-success-subtle text-success-emphasis',
    'trialing', 'pending'         => 'bg-info-subtle text-info-emphasis',
    'past_due', 'open'            => 'bg-warning-subtle text-warning-emphasis',
    'cancelled', 'failed', 'void' => 'bg-danger-subtle text-danger-emphasis',
    default                       => 'bg-light text-dark border',
};
?>

<h5 class="fw-bold mb-3"><i class="bi bi-receipt me-2 text-primary"></i>Billing</h5>

<?php if (!$subscription): ?>
  <div class="alert alert-warning">
    No active subscription. Contact your account manager to get set up.
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm mb-4">
    <div class="card-body d-flex justify-content-between align-items-center">
      <div>
        <div class="text-muted small">Current Plan</div>
        <div class="fs-4 fw-bold"><?= htmlspecialchars($subscription['plan_name']) ?></div>
        <div class="text-muted small text-capitalize"><?= htmlspecialchars($subscription['billing_cycle']) ?> billing</div>
      </div>
      <span class="badge <?= $statusBadge($subscription['status']) ?> text-capitalize fs-6"><?= htmlspecialchars($subscription['status']) ?></span>
    </div>
  </div>

  <?php if ($subscription['status'] === 'trialing' && !empty($subscription['trial_ends_at'])): ?>
    <div class="alert alert-info">
      Your trial ends on <strong><?= \Core\Session::formatDate($subscription['trial_ends_at']) ?></strong>.
    </div>
  <?php elseif ($subscription['status'] === 'past_due'): ?>
    <div class="alert alert-danger">
      Your last invoice is unpaid. Please contact us to avoid service interruption.
    </div>
  <?php endif; ?>
<?php endif; ?>

<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white fw-semibold small">Invoices</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted"><th class="ps-3">Invoice</th><th>Period</th><th>Amount</th><th>Status</th><th class="pe-3">Due</th></tr>
      </thead>
      <tbody>
        <?php if (empty($invoices)): ?>
          <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
        <?php else: foreach ($invoices as $inv): ?>
          <tr>
            <td class="ps-3 font-monospace small"><?= htmlspecialchars($inv['invoice_no']) ?></td>
            <td class="small text-muted">
              <?= \Core\Session::formatDate(substr($inv['period_start'] ?? '', 0, 10)) ?> &ndash;
              <?= \Core\Session::formatDate(substr($inv['period_end'] ?? '', 0, 10)) ?>
            </td>
            <td class="small fw-semibold"><?= htmlspecialchars($inv['currency']) ?> <?= number_format((float) $inv['amount'], 2) ?></td>
            <td><span class="badge <?= $statusBadge($inv['status']) ?> text-capitalize"><?= htmlspecialchars($inv['status']) ?></span></td>
            <td class="pe-3 small"><?= \Core\Session::formatDate($inv['due_date'] ?? null) ?></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold small">Payment History</div>
  <div class="table-responsive">
    <table class="table table-sm table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted"><th class="ps-3">Date</th><th>Amount</th><th>Method</th><th class="pe-3">Status</th></tr>
      </thead>
      <tbody>
        <?php if (empty($payments)): ?>
          <tr><td colspan="4" class="text-center text-muted py-4">No payments yet.</td></tr>
        <?php else: foreach ($payments as $p): ?>
          <tr>
            <td class="ps-3 small"><?= \Core\Session::formatDate(substr($p['paid_at'] ?? '', 0, 10)) ?></td>
            <td class="small fw-semibold"><?= htmlspecialchars($p['currency']) ?> <?= number_format((float) $p['amount'], 2) ?></td>
            <td><span class="badge bg-light text-dark border text-uppercase"><?= htmlspecialchars($p['gateway']) ?></span></td>
            <td class="pe-3"><span class="badge <?= $statusBadge($p['status']) ?> text-capitalize"><?= htmlspecialchars($p['status']) ?></span></td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
