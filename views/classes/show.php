<?php
declare(strict_types=1);


$class       ??= [];
$subjects    ??= [];
$allSubjects ??= [];
$errors      ??= [];

$id = (int) ($class['id'] ?? 0);

// Subject IDs already assigned, so the "assign" dropdown can skip them.
$assignedIds = array_map(static fn ($s) => (int) $s['id'], $subjects);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="text-muted mb-0"><i class="bi bi-easel me-1"></i>Class Profile</h6>
  <div class="d-flex gap-2">
    <?php if ($isAdmin ?? false): ?>
      <a href="/classes/<?= $id ?>/edit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil me-1"></i>Edit
      </a>
    <?php endif; ?>
    <a href="/classes" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $e): ?>
                <li><?= htmlspecialchars($e) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div class="row g-3">

  <!-- ── Left column: identity card ─────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center">

        <div class="rounded-circle mx-auto mb-3 d-flex align-items-center justify-content-center
                    bg-primary-subtle text-primary-emphasis fw-bold shadow-sm"
             style="width:120px;height:120px;font-size:2.25rem;">
          <i class="bi bi-easel2"></i>
        </div>

        <h5 class="fw-bold mb-1"><?= htmlspecialchars($class['class_name'] ?? '') ?></h5>

        <div class="text-muted small mb-2">
          <?= htmlspecialchars($class['level'] ?? 'No level set') ?>
          <?= !empty($class['section']) ? '· ' . htmlspecialchars($class['section']) : '' ?>
        </div>

        <div class="d-flex justify-content-center flex-wrap gap-1 mb-3">
          <span class="badge bg-light text-dark border">
            <?= (int) ($class['student_count'] ?? 0) ?> student<?= (int) ($class['student_count'] ?? 0) === 1 ? '' : 's' ?>
          </span>
          <?php if (!empty($class['capacity'])): ?>
            <span class="badge bg-light text-dark border">Capacity: <?= (int) $class['capacity'] ?></span>
          <?php endif; ?>
        </div>

      </div>
    </div>
  </div>

  <!-- ── Right column: description + subjects ───────────────────────────── -->
  <div class="col-lg-8">
    <div class="row g-3">

      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold small">Description</div>
          <div class="card-body">
            <div class="small"><?= nl2br(htmlspecialchars($class['description'] ?? '—')) ?></div>
          </div>
        </div>
      </div>

      <!-- ── Subjects ──────────────────────────────────────────────────── -->
      <div class="col-12">
        <div class="card border-0 shadow-sm">
          <div class="card-header bg-white fw-semibold small d-flex justify-content-between align-items-center">
            <span><i class="bi bi-journal-bookmark me-1"></i>Subjects</span>
            <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($subjects) ?></span>
          </div>
          <div class="card-body p-0">
            <table class="table table-sm table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="text-muted small">
                  <th class="ps-3">Subject</th>
                  <th>Type</th>
                  <th class="pe-3 text-end">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php if (empty($subjects)): ?>
                  <tr><td colspan="3" class="text-center text-muted py-4">No subjects assigned yet.</td></tr>
                <?php else: ?>
                  <?php foreach ($subjects as $s): ?>
                    <tr>
                      <td class="ps-3"><?= htmlspecialchars($s['subject_name']) ?></td>
                      <td>
                        <?php if ($s['is_compulsory'] ?? false): ?>
                          <span class="badge bg-primary-subtle text-primary-emphasis">Compulsory</span>
                        <?php else: ?>
                          <span class="badge bg-light text-dark border">Elective</span>
                        <?php endif; ?>
                      </td>
                      <td class="pe-3 text-end">
                        <form method="POST" action="/classes/<?= $id ?>/subjects/remove" class="d-inline"
                              onsubmit="return confirm('Remove this subject from the class?');">
                          <?= csrf_field() ?>
                          <input type="hidden" name="subject_id" value="<?= (int) $s['id'] ?>">
                          <button type="submit" class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-x-lg"></i>
                          </button>
                        </form>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php endif; ?>
              </tbody>
            </table>
          </div>

          <?php if (!empty($allSubjects)): ?>
            <div class="card-footer bg-white">
              <form method="POST" action="/classes/<?= $id ?>/subjects" class="row g-2 align-items-end">
                <?= csrf_field() ?>
                <div class="col-sm-6">
                  <label class="form-label small mb-1">Assign a subject</label>
                  <select name="subject_id" class="form-select form-select-sm" required>
                    <option value="">Select subject…</option>
                    <?php foreach ($allSubjects as $s): ?>
                      <?php if (in_array((int) $s['id'], $assignedIds, true)) continue; ?>
                      <option value="<?= (int) $s['id'] ?>"><?= htmlspecialchars($s['subject_name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-sm-4">
                  <div class="form-check">
                    <input type="checkbox" name="is_compulsory" value="1" checked class="form-check-input" id="isCompulsory">
                    <label class="form-check-label small" for="isCompulsory">Compulsory</label>
                  </div>
                </div>
                <div class="col-sm-2">
                  <button type="submit" class="btn btn-sm btn-primary w-100">Assign</button>
                </div>
              </form>
            </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>

</div>