<?php
declare(strict_types=1);

/**
 * Expected (from ExamWebController::results()):
 * $exam        exam row (id, name, type, term_name, class_name, dates, published_at, locked_at)
 * $published   bool — !empty($exam['published_at'])
 * $summaries   rows from exam_student_summaries, joined with student_name, admission_no,
 *              class_name — ordered by class_name, position. NEVER read from exam_results.
 * $isAdmin     optional bool
 */

$exam      ??= [];
$published ??= false;
$summaries ??= [];

$examId = (int) ($exam['id'] ?? 0);

$gradeBadge = static function (?string $g): string {
    if ($g === null || $g === '') { return 'bg-light text-dark border'; }
    return match (true) {
        in_array($g, ['A', 'A-'], true)       => 'bg-success-subtle text-success-emphasis',
        in_array($g, ['B+', 'B', 'B-'], true) => 'bg-info-subtle text-info-emphasis',
        in_array($g, ['C+', 'C', 'C-'], true) => 'bg-warning-subtle text-warning-emphasis',
        default                               => 'bg-danger-subtle text-danger-emphasis',
    };
};

$num = static fn ($n) => rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');

// Group by class for a per-stream table, in the order the repository already sorted them.
$byClass = [];
foreach ($summaries as $row) {
    $byClass[$row['class_name'] ?? '—'][] = $row;
}
?>

<div class="d-flex justify-content-between align-items-center mb-2">
  <h6 class="text-muted mb-0"><i class="bi bi-bar-chart-line me-1"></i>Results</h6>
  <a href="/exams/<?= $examId ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back to Exam</a>
</div>

<div class="card border-0 shadow-sm mb-3">
  <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
    <div>
      <h5 class="fw-bold mb-1"><?= htmlspecialchars($exam['name'] ?? '') ?></h5>
      <div class="text-muted small">
        <span class="badge bg-primary-subtle text-primary-emphasis text-capitalize me-1"><?= htmlspecialchars($exam['type'] ?? '') ?></span>
        <?= htmlspecialchars($exam['term_name'] ?? '') ?>
        · <?= \Core\Session::formatDate($exam['start_date'] ?? null) ?> &ndash; <?= \Core\Session::formatDate($exam['end_date'] ?? null) ?>
      </div>
    </div>
    <?php if ($published): ?>
      <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check-circle-fill me-1"></i>Published</span>
    <?php else: ?>
      <span class="badge bg-warning-subtle text-warning-emphasis">Not Published</span>
    <?php endif; ?>
  </div>
</div>

<?php if (!$published || empty($summaries)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center text-muted py-5">
      <i class="bi bi-hourglass-split d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      This exam hasn't been published yet.
      <div class="mt-3">
        <a href="/exams/<?= $examId ?>" class="btn btn-outline-primary btn-sm">
          <i class="bi bi-pencil-square me-1"></i>Enter marks and publish
        </a>
      </div>
    </div>
  </div>
<?php else: ?>

  <?php foreach ($byClass as $className => $rows): ?>
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-people me-1"></i><?= htmlspecialchars($className) ?></span>
        <span class="text-muted small"><?= count($rows) ?> student<?= count($rows) === 1 ? '' : 's' ?></span>
      </div>
      <div class="table-responsive">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted">
              <th class="ps-3 text-center" style="width:1%;">Pos</th>
              <th>Student</th>
              <th class="text-center">Subjects</th>
              <th class="text-end">Total</th>
              <th class="text-end">Average %</th>
              <th class="text-center">Grade</th>
              <th>Remarks</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($rows as $r): ?>
              <tr>
                <td class="ps-3 text-center fw-semibold">
                  <?= $r['position'] !== null ? (int) $r['position'] : '—' ?>
                </td>
                <td>
                  <div class="fw-semibold small"><?= htmlspecialchars($r['student_name'] ?? '') ?></div>
                  <div class="text-muted" style="font-size:.72rem;"><?= htmlspecialchars($r['admission_no'] ?? '') ?></div>
                </td>
                <td class="text-center small"><?= (int) ($r['subjects'] ?? 0) ?></td>
                <td class="text-end small"><?= $num($r['total_marks'] ?? 0) ?> / <?= $num($r['total_possible'] ?? 0) ?></td>
                <td class="text-end fw-semibold"><?= $num($r['average'] ?? 0) ?>%</td>
                <td class="text-center">
                  <span class="badge <?= $gradeBadge($r['grade'] ?? null) ?>"><?= htmlspecialchars($r['grade'] ?? '—') ?></span>
                </td>
                <td class="small text-muted"><?= htmlspecialchars($r['remarks'] ?? '—') ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </div>
  <?php endforeach; ?>

<?php endif; ?>
