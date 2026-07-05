<?php
declare(strict_types=1);

/** @var array $errors @var array $old */
$errors ??= [];
$old    ??= [];
$v = static fn (string $k, $d = '') => htmlspecialchars((string) ($old[$k] ?? $d));
?>

<div class="container py-5" style="max-width:640px;">
  <div class="text-center mb-4">
    <h1 class="fw-bold">Request a Demo</h1>
    <p class="text-muted">Tell us about your school and we'll set up a short walkthrough.</p>
  </div>

  <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0">
      <?php foreach ($errors as $e): ?><li><?= htmlspecialchars($e) ?></li><?php endforeach; ?>
    </ul></div>
  <?php endif; ?>

  <div class="card border-0 shadow-sm">
    <div class="card-body p-4">
      <form method="POST" action="/demo">
        <?= csrf_field() ?>
        <input type="hidden" name="source" value="demo_page">

        <!-- Honeypot — hidden from real users via CSS, bots tend to fill every field -->
        <div style="position:absolute;left:-9999px;" aria-hidden="true">
          <label>Website</label>
          <input type="text" name="website" tabindex="-1" autocomplete="off">
        </div>

        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">School Name <span class="text-danger">*</span></label>
            <input type="text" name="school_name" class="form-control" required value="<?= $v('school_name') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Your Name <span class="text-danger">*</span></label>
            <input type="text" name="contact_name" class="form-control" required value="<?= $v('contact_name') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control" required value="<?= $v('email') ?>">
          </div>
          <div class="col-md-6">
            <label class="form-label">Phone</label>
            <input type="text" name="phone" class="form-control" placeholder="07XX XXX XXX" value="<?= $v('phone') ?>">
          </div>
          <div class="col-12">
            <label class="form-label">Approximate Number of Students</label>
            <select name="student_count_range" class="form-select">
              <option value="">Select a range…</option>
              <?php foreach (['1-100', '100-500', '500-1000', '1000+'] as $range): ?>
                <option value="<?= $range ?>" <?= ($old['student_count_range'] ?? '') === $range ? 'selected' : '' ?>><?= $range ?> students</option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Anything else we should know?</label>
            <textarea name="message" class="form-control" rows="3" placeholder="Optional"><?= $v('message') ?></textarea>
          </div>
        </div>

        <button type="submit" class="btn btn-accent w-100 mt-4 py-2">Request Demo</button>
        <p class="text-muted small text-center mt-3 mb-0">We'll get back to you within one business day.</p>
      </form>
    </div>
  </div>
</div>
