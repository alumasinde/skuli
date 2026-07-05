<?php
declare(strict_types=1);

/** @var array $parent @var array $children @var array $errors */
$parent   ??= [];
$children ??= [];
$errors   ??= [];
?>

<h5 class="fw-bold mb-3"><i class="bi bi-person-circle me-2 text-primary"></i>My Profile</h5>

<?php if (!empty($errors)): ?>
  <div class="alert alert-danger"><ul class="mb-0">
    <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
  </ul></div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small">Contact Details</div>
      <div class="card-body">
        <div class="mb-3">
          <div class="fw-bold"><?= htmlspecialchars(trim(($parent['first_name'] ?? '') . ' ' . ($parent['last_name'] ?? ''))) ?></div>
          <div class="text-muted small"><?= htmlspecialchars($parent['email'] ?? '') ?></div>
        </div>

        <form method="POST" action="/profile">
          <?= csrf_field() ?>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Phone</label>
            <input type="text" name="phone" class="form-control form-control-sm" value="<?= htmlspecialchars($parent['phone'] ?? '') ?>">
          </div>
          <div class="mb-2">
            <label class="form-label small fw-semibold">Occupation</label>
            <input type="text" name="occupation" class="form-control form-control-sm" value="<?= htmlspecialchars($parent['occupation'] ?? '') ?>">
          </div>
          <div class="mb-3">
            <label class="form-label small fw-semibold">Address</label>
            <textarea name="address" class="form-control form-control-sm" rows="2"><?= htmlspecialchars($parent['address'] ?? '') ?></textarea>
          </div>
          <button type="submit" class="btn btn-primary btn-sm w-100">Save Changes</button>
        </form>
      </div>
    </div>
  </div>

  <div class="col-lg-8">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white fw-semibold small">
        <i class="bi bi-people me-1"></i>My Children
        <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1"><?= count($children) ?></span>
      </div>

      <?php if (empty($children)): ?>
        <div class="card-body text-center text-muted py-5">
          No children are linked to your account yet. Contact the school office if this seems wrong.
        </div>
      <?php else: ?>
        <div class="list-group list-group-flush">
          <?php foreach ($children as $child):
            $childName = trim(($child['first_name'] ?? '') . ' ' . ($child['last_name'] ?? ''));
            $initials  = strtoupper(mb_substr($child['first_name'] ?? '', 0, 1) . mb_substr($child['last_name'] ?? '', 0, 1));
            $childId   = (int) ($child['id'] ?? 0);
          ?>
            <div class="list-group-item">
              <div class="d-flex align-items-center gap-3">
                <?php if (!empty($child['photo_url'])): ?>
                  <img src="<?= htmlspecialchars($child['photo_url']) ?>" alt=""
                       style="width:44px;height:44px;object-fit:cover;border-radius:50%;">
                <?php else: ?>
                  <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center fw-bold"
                       style="width:44px;height:44px;flex-shrink:0;">
                    <?= htmlspecialchars($initials ?: '?') ?>
                  </div>
                <?php endif; ?>

                <div class="flex-grow-1">
                  <div class="fw-semibold small"><?= htmlspecialchars($childName) ?></div>
                  <div class="text-muted" style="font-size:.75rem;">
                    <?= htmlspecialchars($child['admission_no'] ?? '') ?>
                    <?php if (!empty($child['class_name'])): ?> &middot; <?= htmlspecialchars($child['class_name']) ?><?php endif; ?>
                    <?php if (!empty($child['relationship'])): ?>
                      <span class="badge bg-light text-dark border ms-1"><?= htmlspecialchars(ucfirst($child['relationship'])) ?></span>
                    <?php endif; ?>
                  </div>
                </div>

                <div class="d-flex gap-1 flex-wrap justify-content-end">
                  <a href="/finance/statement/<?= $childId ?>" class="btn btn-sm btn-outline-success">
                    <i class="bi bi-cash-coin me-1"></i>Fees
                  </a>
                  <a href="/reports/report-card/<?= $childId ?>" class="btn btn-sm btn-outline-info">
                    <i class="bi bi-file-earmark-text me-1"></i>Report Card
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
