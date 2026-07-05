<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'SchoolMS') ?> — <?= htmlspecialchars(config('app.name','SchoolMS')) ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">
</head>
<body>
<?php
   /** @var string $viewFile Path to the view partial to render */


use Core\Session;
$user  = Session::user();
$roles = Session::roles();
$isAdmin   = Session::hasRole('admin');
$isSuperAdmin = Session::hasRole('superadmin');
$isTeacher = Session::hasRole('teacher');
$isParent  = Session::hasRole('parent');
$firstName = $user['first_name'] ?? 'User';
$lastName  = $user['last_name']  ?? '';
$initial   = strtoupper(substr($firstName, 0, 1));
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/';

function navActive(?string $prefix): string {
    global $currentPath;
    return str_starts_with((string)$currentPath, (string)$prefix) ? 'active' : '';
}
?>

<div class="sidebar">
  <div class="sidebar-brand">
    <a href="/dashboard">
      <div class="logo-icon"><i class="bi bi-mortarboard-fill text-white"></i></div>
      SchoolMS
    </a>
  </div>

  <nav class="mt-2">
    <a href="/dashboard" class="<?= navActive('/dashboard') ?>"><i class="bi bi-grid"></i>Dashboard</a>

    <?php if ($isAdmin || $isTeacher): ?>
    <div class="sidebar-section">Academics</div>
    <a href="/students"        class="<?= navActive('/students') ?>"><i class="bi bi-people"></i>Students</a>
    <?php if ($isAdmin): ?>
    <a href="/teachers"        class="<?= navActive('/teachers') ?>"><i class="bi bi-person-badge"></i>Teachers</a>
    <a href="/classes"         class="<?= navActive('/classes') ?>"><i class="bi bi-building"></i>Classes</a>
    <a href="/subjects"        class="<?= navActive('/subjects') ?>"><i class="bi bi-journal-bookmark"></i>Subjects</a>
    <?php endif; ?>
    <a href="/attendance"      class="<?= navActive('/attendance') ?>"><i class="bi bi-calendar-check"></i>Attendance</a>
    <a href="/exams"           class="<?= navActive('/exams') ?>"><i class="bi bi-pencil-square"></i>Exams</a>

    <div class="sidebar-section">Management</div>
    <?php if ($isAdmin): ?>
    <a href="/finance"         class="<?= navActive('/finance') ?>"><i class="bi bi-cash-coin"></i>Finance</a>
    <a href="/notices"         class="<?= navActive('/notices') ?>"><i class="bi bi-megaphone"></i>Notices</a>
    <a href="/parents"         class="<?= navActive('/parents') ?>"><i class="bi bi-people-fill"></i>Parents</a>
    <a href="/discipline"      class="<?= navActive('/discipline') ?>"><i class="bi bi-shield"></i>Discipline</a>
    <?php else: ?>
    <a href="/notices"         class="<?= navActive('/notices') ?>"><i class="bi bi-megaphone"></i>Notices</a>
    <a href="/discipline"      class="<?= navActive('/discipline') ?>"><i class="bi bi-shield"></i>Discipline</a>
    <?php endif; ?>

    <div class="sidebar-section">Reports</div>
    <a href="/reports/class-results"       class="<?= navActive('/reports/class') ?>"><i class="bi bi-bar-chart-line"></i>Class Results</a>
    <a href="/reports/subject-performance" class="<?= navActive('/reports/subject') ?>"><i class="bi bi-graph-up"></i>Subject Analysis</a>
    <a href="/reports/attendance-summary"  class="<?= navActive('/reports/attendance') ?>"><i class="bi bi-pie-chart"></i>Attendance</a>
    <?php if ($isAdmin): ?>
    <a href="/reports/fee-collection"      class="<?= navActive('/reports/fee') ?>"><i class="bi bi-receipt"></i>Fee Collection</a>
    <?php endif; ?>

    <?php if ($isAdmin): ?>
    <div class="sidebar-section">Admin</div>
    <a href="/academic-years"  class="<?= navActive('/academic-years') ?>"><i class="bi bi-calendar3"></i>Academic Years</a>
    <a href="/terms"           class="<?= navActive('/terms') ?>"><i class="bi bi-calendar-range"></i>Terms</a>
    <a href="/users"           class="<?= navActive('/users') ?>"><i class="bi bi-person-gear"></i>Users</a>
    <a href="/settings"        class="<?= navActive('/settings') ?>"><i class="bi bi-gear"></i>Settings</a>
    <?php endif; ?>
    <?php endif; ?>

    <?php if ($isSuperAdmin): ?>
<div class="sidebar-section">Platform</div>
<a href="/super-admin/tenants"       class="<?= navActive('/super-admin/tenants') ?>"><i class="bi bi-buildings"></i>Tenants</a>
<a href="/super-admin/demo-requests" class="<?= navActive('/super-admin/demo-requests') ?>"><i class="bi bi-inbox"></i>Demo Requests</a>
<a href="/super-admin/billing"       class="<?= navActive('/super-admin/billing') ?>"><i class="bi bi-graph-up-arrow"></i>Billing</a>
<?php endif; ?>

    <?php if ($isParent): ?>
    <div class="sidebar-section">My Family</div>
    <a href="/dashboard"       class="<?= navActive('/dashboard') ?>"><i class="bi bi-house"></i>Dashboard</a>
    <a href="/notices"         class="<?= navActive('/notices') ?>"><i class="bi bi-megaphone"></i>Notices</a>
    <a href="/profile"         class="<?= navActive('/profile') ?>"><i class="bi bi-person-circle"></i>My Profile</a>
    <?php endif; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="avatar"><?= $initial ?></div>
    <div class="user-info">
      <div class="user-name"><?= htmlspecialchars($firstName . ' ' . $lastName) ?></div>
      <div class="user-role"><?= ucfirst($roles[0] ?? '') ?></div>
    </div>
    <a href="/logout" class="ms-auto text-white opacity-50" title="Sign out"><i class="bi bi-box-arrow-right"></i></a>
  </div>
</div>

<div class="main-wrap">
  <div class="topbar">
    <div class="topbar-title"><?= htmlspecialchars($title ?? 'SchoolMS') ?></div>
    <div class="d-flex align-items-center gap-3">
      <span class="text-muted small"><?= date('D, d M Y') ?></span>
    </div>
  </div>
  <div class="page-content">
    <?php if ($flash = Session::flash('success')): ?>
      <div class="flash-success"><i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>
    <?php if ($flash = Session::flash('error')): ?>
      <div class="flash-error"><i class="bi bi-exclamation-circle me-2"></i><?= htmlspecialchars($flash) ?></div>
    <?php endif; ?>

    <?php require $viewFile; ?>
  </div>
</div>
<script src="/assets/js/modal.js"></script>
</body>
</html>
