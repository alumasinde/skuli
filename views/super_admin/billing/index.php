<?php
declare(strict_types=1);

/** @var array $openInvoices @var float $totalRevenue */
$openInvoices ??= [];
$totalRevenue ??= 0.0;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-graph-up-arrow me-2 text-primary"></i>Billing</h5>
  <a href="/super-admin/tenants" class="btn btn-sm btn-outline-secondary">All Tenants</a>
</div>

<div class="row g-3 mb-4">
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Total Revenue Collected</div>
        <div class="fs-3 fw-bold text-success">KES <?= number_format($totalRevenue, 2) ?></div>
      </div>
    </div>
  </div>
  <div class="col-md-6 col-lg-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body">
        <div class="text-muted small">Open Invoices</div>
        <div class="fs-3 fw-bold text-warning"><?= count($openInvoices) ?></div>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold small">Outstanding Invoices — all tenants</div>
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted">
          <th class="ps-3">Invoice</th><th>Tenant</th><th>Amount</th><th>Due</th><th class="text-end pe-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($openInvoices)): ?>
          <tr><td colspan="5" class="text-center text-muted py-5">Nothing outstanding.</td></tr>
        <?php else: foreach ($openInvoices as $inv): ?>
          <?php $overdue = strtotime($inv['due_date']) < strtotime('today'); ?>
          <tr class="<?= $overdue ? 'table-danger' : '' ?>">
            <td class="ps-3 font-monospace small"><?= htmlspecialchars($inv['invoice_no']) ?></td>
            <td class="small"><?= htmlspecialchars($inv['tenant_name']) ?></td>
            <td class="small fw-semibold"><?= htmlspecialchars($inv['currency']) ?> <?= number_format((float) $inv['amount'], 2) ?></td>
            <td class="small">
              <?= \Core\Session::formatDate($inv['due_date'] ?? null) ?>
              <?php if ($overdue): ?><span class="badge bg-danger-subtle text-danger-emphasis ms-1">Overdue</span><?php endif; ?>
            </td>
            <td class="text-end pe-3">
              <a href="/super-admin/billing/tenants/<?= (int) $inv['tenant_id'] ?>" class="btn btn-sm btn-outline-primary">
                Record Payment
              </a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
