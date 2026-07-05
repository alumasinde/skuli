<?php
declare(strict_types=1);

/**
 * Expected:
 * $title
 * $student  the student row (must include id, photo_url, admission_no, all fields)
 * $errors   array of error strings
 * $classes  optional list of classes (id, name)
 */

$student ??= [];
$errors  ??= [];
$classes ??= [];

$v = static fn (string $k, $default = '') => htmlspecialchars((string) ($student[$k] ?? $default));
$currentPhoto = (string) ($student['photo_url'] ?? '');
$id = (int) ($student['id'] ?? 0);
?>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Edit Student</h5>
            <small class="text-muted">
                Admission No: <?= htmlspecialchars((string) ($student['admission_no'] ?? '—')) ?>
            </small>
        </div>
        <a href="/students/<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i> Back
        </a>
    </div>

    <div class="card-body">

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                        <li><?= htmlspecialchars($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <form method="POST" action="/students/<?= $id ?>/update" enctype="multipart/form-data">

            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-12 d-flex align-items-center gap-3">
                    <img id="photoPreview"
                         src="<?= $currentPhoto !== '' ? htmlspecialchars($currentPhoto) : '/uploads/students/placeholder.png' ?>"
                         onerror="this.style.visibility='hidden'"
                         alt=""
                         style="width:72px;height:72px;object-fit:cover;border-radius:50%;background:#eee;">
                    <div>
                        <label class="form-label mb-1">Student Photo</label>
                        <input type="file"
                               name="photo"
                               accept="image/jpeg,image/png,image/webp,image/gif"
                               class="form-control"
                               onchange="previewPhoto(this)">
                        <small class="text-muted">
                            Leave empty to keep the current photo &middot; JPG/PNG/WEBP/GIF &middot; max 3&nbsp;MB
                        </small>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label">First Name <span class="text-danger">*</span></label>
                    <input type="text" name="first_name" class="form-control" required value="<?= $v('first_name') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Middle Name</label>
                    <input type="text" name="middle_name" class="form-control" value="<?= $v('middle_name') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Last Name <span class="text-danger">*</span></label>
                    <input type="text" name="last_name" class="form-control" required value="<?= $v('last_name') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Class <span class="text-danger">*</span></label>
                    <?php if (!empty($classes)): ?>
                        <select name="class_id" class="form-select" required>
                            <?php foreach ($classes as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"
                                    <?= (int) ($student['class_id'] ?? 0) === (int) $c['id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['name']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    <?php else: ?>
                        <input type="number" name="class_id" class="form-control" required value="<?= $v('class_id') ?>">
                    <?php endif; ?>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Gender</label>
                    <select name="gender" class="form-select">
                        <?php foreach (['' => '—', 'male' => 'Male', 'female' => 'Female', 'other' => 'Other'] as $val => $lbl): ?>
                            <option value="<?= $val ?>" <?= (string) ($student['gender'] ?? '') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Date of Birth</label>
                    <input type="date" name="dob" class="form-control" value="<?= htmlspecialchars(substr((string) ($student['dob'] ?? ''), 0, 10)) ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Nationality</label>
                    <input type="text" name="nationality" class="form-control" value="<?= $v('nationality', 'Kenyan') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">National ID</label>
                    <input type="text" name="national_id" class="form-control" value="<?= $v('national_id') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Blood Group</label>
                    <select name="blood_group" class="form-select">
                        <option value="">—</option>
                        <?php foreach (['A+','A-','B+','B-','O+','O-','AB+','AB-'] as $bg): ?>
                            <option value="<?= $bg ?>" <?= (string) ($student['blood_group'] ?? '') === $bg ? 'selected' : '' ?>><?= $bg ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-4">
                    <label class="form-label">Religion</label>
                    <input type="text" name="religion" class="form-control" value="<?= $v('religion') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>
                    <textarea name="address" rows="2" class="form-control"><?= $v('address') ?></textarea>
                </div>

                <div class="col-12">
                    <label class="form-label">Medical Notes</label>
                    <textarea name="medical_notes" rows="2" class="form-control"><?= $v('medical_notes') ?></textarea>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
                <a href="/students/<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>

    </div>

</div>

<script src="/assets/js/photo-preview.js"></script>