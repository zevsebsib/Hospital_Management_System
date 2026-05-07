<?php
/**
 * MediCore HMS — Patient Medical History Page
 * 
 * Displays comprehensive medical records for a specific patient:
 * - Complete consultation history (newest first)
 * - Associated prescriptions for each consultation
 * - Doctor information for each visit
 * - Diagnosis and clinical notes
 * - Treatment timeline
 * 
 * Features:
 * - Full medical record view
 * - Nested prescription display
 * - Doctor attribution tracking
 * - Chronological history organization
 * - Back navigation to patient list
 * 
 * Access: Accessible to admins (via patient lookup)
 * URL Parameter: ?id=<patient_id>
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

$patient = getPatientById((int)($_GET['id'] ?? 0));
if (!$patient) { header('Location: /hms/admin/patients.php'); exit; }

$history = getPatientHistory($patient['id']);
renderLayout('patients', 'Patient History');
?>

<div class="page-header">
  <div>
    <h2><?= e($patient['firstname'] . ' ' . $patient['lastname']) ?></h2>
    <p>Complete medical history &amp; prescription records</p>
  </div>
  <a href="/hms/admin/patients.php" class="btn btn-ghost">← Back to Patients</a>
</div>

<!-- Patient Info Card -->
<div class="card mb-2">
  <div class="card-header"><div class="card-title">Patient Information</div></div>
  <div style="padding:20px 24px">
    <div class="detail-grid">
      <div><div class="di-label">Full Name</div><div class="di-val"><?= e($patient['firstname'].' '.$patient['lastname']) ?></div></div>
      <div><div class="di-label">Date of Birth</div><div class="di-val"><?= e($patient['birthdate']) ?></div></div>
      <div><div class="di-label">Sex</div><div class="di-val"><?= e($patient['sex']) ?></div></div>
      <div><div class="di-label">Contact</div><div class="di-val"><?= e($patient['contact']) ?></div></div>
      <div style="grid-column:span 2"><div class="di-label">Address</div><div class="di-val"><?= e($patient['address']) ?></div></div>
    </div>
  </div>
</div>

<!-- History -->
<div style="font-size:12px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:16px">
  Consultations &amp; Prescriptions (<?= count($history) ?>)
</div>

<?php if ($history): foreach ($history as $c): ?>
<div class="rx-card">
  <div class="d-flex justify-content-between align-items-start mb-2">
    <div class="d-flex align-items-center gap-2">
      <div class="rx-pill-icon">🩺</div>
      <div>
        <div style="font-size:16px;font-weight:700;color:var(--gray-900)"><?= e($c['diagnosis']) ?></div>
        <div class="text-sm"><?= e($c['consultation_date']) ?> &middot; Dr. <?= e($c['d_fn'].' '.$c['d_ln']) ?></div>
      </div>
    </div>
  </div>
  <p style="font-size:13.5px;color:var(--gray-500);margin-bottom:<?= count($c['prescriptions'])>0?'16':'0' ?>px"><?= nl2br(e($c['notes'])) ?></p>

  <?php if ($c['prescriptions']): ?>
  <div style="font-size:11px;font-weight:700;color:var(--gray-400);text-transform:uppercase;letter-spacing:1px;margin-bottom:10px">Prescriptions</div>
  <div class="row g-2">
    <?php foreach ($c['prescriptions'] as $rx): ?>
    <div class="col-md-6">
      <div style="background:white;border:1px solid var(--gray-200);border-radius:8px;padding:14px">
        <div style="color:var(--teal);font-weight:700;margin-bottom:8px">💊 <?= e($rx['medicine_name']) ?></div>
        <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px;font-size:12px">
          <div><div class="di-label">Dosage</div><div style="font-weight:600;color:var(--gray-800)"><?= e($rx['dosage']) ?></div></div>
          <div><div class="di-label">Frequency</div><div style="font-weight:600;color:var(--gray-800)"><?= e($rx['frequency']) ?></div></div>
          <div><div class="di-label">Duration</div><div style="font-weight:600;color:var(--gray-800)"><?= e($rx['duration']) ?></div></div>
        </div>
        <?php if ($rx['instructions']): ?>
        <div style="margin-top:8px;font-size:12px;color:var(--gray-400)">ℹ️ <?= e($rx['instructions']) ?></div>
        <?php endif; ?>
      </div>
    </div>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</div>
<?php endforeach; else: ?>
<div class="empty-state"><div class="ei">📋</div><h4>No consultation history</h4><p>This patient has no recorded consultations yet.</p></div>
<?php endif; ?>

<?php renderLayoutEnd(); ?>
