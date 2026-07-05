<?php
/** @var int $totalStudents */
/** @var array $recentNotices */
/** @var array|null $currentTerm */
?>
<?php if ($currentTerm): ?>
<div class="alert d-flex align-items-center gap-2 py-2 mb-4"
     style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:8px;">
  <i class="bi bi-calendar3"></i>
  <strong>Current Term:</strong>&nbsp;<?= htmlspecialchars($currentTerm['name']) ?>
  &nbsp;·&nbsp;
  <?= \Core\Session::formatDate($currentTerm['start_date']) ?> → <?= \Core\Session::formatDate($currentTerm['end_date']) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><div class="stat-num"><?= number_format($totalStudents) ?></div><div class="stat-label">Total Students</div></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <div class="stat-card" style="background:linear-gradient(135deg,#10b981,#059669)">
      <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
      <div><div class="stat-num">Active</div><div class="stat-label">Term Status</div></div>
    </div>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a href="/exams" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
      <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">Exams</div></div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a href="/finance" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
      <div class="stat-icon"><i class="bi bi-cash-coin"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">Finance</div></div>
    </a>
  </div>
</div>

<div class="row g-3">
  <div class="col-lg-7">
    <div class="card">
      <div class="card-header d-flex justify-content-between align-items-center">
        <span class="fw-semibold small"><i class="bi bi-megaphone me-1 text-primary"></i>Recent Notices</span>
        <a href="/notices" class="btn btn-outline-secondary btn-sm">View All</a>
      </div>
      <div class="list-group list-group-flush">
        <?php if (empty($recentNotices)): ?>
          <div class="list-group-item text-muted small py-3">No notices yet.</div>
        <?php else: foreach ($recentNotices as $n): ?>
          <a href="/notices/<?= $n['id'] ?>" class="list-group-item list-group-item-action py-3">
            <div class="d-flex justify-content-between align-items-start">
              <div>
                <div class="fw-semibold small"><?= htmlspecialchars($n['title']) ?></div>
                <div class="text-muted" style="font-size:.75rem">
                  <?= htmlspecialchars(mb_substr($n['body'] ?? '', 0, 80)) ?>...
                </div>
              </div>
              <span class="badge bg-secondary-subtle text-secondary ms-2 flex-shrink-0">
                <?= htmlspecialchars($n['audience'] ?? 'all') ?>
              </span>
            </div>
          </a>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>

  <div class="col-lg-5">
    <div class="card">
      <div class="card-header"><span class="fw-semibold small"><i class="bi bi-lightning me-1 text-warning"></i>Quick Actions</span></div>
      <div class="card-body">
        <div class="d-grid gap-2">
          <a href="/students/create" class="btn btn-outline-primary btn-sm"><i class="bi bi-person-plus me-2"></i>Enrol New Student</a>
          <a href="/exams"           class="btn btn-outline-warning btn-sm"><i class="bi bi-pencil-square me-2"></i>Manage Exams</a>
          <a href="/attendance"      class="btn btn-outline-success btn-sm"><i class="bi bi-calendar-check me-2"></i>Mark Attendance</a>
          <a href="/finance"         class="btn btn-outline-info btn-sm"><i class="bi bi-receipt me-2"></i>Fee Management</a>
          <a href="/notices/create"  class="btn btn-outline-secondary btn-sm"><i class="bi bi-megaphone me-2"></i>Post Notice</a>
          <a href="/reports/class-results" class="btn btn-outline-dark btn-sm"><i class="bi bi-bar-chart me-2"></i>View Reports</a>
        </div>
      </div>
    </div>
  </div>
</div>
