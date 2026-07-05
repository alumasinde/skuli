<?php
declare(strict_types=1);

/**
 * Expected:
 * $exams   rows (id, name, type, term_name, class_name, start_date, end_date, graded_students)
 * $isAdmin optional bool
 */

$exams ??= [];

$typeBadge = static function (string $t): string {
    return match ($t) {
        'endterm' => 'bg-primary-subtle text-primary-emphasis',
        'midterm' => 'bg-info-subtle text-info-emphasis',
        'cat'     => 'bg-warning-subtle text-warning-emphasis',
        'mock'    => 'bg-danger-subtle text-danger-emphasis',
        'opener'  => 'bg-secondary-subtle text-secondary-emphasis',
        default   => 'bg-light text-dark border',
    };
};
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-clipboard-check me-2 text-primary"></i>Exams</h5>
    <div class="text-muted small"><?= count($exams) ?> exam<?= count($exams) === 1 ? '' : 's' ?></div>
  </div>
  <div class="d-flex gap-2">
    <a href="/exams/grade-scales" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-list-ol me-1"></i>Grade Scales
    </a>
    <?php if ($isAdmin ?? true): ?>
      <a href="/exams/create" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create Exam</a>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($exams)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-clipboard-x d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      No exams yet.
      <?php if ($isAdmin ?? true): ?>
        <div class="mt-3">
          <a href="/exams/create" class="btn btn-outline-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>Create your first exam</a>
        </div>
      <?php endif; ?>
    </div>
  </div>
<?php else: ?>
  <div class="card border-0 shadow-sm">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr class="small text-muted">
            <th class="ps-3">Exam</th>
            <th>Type</th>
            <th>Term</th>
            <th>Class</th>
            <th>Dates</th>
            <th class="text-center">Graded</th>
            <th class="text-end pe-3" style="width:1%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($exams as $e): ?>
            <tr>
              <td class="ps-3">
                <a href="/exams/<?= (int) $e['id'] ?>" class="text-decoration-none fw-semibold">
                  <?= htmlspecialchars($e['name'] ?? '') ?>
                </a>
                <?php if (!empty($e['locked_at'])): ?>
                  <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1"><i class="bi bi-lock-fill"></i></span>
                <?php elseif (!empty($e['published_at'])): ?>
                  <span class="badge bg-success-subtle text-success-emphasis ms-1">Published</span>
                <?php endif; ?>
              </td>
              <td><span class="badge <?= $typeBadge($e['type'] ?? '') ?> text-capitalize"><?= htmlspecialchars($e['type'] ?? '') ?></span></td>
              <td class="small"><?= htmlspecialchars($e['term_name'] ?? '—') ?></td>
              <td class="small"><?= htmlspecialchars($e['class_name'] ?? 'All classes') ?></td>
              <td class="small text-muted">
                <?= \Core\Session::formatDate($e['start_date'] ?? null) ?>
                &ndash; <?= \Core\Session::formatDate($e['end_date'] ?? null) ?>
              </td>
              <td class="text-center">
                <?php $g = (int) ($e['graded_students'] ?? 0); ?>
                <?php if ($g > 0): ?>
                  <span class="badge bg-success-subtle text-success-emphasis"><?= $g ?></span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="/exams/<?= (int) $e['id'] ?>" class="btn btn-outline-secondary" title="Open / enter marks">
                    <i class="bi bi-pencil-square"></i>
                  </a>
                  <a href="/exams/<?= (int) $e['id'] ?>/results" class="btn btn-outline-primary" title="View results">
                    <i class="bi bi-bar-chart-line"></i>
                  </a>
                  <?php if ($isAdmin ?? true): ?>
                    <button type="button" class="btn btn-outline-danger" title="Delete"
                            data-bs-toggle="modal" data-bs-target="#deleteExamModal"
                            data-id="<?= (int) $e['id'] ?>"
                            data-name="<?= htmlspecialchars($e['name'] ?? '', ENT_QUOTES) ?>">
                      <i class="bi bi-trash"></i>
                    </button>
                  <?php endif; ?>
                </div>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <?php if ($isAdmin ?? true): ?>
    <div class="modal fade" id="deleteExamModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title">Delete exam</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">Delete <strong id="deleteExamName">this exam</strong>? Recorded results are kept but the exam is hidden.</div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <form id="deleteExamForm" method="POST" action="/exams/0/delete" class="d-inline">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm"><i class="bi bi-trash me-1"></i>Delete</button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script src="/assets/js/exams-index.js"></script>
  <?php endif; ?>
<?php endif; ?>