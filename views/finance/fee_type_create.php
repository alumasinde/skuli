<?php
declare(strict_types=1);

/** @var array $errors @var array $old */
$errors ??= [];
$old    ??= [];
$v = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold"><i class="bi bi-plus-lg me-2 text-success"></i>Add Fee Type</h5>
  <a href="/finance" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card border-0 shadow-sm" style="max-width:500px;">
  <div class="card-body">
    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger"><ul class="mb-0">
        <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
      </ul></div>
    <?php endif; ?>

    <form method="POST" action="/finance/fee-types">
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-12">
          <label class="form-label">Fee Type Name <span class="text-danger">*</span></label>
          <input type="text" name="name" class="form-control" required placeholder="Tuition Fee" value="<?= $v('name') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Amount (KES) <span class="text-danger">*</span></label>
          <input type="number" name="amount" class="form-control" required min="0.01" step="0.01" value="<?= $v('amount') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Frequency</label>
          <select name="frequency" class="form-select">
            <?php foreach (['termly' => 'Termly', 'monthly' => 'Monthly', 'annual' => 'Annual', 'once' => 'One-time'] as $val => $lbl): ?>
              <option value="<?= $val ?>" <?= ($old['frequency'] ?? 'termly') === $val ? 'selected' : '' ?>><?= $lbl ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-12">
          <div class="form-check">
            <input type="checkbox" name="is_mandatory" class="form-check-input" id="im" <?= !isset($old['is_mandatory']) || $old['is_mandatory'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="im">Mandatory fee</label>
          </div>
        </div>
      </div>
      <div class="mt-4 d-flex gap-2">
        <button type="submit" class="btn btn-success">Add Fee Type</button>
        <a href="/finance" class="btn btn-outline-secondary">Cancel</a>
      </div>
    </form>
  </div>
</div>