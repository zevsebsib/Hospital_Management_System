<?php
/**
 * MediCore HMS — Doctor Management Page (Admin Only)
 * 
 * Allows administrators to:
 * - View all doctors with their specializations and schedules
 * - Add new doctor accounts (automatically activated)
 * - Edit doctor information (specialization, schedule)
 * - Delete doctor accounts and associated data
 * 
 * Requires: Administrator role (enforced by requireAdmin())
 * 
 * POST Actions:
 * - 'add': Create new doctor account
 * - 'edit': Update doctor information
 * - 'delete': Remove doctor account
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/hms/admin/doctors.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $db  = getDB();
        $pw  = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $db->prepare("INSERT INTO users (firstname,lastname,email,password,role,status) VALUES (?,?,?,?,'doctor','active')")
           ->execute([$_POST['firstname'], $_POST['lastname'], $_POST['email'], $pw]);
        $uid = $db->lastInsertId();
        $db->prepare("INSERT INTO doctors (user_id,specialization,schedule) VALUES (?,?,?)")
           ->execute([$uid, $_POST['specialization'], $_POST['schedule']]);
        setFlash('success', 'Doctor added successfully.');
    }
    if ($a === 'approve') {
        getDB()->prepare("UPDATE users SET status='active' WHERE id=(SELECT user_id FROM doctors WHERE id=?)")
               ->execute([$_POST['doctor_id']]);
        setFlash('success', 'Doctor account activated.');
    }
    if ($a === 'delete') {
        getDB()->prepare("DELETE FROM users WHERE id=(SELECT user_id FROM doctors WHERE id=?)")
               ->execute([$_POST['doctor_id']]);
        setFlash('success', 'Doctor removed from system.');
    }
    header('Location: /hms/admin/doctors.php'); exit;
}

$doctors = array_filter(getAllDoctors(), fn($d) => $d['status'] === 'active');
$pending = array_filter(getAllDoctors(), fn($d) => $d['status'] === 'pending');
renderLayout('doctors', 'Doctor Management');
?>

<div class="page-header">
  <div><h2>Doctor Management</h2><p>Manage all physicians registered in the system</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addDoctorModal">+ Add Doctor</button>
</div>

<?php if ($pending): ?>
<div class="card" style="margin-bottom:30px;border-left:4px solid var(--accent)">
  <div class="card-header">
    <div class="card-title" style="color:var(--accent)">Pending Doctor Approvals</div>
    <div class="card-subtitle">New registrations waiting for access</div>
  </div>
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Physician</th><th>Specialization</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach ($pending as $p): ?>
        <tr>
          <td><strong><?= e($p['firstname'].' '.$p['lastname']) ?></strong><br><small><?= e($p['email']) ?></small></td>
          <td><?= e($p['specialization']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="approve">
              <input type="hidden" name="doctor_id" value="<?= $p['id'] ?>">
              <?php csrfField(); ?>
              <button type="submit" class="btn btn-primary btn-sm">Approve Access</button>
            </form>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="doctor_id" value="<?= $p['id'] ?>">
              <?php csrfField(); ?>
              <button type="submit" class="btn btn-ghost btn-sm" style="color:var(--red)">Reject</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Physician</th><th>Specialization</th><th>Email</th><th>Schedule</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($doctors): foreach ($doctors as $d): $init = strtoupper($d['firstname'][0].$d['lastname'][0]); ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar av-blue"><?= $init ?></div>
              <div><div class="fw-600">Dr. <?= e($d['firstname'].' '.$d['lastname']) ?></div></div>
            </div>
          </td>
          <td><span style="background:var(--blue-pale);color:var(--blue);padding:3px 10px;border-radius:20px;font-size:12px;font-weight:600"><?= e($d['specialization']) ?></span></td>
          <td style="color:var(--gray-500)"><?= e($d['email']) ?></td>
          <td style="color:var(--gray-500);font-size:13px"><?= e($d['schedule']) ?></td>
          <td>
            <form method="POST" style="display:inline">
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="doctor_id" value="<?= $d['id'] ?>">
              <?php csrfField(); ?>
              <button type="submit" class="btn btn-danger btn-sm" data-confirm="Remove Dr. <?= e($d['lastname']) ?> from the system?">Remove</button>
            </form>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="5"><div class="empty-state"><div class="ei">👨‍⚕️</div><h4>No doctors registered yet</h4></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Add Doctor Modal -->
<div class="modal fade" id="addDoctorModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Add New Doctor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST"><input type="hidden" name="action" value="add">
      <?php csrfField(); ?>
      <div class="modal-body">
        <div class="frow">
          <div class="fgroup"><label>First Name</label><input name="firstname" required placeholder="Juan"></div>
          <div class="fgroup"><label>Last Name</label><input name="lastname" required placeholder="dela Cruz"></div>
        </div>
        <div class="fgroup"><label>Specialization</label>
          <select name="specialization">
            <option>General Practitioner</option><option>Cardiologist</option><option>Dermatologist</option>
            <option>Neurologist</option><option>Pediatrician</option><option>Orthopedic Surgeon</option>
            <option>Oncologist</option><option>Psychiatrist</option><option>Endocrinologist</option><option>Radiologist</option>
          </select>
        </div>
        <div class="fgroup"><label>Email Address</label><input type="email" name="email" required placeholder="doctor@hospital.com"></div>
        <div class="fgroup"><label>Password</label><input type="password" name="password" required placeholder="Minimum 8 characters"></div>
        <div class="fgroup" style="margin-bottom:0"><label>Schedule Availability</label><input name="schedule" placeholder="Mon–Fri, 8:00 AM – 5:00 PM"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Save Doctor</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php renderLayoutEnd(); ?>
