<?php
declare(strict_types=1);

/** @var array $request @var array $errors */
$request ??= [];
$errors  ??= [];

$nameParts = preg_split('/\s+/', trim($request['contact_name'] ?? ''), 2);
$suggestedCode = strtoupper(substr(preg_replace('/[^A-Za-z]/', '', $request['school_name'] ?? ''), 0, 6)) ?: 'SCH';
?>

<nav class="mb-1"><a href="/super-admin/demo-requests" class="small text-decoration-none">&larr; Demo Requests</a></nav>
<h5 class="fw-bold mb-3"><i class="bi bi-building me-2 text-primary"></i><?= htmlspecialchars($request['school_name'] ?? '') ?></h5>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold small">Submission Details</div>
      <div class="card-body">
        <dl class="row small mb-0">
          <dt class="col-5 text-muted">Contact</dt><dd class="col-7"><?= htmlspecialchars($request['contact_name'] ?? '') ?></dd>
          <dt class="col-5 text-muted">Email</dt><dd class="col-7"><?= htmlspecialchars($request['email'] ?? '') ?></dd>
          <dt class="col-5 text-muted">Phone</dt><dd class="col-7"><?= htmlspecialchars($request['phone'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Size</dt><dd class="col-7"><?= htmlspecialchars($request['student_count_range'] ?? '—') ?></dd>
          <dt class="col-5 text-muted">Submitted</dt><dd class="col-7"><?= \Core\Session::formatDate(substr($request['created_at'] ?? '', 0, 10)) ?></dd>
          <dt class="col-5 text-muted">Message</dt><dd class="col-7"><?= nl2br(htmlspecialchars($request['message'] ?? '—')) ?></dd>
        </dl>
      </div>
    </div>

    <?php if (($request['status'] ?? '') === 'new'): ?>
      <div class="d-flex gap-2">
        <form method="POST" action="/super-admin/demo-requests/<?= (int) $request['id'] ?>/contacted">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-outline-info">Mark Contacted</button>
        </form>
        <form method="POST" action="/super-admin/demo-requests/<?= (int) $request['id'] ?>/decline"
              onsubmit="return confirm('Decline this request?');">
          <?= csrf_field() ?>
          <button type="submit" class="btn btn-sm btn-outline-danger">Decline</button>
        </form>
      </div>
    <?php endif; ?>
  </div>

  <div class="col-lg-7">
    <?php if (($request['status'] ?? '') === 'approved'): ?>
      <div class="alert alert-success">
        <i class="bi bi-check-circle me-1"></i>This request has already been provisioned as a tenant.
        <a href="/super-admin/tenants" class="alert-link">View tenants</a>.
      </div>
    <?php else: ?>
      <div class="card border-0 shadow-sm">
        <div class="card-header bg-white fw-semibold small"><i class="bi bi-arrow-right-circle me-1"></i>Approve &amp; Provision</div>
        <div class="card-body">
          <p class="small text-muted">
            Creates the tenant, first school, and admin login in one step — prefilled from this submission.
            Adjust anything below before confirming.
          </p>
          <form method="POST" action="/super-admin/demo-requests/<?= (int) $request['id'] ?>/approve">
            <?= csrf_field() ?>
            <div class="row g-2">
              <div class="col-md-6">
                <label class="form-label small mb-1">Tenant Name</label>
                <input type="text" name="tenant_name" class="form-control form-control-sm" value="<?= htmlspecialchars($request['school_name'] ?? '') ?>">
              </div>
              <div class="col-md-6">
                <label class="form-label small mb-1">Plan</label>
                <select name="plan" class="form-select form-select-sm">
                  <option value="free">Free</option>
                  <option value="basic">Basic</option>
                  <option value="enterprise">Enterprise</option>
                </select>
              </div>
              <div class="col-md-8">
                <label class="form-label small mb-1">School Name</label>
                <input type="text" name="school_name" class="form-control form-control-sm" value="<?= htmlspecialchars($request['school_name'] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">School Code</label>
                <input type="text" name="school_code" class="form-control form-control-sm text-uppercase" value="<?= htmlspecialchars($suggestedCode) ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Admin First Name</label>
                <input type="text" name="admin_first_name" class="form-control form-control-sm" value="<?= htmlspecialchars($nameParts[0] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Admin Last Name</label>
                <input type="text" name="admin_last_name" class="form-control form-control-sm" value="<?= htmlspecialchars($nameParts[1] ?? '') ?>">
              </div>
              <div class="col-md-4">
                <label class="form-label small mb-1">Admin Email</label>
                <input type="email" name="admin_email" class="form-control form-control-sm" value="<?= htmlspecialchars($request['email'] ?? '') ?>">
              </div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm w-100 mt-3">
              <i class="bi bi-check-lg me-1"></i>Approve &amp; Provision Tenant
            </button>
          </form>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
