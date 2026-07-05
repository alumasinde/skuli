<?php
declare(strict_types=1);

/** @var array $requests @var string|null $status */
$requests ??= [];
$status   ??= null;

$statusBadge = static fn (string $s) => match ($s) {
    'new'       => 'bg-primary-subtle text-primary-emphasis',
    'contacted' => 'bg-info-subtle text-info-emphasis',
    'scheduled' => 'bg-warning-subtle text-warning-emphasis',
    'approved'  => 'bg-success-subtle text-success-emphasis',
    'declined', 'spam' => 'bg-secondary-subtle text-secondary-emphasis',
    default     => 'bg-light text-dark border',
};
$tabs = ['' => 'All', 'new' => 'New', 'contacted' => 'Contacted', 'approved' => 'Approved', 'declined' => 'Declined'];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-inbox me-2 text-primary"></i>Demo Requests</h5>
</div>

<ul class="nav nav-pills small mb-3">
  <?php foreach ($tabs as $val => $label): ?>
    <li class="nav-item">
      <a class="nav-link <?= ($status ?? '') === $val ? 'active' : '' ?>"
         href="/super-admin/demo-requests<?= $val ? '?status=' . $val : '' ?>"><?= $label ?></a>
    </li>
  <?php endforeach; ?>
</ul>

<div class="card border-0 shadow-sm">
  <div class="table-responsive">
    <table class="table table-hover align-middle mb-0">
      <thead class="table-light">
        <tr class="small text-muted">
          <th class="ps-3">School</th><th>Contact</th><th>Size</th><th>Status</th><th>Submitted</th><th class="text-end pe-3"></th>
        </tr>
      </thead>
      <tbody>
        <?php if (empty($requests)): ?>
          <tr><td colspan="6" class="text-center text-muted py-5">No demo requests here.</td></tr>
        <?php else: foreach ($requests as $r): ?>
          <tr>
            <td class="ps-3 fw-semibold small"><?= htmlspecialchars($r['school_name']) ?></td>
            <td class="small">
              <?= htmlspecialchars($r['contact_name']) ?>
              <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['email']) ?></div>
            </td>
            <td class="small text-muted"><?= htmlspecialchars($r['student_count_range'] ?? '—') ?></td>
            <td><span class="badge <?= $statusBadge($r['status']) ?> text-capitalize"><?= htmlspecialchars($r['status']) ?></span></td>
            <td class="small text-muted"><?= \Core\Session::formatDate(substr($r['created_at'] ?? '', 0, 10)) ?></td>
            <td class="text-end pe-3">
              <a href="/super-admin/demo-requests/<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-primary">Review</a>
            </td>
          </tr>
        <?php endforeach; endif; ?>
      </tbody>
    </table>
  </div>
</div>
