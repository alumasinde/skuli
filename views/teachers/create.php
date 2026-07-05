<?php
declare(strict_types=1);

/**
 * Expected:
 * $users   linkable user accounts (id, first_name, last_name, email)
 * $errors  array of error strings
 * $old     previously submitted values
 */

$users  ??= [];
$errors ??= [];
$old    ??= [];

$v = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
$sel = static fn (string $k, string $val): string => (string) ($old[$k] ?? '') === $val ? 'selected' : '';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-person-plus me-2 text-primary"></i>Add Teacher</h5>
  <a href="/teachers" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <?php if (empty($users)): ?>
      <div class="alert alert-warning">
        There are no user accounts available to link. A teacher must be attached to an existing user —
        <a href="/users/create">create a user</a> first.
      </div>
    <?php endif; ?>

    <!-- enctype REQUIRED for the photo to reach $_FILES -->
    <form method="POST" action="/teachers" enctype="multipart/form-data">
      <?= csrf_field() ?>

      <div class="row g-3">

        <div class="col-12 d-flex align-items-center gap-3">
          <img id="photoPreview" src="#" onerror="this.style.visibility='hidden'" alt=""
               style="width:72px;height:72px;object-fit:cover;border-radius:50%;background:#eee;visibility:hidden;">
          <div>
            <label class="form-label mb-1">Photo</label>
            <input type="file" name="photo" accept="image/jpeg,image/png,image/webp,image/gif"
                   class="form-control" onchange="previewPhoto(this)">
            <small class="text-muted">JPG/PNG/WEBP/GIF · max 3&nbsp;MB · optional</small>
          </div>
        </div>

        <div class="col-md-6">
          <label class="form-label">User Account <span class="text-danger">*</span></label>
          <select name="user_id" class="form-select" required <?= empty($users) ? 'disabled' : '' ?>>
            <option value="">Select a user…</option>
            <?php foreach ($users as $u): ?>
              <option value="<?= (int) $u['id'] ?>" <?= $sel('user_id', (string) $u['id']) ?>>
                <?= htmlspecialchars(trim(($u['first_name'] ?? '') . ' ' . ($u['last_name'] ?? ''))) ?>
                (<?= htmlspecialchars($u['email'] ?? '') ?>)
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Employee No <span class="text-danger">*</span></label>
          <input type="text" name="employee_no" class="form-control" required value="<?= $v('employee_no') ?>">
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
          <input type="date" name="dob" class="form-control" value="<?= $v('dob') ?>">
        </div>
        <div class="col-md-4">
          <label class="form-label">Hire Date</label>
          <input type="date" name="hire_date" class="form-control" value="<?= $v('hire_date') ?>">
        </div>

        <div class="col-md-6">
          <label class="form-label">Qualification</label>
          <input type="text" name="qualification" class="form-control"
                 placeholder="e.g. B.Ed Mathematics" value="<?= $v('qualification') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Specialization</label>
          <input type="text" name="specialization" class="form-control"
                 placeholder="e.g. Mathematics & Physics" value="<?= $v('specialization') ?>">
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
                   <?= !empty($old['is_class_teacher']) ? 'checked' : '' ?>>
            <label class="form-check-label" for="ict">Is a class teacher</label>
          </div>
        </div>

        <div class="col-12">
          <label class="form-label">Address</label>
          <textarea name="address" rows="2" class="form-control"><?= $v('address') ?></textarea>
        </div>
      </div>

      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary" <?= empty($users) ? 'disabled' : '' ?>>
          <i class="bi bi-check-lg me-1"></i>Add Teacher
        </button>
        <a href="/teachers" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>

  </div>
</div>

<script src="/assets/js/photo-preview.js"></script>