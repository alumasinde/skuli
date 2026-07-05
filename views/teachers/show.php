<?php
declare(strict_types=1);

/**
 * Expected:
 * $teacher      teacher row (id, names, email, employee_no, photo_url, all fields)
 * $subjects     assigned subjects (id, subject_id, class_id, subject_name, subject_code, class_name)
 * $allClasses   classes for the assign dropdown (id, name)
 * $allSubjects  subjects for the assign dropdown (id, name, code)
 * $errors       array of error strings
 * $isAdmin      optional bool
 */

$teacher     ??= [];
$subjects    ??= [];
$allClasses  ??= [];
$allSubjects ??= [];
$errors      ??= [];

$id       = (int) ($teacher['id'] ?? 0);
$name     = trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''));
$photo    = (string) ($teacher['photo_url'] ?? '');
$initials = strtoupper(mb_substr($teacher['first_name'] ?? '', 0, 1) . mb_substr($teacher['last_name'] ?? '', 0, 1));
$hire     = $teacher['hire_date'] ?? '';
$hireDisp = $hire ? htmlspecialchars(date('d M Y', strtotime($hire))) : '—';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="text-muted mb-0"><i class="bi bi-person-badge me-1"></i>Teacher Profile</h6>
  <div class="d-flex gap-2">
    <?php if ($isAdmin ?? true): ?>
      <a href="/teachers/<?= $id ?>/edit" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i>Edit</a>
    <?php endif; ?>
    <a href="/teachers" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
  </div>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3">

  <!-- Identity + details -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center">
        <?php if ($photo !== ''): ?>
          <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($name) ?>"
               class="rounded-circle shadow-sm mb-3"
               style="width:110px;height:110px;object-fit:cover;border:3px solid #fff;">
        <?php else: ?>
          <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center
                      bg-primary-subtle text-primary-emphasis fw-bold shadow-sm"
               style="width:110px;height:110px;font-size:2rem;">
            <?= htmlspecialchars($initials ?: '?') ?>
          </div>
        <?php endif; ?>

        <h5 class="fw-bold mb-1"><?= htmlspecialchars($name) ?></h5>
        <div class="text-muted small mb-2"><?= htmlspecialchars($teacher['email'] ?? '') ?></div>

        <div class="d-flex justify-content-center flex-wrap gap-1">
          <?php if ($teacher['is_class_teacher'] ?? false): ?>
            <span class="badge bg-info-subtle text-info-emphasis">Class Teacher</span>
          <?php endif; ?>
          <span class="badge bg-light text-dark border text-capitalize">
            <?= htmlspecialchars(str_replace('_', ' ', $teacher['employment_type'] ?? '')) ?>
          </span>
        </div>
      </div>
    </div>

    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold small">Details</div>
      <div class="card-body">
        <dl class="row mb-0 small">
          <dt class="col-5 text-muted">Employee No</dt><dd class="col-7"><?= htmlspecialchars($teacher['employee_no'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">TSC No</dt><dd class="col-7"><?= htmlspecialchars($teacher['tsc_no'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">National ID</dt><dd class="col-7"><?= htmlspecialchars($teacher['national_id'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Phone</dt><dd class="col-7"><?= htmlspecialchars($teacher['phone'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Qualification</dt><dd class="col-7"><?= htmlspecialchars($teacher['qualification'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Specialization</dt><dd class="col-7"><?= htmlspecialchars($teacher['specialization'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Hire Date</dt><dd class="col-7"><?= $hireDisp ?></dd>
        </dl>
      </div>
    </div>
  </div>

  <!-- Subject assignments -->
  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small d-flex justify-content-between align-items-center">
        <span><i class="bi bi-journal-bookmark me-1"></i>Subjects &amp; Classes taught</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($subjects) ?></span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted">
              <th class="ps-3">Subject</th><th>Code</th><th>Class</th>
              <?php if ($isAdmin ?? true): ?><th class="text-end pe-3"></th><?php endif; ?>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($subjects)): ?>
              <tr><td colspan="4" class="text-center text-muted py-4">No subjects assigned yet.</td></tr>
            <?php else: ?>
              <?php foreach ($subjects as $s): ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= htmlspecialchars($s['subject_name'] ?? '') ?></td>
                  <td><span class="badge bg-primary-subtle text-primary-emphasis font-monospace"><?= htmlspecialchars($s['subject_code'] ?? '') ?></span></td>
                  <td><?= htmlspecialchars($s['class_name'] ?? '') ?></td>
                  <?php if ($isAdmin ?? true): ?>
                    <td class="text-end pe-3">
                      <form method="POST" action="/teachers/<?= $id ?>/subjects/remove" class="d-inline"
                            onsubmit="return confirm('Remove this assignment?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="subject_id" value="<?= (int) $s['subject_id'] ?>">
                        <input type="hidden" name="class_id" value="<?= (int) $s['class_id'] ?>">
                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove">
                          <i class="bi bi-x-lg"></i>
                        </button>
                      </form>
                    </td>
                  <?php endif; ?>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>

      <?php if ($isAdmin ?? true): ?>
        <div class="card-footer bg-white">
          <form method="POST" action="/teachers/<?= $id ?>/subjects" class="row g-2 align-items-end">
            <?= csrf_field() ?>
            <div class="col-md-5">
              <label class="form-label small mb-1">Subject</label>
              <select name="subject_id" class="form-select form-select-sm" required>
                <option value="">Select subject…</option>
                <?php foreach ($allSubjects as $su): ?>
                  <option value="<?= (int) $su['id'] ?>">
                    <?= htmlspecialchars($su['name'] ?? '') ?> (<?= htmlspecialchars($su['code'] ?? '') ?>)
                  </option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-5">
              <label class="form-label small mb-1">Class</label>
              <select name="class_id" class="form-select form-select-sm" required>
                <option value="">Select class…</option>
                <?php foreach ($allClasses as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['name'] ?? '') ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-2">
              <button type="submit" class="btn btn-primary btn-sm w-100">
                <i class="bi bi-plus-lg me-1"></i>Assign
              </button>
            </div>
          </form>
          <?php if (empty($allSubjects) || empty($allClasses)): ?>
            <div class="small text-muted mt-2">
              <i class="bi bi-info-circle me-1"></i>You need at least one subject and one class before assigning.
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>