<?php
declare(strict_types=1);

/**
 * Expected:
 * $terms    list (id, name) for the term dropdown
 * $classes  list (id, name) — optional class restriction
 * $errors   array
 * $old      previous values
 */

$terms   ??= [];
$classes ??= [];
$errors  ??= [];
$old     ??= [];

$v   = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
$sel = static fn (string $k, string $val): string => (string) ($old[$k] ?? '') === $val ? 'selected' : '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-plus me-2 text-primary"></i>Create Exam</h5>
  <a href="/exams" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <?php if (empty($terms)): ?>
      <div class="alert alert-warning">
        You need at least one term before creating an exam. <a href="/terms/create">Create a term</a> first.
      </div>
    <?php endif; ?>

    <form method="POST" action="/exams">
      <?= csrf_field() ?>

      <div class="row g-3">
        <div class="col-md-8">
          <label class="form-label">Exam Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required
                 placeholder="e.g. End of Term 2 Examinations" value="<?= $v('name') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Type</label>
          <select name="type" class="form-select">
            <?php foreach (['endterm'=>'End Term','midterm'=>'Mid Term','cat'=>'CAT','mock'=>'Mock','opener'=>'Opener'] as $val=>$lbl): ?>
              <option value="<?= $val ?>" <?= $sel('type', $val) ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Term <span class="text-danger">*</span></label>
          <select name="term_id" class="form-select" required <?= empty($terms) ? 'disabled' : '' ?>>
            <option value="">Select term…</option>
            <?php foreach ($terms as $t): ?>
              <option value="<?= (int) $t['id'] ?>" <?= $sel('term_id', (string) $t['id']) ?>>
                <?= htmlspecialchars($t['name'] ?? '') ?>
                <?= !empty($t['year_name']) ? ' — ' . htmlspecialchars($t['year_name']) : '' ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Class <span class="text-muted small">(optional)</span></label>
          <select name="class_id" class="form-select">
            <option value="">All classes</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= $sel('class_id', (string) $c['id']) ?>>
                <?= htmlspecialchars($c['name'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
          <small class="text-muted">Leave as “All classes” for a school-wide exam.</small>
        </div>

        <div class="col-md-6">
          <label class="form-label">Start Date <span class="text-danger">*</span></label>
          <input type="date" name="start_date" class="form-control" required value="<?= $v('start_date') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">End Date <span class="text-danger">*</span></label>
          <input type="date" name="end_date" class="form-control" required value="<?= $v('end_date') ?>">
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary" <?= empty($terms) ? 'disabled' : '' ?>>
          <i class="bi bi-check-lg me-1"></i>Create Exam
        </button>
        <a href="/exams" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>

  </div>
</div>