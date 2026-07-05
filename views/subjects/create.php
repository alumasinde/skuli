<?php
declare(strict_types=1);

/**
 * Expected:
 * $errors  array of error strings
 * $old     previously submitted values
 */

$errors ??= [];
$old    ??= [];

$v = static fn (string $k) => htmlspecialchars((string) ($old[$k] ?? ''));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-journal-plus me-2 text-primary"></i>Add Subject</h5>
  <a href="/subjects" class="btn btn-sm btn-outline-secondary">
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

        <form method="POST" action="/subjects">
          <?= csrf_field() ?>

          <div class="row g-3">
            <div class="col-md-8">
              <label class="form-label">Subject Name <span class="text-danger">*</span></label>
              <input type="text" name="name" class="form-control" required
                     placeholder="e.g. Mathematics" value="<?= $v('name') ?>">
            </div>

            <div class="col-md-4">
              <label class="form-label">Code <span class="text-danger">*</span></label>
              <input type="text" name="code" class="form-control text-uppercase" required
                     placeholder="e.g. MATH" maxlength="20" value="<?= $v('code') ?>">
              <small class="text-muted">Short unique code (auto-uppercased).</small>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <button type="submit" class="btn btn-primary">
              <i class="bi bi-check-lg me-1"></i>Create Subject
            </button>
            <a href="/subjects" class="btn btn-outline-secondary">Cancel</a>
          </div>
        </form>

      </div>
    </div>
  </div>
</div>