<?php /** @var array $recentNotices @var array|null $currentTerm */ ?>
<?php if ($currentTerm): ?>
<div class="alert d-flex align-items-center gap-2 py-2 mb-4"
     style="background:#eff6ff;border:1px solid #bfdbfe;color:#1d4ed8;border-radius:8px;">
  <i class="bi bi-calendar3"></i>
  <strong>Current Term:</strong>&nbsp;<?= htmlspecialchars($currentTerm['name']) ?>
  &nbsp;·&nbsp;<?= \Core\Session::formatDate($currentTerm['start_date']) ?> → <?= \Core\Session::formatDate($currentTerm['end_date']) ?>
</div>
<?php endif; ?>

<div class="row g-3 mb-4">
  <div class="col-sm-6 col-lg-3">
    <a href="/students" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#3b82f6,#2563eb)">
      <div class="stat-icon"><i class="bi bi-people"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">My Students</div></div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a href="/attendance" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#10b981,#059669)">
      <div class="stat-icon"><i class="bi bi-calendar-check"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">Attendance</div></div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a href="/exams" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#f59e0b,#d97706)">
      <div class="stat-icon"><i class="bi bi-pencil-square"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">Exams & Marks</div></div>
    </a>
  </div>
  <div class="col-sm-6 col-lg-3">
    <a href="/reports/class-results" class="stat-card text-decoration-none" style="background:linear-gradient(135deg,#6366f1,#4f46e5)">
      <div class="stat-icon"><i class="bi bi-bar-chart-line"></i></div>
      <div><div class="stat-num"><i class="bi bi-arrow-right-circle"></i></div><div class="stat-label">Reports</div></div>
    </a>
  </div>
</div>

<div class="card">
  <div class="card-header d-flex justify-content-between align-items-center">
    <span class="fw-semibold small"><i class="bi bi-megaphone me-1 text-primary"></i>Notices</span>
    <a href="/notices" class="btn btn-outline-secondary btn-sm">View All</a>
  </div>
  <div class="list-group list-group-flush">
    <?php foreach (array_slice($recentNotices,0,5) as $n): ?>
    <a href="/notices/<?= $n['id'] ?>" class="list-group-item list-group-item-action">
      <div class="fw-semibold small"><?= htmlspecialchars($n['title']) ?></div>
      <div class="text-muted" style="font-size:.75rem"><?= htmlspecialchars(mb_substr($n['body']??'',0,80)) ?>...</div>
    </a>
    <?php endforeach; ?>
  </div>
</div>
