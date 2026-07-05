<?php
declare(strict_types=1);

/**
 * Expected:
 * $title, $student (array), $school (array|null)
 * Rendered standalone — NOT wrapped in views/layout.php. See
 * StudentWebController::idCard() for why.
 */

$student ??= [];
$school  ??= [];

$fullName = trim(($student['first_name'] ?? '') . ' ' . ($student['last_name'] ?? ''));
$photo    = (string) ($student['photo_url'] ?? '');
$initials = strtoupper(mb_substr($student['first_name'] ?? '', 0, 1) . mb_substr($student['last_name'] ?? '', 0, 1));

$dob = '—';
if (!empty($student['dob'])) {
    try { $dob = (new DateTimeImmutable($student['dob']))->format('d/m/Y'); } catch (Throwable) {}
}

// Simple validity window: issued today, expires end of the current
// calendar year — adjust if you track academic-year end dates elsewhere.
$issued  = date('Y');
$expires = date('d/m/Y', strtotime(($issued) . '-12-31'));

$schoolName = $school['name']    ?? 'School Name';
$schoolAddr = $school['address'] ?? '';
$schoolPhone = $school['phone']  ?? '';
$schoolLogo = $school['logo_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($title ?? 'ID Card') ?></title>
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <style>
    /* CR80 card size — the real physical dimensions of a standard ID card
       (85.6mm x 54mm), so what prints is an actual card, not a shrunk page. */
    :root { --card-w: 85.6mm; --card-h: 54mm; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #eef2f7;
      margin: 0;
      padding: 24px;
      display: flex;
      flex-wrap: wrap;
      gap: 24px;
      justify-content: center;
    }

    .toolbar {
      width: 100%;
      display: flex;
      justify-content: center;
      gap: 8px;
      margin-bottom: 8px;
    }
    .toolbar a, .toolbar button {
      font-family: inherit; font-size: .85rem; padding: .5rem 1rem;
      border-radius: 6px; border: 1px solid #cbd5e1; background: #fff; cursor: pointer;
      text-decoration: none; color: #1e293b;
    }
    .toolbar .btn-primary { background: #3b82f6; color: #fff; border-color: #3b82f6; }

    .card {
      width: var(--card-w);
      height: var(--card-h);
      background: #fff;
      border-radius: 3mm;
      box-shadow: 0 4px 16px rgba(0,0,0,.15);
      overflow: hidden;
      position: relative;
      font-size: 2.6mm;
      display: flex;
      flex-direction: column;
    }

    /* ── Front ── */
    .card-front .band {
      background: linear-gradient(135deg, #0f172a, #1e3a8a);
      color: #fff;
      padding: 2.5mm 3mm;
      display: flex;
      align-items: center;
      gap: 2mm;
    }
    .card-front .band img.logo { width: 6mm; height: 6mm; border-radius: 1mm; object-fit: cover; background: #fff; }
    .card-front .band .school-name { font-weight: 700; font-size: 3mm; line-height: 1.1; }
    .card-front .band .school-sub { font-size: 2.1mm; opacity: .85; }

    .card-front .body { display: flex; padding: 2.5mm 3mm; gap: 3mm; flex: 1; align-items: center; }
    .card-front .photo {
      width: 16mm; height: 19mm; border-radius: 1mm; object-fit: cover;
      border: .3mm solid #e2e8f0; flex-shrink: 0; background: #f1f5f9;
    }
    .card-front .photo-fallback {
      width: 16mm; height: 19mm; border-radius: 1mm; flex-shrink: 0;
      background: #dbeafe; color: #1e40af; font-weight: 700; font-size: 5mm;
      display: flex; align-items: center; justify-content: center;
    }
    .card-front .details { flex: 1; min-width: 0; }
    .card-front .name { font-weight: 700; font-size: 3.2mm; margin-bottom: .8mm; }
    .card-front .row { display: flex; gap: 1.5mm; margin-bottom: .5mm; }
    .card-front .row .label { color: #64748b; width: 13mm; flex-shrink: 0; }
    .card-front .row .value { font-weight: 600; }

    .card-front .foot {
      border-top: .2mm solid #e2e8f0;
      padding: 1.2mm 3mm;
      display: flex;
      justify-content: space-between;
      font-size: 2mm;
      color: #64748b;
    }

    /* ── Back ── */
    .card-back { padding: 3mm; }
    .card-back .title { font-weight: 700; font-size: 2.8mm; margin-bottom: 1.5mm; color: #1e293b; }
    .card-back p { margin: 0 0 1.5mm; line-height: 1.4; color: #334155; }
    .card-back .signature {
      margin-top: auto;
      border-top: .2mm solid #cbd5e1;
      padding-top: 1mm;
      display: flex;
      justify-content: space-between;
      font-size: 2.1mm;
      color: #64748b;
    }
    .card-back .barcode {
      font-family: 'Courier New', monospace;
      letter-spacing: .5mm;
      font-size: 3.5mm;
      text-align: center;
      margin-top: 2mm;
      color: #1e293b;
    }

    @media print {
      body { background: #fff; padding: 0; }
      .toolbar { display: none; }
      .card { box-shadow: none; page-break-inside: avoid; }
    }
  </style>
</head>
<body>

  <div class="toolbar">
    <button type="button" class="btn-primary" onclick="window.print()"><i class="bi bi-printer me-1"></i>Print</button>
    <a href="/students/<?= (int) ($student['id'] ?? 0) ?>">&larr; Back to Profile</a>
  </div>

  <!-- FRONT -->
  <div class="card card-front">
    <div class="band">
      <?php if ($schoolLogo): ?>
        <img src="<?= htmlspecialchars($schoolLogo) ?>" alt="" class="logo">
      <?php endif; ?>
      <div>
        <div class="school-name"><?= htmlspecialchars($schoolName) ?></div>
        <div class="school-sub">Student Identification Card</div>
      </div>
    </div>

    <div class="body">
      <?php if ($photo !== ''): ?>
        <img src="<?= htmlspecialchars($photo) ?>" alt="" class="photo">
      <?php else: ?>
        <div class="photo-fallback"><?= htmlspecialchars($initials ?: '?') ?></div>
      <?php endif; ?>

      <div class="details">
        <div class="name"><?= htmlspecialchars($fullName) ?></div>
        <div class="row"><span class="label">Adm No.</span><span class="value"><?= htmlspecialchars($student['admission_no'] ?? '—') ?></span></div>
        <div class="row"><span class="label">Class</span><span class="value"><?= htmlspecialchars($student['class_name'] ?? '—') ?></span></div>
        <div class="row"><span class="label">DOB</span><span class="value"><?= htmlspecialchars($dob) ?></span></div>
        <?php if (!empty($student['blood_group'])): ?>
          <div class="row"><span class="label">Blood Grp</span><span class="value"><?= htmlspecialchars($student['blood_group']) ?></span></div>
        <?php endif; ?>
      </div>
    </div>

    <div class="foot">
      <span>Issued <?= htmlspecialchars($issued) ?></span>
      <span>Valid until <?= htmlspecialchars($expires) ?></span>
    </div>
  </div>

  <!-- BACK -->
  <div class="card card-back">
    <div class="title">If found, please return to:</div>
    <p>
      <?= htmlspecialchars($schoolName) ?><br>
      <?php if ($schoolAddr): ?><?= htmlspecialchars($schoolAddr) ?><br><?php endif; ?>
      <?php if ($schoolPhone): ?>Tel: <?= htmlspecialchars($schoolPhone) ?><?php endif; ?>
    </p>
    <?php if (!empty($student['medical_notes'])): ?>
      <p><strong>Medical notes:</strong> <?= htmlspecialchars(mb_substr($student['medical_notes'], 0, 80)) ?></p>
    <?php endif; ?>

    <div class="barcode">
      *<?= htmlspecialchars($student['admission_no'] ?? '') ?>*
    </div>

    <div class="signature">
      <span>Holder's Signature</span>
      <span>Principal's Signature</span>
    </div>
  </div>

</body>
</html>