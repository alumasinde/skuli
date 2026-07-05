<?php
/**
 * views/students/index.php
 * Expects: $students (array)
 */
?>

<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Students</h5>

    <a href="/students/create" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>
        Enroll Student
    </a>
</div>

<div class="card">
    <div class="card-body">

        <?php if (empty($students)): ?>

            <div class="text-center py-5 text-muted">
                No students have been enrolled yet.
            </div>

        <?php else: ?>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">

                    <thead class="table-light">
                        <tr>
                            <th>Admission No.</th>
                            <th>Student Name</th>
                            <th>Class</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th width="140">Actions</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($students as $student): ?>

                        <tr>
                            <td><?= htmlspecialchars($student['admission_no']) ?></td>

                            <td>
                                <?= htmlspecialchars(
                                    trim(
                                        $student['first_name'] . ' ' .
                                        ($student['middle_name'] ?? '') . ' ' .
                                        $student['last_name']
                                    )
                                ) ?>
                            </td>

                            <td><?= htmlspecialchars($student['class_name'] ?? $student['class_id']) ?></td>

                            <td><?= htmlspecialchars(ucfirst($student['gender'] ?? '-')) ?></td>

                            <td><?= htmlspecialchars($student['dob'] ?: '-') ?></td>

                            <td>
<a href="/students/<?= (int)$student['id'] ?>"
   class="btn btn-sm btn-outline-primary">
    View
</a>

<a href="/students/<?= (int)$student['id'] ?>/edit"
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