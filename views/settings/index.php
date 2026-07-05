<?php

declare(strict_types=1);

/**
 * Expected:
 * $title
 * $school   => schools table row
 * $settings => school_settings row
 */

$school   ??= [];
$settings ??= [];

/** Small helper: render a value or an em dash when empty. */
$show = static function ($v): string {
    $v = is_scalar($v) ? (string) $v : '';
    return $v === '' ? '—' : htmlspecialchars($v);
};

?>

<div class="d-flex justify-content-between align-items-center mb-3">

    <div>
        <h5 class="mb-1">School Settings</h5>
        <small class="text-muted">
            Configure general school preferences and system behavior.
        </small>
    </div>

    <a href="/settings/edit" class="btn btn-primary btn-sm">
        <i class="bi bi-gear me-1"></i>
        Update Settings
    </a>

</div>

<?php if (!empty($_GET['success'])): ?>
    <div class="alert alert-success py-2">
        Settings updated successfully.
    </div>
<?php endif; ?>

<?php if (empty($school)): ?>

    <div class="card border-0 shadow-sm">
        <div class="card-body text-center py-5 text-muted">
            School settings have not been configured yet.
        </div>
    </div>

<?php else: ?>

    <div class="row g-3">

        <!-- ── School Profile ─────────────────────────────────────────── -->
        <div class="col-lg-7">

            <div class="card border-0 shadow-sm h-100">

                <div class="card-header bg-white">
                    <h6 class="mb-0">School Profile</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:40%">School Name</th>
                                <td><?= $show($school['name'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Motto</th>
                                <td><?= $show($school['motto'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Email</th>
                                <td><?= $show($school['email'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Phone</th>
                                <td><?= $show($school['phone'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Website</th>
                                <td><?= $show($school['website'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Address</th>
                                <td><?= $show($school['address'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">County</th>
                                <td><?= $show($school['county'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Sub County</th>
                                <td><?= $show($school['sub_county'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">KNEC Code</th>
                                <td><?= $show($school['knec_code'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Principal</th>
                                <td><?= $show($school['principal_name'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Type</th>
                                <td><?= $show(ucfirst($school['school_type'] ?? '')) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Level</th>
                                <td><?= $show(ucfirst($school['school_level'] ?? '')) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

        <!-- ── Admission + Payments ───────────────────────────────────── -->
        <div class="col-lg-5">

            <div class="card border-0 shadow-sm mb-3">

                <div class="card-header bg-white">
                    <h6 class="mb-0">Admission Numbering</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:55%">Prefix</th>
                                <td><?= $show($settings['admission_prefix'] ?? 'SCH') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Year Mode</th>
                                <td>
                                    <?= $show(ucwords(str_replace('_', ' ', (string) ($settings['admission_year_mode'] ?? 'academic_year')))) ?>
                                </td>
                            </tr>
                            <tr>
                                <th class="text-muted">Next Number</th>
                                <td><?= $show((string) ($settings['admission_next'] ?? 1)) ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Padding</th>
                                <td><?= $show((string) ($settings['admission_padding'] ?? 4)) ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

            <div class="card border-0 shadow-sm">

                <div class="card-header bg-white">
                    <h6 class="mb-0">M-Pesa</h6>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <tbody>
                            <tr>
                                <th class="text-muted" style="width:55%">Paybill</th>
                                <td><?= $show($school['mpesa_paybill'] ?? '') ?></td>
                            </tr>
                            <tr>
                                <th class="text-muted">Account</th>
                                <td><?= $show($school['mpesa_account'] ?? '') ?></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

            </div>

        </div>

    </div>

<?php endif; ?>