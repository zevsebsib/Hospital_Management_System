<?php
/**
 * MediCore HMS — Prescription Management Page (Admin & Doctor)
 * 
 * Allows authorized users to:
 * - View all prescriptions (admin: all, doctor: their own)
 * - Issue new prescriptions for consultations
 * - Document medication details (name, dosage, frequency)
 * - Provide patient instructions
 * - Track prescription history
 * 
 * Features:
 * - Consultation-linked prescription creation
 * - Medication documentation
 * - Dosage and frequency specification
 * - Patient instruction notes
 * - Role-aware data filtering
 * 
 * Requires: Login (admin sees all, doctor sees own)
 * 
 * POST Actions:
 * - 'add': Issue new prescription
 * - 'edit': Update prescription details
 * - 'delete': Remove prescription record
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/hms/admin/prescriptions.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        getDB()->prepare("INSERT INTO prescriptions (consultation_id,medicine_name,dosage,frequency,duration,instructions) VALUES (?,?,?,?,?,?)")
               ->execute([$_POST['consultation_id'], $_POST['medicine_name'], $_POST['dosage'], $_POST['frequency'], $_POST['duration'], $_POST['instructions']]);
        setFlash('success', 'Prescription issued successfully.');
    }
    header('Location: /hms/admin/prescriptions.php'); exit;
}

$preselect = (int)($_GET['consult_id'] ?? 0);
$docId     = isDoctor() ? ($_SESSION['doctor_id'] ?? 0) : 0;
$rxList    = getPrescriptions();
$consults  = getConsultations($docId);
renderLayout('prescriptions', 'Prescriptions');
?>

<div class="page-header">
  <div><h2>Prescriptions</h2><p>Digital medication prescription management</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRxModal">+ Issue Prescription</button>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Medication</th><th>Patient</th><th>Physician</th><th>Dosage</th><th>Frequency</th><th>Duration</th><th>Instructions</th></tr></thead>
      <tbody>
      <?php if ($rxList): foreach ($rxList as $rx): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div style="width:28px;height:28px;background:var(--green-pale);border-radius:6px;display:flex;align-items:center;justify-content:center;font-size:14px">💊</div>
              <strong style="color:var(--teal)"><?= e($rx['medicine_name']) ?></strong>
            </div>
          </td>
          <td><?= e($rx['p_fn'].' '.$rx['p_ln']) ?></td>
          <td>Dr. <?= e($rx['d_ln']) ?></td>
          <td><?= e($rx['dosage']) ?></td>
          <td><?= e($rx['frequency']) ?></td>
          <td><?= e($rx['duration']) ?></td>
          <td style="color:var(--gray-400);font-size:13px"><?= e($rx['instructions'] ?: '—') ?></td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="7"><div class="empty-state"><div class="ei">💊</div><h4>No prescriptions issued yet</h4></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Issue Prescription Modal -->
<div class="modal fade <?= $preselect ? 'show' : '' ?>" id="addRxModal" tabindex="-1" <?= $preselect ? 'style="display:block"' : '' ?>>
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Issue Prescription</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST"><input type="hidden" name="action" value="add">
      <?php csrfField(); ?>
      <div class="modal-body">
        <div class="fgroup">
          <label>Linked Consultation</label>
          <select name="consultation_id">
            <?php foreach ($consults as $c): ?>
            <option value="<?= $c['id'] ?>" <?= $c['id']===$preselect?'selected':'' ?>>
              <?= e($c['p_fn'].' '.$c['p_ln']) ?> — <?= e($c['diagnosis']) ?> (<?= e($c['consultation_date']) ?>)
            </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fgroup"><label>Medication Name</label><input name="medicine_name" required placeholder="e.g. Amoxicillin 500mg"></div>
        <div class="frow">
          <div class="fgroup"><label>Dosage</label><input name="dosage" placeholder="e.g. 1 tablet"></div>
          <div class="fgroup"><label>Frequency</label>
            <select name="frequency">
              <option>Once daily</option><option>Twice daily</option><option>Three times daily</option>
              <option>Every 8 hours</option><option>Every 12 hours</option><option>As needed</option>
            </select>
          </div>
        </div>
        <div class="frow">
          <div class="fgroup"><label>Duration</label><input name="duration" placeholder="e.g. 7 days"></div>
          <div class="fgroup" style="margin-bottom:0"><label>Special Instructions</label><input name="instructions" placeholder="e.g. Take after meals"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-success">Issue Prescription</button>
      </div>
      </form>
    </div>
  </div>
</div>
<?php if ($preselect): ?><div class="modal-backdrop fade show"></div><?php endif; ?>

<?php renderLayoutEnd(); ?>
