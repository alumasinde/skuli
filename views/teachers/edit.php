<?php
declare(strict_types=1);

/**
 * Expected:
 * $teacher  the teacher row (id, names, employee_no, photo_url, all fields)
 * $errors   array of error strings
 */

$teacher ??= [];
$errors  ??= [];

$id = (int) ($teacher['id'] ?? 0);
$v  = static fn (string $k, $d = '') => htmlspecialchars((string) ($teacher[$k] ?? $d));
$sel = static fn (string $k, string $val): string => (string) ($teacher[$k] ?? '') === $val ? 'selected' : '';
$currentPhoto = (string) ($teacher['photo_url'] ?? '');
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Teacher</h5>
    <div class="text-muted small">
      <?= htmlspecialchars(trim(($teacher['first_name'] ?? '') . ' ' . ($teacher['last_name'] ?? ''))) ?>
      · Employee No <?= htmlspecialchars($teacher['employee_no'] ?? '—') ?>
    </div>
  </div>
  <a href="/teachers/<?= $id ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <div class="alert alert-light border small mb-3">
      <i class="bi bi-info-circle me-1"></i>
      The linked user account and employee number can't be changed here. Personal name and email live on
      the user's own account.
    </div>

    <form method="POST" action="/teachers/<?= $id ?>/update" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-3">

        <div class="col-12 d-flex align-items-center gap-3">
          <img id="photoPreview"
               src="<?= $currentPhoto !== '' ? htmlspecialchars($currentPhoto) : '#' ?>"
               onerror="this.style.visibility='hidden'" alt=""
               style="width:72px;height:72px;object-fit:cover;border-radius:50%;background:#eee;<?= $currentPhoto === '' ? 'visibility:hidden;' : '' ?>">
          <div>
            <label class="form-label mb-1">Photo</label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                   class="form-control" onchange="previewPhoto(this)">
            <small class="text-muted">Leave empty to keep the current photo.</small>
          </div>
        </div>

        <div class="col-md-4">
          <label class="form-label">TSC Number</label>
          <input type="text" name="tsc_no" class="form-control" value="<?= $v('tsc_no') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">National ID</label>
          <input type="text" name="national_id" class="form-control" value="<?= $v('national_id') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Phone</label>
          <input type="text" name="phone" class="form-control" value="<?= $v('phone') ?>">
        </div>

        <div class="col-md-4">
          <label class="form-label">Gender</label>
          <select name="gender" class="form-select">
            <?php foreach (['' => '—', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= $sel('gender', $val) ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-4">
          <label class="form-label">Date of Birth</label>
          <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars(substr((string) ($teacher['dob'] ?? ''), 0, 10)) ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Hire Date</label>
          <input type="date" name="hire_date" class="form-control" value="<?= htmlspecialchars(substr((string) ($teacher['hire_date'] ?? ''), 0, 10)) ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Qualification</label>
          <input type="text" name="qualification" class="form-control" value="<?= $v('qualification') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Specialization</label>
          <input type="text" name="specialization" class="form-control" value="<?= $v('specialization') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Employment Type</label>
          <select name="employment_type" class="form-select">
            <?php foreach (['permanent' => 'Permanent', 'contract' => 'Contract', 'part_time' => 'Part-time'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= $sel('employment_type', $val) ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6 d-flex align-items-end">
          <div class="form-check">
            <input type="checkbox" name="is_class_teacher" value="1" class="form-check-input" id="ict"
                   <?= ($teacher['is_class_teacher'] ?? 0) ? 'checked' : '' ?>>
            <label class="form-check-label" for="ict">Is a class teacher</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" rows="2" class="form-control"><?= $v('address') ?></textarea>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Save Changes</button>
        <a href="/teachers/<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>

  </div>
</div>

<script src="/assets/js/photo-preview.js"></script>