<?php
declare(strict_types=1);

/**
 * Expected:
 * ------------------------------------
 * $parent
 * $children
 * $allStudents
 * $errors
 */

$parent       ??= [];
$children     ??= [];
$allStudents  ??= [];
$errors       ??= [];

$fullName = trim(
    ($parent['first_name'] ?? '') . ' ' .
    ($parent['last_name'] ?? '')
);

$initials = strtoupper(
    mb_substr($parent['first_name'] ?? '', 0, 1) .
    mb_substr($parent['last_name'] ?? '', 0, 1)
);
?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h6 class="text-muted mb-0">
            <i class="bi bi-people me-1"></i>
            Parent Profile
        </h6>
    </div>

    <div class="d-flex gap-2">

        <a href="/parents/<?= (int)$parent['id'] ?>/edit"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-pencil me-1"></i>
            Edit
        </a>

        <a href="/parents"
           class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Back
        </a>

    </div>

</div>

<?php if (!empty($errors)): ?>

<div class="alert alert-danger">
    <ul class="mb-0">
        <?php foreach ($errors as $error): ?>
            <li><?= htmlspecialchars($error) ?></li>
        <?php endforeach; ?>
    </ul>
</div>

<?php endif; ?>


<div class="row g-3">

    <!-- =======================================================
         LEFT COLUMN
    ======================================================== -->

    <div class="col-lg-4">

        <div class="card border-0 shadow-sm h-100">

            <div class="card-body text-center">

                <div
                    class="rounded-circle bg-primary-subtle text-primary-emphasis
                           d-flex align-items-center justify-content-center
                           mx-auto mb-3 fw-bold shadow-sm"
                    style="width:120px;height:120px;font-size:2.4rem;">

                    <?= htmlspecialchars($initials ?: '?') ?>

                </div>

                <h4 class="fw-bold mb-1">
                    <?= htmlspecialchars($fullName) ?>
                </h4>

                <div class="text-muted mb-3">
                    Parent / Guardian
                </div>

                <div class="d-flex justify-content-center flex-wrap gap-2 mb-3">

                    <span class="badge bg-success-subtle text-success-emphasis">
                        Active
                    </span>

                    <span class="badge bg-info-subtle text-info-emphasis">
                        <?= count($children) ?>
                        Child<?= count($children) == 1 ? '' : 'ren' ?>
                    </span>

                </div>

                <div class="bg-light rounded py-3">

                    <small class="text-muted d-block">
                        Parent ID
                    </small>

                    <strong>
                        #<?= (int)$parent['id'] ?>
                    </strong>

                </div>

            </div>

        </div>

    </div>


    <!-- =======================================================
         RIGHT COLUMN
    ======================================================== -->

    <div class="col-lg-8">

        <div class="row g-3">

            <!-- Personal Details -->

            <div class="col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header bg-white fw-semibold small">
                        <i class="bi bi-person-vcard me-1"></i>
                        Personal Details
                    </div>

                    <div class="card-body">

                        <dl class="row mb-0 small">

                            <dt class="col-5 text-muted">
                                Full Name
                            </dt>

                            <dd class="col-7">
                                <?= htmlspecialchars($fullName) ?>
                            </dd>

                            <dt class="col-5 text-muted">
                                Email
                            </dt>

                            <dd class="col-7">
                                <?= htmlspecialchars($parent['email'] ?? '—') ?>
                            </dd>

                            <dt class="col-5 text-muted">
                                Phone
                            </dt>

                            <dd class="col-7">
                                <?= htmlspecialchars($parent['phone'] ?? '—') ?>
                            </dd>

                            <dt class="col-5 text-muted">
                                Occupation
                            </dt>

                            <dd class="col-7">
                                <?= htmlspecialchars($parent['occupation'] ?? '—') ?>
                            </dd>

                        </dl>

                    </div>

                </div>

            </div>


            <!-- Address -->

            <div class="col-md-6">

                <div class="card border-0 shadow-sm h-100">

                    <div class="card-header bg-white fw-semibold small">
                        <i class="bi bi-geo-alt me-1"></i>
                        Address
                    </div>

                    <div class="card-body">

                        <?php if (!empty($parent['address'])): ?>

                            <div class="small">

                                <?= nl2br(htmlspecialchars($parent['address'])) ?>

                            </div>

                        <?php else: ?>

                            <div class="text-muted small">

                                No address has been provided.

                            </div>

                        <?php endif; ?>

                    </div>

                </div>

            </div>

        </div>

    </div>

    <!-- =======================================================
         LINKED CHILDREN SECTION STARTS HERE
         (Continue with Part 2)
    ======================================================== -->

    <div class="col-12">
              <div class="card border-0 shadow-sm">

            <div class="card-header bg-white d-flex justify-content-between align-items-center">

                <div class="fw-semibold small">
                    <i class="bi bi-mortarboard me-1"></i>
                    Linked Children
                </div>

                <span class="badge bg-primary-subtle text-primary-emphasis">
                    <?= count($children) ?>
                </span>

            </div>

            <div class="card-body p-0">

                <?php if (empty($children)): ?>

                    <div class="text-center py-5">

                        <div class="display-6 text-muted mb-3">
                            <i class="bi bi-person-x"></i>
                        </div>

                        <h6 class="fw-semibold">
                            No Children Linked
                        </h6>

                        <p class="text-muted mb-0">
                            This parent has not been linked to any students.
                        </p>

                    </div>

                <?php else: ?>

                    <div class="table-responsive">

                        <table class="table table-hover align-middle mb-0">

                            <thead class="table-light">

                            <tr>

                                <th width="70"></th>

                                <th>
                                    Student
                                </th>

                                <th>
                                    Admission No.
                                </th>

                                <th>
                                    Class
                                </th>

                                <th>
                                    Relationship
                                </th>

                                <th width="180">
                                    Actions
                                </th>

                            </tr>

                            </thead>

                            <tbody>

                            <?php foreach ($children as $child): ?>

                                <?php
                                $studentName = trim(
                                    ($child['first_name'] ?? '') . ' ' .
                                    ($child['last_name'] ?? '')
                                );

                                $studentInitials = strtoupper(
                                    mb_substr($child['first_name'] ?? '', 0, 1) .
                                    mb_substr($child['last_name'] ?? '', 0, 1)
                                );

                                $photo = (string)($child['photo_url'] ?? '');
                                ?>

                                <tr>

                                    <td>

                                        <?php if ($photo !== ''): ?>

                                            <img
                                                src="<?= htmlspecialchars($photo) ?>"
                                                class="rounded-circle border"
                                                alt=""
                                                style="width:48px;height:48px;object-fit:cover;">

                                        <?php else: ?>

                                            <div
                                                class="rounded-circle bg-primary-subtle text-primary-emphasis
                                                       d-flex align-items-center justify-content-center fw-bold"
                                                style="width:48px;height:48px;">

                                                <?= htmlspecialchars($studentInitials ?: '?') ?>

                                            </div>

                                        <?php endif; ?>

                                    </td>

                                    <td>

                                        <div class="fw-semibold">

                                            <?= htmlspecialchars($studentName) ?>

                                        </div>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($child['admission_no'] ?? '—') ?>

                                    </td>

                                    <td>

                                        <?= htmlspecialchars($child['class_name'] ?? '—') ?>

                                    </td>

                                    <td>

                                        <span class="badge bg-info-subtle text-info-emphasis">

                                            <?= htmlspecialchars(ucfirst($child['relationship'] ?? 'Parent')) ?>

                                        </span>

                                    </td>

                                    <td>

                                        <div class="btn-group btn-group-sm">

                                            <a
                                                href="/students/<?= (int)$child['id'] ?>"
                                                class="btn btn-outline-primary">

                                                <i class="bi bi-eye me-1"></i>

                                                View

                                            </a>

                                            <form
                                                method="POST"
                                                action="/parents/<?= (int)$parent['id'] ?>/unlink"
                                                class="d-inline">

                                                <?= csrf_field() ?>

                                                <input
                                                    type="hidden"
                                                    name="student_id"
                                                    value="<?= (int)$child['id'] ?>">

                                                <button
                                                    type="submit"
                                                    class="btn btn-outline-danger"
                                                    onclick="return confirm('Unlink this student from this parent?');">

                                                    <i class="bi bi-link-45deg"></i>

                                                    Unlink

                                                </button>

                                            </form>

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

    </div>

    <!-- ============================================
         PART 3 STARTS HERE
         Link Child Form + Closing Tags
    ============================================= -->

        <div class="col-12">

        <div class="card border-0 shadow-sm">

            <div class="card-header bg-white">

                <div class="fw-semibold small">
                    <i class="bi bi-link-45deg me-1"></i>
                    Link Child
                </div>

            </div>

            <div class="card-body">

                <form method="POST" action="/parents/link-student">

                    <?= csrf_field() ?>

                    <input
                        type="hidden"
                        name="parent_id"
                        value="<?= (int)$parent['id'] ?>">

                    <input
                        type="hidden"
                        name="from_parent"
                        value="1">

                    <div class="row g-3">

                        <div class="col-md-8">

                            <label class="form-label">
                                Student
                                <span class="text-danger">*</span>
                            </label>

                            <?php if (!empty($allStudents)): ?>

                                <select
                                    name="student_id"
                                    class="form-select"
                                    required>

                                    <option value="">
                                        Select Student...
                                    </option>

                                    <?php foreach ($allStudents as $student): ?>

                                        <option value="<?= (int)$student['id'] ?>">

                                            <?= htmlspecialchars(
                                                trim(
                                                    ($student['first_name'] ?? '') . ' ' .
                                                    ($student['last_name'] ?? '')
                                                )
                                            ) ?>

                                            <?php if (!empty($student['admission_no'])): ?>
                                                (<?= htmlspecialchars($student['admission_no']) ?>)
                                            <?php endif; ?>

                                            <?php if (!empty($student['class_name'])): ?>
                                                - <?= htmlspecialchars($student['class_name']) ?>
                                            <?php endif; ?>

                                        </option>

                                    <?php endforeach; ?>

                                </select>

                            <?php else: ?>

                                <div class="alert alert-warning mb-0">

                                    <i class="bi bi-exclamation-triangle me-1"></i>

                                    No students are available for linking.

                                </div>

                            <?php endif; ?>

                        </div>

                        <div class="col-md-4">

                            <label class="form-label">
                                Relationship
                            </label>

                            <select
                                name="relationship"
                                class="form-select">

                                <option value="parent">Parent</option>
                                <option value="father">Father</option>
                                <option value="mother">Mother</option>
                                <option value="guardian">Guardian</option>
                                <option value="grandparent">Grandparent</option>
                                <option value="uncle">Uncle</option>
                                <option value="aunt">Aunt</option>
                                <option value="sibling">Sibling</option>
                                <option value="other">Other</option>

                            </select>

                        </div>

                    </div>

                    <div class="mt-4">

                        <button
                            type="submit"
                            class="btn btn-primary"
                            <?= empty($allStudents) ? 'disabled' : '' ?>>

                            <i class="bi bi-link-45deg me-1"></i>

                            Link Student

                        </button>

                    </div>

                </form>

            </div>

        </div>

    </div>

</div>