<?php
declare(strict_types=1);

/**
 * Expected:
 * $student  the student row, enriched with 'dashboard', 'parents',
 *           'availableParents' (see StudentService::studentProfile()).
 */

$student ??= [];

$fullName = trim(
    ($student['first_name'] ?? '') . ' ' .
    ($student['middle_name'] ?? '') . ' ' .
    ($student['last_name'] ?? '')
);

$photo = (string) ($student['photo_url'] ?? '');

$initials = strtoupper(
    mb_substr($student['first_name'] ?? '', 0, 1) .
    mb_substr($student['last_name'] ?? '', 0, 1)
);

$dobDisplay = '—';
$age = '';

if (!empty($student['dob'])) {
    try {
        $dob   = new DateTimeImmutable($student['dob']);
        $today = new DateTimeImmutable();
        $dobDisplay = $dob->format('d M Y');
        $age = $dob->diff($today)->y . ' yrs';
    } catch (Throwable) {
    }
}

$dashboard  = $student['dashboard'] ?? [];
$fees       = $dashboard['fees']       ?? [];
$attendance = $dashboard['attendance'] ?? [];
$exam       = $dashboard['exam']       ?? [];
$library    = $dashboard['library']    ?? [];
$discipline = $dashboard['discipline'] ?? [];

$parents          = $student['parents'] ?? [];
$availableParents  = $student['availableParents'] ?? [];
$canEditParents    = \Core\Session::can('parents.edit');

$studentId = (int) ($student['id'] ?? 0);

// Compact label/value pairs for Personal Details — one array to loop over
// in a 2-column grid instead of an 11-row single-column <dl>.
$personalDetails = [
    'Admission No.' => $student['admission_no'] ?? '—',
    'Class'         => $student['class_name'] ?? '—',
    'Gender'        => !empty($student['gender']) ? ucfirst($student['gender']) : '—',
    'Date of Birth' => $dobDisplay,
    'Age'           => $age ?: '—',
    'Nationality'   => $student['nationality'] ?? '—',
    'National ID'   => $student['national_id'] ?? '—',
    'Religion'      => $student['religion'] ?? '—',
    'Blood Group'   => $student['blood_group'] ?? '—',
    'Enrolled'      => !empty($student['enrolled_at']) ? date('d M Y', strtotime($student['enrolled_at'])) : '—',
];

// Quick Actions — one compact list instead of a grid of large cards.
$quickActions = [
    ['href' => "/reports/report-card/{$studentId}", 'icon' => 'bi-file-earmark-bar-graph', 'label' => 'Report Card'],
    ['href' => "/finance/statement/{$studentId}",    'icon' => 'bi-cash-stack',              'label' => 'Fees'],
    // Attendance/Subjects/Documents/Medical still have no backing route —
    // see the note below on what's real vs. not yet built.
];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
  <div>
    <h3 class="fw-bold mb-1"><?= htmlspecialchars($fullName) ?></h3>
    <div class="text-muted">
      Student Profile
      <?php if (!empty($student['class_name'])): ?>
        &middot; <?= htmlspecialchars($student['class_name']) ?>
      <?php endif; ?>
    </div>
  </div>

  <div class="btn-group">
    <a href="/students/<?= $studentId ?>/edit" class="btn btn-outline-primary btn-sm">
      <i class="bi bi-pencil-square me-1"></i>Edit
    </a>
    <a href="/students/<?= $studentId ?>/promote" class="btn btn-outline-success btn-sm">
      <i class="bi bi-arrow-up-circle me-1"></i>Promote
    </a>
    <a href="/students/<?= $studentId ?>/id-card" class="btn btn-outline-secondary btn-sm">
      <i class="bi bi-person-badge me-1"></i>ID Card
    </a>
  </div>
</div>

<!-- Quick Actions — one slim row, no card chrome, no descriptions -->
<div class="d-flex flex-wrap gap-2 mb-3">
  <?php foreach ($quickActions as $qa): ?>
    <a href="<?= $qa['href'] ?>" class="btn btn-outline-secondary btn-sm">
      <i class="bi <?= $qa['icon'] ?> me-1"></i><?= $qa['label'] ?>
    </a>
  <?php endforeach; ?>
</div>

<div class="row g-3">

  <!-- ── Profile card ─────────────────────────────────────────────────── -->
  <div class="col-lg-4">
    <div class="card border-0 shadow-sm">
      <div class="card-body text-center py-4">
        <?php if ($photo !== ''): ?>
          <img src="<?= htmlspecialchars($photo) ?>" alt="<?= htmlspecialchars($fullName) ?>"
               class="rounded-circle shadow-sm mb-3"
               style="width:110px;height:110px;object-fit:cover;border:3px solid #fff;">
        <?php else: ?>
          <div class="rounded-circle mx-auto mb-3 bg-primary-subtle text-primary-emphasis
                      d-flex align-items-center justify-content-center fw-bold shadow-sm"
               style="width:110px;height:110px;font-size:2.2rem;">
            <?= htmlspecialchars($initials ?: '?') ?>
          </div>
        <?php endif; ?>

        <h5 class="fw-bold mb-1"><?= htmlspecialchars($fullName) ?></h5>
        <div class="text-muted small mb-2"><?= htmlspecialchars($student['class_name'] ?? 'No Class Assigned') ?></div>

        <div class="d-flex justify-content-center flex-wrap gap-1 mb-3">
          <?php if ($student['is_active'] ?? true): ?>
            <span class="badge bg-success-subtle text-success-emphasis">Active</span>
          <?php else: ?>
            <span class="badge bg-secondary-subtle text-secondary-emphasis">Inactive</span>
          <?php endif; ?>
          <?php if (!empty($student['gender'])): ?>
            <span class="badge bg-light border text-dark"><?= htmlspecialchars(ucfirst($student['gender'])) ?></span>
          <?php endif; ?>
          <?php if ($age !== ''): ?>
            <span class="badge bg-light border text-dark"><?= htmlspecialchars($age) ?></span>
          <?php endif; ?>
        </div>

        <div class="border-top pt-3">
          <div class="small text-muted">Admission No.</div>
          <div class="fw-bold"><?= htmlspecialchars($student['admission_no'] ?? '—') ?></div>
        </div>
      </div>
    </div>

    <!-- Personal Details — compact 2-column grid instead of an 11-row list -->
    <div class="card border-0 shadow-sm mt-3">
      <div class="card-header bg-white fw-semibold py-2"><i class="bi bi-person-vcard me-2"></i>Personal Details</div>
      <div class="card-body">
        <div class="row g-2">
          <?php foreach ($personalDetails as $label => $value): ?>
            <div class="col-6">
              <div class="text-muted" style="font-size:.7rem;"><?= htmlspecialchars($label) ?></div>
              <div class="small fw-semibold text-truncate" title="<?= htmlspecialchars((string) $value) ?>">
                <?= htmlspecialchars((string) $value) ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="mt-2">
          <?php if ($student['is_active'] ?? true): ?>
            <span class="badge bg-success">Active</span>
          <?php else: ?>
            <span class="badge bg-secondary">Inactive</span>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

  <!-- ── Everything else ──────────────────────────────────────────────── -->
  <div class="col-lg-8">

    <!-- Dashboard Summary — merged related cards, tighter padding -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold py-2"><i class="bi bi-speedometer2 me-2"></i>Student Summary</div>
      <div class="card-body py-3">
        <div class="row g-2">

          <!-- Fees: due / paid / balance, one card instead of two -->
          <div class="col-md-6">
            <div class="border-start border-4 border-warning rounded-1 ps-3 py-2 h-100">
              <div class="small text-muted mb-1">Fees</div>
              <div class="d-flex justify-content-between">
                <span class="small">Balance</span>
                <span class="fw-bold">KES <?= number_format((float) ($fees['balance'] ?? 0), 2) ?></span>
              </div>
              <div class="d-flex justify-content-between text-muted" style="font-size:.75rem;">
                <span>Due KES <?= number_format((float) ($fees['total_due'] ?? 0), 2) ?></span>
                <span>Paid KES <?= number_format((float) ($fees['total_paid'] ?? 0), 2) ?></span>
              </div>
            </div>
          </div>

          <!-- Exam: grade / average / position, one card instead of three -->
          <div class="col-md-6">
            <div class="border-start border-4 border-primary rounded-1 ps-3 py-2 h-100">
              <div class="small text-muted mb-1"><?= htmlspecialchars($exam['exam_name'] ?? 'Latest Exam') ?></div>
              <div class="d-flex gap-3">
                <div><div class="fs-6 fw-bold"><?= htmlspecialchars($exam['grade'] ?? '—') ?></div><small class="text-muted">Grade</small></div>
                <div><div class="fs-6 fw-bold"><?= htmlspecialchars((string) ($exam['average'] ?? '—')) ?></div><small class="text-muted">Average</small></div>
                <div><div class="fs-6 fw-bold"><?= htmlspecialchars((string) ($exam['position'] ?? '—')) ?></div><small class="text-muted">Position</small></div>
              </div>
            </div>
          </div>

          <!-- Attendance -->
          <div class="col-md-4">
            <div class="border-start border-4 border-success rounded-1 ps-3 py-2 h-100">
              <div class="small text-muted mb-1">Attendance</div>
              <?php if (($attendance['available'] ?? true) === false): ?>
                <div class="fw-bold text-muted">Unavailable</div>
              <?php else: ?>
                <div class="fw-bold"><?= htmlspecialchars((string) ($attendance['attendance_rate'] ?? 0)) ?>%</div>
                <small class="text-muted"><?= (int) ($attendance['present_days'] ?? 0) ?>/<?= (int) ($attendance['total_days'] ?? 0) ?> days</small>
              <?php endif; ?>
            </div>
          </div>

          <!-- Library -->
          <div class="col-md-4">
            <div class="border-start border-4 border-info rounded-1 ps-3 py-2 h-100">
              <div class="small text-muted mb-1">Library</div>
              <?php if (($library['available'] ?? true) === false): ?>
                <div class="fw-bold text-muted">Unavailable</div>
              <?php else: ?>
                <div class="fw-bold"><?= (int) ($library['borrowed'] ?? 0) ?> borrowed</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Discipline -->
          <div class="col-md-4">
            <div class="border-start border-4 border-danger rounded-1 ps-3 py-2 h-100">
              <div class="small text-muted mb-1">Discipline</div>
              <div class="fw-bold"><?= (int) ($discipline['cases'] ?? 0) ?> case<?= ((int) ($discipline['cases'] ?? 0)) === 1 ? '' : 's' ?></div>
            </div>
          </div>

        </div>
      </div>
    </div>

    <!-- Address & Medical -->
    <div class="card border-0 shadow-sm mb-3">
      <div class="card-header bg-white fw-semibold py-2"><i class="bi bi-heart-pulse me-2"></i>Address &amp; Medical</div>
      <div class="card-body py-3">
        <div class="row g-3">
          <div class="col-md-6">
            <div class="small text-muted mb-1"><i class="bi bi-geo-alt me-1"></i>Residential Address</div>
            <div class="border rounded p-2 bg-light small">
              <?= !empty($student['address']) ? nl2br(htmlspecialchars($student['address'])) : '<span class="text-muted">No address available.</span>' ?>
            </div>
          </div>
          <div class="col-md-6">
            <div class="small text-muted mb-1"><i class="bi bi-heart-pulse me-1"></i>Medical Notes</div>
            <div class="border-start border-4 border-info rounded bg-info-subtle p-2 small">
              <?= !empty($student['medical_notes']) ? nl2br(htmlspecialchars($student['medical_notes'])) : '<span class="text-muted">No medical notes recorded.</span>' ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Parents & Guardians -->
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white d-flex justify-content-between align-items-center py-2">
        <div class="fw-semibold"><i class="bi bi-people me-2"></i>Parents & Guardians</div>
        <?php if ($canEditParents): ?>
          <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#linkParentModal">
            <i class="bi bi-person-plus me-1"></i>Link Parent
          </button>
        <?php endif; ?>
      </div>
      <div class="card-body p-0">
        <?php if (empty($parents)): ?>
          <div class="text-center py-4 text-muted small">No parent or guardian linked yet.</div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr class="small"><th style="width:50px;"></th><th>Name</th><th>Email</th><th>Phone</th><th>Relationship</th><th class="text-end" style="width:140px;">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($parents as $parent): ?>
                  <?php
                    $parentName = trim(($parent['first_name'] ?? '') . ' ' . ($parent['last_name'] ?? ''));
                    $avatar = strtoupper(mb_substr($parent['first_name'] ?? '', 0, 1) . mb_substr($parent['last_name'] ?? '', 0, 1));
                  ?>
                  <tr>
                    <td>
                      <div class="rounded-circle bg-primary-subtle text-primary-emphasis d-flex align-items-center justify-content-center fw-bold small" style="width:34px;height:34px;">
                        <?= htmlspecialchars($avatar ?: '?') ?>
                      </div>
                    </td>
                    <td class="small fw-semibold"><?= htmlspecialchars($parentName) ?></td>
                    <td class="small">
                      <?php if (!empty($parent['email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($parent['email']) ?>"><?= htmlspecialchars($parent['email']) ?></a>
                      <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                    </td>
                    <td class="small"><?= htmlspecialchars($parent['phone'] ?? '—') ?></td>
                    <td><span class="badge bg-info-subtle text-info-emphasis"><?= htmlspecialchars(ucfirst($parent['relationship'] ?? 'Parent')) ?></span></td>
                    <td class="text-end">
                      <div class="btn-group btn-group-sm">
                        <a href="/parents/<?= (int) $parent['id'] ?>" class="btn btn-outline-primary"><i class="bi bi-eye"></i></a>
                        <?php if ($canEditParents): ?>
                          <form method="POST" action="/parents/<?= (int) $parent['id'] ?>/unlink" class="d-inline"
                                onsubmit="return confirm('Unlink this parent from the student?');">
                            <?= csrf_field() ?>
                            <input type="hidden" name="student_id" value="<?= $studentId ?>">
                            <button class="btn btn-outline-danger"><i class="bi bi-link-45deg"></i></button>
                          </form>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- Link Parent modal -->
    <?php if ($canEditParents): ?>
      <dialog class="modal" id="linkParentModal">
        <div class="modal-dialog"><form method="POST" action="/parents/link-student">
          <?= csrf_field() ?>
          <input type="hidden" name="student_id" value="<?= $studentId ?>">
          <input type="hidden" name="from_parent" value="0">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Link Parent / Guardian</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
              <?php if (!empty($availableParents)): ?>
                <div class="mb-3">
                  <label class="form-label">Parent</label>
                  <select name="parent_id" class="form-select" required>
                    <option value="">-- Select Parent --</option>
                    <?php foreach ($availableParents as $ap): ?>
                      <option value="<?= (int) $ap['id'] ?>">
                        <?= htmlspecialchars(trim(($ap['first_name'] ?? '') . ' ' . ($ap['last_name'] ?? ''))) ?>
                        <?php if (!empty($ap['email'])): ?>(<?= htmlspecialchars($ap['email']) ?>)<?php endif; ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                </div>
              <?php else: ?>
                <div class="alert alert-warning mb-0">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  No available parents found. All existing parents are already linked to this student.
                </div>
              <?php endif; ?>

              <div class="mt-3">
                <label class="form-label">Relationship</label>
                <select name="relationship" class="form-select">
                  <?php foreach (['Parent','Father','Mother','Guardian','Sponsor','Grandparent','Uncle','Aunt','Other'] as $r): ?>
                    <option value="<?= $r ?>"><?= $r ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
              <?php if (!empty($availableParents)): ?>
                <button type="submit" class="btn btn-primary"><i class="bi bi-link-45deg me-1"></i>Link Parent</button>
              <?php endif; ?>
            </div>
          </div>
        </form></div>
      </dialog>
    <?php endif; ?>

  </div>
</div>