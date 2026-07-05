<?php
declare(strict_types=1);

/**
 * Expected:
 * $scales  all grade scales (id, system, grade, min_score, max_score, points, remark)
 * $errors  array
 * $old     previous add-form values
 * $isAdmin optional bool
 */

$scales ??= [];
$errors ??= [];
$old    ??= [];

$v = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
$num = static fn ($n) => rtrim(rtrim((string) $n, '0'), '.');

// group by system
$grouped = [];
foreach ($scales as $s) { $grouped[$s['grade_system'] ?? 'kcse'][] = $s; }
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-list-ol me-2 text-primary"></i>Grade Scales</h5>
    <div class="text-muted small">Score bands used to auto-grade exam results</div>
  </div>
  <a href="/exams" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Exams</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-8">
    <?php if (empty($grouped)): ?>
      <div class="card border-0 shadow-sm">
        <div class="card-body text-center text-muted py-5">
          <i class="bi bi-list-ol d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
          No grade scales yet. Add your first band on the right.
        </div>
      </div>
    <?php else: ?>
      <?php foreach ($grouped as $grade_system => $rows): ?>
        <div class="card border-0 shadow-sm mb-3">
          <div class="card-header bg-white fw-semibold small text-uppercase"><?= htmlspecialchars($grade_system) ?> scale</div>
          <div class="table-responsive">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="small text-muted">
                  <th class="ps-3">Grade</th><th>Range</th><th>Points</th><th>Remark</th>
                  <?php if ($isAdmin ?? true): ?><th class="text-end pe-3"></th><?php endif; ?>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $s): ?>
                  <tr>
                    <td class="ps-3"><span class="badge bg-primary-subtle text-primary-emphasis"><?= htmlspecialchars($s['grade']) ?></span></td>
                    <td class="small"><?= $num($s['min_score']) ?> – <?= $num($s['max_score']) ?></td>
                    <td class="small"><?= $s['points'] !== null ? $num($s['points']) : '—' ?></td>
                    <td class="small text-muted"><?= htmlspecialchars($s['remark'] ?? '—') ?></td>
                    <?php if ($isAdmin ?? true): ?>
                      <td class="text-end pe-3">
                        <form method="POST" action="/exams/grade-scales/<?= (int) $s['id'] ?>/delete" class="d-inline"
                              onsubmit="return confirm('Delete grade <?= htmlspecialchars($s['grade'], ENT_QUOTES) ?>?');">
                          <?= csrf_field() ?>
                          <button type="submit" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                      </td>
                    <?php endif; ?>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>

  <?php if ($isAdmin ?? true): ?>
    <div class="col-lg-4">
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold small"><i class="bi bi-plus-lg me-1"></i>Add Grade Band</div>
        <div class="card-body">
          <form method="POST" action="/exams/grade-scales">
            <?= csrf_field() ?>
            <div class="mb-2">
              <label class="form-label small mb-1">System</label>
              <select name="grade_system" class="form-select form-select-sm">
                <option value="kcse" <?= ($old['grade_system'] ?? '') === 'kcse' ? 'selected' : '' ?>>KCSE</option>
                <option value="cbc"  <?= ($old['grade_system'] ?? '') === 'cbc'  ? 'selected' : '' ?>>CBC</option>
              </select>
            </div>
            <div class="mb-2">
              <label class="form-label small mb-1">Grade <span class="text-danger">*</span></label>
              <input type="text" name="grade" class="form-control form-control-sm" required maxlength="5"
                     placeholder="e.g. A" value="<?= $v('grade') ?>">
            </div>
            <div class="row g-2 mb-2">
              <div class="col-6">
                <label class="form-label small mb-1">Min %</label>
                <input type="number" name="min_score" class="form-control form-control-sm" required min="0" max="100" step="0.01" value="<?= $v('min_score') ?>">
              </div>
              <div class="col-6">
                <label class="form-label small mb-1">Max %</label>
                <input type="number" name="max_score" class="form-control form-control-sm" required min="0" max="100" step="0.01" value="<?= $v('max_score') ?>">
              </div>
            </div>
            <div class="mb-2">
              <label class="form-label small mb-1">Points <span class="text-muted">(optional)</span></label>
              <input type="number" name="points" class="form-control form-control-sm" step="0.01" value="<?= $v('points') ?>">
            </div>
            <div class="mb-3">
              <label class="form-label small mb-1">Remark <span class="text-muted">(optional)</span></label>
              <input type="text" name="remark" class="form-control form-control-sm" maxlength="100"
                     placeholder="e.g. Excellent" value="<?= $v('remark') ?>">
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100"><i class="bi bi-check-lg me-1"></i>Add Band</button>
          </form>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>