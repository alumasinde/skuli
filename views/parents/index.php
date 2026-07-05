<?php
declare(strict_types=1);

/**
 * Expected:
 * $title
 * $parents
 */

$parents ??= [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Parents & Guardians</h5>
        <small class="text-muted">
            Manage parents and their linked children.
        </small>
    </div>

    <a href="/parents/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>
        Add Parent
    </a>
</div>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <div>
            <strong>Parent Directory</strong>
        </div>

        <span class="badge bg-primary-subtle text-primary-emphasis">
            <?= count($parents) ?> Parent<?= count($parents) === 1 ? '' : 's' ?>
        </span>
    </div>

    <div class="card-body p-0">

        <?php if (empty($parents)): ?>

            <div class="text-center py-5">

                <div class="display-6 text-muted mb-3">
                    <i class="bi bi-people"></i>
                </div>

                <h6 class="fw-semibold">
                    No Parents Found
                </h6>

                <p class="text-muted mb-4">
                    There are currently no registered parents or guardians.
                </p>

                <a href="/parents/create" class="btn btn-primary">
                    <i class="bi bi-plus-lg me-1"></i>
                    Add First Parent
                </a>

            </div>

        <?php else: ?>

            <div class="table-responsive">

                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                    <tr>
                        <th width="60"></th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Occupation</th>
                        <th class="text-center">Children</th>
                        <th width="170">Actions</th>
                    </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($parents as $parent): ?>

                        <?php
                        $name = trim(
                            ($parent['first_name'] ?? '') . ' ' .
                            ($parent['last_name'] ?? '')
                        );

                        $initials = strtoupper(
                            mb_substr($parent['first_name'] ?? '', 0, 1) .
                            mb_substr($parent['last_name'] ?? '', 0, 1)
                        );
                        ?>

                        <tr>

                            <td>

                                <div
                                    class="rounded-circle bg-primary-subtle text-primary-emphasis fw-bold d-flex align-items-center justify-content-center"
                                    style="width:42px;height:42px;">

                                    <?= htmlspecialchars($initials ?: '?') ?>

                                </div>

                            </td>

                            <td>

                                <div class="fw-semibold">
                                    <?= htmlspecialchars($name) ?>
                                </div>

                                <small class="text-muted">
                                    Parent / Guardian
                                </small>

                            </td>

                            <td>

                                <?php if (!empty($parent['email'])): ?>

                                    <a href="mailto:<?= htmlspecialchars($parent['email']) ?>"
                                       class="text-decoration-none">

                                        <?= htmlspecialchars($parent['email']) ?>

                                    </a>

                                <?php else: ?>

                                    <span class="text-muted">—</span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <?= !empty($parent['phone'])
                                    ? htmlspecialchars($parent['phone'])
                                    : '<span class="text-muted">—</span>' ?>

                            </td>

                            <td>

                                <?= !empty($parent['occupation'])
                                    ? htmlspecialchars($parent['occupation'])
                                    : '<span class="text-muted">—</span>' ?>

                            </td>

                            <td class="text-center">

                                <span class="badge bg-info-subtle text-info-emphasis">

                                    <i class="bi bi-mortarboard me-1"></i>

                                    <?= (int)($parent['child_count'] ?? 0) ?>

                                </span>

                            </td>

                            <td>

                                <div class="btn-group btn-group-sm">

                                    <a href="/parents/<?= (int)$parent['id'] ?>"
                                       class="btn btn-outline-primary">

                                        <i class="bi bi-eye me-1"></i>

                                        View

                                    </a>

                                    <a href="/parents/<?= (int)$parent['id'] ?>/edit"
                                       class="btn btn-outline-secondary">

                                        <i class="bi bi-pencil me-1"></i>

                                        Edit

                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        <?php endif; ?>

    </div>

</div>