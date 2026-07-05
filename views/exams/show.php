<?php
declare(strict_types=1);

/**
 * Expected:
 * $exam        exam row (id, name, type, term_name, class_name, class_id, dates,
 *              published_at, published_by, locked_at — all present since the
 *              repository selects e.*)
 * $classes     list (id, name) for the class picker
 * $gradeScales all scales (id, grade_system, grade, min_score, max_score) — for legend + system pick
 * $marksheet   null OR ['class_id'=>int, 'students'=>[], 'subjects'=>[], 'existing'=>['sid:subid'=>row]]
 * $errors      array
 * $isAdmin     optional bool
 */

$exam        ??= [];
$classes     ??= [];
$gradeScales ??= [];
$marksheet   ??= null;
$errors      ??= [];

$examId     = (int) ($exam['id'] ?? 0);
$fixedClass = !empty($exam['class_id']); // exam is locked to one class
$isPublished = !empty($exam['published_at']);
$isLocked    = !empty($exam['locked_at']);

// distinct grading systems available (kcse/cbc)
$systems = array_values(array_unique(array_map(static fn ($s) => $s['grade_system'] ?? 'kcse', $gradeScales)));
if (empty($systems)) { $systems = ['kcse']; }
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h6 class="text-muted mb-0"><i class="bi bi-clipboard-check me-1"></i>Exam</h6>
  <a href="/exams" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="fw-bold mb-1">
        <?= htmlspecialchars($exam['name'] ?? '') ?>
        <?php if ($isLocked): ?>
          <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1"><i class="bi bi-lock-fill me-1"></i>Locked</span>
        <?php elseif ($isPublished): ?>
          <span class="badge bg-success-subtle text-success-emphasis ms-1"><i class="bi bi-check-circle-fill me-1"></i>Published</span>
        <?php else: ?>
          <span class="badge bg-warning-subtle text-warning-emphasis ms-1">Not Published</span>
        <?php endif; ?>
      </h5>
      <div class="text-muted small">
        <span class="badge bg-primary-subtle text-primary-emphasis text-capitalize me-1"><?= htmlspecialchars($exam['type'] ?? '') ?></span>
        <?= htmlspecialchars($exam['term_name'] ?? '') ?>
        <?php if (!empty($exam['class_name'])): ?> · <?= htmlspecialchars($exam['class_name']) ?><?php endif; ?>
        · <?= \Core\Session::formatDate($exam['start_date'] ?? null) ?> &ndash; <?= \Core\Session::formatDate($exam['end_date'] ?? null) ?>
        <?php if ($isPublished): ?>
          · published <?= \Core\Session::formatDate($exam['published_at']) ?>
        <?php endif; ?>
      </div>
    </div>
    <div class="d-flex gap-2">
      <a href="/exams/<?= $examId ?>/results" class="btn btn-sm btn-outline-primary">
        <i class="bi bi-bar-chart-line me-1"></i>View Results
      </a>
      <?php if (($isAdmin ?? true) && !$isLocked): ?>
        <form method="POST" action="/exams/<?= $examId ?>/publish" class="d-inline"
              onsubmit="return confirm('<?= $isPublished ? 'Republish this exam? Class positions and grades will be recalculated.' : 'Publish this exam? Report cards will become available to teachers and parents.' ?>');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-primary">
            <i class="bi bi-send-check me-1"></i><?= $isPublished ? 'Republish' : 'Publish Exam' ?>
          </button>
        </form>
      <?php endif; ?>
    </div>
  </div>
</div>

<?php if ($isLocked): ?>
  <div class="alert alert-secondary"><i class="bi bi-lock-fill me-1"></i>This exam is locked. Marks and results can no longer be changed.</div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<!-- Class picker (hidden when the exam is locked to one class) -->
<?php if (!$fixedClass): ?>
  <div class="card border-0 shadow-sm mb-3">
    <div class="card-body">
      <form method="GET" action="/exams/<?= $examId ?>" class="row g-2 align-items-end">
        <div class="col-md-6">
          <label class="form-label small mb-1">Select a class to enter marks</label>
          <select name="class_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Choose class…</option>
            <?php foreach ($classes as $c): ?>
              <option value="<?= (int) $c['id'] ?>" <?= ($marksheet['class_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                <?= htmlspecialchars($c['name'] ?? '') ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </form>
    </div>
  </div>
<?php endif; ?>

<?php if ($marksheet === null): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-table d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      Select a class above to enter marks.
    </div>
  </div>
<?php elseif (empty($marksheet['students'])): ?>
  <div class="alert alert-warning">This class has no students enrolled.</div>
<?php elseif (empty($marksheet['subjects'])): ?>
  <div class="alert alert-warning">
    This class has no subjects assigned. <a href="/classes/<?= (int) $marksheet['class_id'] ?>">Assign subjects</a> first.
  </div>
<?php else: ?>

  <?php $students = $marksheet['students']; $subjects = $marksheet['subjects']; $existing = $marksheet['existing']; ?>

  <form method="POST" action="/exams/<?= $examId ?>/results">
    <?= csrf_field() ?>
    <input type="hidden" name="class_id" value="<?= (int) $marksheet['class_id'] ?>">

    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex flex-wrap justify-content-between align-items-center gap-2">
        <span class="fw-semibold small"><i class="bi bi-pencil-square me-1"></i>Marksheet — <?= count($students) ?> students × <?= count($subjects) ?> subjects</span>
        <div class="d-flex align-items-center gap-2">
          <label class="small text-muted mb-0">Out of</label>
          <input type="number" name="max_marks" value="100" min="1" step="1" class="form-control form-control-sm" style="width:80px;" <?= $isLocked ? 'disabled' : '' ?>>
          <label class="small text-muted mb-0 ms-2">System</label>
          <select name="grade_system" class="form-select form-select-sm" style="width:auto;" <?= $isLocked ? 'disabled' : '' ?>>
            <?php foreach ($systems as $sys): ?>
              <option value="<?= htmlspecialchars($sys) ?>"><?= strtoupper(htmlspecialchars($sys)) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm table-bordered align-middle mb-0" style="min-width:640px;">
          <thead class="table-light">
            <tr class="small">
              <th class="ps-3" style="position:sticky;left:0;background:inherit;min-width:180px;">Student</th>
              <?php foreach ($subjects as $sub): ?>
                <th class="text-center" title="<?= htmlspecialchars($sub['subject_name'] ?? $sub['name'] ?? '') ?>">
                  <?= htmlspecialchars($sub['subject_code'] ?? $sub['code'] ?? '') ?>
                </th>
              <?php endforeach; ?>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($students as $st): $sid = (int) $st['id']; ?>
              <tr>
                <td class="ps-3" style="position:sticky;left:0;background:#fff;">
                  <div class="fw-semibold small"><?= htmlspecialchars(trim(($st['first_name'] ?? '') . ' ' . ($st['last_name'] ?? ''))) ?></div>
                  <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($st['admission_no'] ?? '') ?></div>
                </td>
                <?php foreach ($subjects as $sub): $subId = (int) ($sub['subject_id'] ?? $sub['id']); ?>
                  <?php $cell = $existing[$sid . ':' . $subId] ?? null; ?>
                  <td class="p-1" style="min-width:64px;">
                    <input type="number"
                           name="marks[<?= $sid ?>][<?= $subId ?>]"
                           value="<?= $cell !== null ? htmlspecialchars((string) $cell['marks']) : '' ?>"
                           min="0" step="0.01"
                           class="form-control form-control-sm text-center"
                           style="min-width:56px;"
                           placeholder="—"
                           <?= $isLocked ? 'readonly' : '' ?>>
                  </td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <small class="text-muted">
          <?= $isLocked ? 'This exam is locked — marks can no longer be edited.' : 'Blank cells are skipped. Existing marks are pre-filled and will be updated.' ?>
        </small>
        <button type="submit" class="btn btn-primary btn-sm" <?= $isLocked ? 'disabled' : '' ?>>
          <i class="bi bi-save me-1"></i>Save Marks
        </button>
      </div>
    </div>
  </form>

  <?php if ($isPublished): ?>
    <div class="alert alert-info mt-3 d-flex justify-content-between align-items-center">
      <span><i class="bi bi-info-circle me-1"></i>This exam has been published. Saving new marks here will not update report cards until you Republish above.</span>
      <a href="/exams/<?= $examId ?>/results" class="btn btn-sm btn-outline-info">View Results</a>
    </div>
  <?php endif; ?>

  <?php if (!empty($gradeScales)): ?>
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold small"><i class="bi bi-list-ol me-1"></i>Grade Bands</div>
      <div class="card-body d-flex flex-wrap gap-2">
        <?php foreach ($gradeScales as $s): ?>
          <span class="badge bg-light text-dark border">
            <strong><?= htmlspecialchars($s['grade']) ?></strong>
            <?= rtrim(rtrim((string) $s['min_score'], '0'), '.') ?>–<?= rtrim(rtrim((string) $s['max_score'], '0'), '.') ?>
            <span class="text-muted">(<?= strtoupper(htmlspecialchars($s['grade_system'])) ?>)</span>
          </span>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>