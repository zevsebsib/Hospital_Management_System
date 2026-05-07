<?php
/**
 * MediCore HMS — Main Dashboard Page
 * 
 * Displays the home view for both admins and doctors with:
 * - Dashboard statistics cards (patients, doctors, appointments, prescriptions)
 * - Upcoming scheduled appointments list
 * - Recent prescriptions view
 * 
 * Role-specific behavior:
 * - Admin: Sees system-wide statistics
 * - Doctor: Sees only their appointments and prescriptions
 * 
 * Requires: User must be logged in (enforced by requireLogin())
 */

require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/layout.php';
requireLogin();

$docId    = isDoctor() ? ($_SESSION['doctor_id'] ?? 0) : 0;
$stats    = getDashboardStats($docId);
$upcoming = array_slice(getAppointments('Scheduled', $docId), 0, 6);
$recentRx = array_slice(getPrescriptions(0, $docId), 0, 5);

renderLayout('dashboard', 'Dashboard');
?>

<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-header"><div class="stat-icon-wrap">👥</div><span class="stat-trend">Total</span></div>
    <div class="stat-value"><?= $stats['patients'] ?></div>
    <div class="stat-label">Registered Patients</div>
    <div class="stat-bar"><div class="stat-bar-fill"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-header"><div class="stat-icon-wrap">👨‍⚕️</div><span class="stat-trend">Active</span></div>
    <div class="stat-value"><?= $stats['doctors'] ?></div>
    <div class="stat-label">Medical Doctors</div>
    <div class="stat-bar"><div class="stat-bar-fill"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-header"><div class="stat-icon-wrap">📅</div><span class="stat-trend">Pending</span></div>
    <div class="stat-value"><?= $stats['appointments'] ?></div>
    <div class="stat-label">Upcoming Appointments</div>
    <div class="stat-bar"><div class="stat-bar-fill"></div></div>
  </div>
  <div class="stat-card">
    <div class="stat-header"><div class="stat-icon-wrap">💊</div><span class="stat-trend">Issued</span></div>
    <div class="stat-value"><?= $stats['prescriptions'] ?></div>
    <div class="stat-label">Prescriptions</div>
    <div class="stat-bar"><div class="stat-bar-fill"></div></div>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Upcoming Appointments</div><div class="card-subtitle">Next scheduled visits</div></div>
      <?php if (isAdmin()): ?><a href="/hms/admin/appointments.php" class="btn btn-ghost btn-sm">View all</a><?php endif; ?>
    </div>
    <?php if ($upcoming): foreach ($upcoming as $a): ?>
    <div class="appt-row">
      <div>
        <div class="appt-name"><?= e($a['p_fn'] . ' ' . $a['p_ln']) ?></div>
        <div class="appt-meta">Dr. <?= e($a['d_ln']) ?> &middot; <?= e($a['appointment_date']) ?> at <?= e($a['appointment_time']) ?></div>
      </div>
      <span class="badge badge-Scheduled">Scheduled</span>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state"><div class="ei">📅</div><h4>No upcoming appointments</h4></div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header">
      <div><div class="card-title">Recent Prescriptions</div><div class="card-subtitle">Latest issued medications</div></div>
      <a href="/hms/<?= isAdmin()?'admin':'doctor' ?>/prescriptions.php" class="btn btn-ghost btn-sm">View all</a>
    </div>
    <?php if ($recentRx): foreach ($recentRx as $rx): ?>
    <div class="appt-row">
      <div>
        <div class="appt-name" style="color:var(--teal)">💊 <?= e($rx['medicine_name']) ?></div>
        <div class="appt-meta"><?= e($rx['p_fn'] . ' ' . $rx['p_ln']) ?> &middot; <?= e($rx['dosage']) ?> &middot; <?= e($rx['frequency']) ?></div>
      </div>
    </div>
    <?php endforeach; else: ?>
    <div class="empty-state"><div class="ei">💊</div><h4>No prescriptions issued</h4></div>
    <?php endif; ?>
  </div>
</div>

<?php renderLayoutEnd(); ?>
