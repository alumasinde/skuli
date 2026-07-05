<?php
declare(strict_types=1);

/**
 * Expected:
 * -----------------------
 * $title
 * $parent
 * $errors (optional)
 */

$parent ??= [];
$errors ??= [];

$id = (int)($parent['id'] ?? 0);

$fullName = trim(
    ($parent['first_name'] ?? '') . ' ' .
    ($parent['last_name'] ?? '')
);

$initials = strtoupper(
    mb_substr($parent['first_name'] ?? '', 0, 1) .
    mb_substr($parent['last_name'] ?? '', 0, 1)
);

$v = static fn(string $key, string $default = ''): string =>
    htmlspecialchars((string)($parent[$key] ?? $default));
?>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-1">Edit Parent</h5>
            <small class="text-muted">
                <?= htmlspecialchars($fullName) ?>
            </small>
        </div>

        <a href="/parents/<?= $id ?>" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Back
        </a>

    </div>

    <div class="card-body">

        <?php if (!empty($errors)): ?>

            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?= htmlspecialchars($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>

        <?php endif; ?>

        <form method="POST" action="/parents/<?= $id ?>/update">

            <?= csrf_field() ?>

            <div class="row g-4">

                <div class="col-lg-3">

                    <div class="text-center">

                        <div
                            class="rounded-circle bg-primary-subtle text-primary-emphasis
                                   d-flex align-items-center justify-content-center
                                   fw-bold shadow-sm mx-auto mb-3"
                            style="width:120px;height:120px;font-size:2.5rem;">

                            <?= htmlspecialchars($initials ?: '?') ?>

                        </div>

                        <h5 class="mb-1">
                            <?= htmlspecialchars($fullName) ?>
                        </h5>

                        <div class="text-muted small">
                            <?= htmlspecialchars($parent['email'] ?? '') ?>
                        </div>

                    </div>

                </div>

                <div class="col-lg-9">

                    <div class="row g-3">

                        <div class="col-md-6">

                            <label class="form-label">
                                Phone Number
                            </label>

                            <input
                                type="text"
                                name="phone"
                                class="form-control"
                                value="<?= $v('phone') ?>"
                                placeholder="e.g. 0712345678">

                        </div>

                        <div class="col-md-6">

                            <label class="form-label">
                                Occupation
                            </label>

                            <input
                                type="text"
                                name="occupation"
                                class="form-control"
                                value="<?= $v('occupation') ?>"
                                placeholder="Occupation">

                        </div>

                        <div class="col-12">

                            <label class="form-label">
                                Address
                            </label>

                            <textarea
                                name="address"
                                rows="4"
                                class="form-control"
                                placeholder="Residential address"><?= $v('address') ?></textarea>

                        </div>

                    </div>

                </div>

            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">

                <button type="submit" class="btn btn-primary">

                    <i class="bi bi-check-lg me-1"></i>

                    Save Changes

                </button>

                <a href="/parents/<?= $id ?>" class="btn btn-outline-secondary">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>