<?php
/** @var array $children @var array $feeSummary @var array $recentNotices @var array|null $currentTerm */
?>
<?php if ($currentTerm): ?>
<div class="alert d-flex align-items-center gap-2 py-2 mb-4"
     style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:8px;">
  <i class="bi bi-calendar3"></i>
  <strong>Current Term:</strong>&nbsp;<?= htmlspecialchars($currentTerm['name']) ?>
</div>
<?php endif; ?>

<h5 class="fw-bold mb-3">My Children</h5>
<?php if (empty($children)): ?>
  <div class="alert alert-info">No children linked to your account. Contact the school office.</div>
<?php else: ?>
<div class="row g-3 mb-4">
  <?php foreach ($children as $child):
    $stmt = $feeSummary[$child['id']] ?? [];
    $balance = $stmt['balance'] ?? 0;
  ?>
  <div class="col-md-6 col-lg-4">
    <div class="card h-100">
      <div class="card-body">
        <div class="d-flex align-items-center gap-3 mb-3">
          <div style="width:44px;height:44px;border-radius:50%;background:#3b82f6;display:flex;align-items:center;
               justify-content:center;color:#fff;font-weight:700;font-size:1.1rem;flex-shrink:0;">
            <?= strtoupper(substr($child['first_name']??'S',0,1)) ?>
          </div>
          <div>
            <div class="fw-bold"><?= htmlspecialchars($child['first_name'].' '.$child['last_name']) ?></div>
            <div class="text-muted small"><?= htmlspecialchars($child['admission_no']??'') ?></div>
          </div>
        </div>
        <?php if (!empty($stmt)): ?>
        <div class="mb-3 p-2 rounded" style="background:#f8fafc">
          <div class="d-flex justify-content-between small">
            <span class="text-muted">Fee Balance</span>
            <span class="fw-bold <?= $balance > 0 ? 'text-danger' : 'text-success' ?>">
              KES <?= number_format($balance,2) ?>
            </span>
          </div>
        </div>
        <?php endif; ?>
        <div class="d-flex gap-2 flex-wrap">
          <a href="/parent/children/<?= $child['id'] ?>/results"    class="btn btn-warning btn-sm"><i class="bi bi-clipboard-data me-1"></i>Results</a>
          <a href="/parent/children/<?= $child['id'] ?>/fees"       class="btn btn-success btn-sm"><i class="bi bi-cash me-1"></i>Fees</a>
          <a href="/parent/children/<?= $child['id'] ?>/attendance" class="btn btn-info btn-sm text-white"><i class="bi bi-calendar-check me-1"></i>Attendance</a>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold small"><i class="bi bi-megaphone me-1 text-primary"></i>Recent Notices</span>
    <a href="/notices" class="btn btn-outline-secondary btn-sm">View All</a>
  </div>
  <div class="list-group list-group-flush">
    <?php if (empty($recentNotices)): ?>
      <div class="list-group-item text-muted small py-3">No notices.</div>
    <?php else: foreach (array_slice($recentNotices,0,5) as $n): ?>
      <a href="/notices/<?= $n['id'] ?>" class="list-group-item list-group-item-action">
        <div class="fw-semibold small"><?= htmlspecialchars($n['title']) ?></div>
        <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars(mb_substr($n['body']??'',0,80)) ?>...</div>
      </a>
    <?php endforeach; endif; ?>
  </div>
</div>
