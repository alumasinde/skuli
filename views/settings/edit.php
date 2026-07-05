<?php
declare(strict_types=1);

/**
 * Expected:
 * $school   => schools table row
 * $settings => school_settings row
 */

$school ??= [];
$settings ??= [];

?>

<div class="card border-0 shadow-sm">

    <div class="card-header bg-white d-flex justify-content-between align-items-center">

        <div>
            <h5 class="mb-1">
                Edit School Settings
            </h5>

            <small class="text-muted">
                Update school profile and admission configuration.
            </small>
        </div>

        <a href="/settings" class="btn btn-outline-secondary btn-sm">
            <i class="bi bi-arrow-left me-1"></i>
            Back
        </a>

    </div>

    <div class="card-body">

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" action="/settings/edit">

            <?= csrf_field() ?>

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">School Name</label>

                    <input
                        type="text"
                        name="name"
                        class="form-control"
                        value="<?= htmlspecialchars($school['name'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Email</label>

                    <input
                        type="email"
                        name="email"
                        class="form-control"
                        value="<?= htmlspecialchars($school['email'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Phone</label>

                    <input
                        type="text"
                        name="phone"
                        class="form-control"
                        value="<?= htmlspecialchars($school['phone'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Website</label>

                    <input
                        type="text"
                        name="website"
                        class="form-control"
                        value="<?= htmlspecialchars($school['website'] ?? '') ?>"
                    >
                </div>

                <div class="col-12">
                    <label class="form-label">Address</label>

                    <textarea
                        name="address"
                        rows="3"
                        class="form-control"
                    ><?= htmlspecialchars($school['address'] ?? '') ?></textarea>
                </div>

                <div class="col-md-6">
                    <label class="form-label">County</label>

                    <input
                        type="text"
                        name="county"
                        class="form-control"
                        value="<?= htmlspecialchars($school['county'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Sub County</label>

                    <input
                        type="text"
                        name="sub_county"
                        class="form-control"
                        value="<?= htmlspecialchars($school['sub_county'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">KNEC Code</label>

                    <input
                        type="text"
                        name="knec_code"
                        class="form-control"
                        value="<?= htmlspecialchars($school['knec_code'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">Principal Name</label>

                    <input
                        type="text"
                        name="principal_name"
                        class="form-control"
                        value="<?= htmlspecialchars($school['principal_name'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">School Type</label>

                    <select
                        name="school_type"
                        class="form-select"
                    >
                        <option value="day"
                            <?= ($school['school_type'] ?? '') === 'day' ? 'selected' : '' ?>>
                            Day
                        </option>

                        <option value="boarding"
                            <?= ($school['school_type'] ?? '') === 'boarding' ? 'selected' : '' ?>>
                            Boarding
                        </option>

                        <option value="mixed"
                            <?= ($school['school_type'] ?? '') === 'mixed' ? 'selected' : '' ?>>
                            Mixed
                        </option>
                    </select>
                </div>

                <div class="col-md-6">
                    <label class="form-label">School Level</label>

                    <select
                        name="school_level"
                        class="form-select"
                    >
                        <option value="primary"
                            <?= ($school['school_level'] ?? '') === 'primary' ? 'selected' : '' ?>>
                            Primary
                        </option>

                        <option value="secondary"
                            <?= ($school['school_level'] ?? '') === 'secondary' ? 'selected' : '' ?>>
                            Secondary
                        </option>

                        <option value="college"
                            <?= ($school['school_level'] ?? '') === 'college' ? 'selected' : '' ?>>
                            College
                        </option>
                    </select>
                </div>

                <div class="col-12">
                    <label class="form-label">School Motto</label>

                    <input
                        type="text"
                        name="motto"
                        class="form-control"
                        value="<?= htmlspecialchars($school['motto'] ?? '') ?>"
                    >
                </div>

            </div>

            <hr class="my-4">

            <h6 class="mb-3">
                Admission Settings
            </h6>

            <div class="row g-3">

                <div class="col-md-3">
                    <label class="form-label">
                        Prefix
                    </label>

                    <input
                        type="text"
                        name="admission_prefix"
                        class="form-control"
                        value="<?= htmlspecialchars($settings['admission_prefix'] ?? 'SCH') ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Year Mode
                    </label>

                    <select
                        name="admission_year_mode"
                        class="form-select"
                    >
                        <option value="academic_year"
                            <?= ($settings['admission_year_mode'] ?? '') === 'academic_year' ? 'selected' : '' ?>>
                            Academic Year
                        </option>

                        <option value="calendar_year"
                            <?= ($settings['admission_year_mode'] ?? '') === 'calendar_year' ? 'selected' : '' ?>>
                            Calendar Year
                        </option>
                    </select>
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Next Number
                    </label>

                    <input
                        type="number"
                        name="admission_next"
                        class="form-control"
                        value="<?= htmlspecialchars((string) ($settings['admission_next'] ?? 1)) ?>"
                    >
                </div>

                <div class="col-md-3">
                    <label class="form-label">
                        Padding
                    </label>

                    <input
                        type="number"
                        name="admission_padding"
                        class="form-control"
                        value="<?= htmlspecialchars((string) ($settings['admission_padding'] ?? 4)) ?>"
                    >
                </div>

            </div>

            <hr class="my-4">

            <div class="row g-3">

                <div class="col-md-6">
                    <label class="form-label">
                        M-Pesa Paybill
                    </label>

                    <input
                        type="text"
                        name="mpesa_paybill"
                        class="form-control"
                        value="<?= htmlspecialchars($school['mpesa_paybill'] ?? '') ?>"
                    >
                </div>

                <div class="col-md-6">
                    <label class="form-label">
                        M-Pesa Account
                    </label>

                    <input
                        type="text"
                        name="mpesa_account"
                        class="form-control"
                        value="<?= htmlspecialchars($school['mpesa_account'] ?? '') ?>"
                    >
                </div>

            </div>

            <div class="mt-4">

                <button
                    type="submit"
                    class="btn btn-primary"
                >
                    <i class="bi bi-check-lg me-1"></i>
                    Save Changes
                </button>

            </div>

        </form>

    </div>

</div>