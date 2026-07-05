<?php
declare(strict_types=1);

/**
 * Expected:
 * $title
 * $users   Available user accounts that don't already have a parent record
 * $errors
 */

$users ??= [];
$errors ??= [];
?>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-1">Add Parent / Guardian</h5>
            <small class="text-muted">
                Link an existing user account as a parent or guardian.
            </small>
        </div>

        <a href="/parents" class="btn btn-outline-secondary btn-sm">
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

        <form method="POST" action="/parents">

            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-12">

                    <label class="form-label">
                        User Account
                        <span class="text-danger">*</span>
                    </label>

                    <?php if (!empty($users)): ?>

                        <select
                            name="user_id"
                            class="form-select"
                            required>

                            <option value="">
                                Select User...
                            </option>

                            <?php foreach ($users as $user): ?>

                                <option
                                    value="<?= (int)$user['id'] ?>">

                                    <?= htmlspecialchars(
                                        trim(
                                            ($user['first_name'] ?? '') . ' ' .
                                            ($user['last_name'] ?? '')
                                        )
                                    ) ?>

                                    <?php if (!empty($user['email'])): ?>
                                        (<?= htmlspecialchars($user['email']) ?>)
                                    <?php endif; ?>

                                </option>

                            <?php endforeach; ?>

                        </select>

                    <?php else: ?>

                        <div class="alert alert-warning mb-0">

                            <i class="bi bi-exclamation-triangle me-1"></i>

                            There are no available user accounts to link as parents.

                        </div>

                    <?php endif; ?>

                </div>

                <div class="col-md-6">

                    <label class="form-label">
                        Phone Number
                    </label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
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
                        placeholder="e.g. Engineer">

                </div>

                <div class="col-12">

                    <label class="form-label">
                        Address
                    </label>

                    <textarea
                        name="address"
                        rows="3"
                        class="form-control"
                        placeholder="Residential Address"></textarea>

                </div>

            </div>

            <hr class="my-4">

            <div class="d-flex gap-2">

                <button
                    type="submit"
                    class="btn btn-primary"
                    <?= empty($users) ? 'disabled' : '' ?>>

                    <i class="bi bi-check-lg me-1"></i>

                    Create Parent

                </button>

                <a
                    href="/parents"
                    class="btn btn-outline-secondary">

                    Cancel

                </a>

            </div>

        </form>

    </div>

</div>