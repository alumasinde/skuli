<?php
declare(strict_types=1);
/**
 * views/classes/index.php
 * $classes (array)
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Classes</h5>

    <a href="/classes/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>
        Add Class
    </a>
</div>

<div class="card">
    <div class="card-body">

        <?php if (empty($classes)): ?>

            <div class="text-center py-5 text-muted">
                No classes have been created yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Grade</th>
                            <th>Section</th>
                            <th>Students</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($classes as $class): ?>

                        <tr>
                            <td class="fw-semibold"><?= htmlspecialchars($class['class_name']) ?></td>

                            <td><?= htmlspecialchars($class['level'] ?? '-') ?></td>

                            <td><?= htmlspecialchars($class['stream'] ?? '-') ?></td>

                            <td><?= htmlspecialchars((string) ($class['student_count'] ?? '0')) ?></td>

                            <td>
<a href="/classes/<?= (int)$class['id'] ?>"
   class="btn btn-sm btn-outline-primary">
    View
</a>

<a href="/classes/<?= (int)$class['id'] ?>/edit"
   class="btn btn-sm btn-outline-secondary">
    Edit
</a>
                            </td>
                        </tr>

                    <?php endforeach; ?>

                    </tbody>

                </table>
            </div>

        <?php endif; ?>

    </div>
</div>