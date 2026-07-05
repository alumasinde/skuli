<?php
declare(strict_types=1);

$subjects ??= [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-journal-bookmark me-2 text-primary"></i>Subjects</h5>
    <div class="text-muted small"><?= count($subjects) ?> subject<?= count($subjects) === 1 ? '' : 's' ?></div>
  </div>
  <?php if ($isAdmin ?? true): ?>
    <a href="/subjects/create" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Subject
    </a>
  <?php endif; ?>
</div>

<?php if (empty($subjects)): ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-journal-x d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      No subjects yet.
      <?php if ($isAdmin ?? true): ?>
        <div class="mt-3">
          <a href="/subjects/create" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add your first subject
          </a>
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
            <th class="ps-3">Code</th>
            <th>Subject</th>
            <th class="text-center">Classes</th>
            <th class="text-end pe-3" style="width:1%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($subjects as $s): ?>
            <tr>
              <td class="ps-3">
                <span class="badge bg-primary-subtle text-primary-emphasis font-monospace">
                  <?= htmlspecialchars($s['code'] ?? '—') ?>
                </span>
              </td>
              <td>
                <a href="/subjects/<?= (int) $s['id'] ?>" class="text-decoration-none fw-semibold">
                  <?= htmlspecialchars($s['name'] ?? '') ?>
                </a>
              </td>
              <td class="text-center">
                <?php $cc = (int) ($s['class_count'] ?? 0); ?>
                <?php if ($cc > 0): ?>
                  <span class="badge bg-light text-dark border"><?= $cc ?></span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="/subjects/<?= (int) $s['id'] ?>" class="btn btn-outline-secondary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($isAdmin ?? true): ?>
                    <a href="/subjects/<?= (int) $s['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger"
                            title="Delete"
                            data-bs-toggle="modal"
                            data-bs-target="#deleteSubjectModal"
                            data-id="<?= (int) $s['id'] ?>"
                            data-name="<?= htmlspecialchars($s['name'] ?? '', ENT_QUOTES) ?>">
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
    <!-- Delete confirmation modal (shared, populated via JS) -->
    <div class="modal fade" id="deleteSubjectModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title">Delete subject</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Delete <strong id="deleteSubjectName">this subject</strong>? It will be removed from the
            active list. This does not delete existing exam results tied to it.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <form id="deleteSubjectForm" method="POST" action="/subjects/0/delete" class="d-inline">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-trash me-1"></i>Delete
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <script src="/assets/js/subjects-index.js"></script>
  <?php endif; ?>

<?php endif; ?>