<?php
declare(strict_types=1);

/**
 * Expected:
 * $subject  the subject row (id, name, code, is_active, created_at)
 * $classes  classes this subject is assigned to (id, name, is_compulsory)
 * $isAdmin  optional bool
 */

$subject ??= [];
$classes ??= [];
$id = (int) ($subject['id'] ?? 0);
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h6 class="text-muted mb-0"><i class="bi bi-journal-bookmark me-1"></i>Subject</h6>
  <div class="d-flex gap-2">
    <?php if ($isAdmin ?? true): ?>
      <a href="/subjects/<?= $id ?>/edit" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-pencil me-1"></i>Edit
      </a>
    <?php endif; ?>
    <a href="/subjects" class="btn btn-sm btn-outline-secondary">
      <i class="bi bi-arrow-left me-1"></i>Back
    </a>
  </div>
</div>

<div class="row g-3">

  <div class="col-lg-4">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-body text-center">
        <div class="rounded mx-auto mb-3 d-flex align-items-center justify-content-center
                    bg-primary-subtle text-primary-emphasis fw-bold"
             style="width:80px;height:80px;font-size:1.5rem;">
          <?= htmlspecialchars(strtoupper(mb_substr($subject['code'] ?? $subject['name'] ?? '?', 0, 3))) ?>
        </div>
        <h5 class="fw-bold mb-1"><?= htmlspecialchars($subject['name'] ?? '') ?></h5>
        <div class="mb-2">
          <span class="badge bg-primary-subtle text-primary-emphasis font-monospace">
            <?= htmlspecialchars($subject['code'] ?? '—') ?>
          </span>
        </div>
        <?php if ($subject['is_active'] ?? true): ?>
          <span class="badge bg-success-subtle text-success-emphasis">Active</span>
        <?php else: ?>
          <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card h-100 border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small d-flex justify-content-between align-items-center">
        <span><i class="bi bi-building me-1"></i>Classes taking this subject</span>
        <span class="badge bg-secondary-subtle text-secondary-emphasis"><?= count($classes) ?></span>
      </div>
      <div class="card-body p-0">
        <table class="table table-sm table-hover align-middle mb-0">
          <thead class="table-light">
            <tr class="small text-muted">
              <th class="ps-3">Class</th>
              <th>Type</th>
              <th class="text-end pe-3"></th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($classes)): ?>
              <tr>
                <td colspan="3" class="text-center text-muted py-4">
                  Not assigned to any class yet.
                  <div class="small mt-1">Assign subjects to a class from the class page.</div>
                </td>
              </tr>
            <?php else: ?>
              <?php foreach ($classes as $c): ?>
                <tr>
                  <td class="ps-3 fw-semibold"><?= htmlspecialchars($c['name'] ?? '') ?></td>
                  <td>
                    <?php if ($c['is_compulsory'] ?? false): ?>
                      <span class="badge bg-info-subtle text-info-emphasis">Compulsory</span>
                    <?php else: ?>
                      <span class="badge bg-light text-dark border">Optional</span>
                    <?php endif; ?>
                  </td>
                  <td class="text-end pe-3">
                    <a href="/classes/<?= (int) ($c['id'] ?? 0) ?>" class="btn btn-sm btn-outline-secondary">
                      <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>