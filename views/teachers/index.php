<?php
declare(strict_types=1);

/**
 * Expected:
 * $teachers  rows (id, first_name, last_name, email, employee_no, specialization,
 *                  tsc_no, employment_type, is_class_teacher, photo_url, subject_count)
 * $isAdmin   optional bool
 */

$teachers ??= [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-person-badge me-2 text-primary"></i>Teachers</h5>
    <div class="text-muted small"><?= count($teachers) ?> teacher<?= count($teachers) === 1 ? '' : 's' ?></div>
  </div>
  <?php if ($isAdmin ?? true): ?>
    <a href="/teachers/create" class="btn btn-primary btn-sm">
      <i class="bi bi-plus-lg me-1"></i>Add Teacher
    </a>
  <?php endif; ?>
</div>

<?php if (empty($teachers)): ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-person-x d-block mb-2" style="font-size:2rem;opacity:.4;"></i>
      No teachers yet.
      <?php if ($isAdmin ?? true): ?>
        <div class="mt-3">
          <a href="/teachers/create" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-plus-lg me-1"></i>Add your first teacher
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
            <th class="ps-3">Teacher</th>
            <th>Employee No</th>
            <th>Specialization</th>
            <th>Type</th>
            <th class="text-center">Subjects</th>
            <th class="text-end pe-3" style="width:1%;">Actions</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($teachers as $t):
            $name     = trim(($t['first_name'] ?? '') . ' ' . ($t['last_name'] ?? ''));
            $photo    = (string) ($t['photo_url'] ?? '');
            $initials = strtoupper(mb_substr($t['first_name'] ?? '', 0, 1) . mb_substr($t['last_name'] ?? '', 0, 1));
          ?>
            <tr>
              <td class="ps-3">
                <div class="d-flex align-items-center gap-2">
                  <?php if ($photo !== ''): ?>
                    <img src="<?= htmlspecialchars($photo) ?>" alt=""
                         style="width:36px;height:36px;object-fit:cover;border-radius:50%;">
                  <?php else: ?>
                    <span class="d-inline-flex align-items-center justify-content-center rounded-circle
                                 bg-primary-subtle text-primary-emphasis fw-semibold"
                          style="width:36px;height:36px;font-size:.8rem;">
                      <?= htmlspecialchars($initials ?: '?') ?>
                    </span>
                  <?php endif; ?>
                  <div>
                    <a href="/teachers/<?= (int) $t['id'] ?>" class="text-decoration-none fw-semibold d-block">
                      <?= htmlspecialchars($name) ?>
                      <?php if ($t['is_class_teacher'] ?? false): ?>
                        <span class="badge bg-info-subtle text-info-emphasis ms-1">Class Teacher</span>
                      <?php endif; ?>
                    </a>
                    <span class="text-muted small"><?= htmlspecialchars($t['email'] ?? '') ?></span>
                  </div>
                </div>
              </td>
              <td class="small"><?= htmlspecialchars($t['employee_no'] ?? '—') ?></td>
              <td class="small"><?= htmlspecialchars($t['specialization'] ?? '—') ?></td>
              <td>
                <span class="badge bg-light text-dark border text-capitalize">
                  <?= htmlspecialchars(str_replace('_', ' ', $t['employment_type'] ?? '—')) ?>
                </span>
              </td>
              <td class="text-center">
                <?php $sc = (int) ($t['subject_count'] ?? 0); ?>
                <?php if ($sc > 0): ?>
                  <span class="badge bg-light text-dark border"><?= $sc ?></span>
                <?php else: ?>
                  <span class="text-muted small">—</span>
                <?php endif; ?>
              </td>
              <td class="text-end pe-3">
                <div class="btn-group btn-group-sm">
                  <a href="/teachers/<?= (int) $t['id'] ?>" class="btn btn-outline-secondary" title="View">
                    <i class="bi bi-eye"></i>
                  </a>
                  <?php if ($isAdmin ?? true): ?>
                    <a href="/teachers/<?= (int) $t['id'] ?>/edit" class="btn btn-outline-secondary" title="Edit">
                      <i class="bi bi-pencil"></i>
                    </a>
                    <button type="button" class="btn btn-outline-danger" title="Deactivate"
                            data-bs-toggle="modal" data-bs-target="#deactivateTeacherModal"
                            data-id="<?= (int) $t['id'] ?>"
                            data-name="<?= htmlspecialchars($name, ENT_QUOTES) ?>">
                      <i class="bi bi-person-dash"></i>
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
    <div class="modal fade" id="deactivateTeacherModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
          <div class="modal-header">
            <h6 class="modal-title">Deactivate teacher</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            Deactivate <strong id="deactivateTeacherName">this teacher</strong>? Their record is kept
            but removed from the active roster and their subject assignments stop applying.
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
            <form id="deactivateTeacherForm" method="POST" action="/teachers/0/delete" class="d-inline">
              <?= csrf_field() ?>
              <button type="submit" class="btn btn-danger btn-sm">
                <i class="bi bi-person-dash me-1"></i>Deactivate
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>
    <script src="/assets/js/teachers-index.js"></script>
  <?php endif; ?>

<?php endif; ?>