<?php
declare(strict_types=1);

/** @var array $errors @var array $old */
$errors ??= [];
$old    ??= [];
$v = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-buildings-fill me-2 text-primary"></i>Provision New Tenant</h5>
  <a href="/super-admin/tenants" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="alert alert-light border small">
  <i class="bi bi-info-circle me-1"></i>
  Use this after a demo has been approved. It creates the tenant account, their first school, and an
  admin login in one step. A temporary password is generated and shown once — share it securely.
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<form method="POST" action="/super-admin/tenants">
  <?= csrf_field() ?>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold small">Tenant / Account</div>
    <div class="card-body row g-3">
      <div class="col-md-6">
        <label class="form-label">Tenant Name <span class="text-danger">*</span></label>
        <input type="text" name="tenant_name" class="form-control" required
               placeholder="e.g. Highway Group of Schools" value="<?= $v('tenant_name') ?>">
      </div>
      <div class="col-md-3">
        <label class="form-label">Plan</label>
        <select name="plan" class="form-select">
          <?php foreach (['free' => 'Free', 'basic' => 'Basic', 'enterprise' => 'Enterprise'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($old['plan'] ?? 'free') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-3">
        <label class="form-label">Custom Domain <span class="text-muted small">(optional)</span></label>
        <input type="text" name="domain" class="form-control" placeholder="highway.sms.co.ke" value="<?= $v('domain') ?>">
      </div>
      <div class="col-12">
        <label class="form-label">Internal Notes <span class="text-muted small">(optional)</span></label>
        <textarea name="notes" class="form-control" rows="2" placeholder="Demo notes, contact person, deal terms…"><?= $v('notes') ?></textarea>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold small">First School</div>
    <div class="card-body row g-3">
      <div class="col-md-8">
        <label class="form-label">School Name <span class="text-danger">*</span></label>
        <input type="text" name="school_name" class="form-control" required value="<?= $v('school_name') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">School Code <span class="text-danger">*</span></label>
        <input type="text" name="school_code" class="form-control text-uppercase" required
               placeholder="e.g. HGS" value="<?= $v('school_code') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">School Email</label>
        <input type="email" name="school_email" class="form-control" value="<?= $v('school_email') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">School Phone</label>
        <input type="text" name="school_phone" class="form-control" value="<?= $v('school_phone') ?>">
      </div>
      <div class="col-md-2">
        <label class="form-label">Type</label>
        <select name="school_type" class="form-select">
          <?php foreach (['day' => 'Day', 'boarding' => 'Boarding', 'mixed' => 'Mixed'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($old['school_type'] ?? 'day') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-md-2">
        <label class="form-label">Level</label>
        <select name="school_level" class="form-select">
          <?php foreach (['primary' => 'Primary', 'secondary' => 'Secondary', 'both' => 'Both'] as $val => $lbl): ?>
            <option value="<?= $val ?>" <?= ($old['school_level'] ?? 'secondary') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
  </div>

  <div class="card border-0 shadow-sm mb-3">
    <div class="card-header bg-white fw-semibold small">First Admin User</div>
    <div class="card-body row g-3">
      <div class="col-md-4">
        <label class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" name="admin_first_name" class="form-control" required value="<?= $v('admin_first_name') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" name="admin_last_name" class="form-control" required value="<?= $v('admin_last_name') ?>">
      </div>
      <div class="col-md-4">
        <label class="form-label">Email <span class="text-danger">*</span></label>
        <input type="email" name="admin_email" class="form-control" required value="<?= $v('admin_email') ?>">
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i>Provision Tenant</button>
  <a href="/super-admin/tenants" class="btn btn-outline-secondary">Cancel</a>
</form>
