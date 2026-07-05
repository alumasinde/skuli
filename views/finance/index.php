<?php
declare(strict_types=1);

/** @var array $feeTypes @var array $terms @var array $classes @var array $errors */
$feeTypes ??= [];
$terms    ??= [];
$classes  ??= [];
$errors   ??= [];
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <h5 class="mb-0 fw-bold"><i class="bi bi-cash-coin me-2 text-success"></i>Finance</h5>
    <div class="text-muted small">Fee types, invoicing, and payments</div>
  </div>
  <a href="/finance/fee-types/create" class="btn btn-success btn-sm"><i class="bi bi-plus-lg me-1"></i>Add Fee Type</a>
</div>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-lg-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small"><i class="bi bi-list-ul me-1"></i>Fee Types</div>
      <div class="list-group list-group-flush">
        <?php if (empty($feeTypes)): ?>
          <div class="list-group-item text-muted small py-3">No fee types yet.</div>
        <?php else: foreach ($feeTypes as $f): ?>
          <div class="list-group-item d-flex justify-content-between align-items-center">
            <div>
              <div class="fw-semibold small"><?= htmlspecialchars($f['name']) ?></div>
              <div class="text-muted" style="font-size:.73rem;">
                <?= ucfirst($f['frequency'] ?? '') ?><?php if ($f['is_mandatory'] ?? false): ?> &middot; Mandatory<?php endif; ?>
              </div>
            </div>
            <span class="fw-bold text-success small">KES <?= number_format((float) $f['amount'], 2) ?></span>
          </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white fw-semibold small"><i class="bi bi-receipt me-1"></i>Generate Invoices</div>
      <div class="card-body">
        <form method="POST" action="/finance/invoices/generate">
          <?= csrf_field() ?>
          <div class="row g-2">
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Fee Type</label>
              <select name="fee_type_id" class="form-select form-select-sm" required>
                <option value="">Select…</option>
                <?php foreach ($feeTypes as $f): ?>
                  <option value="<?= (int) $f['id'] ?>"><?= htmlspecialchars($f['name']) ?> — KES <?= number_format((float) $f['amount'], 2) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label small fw-semibold">Term</label>
              <select name="term_id" class="form-select form-select-sm" required>
                <option value="">Select…</option>
                <?php foreach ($terms as $t): ?>
                  <option value="<?= (int) $t['id'] ?>" <?= ($t['is_current'] ?? false) ? 'selected' : '' ?>><?= htmlspecialchars($t['name']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-md-8">
              <label class="form-label small fw-semibold">Classes</label>
              <select name="class_ids[]" class="form-select form-select-sm" multiple required style="min-height:90px;">
                <?php foreach ($classes as $c): ?>
                  <option value="<?= (int) $c['id'] ?>"><?= htmlspecialchars($c['class_name']) ?></option>
                <?php endforeach; ?>
              </select>
              <small class="text-muted">Ctrl/Cmd-click to select multiple.</small>
            </div>
            <div class="col-md-4">
              <label class="form-label small fw-semibold">Due Date</label>
              <input type="date" name="due_date" class="form-control form-control-sm" required>
              <label class="form-label small fw-semibold mt-2 mb-1">Override Amount <span class="text-muted">(optional)</span></label>
              <input type="number" name="amount" step="0.01" min="0" class="form-control form-control-sm" placeholder="Uses fee type amount">
            </div>
            <div class="col-12 pt-2">
              <button type="submit" class="btn btn-success btn-sm"><i class="bi bi-receipt me-1"></i>Generate Invoices</button>
            </div>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-header bg-white fw-semibold small"><i class="bi bi-search me-1"></i>Find a Student's Statement</div>
  <div class="card-body">
    <p class="text-muted small mb-2">Open a student's profile and use the "Fees" button there, or go to
      <a href="/students">Students</a> to search directly.</p>
  </div>
</div>