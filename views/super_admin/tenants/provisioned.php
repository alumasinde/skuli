<?php
declare(strict_types=1);

/** @var array $tenant @var array $result */
$tenant ??= [];
$result ??= [];

// Same host-building logic as WebAuthController::findSchool() — kept
// consistent so the URL shown here is exactly the one that will actually
// resolve via TenantResolver, not a guess.
 $loginUrl = ($tenant['domain'] ?? null)
            ? \Core\UrlBuilder::tenantLoginUrl($tenant['domain'])
            : null;
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <h5 class="mb-0 fw-bold text-success"><i class="bi bi-check-circle me-2"></i>Tenant Provisioned</h5>
  <a href="/super-admin/tenants" class="btn btn-sm btn-outline-secondary">Back to Tenants</a>
</div>

<div class="alert alert-warning">
  <i class="bi bi-shield-exclamation me-1"></i>
  <strong>The password and URL below are shown once and cannot be retrieved again.</strong>
  Copy both now and share them with the customer through a secure channel — not email in plain text.
</div>

<div class="card border-0 shadow-sm" style="max-width:560px;">
  <div class="card-body">
    <dl class="row mb-0">
      <dt class="col-5 text-muted">Tenant</dt>
      <dd class="col-7 fw-semibold"><?= htmlspecialchars($tenant['name'] ?? '') ?></dd>

      <dt class="col-5 text-muted">Login URL</dt>
      <dd class="col-7">
        <?php if ($loginUrl): ?>
          <code class="bg-light px-2 py-1 rounded d-inline-block" id="loginUrl"><?= htmlspecialchars($loginUrl) ?></code>
          <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                  onclick="navigator.clipboard.writeText(document.getElementById('loginUrl').textContent)">
            <i class="bi bi-clipboard"></i>
          </button>
          <div class="small text-muted mt-1">This is the URL to bookmark — send it to the school directly.</div>
        <?php else: ?>
          <span class="text-danger small">No domain set for this tenant — it has no reachable login URL yet.</span>
        <?php endif; ?>
      </dd>

      <dt class="col-5 text-muted">Login Email</dt>
      <dd class="col-7"><code><?= htmlspecialchars($_GET['email'] ?? '') ?></code></dd>

      <dt class="col-5 text-muted">Temporary Password</dt>
      <dd class="col-7">
        <code class="fs-5 bg-light px-2 py-1 rounded" id="tempPw"><?= htmlspecialchars($result['temp_password'] ?? '') ?></code>
        <button type="button" class="btn btn-sm btn-outline-secondary ms-1"
                onclick="navigator.clipboard.writeText(document.getElementById('tempPw').textContent)">
          <i class="bi bi-clipboard"></i>
        </button>
      </dd>
    </dl>
  </div>
</div>

<div class="mt-3">
  <a href="/super-admin/tenants" class="btn btn-primary btn-sm">Done</a>
</div>