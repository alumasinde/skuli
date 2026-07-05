<?php
declare(strict_types=1);

/** @var array $plans */
$plans ??= [];
?>

<div class="container py-5">
  <div class="text-center mb-5">
    <h1 class="fw-bold">Simple, transparent pricing</h1>
    <p class="text-muted">Pick a plan that fits your school. Upgrade anytime.</p>
  </div>

  <div class="row g-4 justify-content-center">
    <?php foreach ($plans as $i => $p):
      $features = is_array($p['features'] ?? null) ? $p['features'] : json_decode((string) ($p['features'] ?? '{}'), true) ?? [];
      $isPopular = ($p['code'] ?? '') === 'basic';
    ?>
    <div class="col-md-4">
      <div class="card h-100 <?= $isPopular ? 'border-primary shadow' : 'border' ?>">
        <?php if ($isPopular): ?>
          <div class="text-center bg-primary text-white small fw-semibold py-1">MOST POPULAR</div>
        <?php endif; ?>
        <div class="card-body p-4">
          <h5 class="fw-bold"><?= htmlspecialchars($p['name'] ?? '') ?></h5>
          <div class="my-3">
            <span class="display-6 fw-bold"><?= htmlspecialchars($p['currency'] ?? 'KES') ?> <?= number_format((float) ($p['price_monthly'] ?? 0), 0) ?></span>
            <span class="text-muted">/month</span>
          </div>
          <p class="text-muted small">
            or <?= htmlspecialchars($p['currency'] ?? 'KES') ?> <?= number_format((float) ($p['price_yearly'] ?? 0), 0) ?>/year
          </p>
          <ul class="list-unstyled small mt-4">
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>
              <?= $p['max_students'] ? 'Up to ' . (int) $p['max_students'] . ' students' : 'Unlimited students' ?>
            </li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>
              <?= $p['max_staff'] ? 'Up to ' . (int) $p['max_staff'] . ' staff' : 'Unlimited staff' ?>
            </li>
            <li class="mb-2">
              <i class="bi <?= !empty($features['sms']) ? 'bi-check-circle-fill text-success' : 'bi-dash-circle text-muted' ?> me-2"></i>
              SMS notifications
            </li>
            <li class="mb-2">
              <i class="bi <?= !empty($features['api_access']) ? 'bi-check-circle-fill text-success' : 'bi-dash-circle text-muted' ?> me-2"></i>
              API access
            </li>
            <li class="mb-2"><i class="bi bi-check-circle-fill text-success me-2"></i>
              <?= htmlspecialchars(ucfirst($features['support'] ?? 'Email')) ?> support
            </li>
          </ul>
          <a href="/demo?plan=<?= urlencode($p['code'] ?? '') ?>" class="btn <?= $isPopular ? 'btn-accent' : 'btn-outline-primary' ?> w-100 mt-3">
            Request a Demo
          </a>
        </div>
      </div>
    </div>
    <?php endforeach; ?>
  </div>

  <p class="text-center text-muted small mt-5">
    All plans are set up manually after a short demo call — no self-service signup yet.
    <a href="/demo">Get started</a>.
  </p>
</div>
