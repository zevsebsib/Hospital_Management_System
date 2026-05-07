<?php
/**
 * Layout Template Functions
 * 
 * Provides the main HTML layout framework with sidebar navigation,
 * top bar, and flash message display for authenticated pages.
 */

/**
 * Render the Main Layout Template
 * 
 * Outputs the complete HTML layout structure including:
 * - Sidebar with navigation menu (role-aware)
 * - Top bar with page title and user info
 * - Flash message toast notification
 * - Integration with Bootstrap and custom CSS
 * 
 * Must be paired with renderLayoutEnd() to close tags.
 * 
 * @param string $active The currently active navigation item key (e.g., 'dashboard', 'doctors')
 * @param string $title The page title to display
 * @param string $sub Optional subtitle (default: auto-generated from $active)
 * @return void Outputs HTML directly
 */
function renderLayout(string $active, string $title, string $sub = ''): void {
    $u = currentUser();
    $flash = getFlash();
    $subs = [
        'dashboard'     => 'Overview &amp; statistics',
        'doctors'       => 'Manage physicians',
        'patients'      => 'Patient registry',
        'appointments'  => 'Schedule management',
        'consultations' => 'Clinical records',
        'prescriptions' => 'Medication management',
    ];
    $pageSub = $sub ?: ($subs[$active] ?? '');

    // Count scheduled appointments for badge
    $db = getDB();
    $apptCount = $db->query("SELECT COUNT(*) FROM appointments WHERE status='Scheduled'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($title) ?> — MediCore HMS</title>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link href="https://fonts.googleapis.com/css2?family=Instrument+Serif:ital@0;1&family=Geist:wght@300;400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/hms/assets/css/style.css">
</head>
<body>

<?php if ($flash): ?>
<div class="position-fixed top-0 end-0 p-3" style="z-index:9999">
  <div class="toast show" role="alert" style="background:white;border:1px solid var(--gray-200);border-radius:12px;box-shadow:var(--shadow-lg);min-width:300px;overflow:hidden">
    <div style="height:3px;background:<?= $flash['type']==='success' ? 'var(--green)' : 'var(--red)' ?>"></div>
    <div class="d-flex align-items-center gap-2 p-3">
      <span style="font-size:18px"><?= $flash['type']==='success' ? '✅' : '❌' ?></span>
      <span style="font-size:13.5px;color:var(--gray-700);font-weight:500"><?= e($flash['msg']) ?></span>
      <button type="button" class="btn-close ms-auto" data-bs-dismiss="toast"></button>
    </div>
  </div>
</div>
<?php endif; ?>

<div class="shell">
  <!-- SIDEBAR -->
  <aside class="sidebar">
    <div class="sb-logo">
      <div class="sb-logo-row">
        <div class="sb-logo-icon">🏥</div>
        <div>
          <div class="sb-logo-name">MediCore</div>
          <div class="sb-logo-sub">HMS Platform</div>
        </div>
      </div>
    </div>
    <nav class="sb-nav">
      <div class="sb-section">
        <div class="sb-label">Overview</div>
        <a href="/hms/dashboard.php" class="nav-link <?= $active==='dashboard'?'active':'' ?>">
          <span class="nav-icon">⬛</span> Dashboard
        </a>
      </div>

      <?php if (isAdmin()): ?>
      <div class="sb-section">
        <div class="sb-label">Management</div>
        <a href="/hms/admin/doctors.php" class="nav-link <?= $active==='doctors'?'active':'' ?>">
          <span class="nav-icon">👨‍⚕️</span> Doctors
        </a>
        <a href="/hms/admin/patients.php" class="nav-link <?= $active==='patients'?'active':'' ?>">
          <span class="nav-icon">👥</span> Patients
        </a>
        <a href="/hms/admin/appointments.php" class="nav-link <?= $active==='appointments'?'active':'' ?>">
          <span class="nav-icon">📅</span> Appointments
          <?php if ($apptCount > 0): ?>
          <span class="nav-badge"><?= $apptCount ?></span>
          <?php endif; ?>
        </a>
      </div>
      <?php endif; ?>

      <div class="sb-sep"></div>
      <div class="sb-section">
        <div class="sb-label">Clinical</div>
        <a href="/hms/<?= isAdmin()?'admin':'doctor' ?>/consultations.php" class="nav-link <?= $active==='consultations'?'active':'' ?>">
          <span class="nav-icon">🩺</span> Consultations
        </a>
        <a href="/hms/<?= isAdmin()?'admin':'doctor' ?>/prescriptions.php" class="nav-link <?= $active==='prescriptions'?'active':'' ?>">
          <span class="nav-icon">💊</span> Prescriptions
        </a>
      </div>
    </nav>
    <div class="sb-footer">
      <div class="sb-user">
        <div class="sb-avatar"><?= e($u['initials']) ?></div>
        <div style="flex:1;min-width:0">
          <div class="sb-uname"><?= e($u['name']) ?></div>
          <div class="sb-urole"><?= ucfirst($u['role']) ?></div>
        </div>
        <a href="/hms/logout.php" class="sb-logout" title="Sign out">↩</a>
      </div>
    </div>
  </aside>

  <!-- MAIN -->
  <div class="main">
    <header class="topbar">
      <div>
        <div class="tb-title"><?= e($title) ?></div>
        <div class="tb-sub"><?= $pageSub ?></div>
      </div>
      <div class="tb-right">
        <span class="role-pill role-<?= $u['role'] ?>"><?= $u['role']==='admin'?'Administrator':'Doctor' ?></span>
        <span class="tb-date"><?= date('D, d M Y') ?></span>
      </div>
    </header>
    <div class="content">
<?php
}

/**
 * Close the Main Layout Template
 * 
 * Closes all HTML tags opened by renderLayout().
 * Must be called at the end of every authenticated page after page content.
 * 
 * @return void Outputs closing HTML tags
 */
function renderLayoutEnd(): void {
?>
    </div><!-- .content -->
  </div><!-- .main -->
</div><!-- .shell -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="/hms/assets/js/app.js"></script>
</body>
</html>
<?php
}
