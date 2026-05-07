<?php
/**
 * MediCore HMS — Patient Management Page (Admin Only)
 * 
 * Allows administrators to:
 * - View all patients with searchable directory
 * - Register new patient records with demographics
 * - Delete patient accounts and associated medical records
 * - View patient medical history and consultation records
 * 
 * Features:
 * - Live search filtering by patient name
 * - Patient demographics capture (DOB, sex, address, contact)
 * - Soft delete tracking (if implemented)
 * 
 * Requires: Administrator role (enforced by requireAdmin())
 * 
 * POST Actions:
 * - 'add': Register new patient
 * - 'delete': Remove patient record
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/hms/admin/patients.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        getDB()->prepare("INSERT INTO patients (firstname,lastname,birthdate,sex,address,contact) VALUES (?,?,?,?,?,?)")
               ->execute([$_POST['firstname'],$_POST['lastname'],$_POST['birthdate'],$_POST['sex'],$_POST['address'],$_POST['contact']]);
        setFlash('success', 'Patient registered successfully.');
    }
    if ($a === 'delete') {
        getDB()->prepare("DELETE FROM patients WHERE id=?")->execute([$_POST['patient_id']]);
        setFlash('success', 'Patient record deleted.');
    }
    header('Location: /hms/admin/patients.php'); exit;
}

$search   = trim($_GET['q'] ?? '');
$patients = getAllPatients($search);
renderLayout('patients', 'Patient Records');
?>

<div class="page-header">
  <div><h2>Patient Records</h2><p>Register and manage patient information</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addPatientModal">+ Register Patient</button>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <form method="GET" class="search-wrap">
    <span class="search-icon">🔍</span>
    <input name="q" placeholder="Search patients by name..." value="<?= e($search) ?>" oninput="this.form.submit()">
  </form>
  <span style="font-size:13px;color:var(--gray-400)"><?= count($patients) ?> patient<?= count($patients)!==1?'s':'' ?></span>
</div>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Date of Birth</th><th>Sex</th><th>Contact</th><th>Address</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($patients): foreach ($patients as $p): $init = strtoupper($p['firstname'][0].$p['lastname'][0]); ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar av-teal"><?= $init ?></div>
              <div>
                <div class="fw-600"><?= e($p['firstname'].' '.$p['lastname']) ?></div>
                <div class="text-sm">Registered <?= e($p['created_at']) ?></div>
              </div>
            </div>
          </td>
          <td><?= e($p['birthdate']) ?></td>
          <td><?= e($p['sex']) ?></td>
          <td style="color:var(--gray-500)"><?= e($p['contact']) ?></td>
          <td style="max-width:160px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;color:var(--gray-400);font-size:13px"><?= e($p['address']) ?></td>
          <td>
            <div class="d-flex gap-2">
              <a href="/hms/admin/patient_history.php?id=<?= $p['id'] ?>" class="btn btn-ghost btn-sm">History</a>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="patient_id" value="<?= $p['id'] ?>">
                <?php csrfField(); ?>
                <button type="submit" class="btn btn-danger btn-sm" data-confirm="Delete this patient record permanently?">Delete</button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="6"><div class="empty-state"><div class="ei">👥</div><h4>No patients found</h4></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Patient Modal -->
<div class="modal fade" id="addPatientModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Register New Patient</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST"><input type="hidden" name="action" value="add">
      <?php csrfField(); ?>
      <div class="modal-body">
        <div class="frow">
          <div class="fgroup"><label>First Name</label><input name="firstname" required placeholder="Maria"></div>
          <div class="fgroup"><label>Last Name</label><input name="lastname" required placeholder="Garcia"></div>
        </div>
        <div class="frow">
          <div class="fgroup"><label>Date of Birth</label><input type="date" name="birthdate"></div>
          <div class="fgroup"><label>Sex</label>
            <select name="sex"><option value="">— Select —</option><option>Male</option><option>Female</option><option>Other</option></select>
          </div>
        </div>
        <div class="fgroup"><label>Address</label><input name="address" placeholder="123 Rizal St, Manila"></div>
        <div class="fgroup" style="margin-bottom:0"><label>Contact Number</label><input name="contact" placeholder="09XX XXX XXXX"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Register Patient</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php renderLayoutEnd(); ?>
