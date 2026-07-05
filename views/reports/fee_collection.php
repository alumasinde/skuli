<?php
declare(strict_types=1);

/** @var array $terms @var int $termId @var array|null $report */
$terms  ??= [];
$termId ??= 0;
$report ??= null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-graph-up-arrow me-2 text-success"></i>Fee Collection Report</h5>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-4">
        <label class="form-label small mb-1">Term</label>
        <select name="term_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="">Select term…</option>
          <?php foreach ($terms as $t): ?>
            <option value="<?= (int) $t['id'] ?>" <?= $termId === (int) $t['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($t['name']) ?><?= ($t['is_current'] ?? false) ? ' (Current)' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$report): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">Select a term to view its collection summary.</div>
  </div>
<?php else: ?>
  <div class="row g-3">
    <div class="col-md-4">
      <div class="card border-0 shadow-sm bg-light"><div class="card-body">
        <div class="text-muted small">Total Billed</div>
        <div class="fs-4 fw-bold">KES <?= number_format((float) ($report['total_billed'] ?? 0), 2) ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <div class="card border-0 shadow-sm bg-success-subtle"><div class="card-body">
        <div class="text-muted small">Total Collected</div>
        <div class="fs-4 fw-bold text-success">KES <?= number_format((float) ($report['total_paid'] ?? 0), 2) ?></div>
      </div></div>
    </div>
    <div class="col-md-4">
      <?php $balance = (float) ($report['balance'] ?? 0); ?>
      <div class="card border-0 shadow-sm <?= $balance > 0 ? 'bg-danger-subtle' : 'bg-success-subtle' ?>"><div class="card-body">
        <div class="text-muted small">Outstanding</div>
        <div class="fs-4 fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">KES <?= number_format($balance, 2) ?></div>
      </div></div>
    </div>
  </div>

  <?php if (!empty($report['by_class'])): ?>
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold small">By Class</div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light"><tr class="small text-muted"><th class="ps-3">Class</th><th>Billed</th><th>Collected</th><th class="pe-3">Balance</th></tr></thead>
          <tbody>
            <?php foreach ($report['by_class'] as $row): ?>
              <tr class="small">
                <td class="ps-3"><?= htmlspecialchars($row['class_name'] ?? '') ?></td>
                <td>KES <?= number_format((float) ($row['billed'] ?? 0), 2) ?></td>
                <td>KES <?= number_format((float) ($row['paid'] ?? 0), 2) ?></td>
                <td class="pe-3">KES <?= number_format((float) ($row['billed'] ?? 0) - (float) ($row['paid'] ?? 0), 2) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endif; ?>
<?php endif; ?>
