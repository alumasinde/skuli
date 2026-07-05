<?php
declare(strict_types=1);

/** @var array $tenants */
$tenants ??= [];

$statusBadge = static fn (string $s) => match ($s) {
    'active'    => 'bg-success-subtle text-success-emphasis',
    'trial'     => 'bg-info-subtle text-info-emphasis',
    'pending'   => 'bg-warning-subtle text-warning-emphasis',
    'suspended' => 'bg-danger-subtle text-danger-emphasis',
    'cancelled' => 'bg-secondary-subtle text-secondary-emphasis',
    default     => 'bg-light text-dark border',
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-buildings me-2 text-primary"></i>Tenants</h5>
    <div class="text-muted small"><?= count($tenants) ?> tenant<?= count($tenants) === 1 ? '' : 's' ?></div>
  </div>
  <a href="/super-admin/tenants/create" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Provision New Tenant
  </a>
</div>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted">
          <th class="ps-3">Tenant</th><th>Plan</th><th>Status</th>
          <th class="text-center">Schools</th><th class="text-center">Users</th>
          <th>Created</th><th class="text-end pe-3">Actions</th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($tenants)): ?>
          <tr><td colspan="7" class="text-center text-muted py-5">No tenants yet.</td></tr>
        <?php else: foreach ($tenants as $t): ?>
          <tr>
            <td class="ps-3">
              <div class="fw-semibold small"><?= htmlspecialchars($t['name']) ?></div>
              <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($t['slug']) ?></div>
            </td>
            <td><span class="badge bg-light text-dark border text-capitalize"><?= htmlspecialchars($t['plan']) ?></span></td>
            <td><span class="badge <?= $statusBadge($t['status'] ?? 'active') ?> text-capitalize"><?= htmlspecialchars($t['status'] ?? 'active') ?></span></td>
            <td class="text-center small"><?= (int) $t['school_count'] ?></td>
            <td class="text-center small"><?= (int) $t['user_count'] ?></td>
            <td class="small text-muted"><?= \Core\Session::formatDate($t['created_at'] ?? null) ?></td>
            <td class="text-end pe-3">
              <?php if (($t['status'] ?? 'active') === 'suspended'): ?>
                <form method="POST" action="/super-admin/tenants/<?= (int) $t['id'] ?>/reactivate" class="d-inline">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-success">Reactivate</button>
                </form>
              <?php else: ?>
                <form method="POST" action="/super-admin/tenants/<?= (int) $t['id'] ?>/suspend" class="d-inline"
                      onsubmit="return confirm('Suspend this tenant? All their users will be locked out.');">
                  <?= csrf_field() ?>
                  <button class="btn btn-sm btn-outline-danger">Suspend</button>
                </form>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
