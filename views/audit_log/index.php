<?php
declare(strict_types=1);

/** @var array $entries @var int $total @var int $page @var int $perPage */
$entries ??= [];
$total   ??= 0;
$page    ??= 1;
$perPage ??= 50;

// Small icon/color per action type — purely cosmetic, safe to ignore if you
// just want the plain sentence list.
$actionStyle = static function (string $action): array {
    return match (true) {
        str_starts_with($action, 'login')   => ['bi-box-arrow-in-right', 'text-primary'],
        str_starts_with($action, 'logout')  => ['bi-box-arrow-right', 'text-muted'],
        $action === 'create'                => ['bi-plus-circle', 'text-success'],
        $action === 'update'                => ['bi-pencil', 'text-info'],
        in_array($action, ['delete', 'deactivate', 'suspend', 'cancel'], true) => ['bi-dash-circle', 'text-danger'],
        default                             => ['bi-clock-history', 'text-secondary'],
    };
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-clock-history me-2 text-primary"></i>Activity Log</h5>
    <div class="text-muted small"><?= number_format($total) ?> event<?= $total === 1 ? '' : 's' ?></div>
  </div>
</div>

<?php if (empty($entries)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-clock-history d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      No activity recorded yet.
    </div>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="list-group list-group-flush">
      <?php foreach ($entries as $e): [$icon, $color] = $actionStyle($e['action']); ?>
        <div class="list-group-item d-flex align-items-start gap-3">
          <i class="bi <?= $icon ?> <?= $color ?> fs-5 mt-1"></i>
          <div class="small"><?= htmlspecialchars($e['sentence']) ?></div>
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
