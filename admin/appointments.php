<?php
/**
 * MediCore HMS — Appointment Scheduling Page (Admin Only)
 * 
 * Allows administrators to:
 * - View all scheduled appointments with doctor/patient details
 * - Create new appointments with automatic conflict detection
 * - Prevent double-booking (doctor can't have overlapping appointments)
 * - Update appointment status (Scheduled → Completed)
 * - Cancel appointments if needed
 * 
 * Features:
 * - Real-time conflict checking
 * - Date/time validation
 * - Doctor availability verification
 * - Patient-Doctor matching
 * 
 * Requires: Administrator role (enforced by requireAdmin())
 * 
 * POST Actions:
 * - 'add': Schedule new appointment
 * - 'update': Change appointment status
 * - 'delete': Cancel appointment
 */

require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/layout.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verifyCsrf('/hms/admin/appointments.php');
    $a = $_POST['action'] ?? '';
    if ($a === 'add') {
        $did  = (int)$_POST['doctor_id'];
        $date = $_POST['appointment_date'];
        $time = $_POST['appointment_time'];
        if (checkConflict($did, $date, $time)) {
            setFlash('danger', 'Schedule conflict! The doctor already has an appointment at this time.');
        } else {
            getDB()->prepare("INSERT INTO appointments (patient_id,doctor_id,appointment_date,appointment_time,status) VALUES (?,?,?,?,'Scheduled')")
                   ->execute([$_POST['patient_id'], $did, $date, $time]);
            setFlash('success', 'Appointment booked successfully.');
        }
    }
    if ($a === 'status') {
        getDB()->prepare("UPDATE appointments SET status=? WHERE id=?")
               ->execute([$_POST['status'], $_POST['appt_id']]);
        setFlash('success', 'Appointment status updated.');
    }
    header('Location: /hms/admin/appointments.php'); exit;
}

$filter   = $_GET['status'] ?? '';
$appts    = getAppointments($filter);
$patients = getAllPatients();
$doctors  = getAllDoctors();
renderLayout('appointments', 'Appointment Scheduling');
?>

<div class="page-header">
  <div><h2>Appointment Scheduling</h2><p>Book and manage patient–doctor consultations</p></div>
  <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addApptModal">+ Book Appointment</button>
</div>

<!-- Filter Tabs -->
<ul class="nav nav-tabs mb-0" style="border-bottom:1px solid var(--gray-200);margin-bottom:20px">
  <?php foreach (['' => 'All', 'Scheduled' => 'Scheduled', 'Completed' => 'Completed', 'Cancelled' => 'Cancelled'] as $val => $label): ?>
  <li class="nav-item">
    <a href="?status=<?= $val ?>" class="nav-link <?= $filter===$val?'active':'' ?>"
       style="<?= $filter===$val ? 'color:var(--blue);border-bottom:2px solid var(--blue)' : 'color:var(--gray-500)' ?>;border:none;padding:10px 18px;font-weight:600;font-size:13.5px;text-decoration:none;background:none;display:inline-block">
      <?= $label ?>
    </a>
  </li>
  <?php endforeach; ?>
</ul>

<div class="card">
  <div class="tbl-wrap">
    <table>
      <thead><tr><th>Patient</th><th>Physician</th><th>Date &amp; Time</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
      <?php if ($appts): foreach ($appts as $a): ?>
        <tr>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="avatar av-teal"><?= strtoupper($a['p_fn'][0].$a['p_ln'][0]) ?></div>
              <strong><?= e($a['p_fn'].' '.$a['p_ln']) ?></strong>
            </div>
          </td>
          <td>Dr. <?= e($a['d_fn'].' '.$a['d_ln']) ?></td>
          <td>
            <div class="fw-600"><?= e($a['appointment_date']) ?></div>
            <div class="text-sm"><?= e($a['appointment_time']) ?></div>
          </td>
          <td><span class="badge badge-<?= e($a['status']) ?>"><?= e($a['status']) ?></span></td>
          <td>
            <?php if ($a['status'] === 'Scheduled'): ?>
            <div class="d-flex gap-2">
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="status" value="Completed">
                <?php csrfField(); ?>
                <button type="submit" class="btn btn-success btn-sm">Complete</button>
              </form>
              <form method="POST" style="display:inline">
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="appt_id" value="<?= $a['id'] ?>">
                <input type="hidden" name="status" value="Cancelled">
                <?php csrfField(); ?>
                <button type="submit" class="btn btn-ghost btn-sm">Cancel</button>
              </form>
            </div>
            <?php else: echo '<span style="color:var(--gray-300)">—</span>'; endif; ?>
          </td>
        </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="5"><div class="empty-state"><div class="ei">📅</div><h4>No appointments found</h4></div></td></tr>
      <?php endif; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Book Appointment Modal -->
<div class="modal fade" id="addApptModal" tabindex="-1">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header"><h5 class="modal-title">Book Appointment</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <form method="POST"><input type="hidden" name="action" value="add">
      <?php csrfField(); ?>
      <div class="modal-body">
        <div class="fgroup">
          <label>Patient</label>
          <select name="patient_id">
            <?php foreach ($patients as $p): ?>
            <option value="<?= $p['id'] ?>"><?= e($p['firstname'].' '.$p['lastname']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="fgroup">
          <label>Physician</label>
          <select name="doctor_id">
            <?php foreach ($doctors as $d): ?>
            <option value="<?= $d['id'] ?>">Dr. <?= e($d['firstname'].' '.$d['lastname']) ?> (<?= e($d['specialization']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="frow">
          <div class="fgroup"><label>Date</label><input type="date" name="appointment_date" required></div>
          <div class="fgroup" style="margin-bottom:0"><label>Time</label><input type="time" name="appointment_time" required></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-ghost" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary">Book Appointment</button>
      </div>
      </form>
    </div>
  </div>
</div>

<?php renderLayoutEnd(); ?>
