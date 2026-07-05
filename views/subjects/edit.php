<?php
declare(strict_types=1);

/**
 * Expected:
 * $subject  the subject row (id, name, code)
 * $errors   array of error strings
 */

$subject ??= [];
$errors  ??= [];

$id = (int) ($subject['id'] ?? 0);
$v  = static fn (string $k) => htmlspecialchars((string) ($subject[$k] ?? ''));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-pencil-square me-2 text-primary"></i>Edit Subject</h5>
  <a href="/subjects/<?= $id ?>" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Back
  </a>
</div>

<div class="row justify-content-center">
  <div class="col-lg-7">
    <div class="card border-0 shadow-sm">
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

        <form method="POST" action="/subjects/<?= $id ?>/update">
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Subject Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required value="<?= $v('name') ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase" required
                     maxlength="20" value="<?= $v('code') ?>">
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i>Save Changes
            </button>
            <a href="/subjects/<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>