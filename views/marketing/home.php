<section class="hero py-5">
  <div class="container py-5">
    <div class="row align-items-center">
      <div class="col-lg-7">
        <h1 class="display-5 fw-bold mb-3">School management, without the spreadsheets.</h1>
        <p class="fs-5" style="color:rgba(255,255,255,.85);">
          Students, fees, exams, attendance, and reports — one platform built for Kenyan schools,
          with M-Pesa built in.
        </p>
        <div class="d-flex gap-2 mt-4">
          <a href="/demo" class="btn btn-accent btn-lg">Request a Demo</a>
          <a href="/pricing" class="btn btn-outline-light btn-lg">See Pricing</a>
        </div>
      </div>
    </div>
  </div>
</section>

<section id="features" class="py-5">
  <div class="container py-4">
    <h2 class="text-center fw-bold mb-5">Everything your school office needs</h2>
    <div class="row g-4">
      <?php foreach ([
        ['bi-people', 'Student Records', 'Admissions, class assignment, parent linking, and profiles in one place.'],
        ['bi-cash-coin', 'Fee Management', 'Invoices, M-Pesa payments, and balances tracked automatically.'],
        ['bi-clipboard-check', 'Exams & Results', 'Marksheets, grade scales, and printable report cards.'],
        ['bi-calendar-check', 'Attendance', 'Daily class attendance with summary reporting.'],
        ['bi-person-badge', 'Staff Management', 'Teacher records, subject assignments, and class teachers.'],
        ['bi-shield-check', 'Role-Based Access', 'Admins, teachers, and parents each see exactly what they need.'],
      ] as [$icon, $featureTitle, $featureDesc]): ?>
      <div class="col-md-4">
        <div class="p-4 h-100 border rounded-3">
          <div class="fs-2 text-primary mb-2"><i class="bi <?= $icon ?>"></i></div>
          <h5 class="fw-bold"><?= htmlspecialchars($featureTitle) ?></h5>
          <p class="text-muted small mb-0"><?= htmlspecialchars($featureDesc) ?></p>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="py-5 bg-light">
  <div class="container py-4 text-center">
    <h2 class="fw-bold mb-3">Ready to see it in action?</h2>
    <p class="text-muted mb-4">Book a short walkthrough with our team — no commitment required.</p>
    <a href="/demo" class="btn btn-accent btn-lg">Request a Demo</a>
  </div>
</section>