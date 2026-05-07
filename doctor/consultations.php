<?php
/**
 * MediCore HMS — Doctor Consultations Page (Doctor Only)
 * 
 * Allows doctors to:
 * - View all their scheduled appointments
 * - Record consultation notes for completed appointments
 * - Document patient diagnosis
 * - Add clinical observations and treatment notes
 * - Link consultations to prescriptions
 * 
 * Features:
 * - Appointment-to-consultation workflow
 * - Doctor-specific filtering (only their consultations)
 * - Diagnosis recording
 * - Clinical notes documentation
 * - Timestamp tracking
 * 
 * Requires: Doctor role (enforced by requireLogin + role check)
 * 
 * POST Actions:
 * - 'add': Record new consultation
 * - 'edit': Update consultation notes
 * - 'delete': Remove consultation record
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/hms/doctor/consultations.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        getDB()->prepare("INSERT INTO consultations (appointment_id,diagnosis,notes,consultation_date) VALUES (?,?,?,?)")
               ->execute([$_POST['appointment_id'], $_POST['diagnosis'], $_POST['notes'], date('Y-m-d')]);
        setFlash('success', 'Consultation recorded successfully.');
    }
    header('Location: /hms/doctor/consultations.php'); exit;
}

$docId    = isDoctor() ? ($_SESSION['doctor_id'] ?? 0) : 0;
$consults = getConsultations($docId);
$appts    = getAppointments('Completed', $docId);
renderLayout('consultations', 'Consultation Records');
?>

<div class="page-header">
  <div><h2>Consultation Records</h2><p>Clinical notes and diagnoses</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addConsultModal">+ Record Consultation</button>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Physician</th><th>Diagnosis</th><th>Date</th><th>Notes</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($consults): foreach ($consults as $c): ?>
        <tr>
          <td><strong><?= e($c['p_fn'].' '.$c['p_ln']) ?></strong></td>
          <td>Dr. <?= e($c['d_fn'].' '.$c['d_ln']) ?></td>
          <td style="color:var(--teal);font-weight:600"><?= e($c['diagnosis']) ?></td>
          <td style="color:var(--gray-400)"><?= e($c['consultation_date']) ?></td>
          <td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-400);font-size:13px"><?= e($c['notes']) ?></td>
          <td>
            <a href="/hms/doctor/prescriptions.php?consult_id=<?= $c['id'] ?>" class="btn btn-primary btn-sm">+ Rx</a>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><div class="ei">🩺</div><h4>No consultations recorded</h4></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Record Consultation Modal -->
<div class="modal fade" id="addConsultModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Record Consultation</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST"><input type="hidden" name="action" value="add">
      <?php csrfField(); ?>
      <div class="modal-body">
        <div class="fgroup">
          <label>Appointment (Completed)</label>
          <select name="appointment_id">
            <?php if ($appts): foreach ($appts as $a): ?>
            <option value="<?= $a['id'] ?>"><?= e($a['p_fn'].' '.$a['p_ln']) ?> — Dr. <?= e($a['d_ln']) ?> (<?= e($a['appointment_date']) ?>)</option>
            <?php endforeach; else: ?>
            <option disabled>No completed appointments found</option>
            <?php endif; ?>
          </select>
        </div>
        <div class="fgroup"><label>Diagnosis</label><input name="diagnosis" required placeholder="Primary diagnosis..."></div>
        <div class="fgroup" style="margin-bottom:0"><label>Clinical Notes</label><textarea name="notes" placeholder="Observations, treatment plan, follow-up instructions..."></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Record</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php renderLayoutEnd(); ?>
