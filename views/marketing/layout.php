<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($title ?? 'SchoolMS') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/marketing.css">
</head>
<body>

<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom">
  <div class="container">
    <a class="navbar-brand" href="/"><i class="bi bi-mortarboard-fill text-primary me-1"></i>SchoolMS</a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="nav">
      <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
        <li class="nav-item"><a class="nav-link" href="/#features">Features</a></li>
        <li class="nav-item"><a class="nav-link" href="/pricing">Pricing</a></li>
        <li class="nav-item"><a class="nav-link" href="/login">Log In</a></li>
        <li class="nav-item"><a class="btn btn-accent btn-sm ms-lg-2" href="/demo">Request a Demo</a></li>
      </ul>
    </div>
  </div>
</nav>

<?= $content ?? '' ?>

<footer class="py-5 mt-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2"><i class="bi bi-mortarboard-fill me-1"></i>SchoolMS</div>
        <p class="small">School management built for Kenyan schools — students, fees, exams, attendance, all in one place.</p>
      </div>
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2">Product</div>
        <ul class="list-unstyled small">
          <li><a href="/pricing">Pricing</a></li>
          <li><a href="/demo">Request a Demo</a></li>
          <li><a href="/login">Log In</a></li>
        </ul>
      </div>
      <div class="col-md-4">
        <div class="fw-bold text-white mb-2">Contact</div>
        <ul class="list-unstyled small">
          <li><i class="bi bi-envelope me-1"></i>hello@schoolms.co.ke</li>
        </ul>
      </div>
    </div>
    <hr class="border-secondary my-4">
    <div class="small text-center">&copy; <?= date('Y') ?> SchoolMS. All rights reserved.</div>
  </div>
</footer>
<script src="/assets/js/nav-toggle.js"></script>
</body>
</html>
