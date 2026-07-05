<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Find Your School — <?= htmlspecialchars($appName ?? 'SchoolMS') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="/assets/css/app.css">
  <link rel="stylesheet" href="/assets/css/auth.css">
</head>
<body>
<div class="container">
  <div class="row justify-content-center">
    <div class="col-sm-10 col-md-6 col-lg-4">
      <div class="login-card card p-4 mt-4 mb-4">
        <div class="text-center mb-4">
          <div class="brand-logo mx-auto mb-3">
            <i class="bi bi-mortarboard-fill text-white fs-4"></i>
          </div>
          <h4 class="fw-bold mb-1"><?= htmlspecialchars($appName ?? 'SchoolMS') ?></h4>
          <p class="text-muted small mb-0">Find your school to sign in</p>
        </div>

        <?php if ($flash = \Core\Session::flash('error')): ?>
          <div class="alert alert-danger py-2 small"><i class="bi bi-exclamation-circle me-1"></i><?= $flash /* pre-escaped, may include a link */ ?></div>
        <?php endif; ?>

        <form method="POST" action="/login/find-school">
          <?= csrf_field() ?>
          <div class="mb-4">
            <label class="form-label small fw-semibold">School Name or Code</label>
            <input type="text" name="school" class="form-control" placeholder="e.g. Highway School or HGS"
                   value="<?= htmlspecialchars($_POST['school'] ?? '') ?>" required autofocus>
            <small class="text-muted">We'll take you to your school's own sign-in page.</small>
          </div>
          <button type="submit" class="btn btn-primary w-100 fw-semibold">
            <i class="bi bi-search me-2"></i>Continue
          </button>
        </form>

        <div class="text-center mt-3">
          <small class="text-muted">
            New to SchoolMS? <a href="/demo">Request a demo</a>
          </small>
        </div>
      </div>
    </div>
  </div>
</div>
</body>
</html>
