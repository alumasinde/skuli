<?php
declare(strict_types=1);

/**
 * Expected:
 * $title
 * $class   the class row (id, name, grade_level, section, capacity, description)
 * $errors  array of error strings
 */

$class  ??= [];
$errors ??= [];

$v  = static fn (string $k, $default = '') => htmlspecialchars((string) ($class[$k] ?? $default));
$id = (int) ($class['id'] ?? 0);
?>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <h5 class="mb-1">Edit Class</h5>
            <small class="text-muted">
                <?= htmlspecialchars($class['name'] ?? '') ?>
            </small>
        </div>
        <a href="/classes/<?= $id ?>" class="btn btn-outline-secondary btn-sm">
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

        <form method="POST" action="/classes/<?= $id ?>/update">

            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">Class Name <span class="text-danger">*</span></label>
                    <input type="text" name="name" class="form-control" required value="<?= $v('name') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Grade Level</label>
                    <input type="text" name="grade_level" class="form-control" value="<?= $v('grade_level') ?>">
                </div>

                <div class="col-md-3">
                    <label class="form-label">Section</label>
                    <input type="text" name="section" class="form-control" value="<?= $v('section') ?>">
                </div>

                <div class="col-md-4">
                    <label class="form-label">Capacity</label>
                    <input type="number" name="capacity" min="1" class="form-control" value="<?= $v('capacity') ?>">
                </div>

                <div class="col-12">
                    <label class="form-label">Description</label>
                    <textarea name="description" rows="2" class="form-control"><?= $v('description') ?></textarea>
                </div>

            </div>

            <div class="mt-4 d-flex gap-2">
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-check-lg me-1"></i> Save Changes
                </button>
                <a href="/classes/<?= $id ?>" class="btn btn-outline-secondary">Cancel</a>
            </div>

        </form>

    </div>

</div>