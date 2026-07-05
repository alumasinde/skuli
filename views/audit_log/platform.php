<?php
declare(strict_types=1);

/** @var array $entries @var int $total @var int $page @var int $perPage */
$entries ??= [];
$total   ??= 0;
$page    ??= 1;
$perPage ??= 50;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Platform Activity Log</h5>
    <div class="text-muted small"><?= number_format($total) ?> event<?= $total === 1 ? '' : 's' ?> across all tenants</div>
  </div>
</div>

<?php if (empty($entries)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">No activity recorded yet.</div>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
      <?php foreach ($entries as $e): ?>
        <div class="list-group-item d-flex justify-content-between align-items-start gap-3">
          <div class="small"><?= htmlspecialchars($e['sentence']) ?></div>
          <span class="badge bg-light text-dark border flex-shrink-0"><?= htmlspecialchars($e['tenant_name'] ?? '—') ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  </div>

  <?php if ($total > $perPage): ?>
    <div class="d-flex justify-content-between align-items-center mt-3">
      <span class="small text-muted">Page <?= $page ?> of <?= (int) ceil($total / $perPage) ?></span>
      <div class="d-flex gap-1">
        <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>" class="btn btn-sm btn-outline-secondary">&larr; Prev</a><?php endif; ?>
        <?php if ($page * $perPage < $total): ?><a href="?page=<?= $page + 1 ?>" class="btn btn-sm btn-outline-secondary">Next &rarr;</a><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
