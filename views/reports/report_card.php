<?php
declare(strict_types=1);

/** @var array $student @var array|null $card @var array $exams @var int $examId
 *  @var string $system @var bool $canEditRemarks @var array $errors */
$student ??= [];
$card    ??= null;
$exams   ??= [];
$errors  ??= [];

$studentId = (int) ($student['id'] ?? 0);
$fullName  = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="fw-bold mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Report Card</h5>
  <div class="d-flex gap-2">
    <?php if ($card): ?>
      <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()">
        <i class="bi bi-printer me-1"></i>Print
      </button>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<!-- Exam picker -->
<div class="card border-0 shadow-sm mb-3 no-print">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-md-6">
        <label class="form-label small mb-1">Exam</label>
        <select name="exam_id" class="form-select form-select-sm" onchange="this.form.submit()">
          <?php if (empty($exams)): ?>
            <option value="">No exams recorded for this class yet</option>
          <?php else: foreach ($exams as $e): ?>
            <option value="<?= (int) $e['id'] ?>" <?= $examId === (int) $e['id'] ? 'selected' : '' ?>>
              <?= htmlspecialchars($e['name']) ?> (<?= htmlspecialchars(ucfirst($e['type'] ?? '')) ?>)
            </option>
          <?php endforeach; endif; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label small mb-1">Grading System</label>
        <select name="system" class="form-select form-select-sm" onchange="this.form.submit()">
          <option value="kcse" <?= ($system ?? 'kcse') === 'kcse' ? 'selected' : '' ?>>KCSE</option>
          <option value="cbc" <?= ($system ?? '') === 'cbc' ? 'selected' : '' ?>>CBC</option>
        </select>
      </div>
    </form>
  </div>
</div>

<?php if (!$card): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-file-earmark-x d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      No report card available yet<?= !empty($exams) ? ' for the selected exam' : ' — no exams have been recorded for this class' ?>.
    </div>
  </div>
<?php else: ?>

  <div class="card border-0 shadow-sm" id="report-card-print">
    <div class="card-body">

      <!-- Header -->
      <div class="text-center mb-4 pb-3 border-bottom">
        <?php if (!empty($card['photo_url'])): ?>
          <img src="<?= htmlspecialchars($card['photo_url']) ?>" alt=""
               style="width:70px;height:70px;object-fit:cover;border-radius:50%;" class="mb-2">
        <?php endif; ?>
        <h4 class="fw-bold mb-0"><?= htmlspecialchars($card['student_name'] ?? $fullName) ?></h4>
        <div class="text-muted small">
          <?= htmlspecialchars($card['admission_no'] ?? '') ?> &middot; <?= htmlspecialchars($card['class_name'] ?? '') ?>
        </div>
        <div class="fw-semibold mt-1"><?= htmlspecialchars($card['exam_name'] ?? '') ?> &mdash; <?= htmlspecialchars($card['term_name'] ?? '') ?></div>
      </div>

      <!-- Subjects table -->
      <div class="table-responsive mb-4">
        <table class="table table-sm table-bordered align-middle mb-0">
          <thead class="table-light">
            <tr class="small">
              <th>Subject</th><th class="text-center">Marks</th><th class="text-center">%</th>
              <th class="text-center">Grade</th><th class="text-center">Points</th>
              <th class="text-center">Rank</th><th>Teacher's Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach (($card['results'] ?? []) as $r): ?>
              <tr class="small">
                <td class="fw-semibold"><?= htmlspecialchars($r['subject_name']) ?> <span class="text-muted">(<?= htmlspecialchars($r['subject_code']) ?>)</span></td>
                <td class="text-center"><?= (float) $r['marks'] ?>/<?= (float) $r['max_marks'] ?></td>
                <td class="text-center"><?= $r['percentage'] ?>%</td>
                <td class="text-center"><span class="badge bg-primary-subtle text-primary-emphasis"><?= htmlspecialchars($r['grade']) ?></span></td>
                <td class="text-center"><?= $r['points'] ?? '—' ?></td>
                <td class="text-center"><?= $r['rank'] ?? '—' ?><?php if (!empty($r['class_size_subject'])): ?>/<?= $r['class_size_subject'] ?><?php endif; ?></td>
                <td class="text-muted"><?= htmlspecialchars($r['remarks'] ?? '') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Summary -->
      <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
          <div class="border rounded p-2 text-center">
            <div class="small text-muted">Average</div>
            <div class="fw-bold fs-5"><?= $card['average'] ?>%</div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-2 text-center">
            <div class="small text-muted">Overall Grade</div>
            <div class="fw-bold fs-5"><?= htmlspecialchars($card['overall_grade'] ?? '—') ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-2 text-center">
            <div class="small text-muted">Position</div>
            <div class="fw-bold fs-5"><?= $card['position'] ?? '—' ?><?php if (!empty($card['class_size'])): ?>/<?= $card['class_size'] ?><?php endif; ?></div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="border rounded p-2 text-center">
            <div class="small text-muted">Attendance</div>
            <div class="fw-bold fs-5"><?= $card['attendance_pct'] ?? '—' ?><?= $card['attendance_pct'] !== null ? '%' : '' ?></div>
          </div>
        </div>
        <?php if (($card['system'] ?? 'kcse') === 'kcse' && $card['mean_points'] !== null): ?>
          <div class="col-6 col-md-3">
            <div class="border rounded p-2 text-center">
              <div class="small text-muted">Mean Points</div>
              <div class="fw-bold fs-5"><?= $card['mean_points'] ?></div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <!-- Remarks -->
      <?php if ($canEditRemarks ?? false): ?>
        <form method="POST" action="/reports/report-card/<?= $studentId ?>/remarks" class="no-print">
          <?= csrf_field() ?>
          <input type="hidden" name="exam_id" value="<?= (int) $card['exam_id'] ?>">
          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Class Teacher's Remarks</label>
              <textarea name="class_teacher_remarks" class="form-control" rows="2"><?= htmlspecialchars($card['class_teacher_remarks'] ?? '') ?></textarea>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Principal's Remarks</label>
              <textarea name="principal_remarks" class="form-control" rows="2"><?= htmlspecialchars($card['principal_remarks'] ?? '') ?></textarea>
            </div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Save Remarks</button>
        </form>
      <?php else: ?>
        <div class="row g-3">
          <?php if (!empty($card['class_teacher_remarks'])): ?>
            <div class="col-md-6">
              <div class="small text-muted mb-1">Class Teacher's Remarks<?php if (!empty($card['class_teacher_name'])): ?> — <?= htmlspecialchars($card['class_teacher_name']) ?><?php endif; ?></div>
              <div class="border rounded p-2 small"><?= nl2br(htmlspecialchars($card['class_teacher_remarks'])) ?></div>
            </div>
          <?php endif; ?>
          <?php if (!empty($card['principal_remarks'])): ?>
            <div class="col-md-6">
              <div class="small text-muted mb-1">Principal's Remarks<?php if (!empty($card['principal_name'])): ?> — <?= htmlspecialchars($card['principal_name']) ?><?php endif; ?></div>
              <div class="border rounded p-2 small"><?= nl2br(htmlspecialchars($card['principal_remarks'])) ?></div>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </div>

<?php endif; ?>

<style>
  @media print {
    .no-print, .sidebar, .topbar { display: none !important; }
    .main-wrap { margin-left: 0 !important; }
    #report-card-print { box-shadow: none !important; border: none !important; }
  }
</style>
