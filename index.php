<?php
/**
 * MediCore HMS — Authentication Page (Login & Registration)
 * 
 * This is the public entry point for user authentication. Features:
 * - Doctor registration with email validation and duplicate checking
 * - Secure login with password hashing verification
 * - Account status validation (pending vs active)
 * - Automatic session generation with user context
 * - Role-specific session data (includes doctor_id for doctors)
 * - Transaction rollback on registration errors
 * 
 * POST Actions:
 * - 'register_doctor': Creates new doctor account pending admin approval
 * - 'login': Authenticates user and starts session
 * 
 * GET: Displays authentication UI (login form / registration modal)
 */

require_once __DIR__ . '/includes/db.php';
session_start();

// Redirect already-authenticated users to dashboard
if (isset($_SESSION['user_id'])) { header('Location: /hms/dashboard.php'); exit; }

$error = '';
$success = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'login';
    
    /**
     * Doctor Registration Handler
     * 
     * Validates input, checks for duplicate emails, creates user account
     * with hashed password, then creates associated doctor profile.
     * Requires admin approval before login is allowed.
     */
    if ($action === 'register_doctor') {
        $fname = trim($_POST['firstname'] ?? '');
        $lname = trim($_POST['lastname'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['password'] ?? '');
        $spec  = trim($_POST['specialization'] ?? '');
        $sched = trim($_POST['schedule'] ?? '');

        // Check if email exists
        $chk = getDB()->prepare("SELECT id FROM users WHERE email=? LIMIT 1");
        $chk->execute([$email]);
        if ($chk->fetch()) {
            $error = 'This email is already registered.';
        } else {
            $db = getDB();
            $db->beginTransaction();
            try {
                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $db->prepare("INSERT INTO users (firstname, lastname, email, password, role, status) VALUES (?,?,?,?,'doctor','pending')")
                   ->execute([$fname, $lname, $email, $hash]);
                $uid = $db->lastInsertId();
                $db->prepare("INSERT INTO doctors (user_id, specialization, schedule) VALUES (?,?,?)")
                   ->execute([$uid, $spec, $sched]);
                $db->commit();
                $success = 'Account created! Please wait for Admin approval.';
            } catch (Exception $e) {
                $db->rollBack();
                $error = 'Registration failed. Please try again.';
            }
        }
    } else {
        /**
         * User Login Handler
         * 
         * Authenticates with email and password. On success:
         * - Regenerates session ID for security
         * - Stores user context (id, name, initials, role)
         * - For doctors: Retrieves and stores doctor_id for clinical operations
         * 
         * Fails if account status is 'pending' (awaiting admin approval)
         */
        $email = trim($_POST['email'] ?? '');
        $pass  = trim($_POST['password'] ?? '');
        $s = getDB()->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
        $s->execute([$email]);
        $user = $s->fetch();
        if ($user && password_verify($pass, $user['password'])) {
            if ($user['status'] !== 'active') {
                $error = 'Your account is pending Admin approval.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id']   = $user['id'];
                $_SESSION['user_name'] = $user['firstname'] . ' ' . $user['lastname'];
                $_SESSION['initials']  = strtoupper($user['firstname'][0] . $user['lastname'][0]);
                $_SESSION['role']      = $user['role'];
                if ($user['role'] === 'doctor') {
                    $ds = getDB()->prepare("SELECT id FROM doctors WHERE user_id=?");
                    $ds->execute([$user['id']]);
                    $doc = $ds->fetch();
                    $_SESSION['doctor_id'] = $doc ? $doc['id'] : null;
                }
                header('Location: /hms/dashboard.php'); exit;
            }
        } else {
            $error = 'Invalid email or password. Please try again.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sign In — MediCore HMS</title>
  <link href="https://fonts.googleapis.com/css2?family=Fraunces:ital,opsz,wght@0,9..144,300;0,9..144,400;1,9..144,300&family=DM+Sans:opsz,wght@9..40,300;9..40,400;9..40,500;9..40,600;9..40,700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="/hms/assets/css/style.css">
  <style>
/* ═══════════════════════════════════════════════════════════
   LOGIN PAGE
═══════════════════════════════════════════════════════════ */

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

:root {
  --ink:       #0b1220;
  --ink2:      #1c2d42;
  --slate:     #3a5068;
  --muted:     #6b8398;
  --subtle:    #a8bccb;
  --border:    #dce4ed;
  --surface:   #f3f7fa;
  --white:     #ffffff;
  --accent:    #0d9276;
  --accent-d:  #0a735e;
  --accent-g:  rgba(13,146,118,.14);
  --blue:      #1e50b3;
  --red:       #c0392b;
  --red-bg:    #fdf1f0;
  --red-b:     #f5c5bf;
  --green:     #176e3e;
  --r:         10px;
  --font-d:    'Fraunces', Georgia, serif;
  --font-b:    'DM Sans', system-ui, sans-serif;
}

html, body {
  height: 100%;
  font-family: var(--font-b);
  background: var(--white);
  color: var(--ink);
  -webkit-font-smoothing: antialiased;
}

/* ── LAYOUT ─────────────────────────────────────────────── */
.login-page {
  display: grid;
  grid-template-columns: 1fr 1fr;
  min-height: 100vh;
}

/* ── LEFT PANEL ─────────────────────────────────────────── */
.lp-left {
  background: var(--ink);
  display: flex;
  flex-direction: column;
  justify-content: space-between;
  padding: 48px 56px;
  position: relative;
  overflow: hidden;
}

/* Decorative radial glow */
.lp-left::before {
  content: '';
  position: absolute;
  width: 600px; height: 600px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(13,146,118,.18) 0%, transparent 65%);
  top: -120px; right: -160px;
  pointer-events: none;
}
.lp-left::after {
  content: '';
  position: absolute;
  width: 400px; height: 400px;
  border-radius: 50%;
  background: radial-gradient(circle, rgba(30,80,179,.14) 0%, transparent 65%);
  bottom: 40px; left: -100px;
  pointer-events: none;
}

/* Grid overlay */
.lp-grid {
  position: absolute;
  inset: 0;
  background-image:
    linear-gradient(rgba(255,255,255,.028) 1px, transparent 1px),
    linear-gradient(90deg, rgba(255,255,255,.028) 1px, transparent 1px);
  background-size: 48px 48px;
  pointer-events: none;
}

.lp-body { position: relative; z-index: 1; }
.lp-footer { position: relative; z-index: 1; }

/* Brand */
.lp-brand {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 80px;
}
.lp-brand-icon {
  width: 44px; height: 44px;
  background: linear-gradient(135deg, var(--accent), var(--accent-d));
  border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  box-shadow: 0 4px 16px rgba(13,146,118,.4);
  flex-shrink: 0;
}
.lp-brand-icon svg { width: 22px; height: 22px; color: white; }
.lp-brand-name {
  font-family: var(--font-d);
  font-size: 20px;
  font-weight: 400;
  color: white;
  letter-spacing: -.2px;
  line-height: 1.1;
}
.lp-brand-tag {
  font-size: 10px;
  color: rgba(255,255,255,.28);
  letter-spacing: 2.5px;
  text-transform: uppercase;
  margin-top: 3px;
}

/* Headline */
.lp-headline {
  font-family: var(--font-d);
  font-size: 52px;
  font-weight: 300;
  color: white;
  line-height: 1.08;
  letter-spacing: -.5px;
  margin-bottom: 22px;
}
.lp-headline em {
  font-style: italic;
  color: #5ec9b8;
}
.lp-sub {
  font-size: 15px;
  color: rgba(255,255,255,.45);
  line-height: 1.75;
  max-width: 380px;
  font-weight: 300;
}

/* Feature list */
.lp-features { margin-top: 44px; display: flex; flex-direction: column; gap: 13px; }
.lp-feat {
  display: flex;
  align-items: center;
  gap: 13px;
  color: rgba(255,255,255,.62);
  font-size: 13.5px;
}
.lp-feat-dot {
  width: 24px; height: 24px;
  border-radius: 50%;
  background: rgba(13,146,118,.16);
  border: 1px solid rgba(13,146,118,.35);
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
  color: #5ec9b8;
}
.lp-feat-dot svg { width: 11px; height: 11px; }

/* Stats footer */
.lp-stats {
  display: flex;
  align-items: center;
  gap: 20px;
  padding-top: 28px;
  border-top: 1px solid rgba(255,255,255,.08);
}
.lp-stat-val {
  font-family: var(--font-d);
  font-size: 22px;
  color: white;
  font-weight: 400;
  line-height: 1;
}
.lp-stat-lbl {
  font-size: 11px;
  color: rgba(255,255,255,.28);
  margin-top: 4px;
  font-weight: 400;
}
.lp-divider { width: 1px; height: 38px; background: rgba(255,255,255,.09); }

/* ── RIGHT PANEL ─────────────────────────────────────────── */
.lp-right {
  background: white;
  display: flex;
  align-items: center;
  justify-content: center;
  padding: 60px 72px;
}

.lp-form-wrap { width: 100%; max-width: 400px; }

/* Form header */
.lp-form-hd { margin-bottom: 34px; }
.lp-form-hd h1 {
  font-family: var(--font-d);
  font-size: 32px;
  font-weight: 400;
  color: var(--ink);
  letter-spacing: -.6px;
  margin-bottom: 8px;
}
.lp-form-hd p {
  font-size: 14px;
  color: var(--muted);
  line-height: 1.5;
}

/* Demo box */
.demo-box {
  background: var(--surface);
  border: 1px solid var(--border);
  border-radius: var(--r);
  padding: 14px 16px;
  margin-bottom: 28px;
}
.demo-box-lbl {
  font-size: 10.5px;
  font-weight: 700;
  color: var(--subtle);
  text-transform: uppercase;
  letter-spacing: 2px;
  margin-bottom: 10px;
}
.demo-cred {
  display: flex;
  align-items: center;
  gap: 9px;
  padding: 8px 11px;
  border-radius: 7px;
  background: white;
  border: 1.5px solid var(--border);
  margin-bottom: 7px;
  cursor: pointer;
  transition: border-color .15s, box-shadow .15s, background .15s;
  user-select: none;
}
.demo-cred:last-child { margin-bottom: 0; }
.demo-cred:hover {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-g);
  background: #f9fefd;
}
.demo-role {
  font-size: 10px;
  font-weight: 700;
  color: white;
  padding: 2px 8px;
  border-radius: 4px;
  text-transform: uppercase;
  letter-spacing: .5px;
  flex-shrink: 0;
}
.role-admin  { background: var(--blue); }
.role-doctor { background: var(--accent); }
.demo-email { font-size: 12.5px; color: var(--ink2); font-weight: 500; flex: 1; }
.demo-pass  { font-size: 12px; color: var(--subtle); font-family: 'Courier New', monospace; }
.demo-fill-hint {
  display: flex; align-items: center; gap: 5px;
  font-size: 11px; color: var(--subtle);
  margin-left: auto;
  flex-shrink: 0;
}
.demo-fill-hint svg { width: 11px; height: 11px; }

/* Error */
.lp-error {
  display: flex;
  align-items: flex-start;
  gap: 10px;
  background: var(--red-bg);
  border: 1px solid var(--red-b);
  border-radius: var(--r);
  padding: 12px 15px;
  margin-bottom: 22px;
  font-size: 13.5px;
  color: var(--red);
  font-weight: 500;
  line-height: 1.4;
}
.lp-error svg { width: 16px; height: 16px; flex-shrink: 0; margin-top: 1px; }

/* Field */
.lp-field { margin-bottom: 18px; }
.lp-field label {
  display: block;
  font-size: 11.5px;
  font-weight: 600;
  color: var(--slate);
  text-transform: uppercase;
  letter-spacing: .8px;
  margin-bottom: 7px;
}
.lp-input-wrap { position: relative; }
.lp-input-wrap input {
  display: block;
  width: 100%;
  padding: 11px 14px 11px 42px;
  border: 1.5px solid var(--border);
  border-radius: var(--r);
  font-family: var(--font-b);
  font-size: 14px;
  color: var(--ink);
  background: white;
  outline: none;
  transition: border-color .18s, box-shadow .18s;
  line-height: 1.4;
}
.lp-input-wrap input:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px var(--accent-g);
}
.lp-input-wrap input::placeholder { color: var(--subtle); font-weight: 300; }
.lp-input-icon {
  position: absolute;
  left: 13px;
  top: 50%;
  transform: translateY(-50%);
  color: var(--subtle);
  pointer-events: none;
  display: flex; align-items: center;
}
.lp-input-icon svg { width: 16px; height: 16px; }

/* Password toggle */
.lp-input-wrap.has-pw-toggle input { padding-right: 44px; }
.pw-toggle-btn {
  position: absolute;
  right: 11px;
  top: 50%;
  transform: translateY(-50%);
  background: none;
  border: none;
  cursor: pointer;
  color: var(--muted);
  padding: 5px;
  border-radius: 6px;
  display: flex; align-items: center;
  transition: color .15s, background .15s;
}
.pw-toggle-btn:hover { color: var(--ink); background: var(--surface); }
.pw-toggle-btn svg { width: 16px; height: 16px; display: block; }

/* Submit */
.lp-submit {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 8px;
  width: 100%;
  margin-top: 10px;
  padding: 13px 20px;
  background: linear-gradient(135deg, #0d9276 0%, #0a735e 100%);
  color: #ffffff !important;
  border: none;
  border-radius: var(--r);
  font-family: var(--font-b);
  font-size: 14.5px;
  font-weight: 600;
  letter-spacing: .2px;
  cursor: pointer;
  box-shadow: 0 4px 18px rgba(13,146,118,.32);
  transition: transform .18s, box-shadow .18s, opacity .18s;
  text-decoration: none;
}
.lp-submit:hover {
  transform: translateY(-1px);
  box-shadow: 0 8px 26px rgba(13,146,118,.4);
  background: linear-gradient(135deg, #0fa884 0%, #0d9276 100%);
  color: #ffffff !important;
}
.lp-submit:active { transform: none; box-shadow: 0 2px 8px rgba(13,146,118,.2); }
.lp-submit svg { width: 16px; height: 16px; flex-shrink: 0; }
.lp-submit.loading { opacity: .75; pointer-events: none; }
.lp-spinner {
  width: 16px; height: 16px;
  border: 2px solid rgba(255,255,255,.35);
  border-top-color: white;
  border-radius: 50%;
  flex-shrink: 0;
  animation: lp-spin .65s linear infinite;
}
@keyframes lp-spin { to { transform: rotate(360deg); } }

/* Footer note */
.lp-note {
  margin-top: 24px;
  text-align: center;
  font-size: 12px;
  color: var(--subtle);
  line-height: 1.7;
}
.lp-note svg { width: 12px; height: 12px; vertical-align: middle; margin-right: 3px; }

/* Page entrance animation */
@keyframes lp-fade  { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
@keyframes lp-slide { from { opacity:0; transform:translateX(20px); } to { opacity:1; transform:none; } }
.lp-left  { animation: lp-fade .4s ease both; }
.lp-right { animation: lp-slide .45s cubic-bezier(.16,1,.3,1) .07s both; }

/* Focus ring */
:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; border-radius: 4px; }
  </style>
</head>
<body>
<div class="login-page">

  <!-- ════════════ LEFT PANEL ════════════ -->
  <div class="lp-left">
    <div class="lp-grid"></div>

    <div class="lp-body">
      <!-- Brand -->
      <div class="lp-brand">
        <div class="lp-brand-icon">
          <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M22 12h-4l-3 9L9 3l-3 9H2"/>
          </svg>
        </div>
        <div>
          <div class="lp-brand-name">MediCore</div>
          <div class="lp-brand-tag">Hospital Management System</div>
        </div>
      </div>

      <!-- Headline -->
      <h1 class="lp-headline">
        Modern care,<br>
        <em>seamlessly</em><br>
        managed.
      </h1>
      <p class="lp-sub">
        A unified platform for patient records, doctor consultations,
        and prescription management — built for modern healthcare teams.
      </p>

      <!-- Features -->
      <div class="lp-features">
        <?php
        $feats = [
          'Complete patient medical histories',
          'Digital prescription management',
          'Role-based access &amp; audit logs',
          'Conflict-free appointment scheduling',
        ];
        foreach ($feats as $f): ?>
        <div class="lp-feat">
          <div class="lp-feat-dot">
            <svg viewBox="0 0 12 12" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M2.5 6l2.5 2.5 4.5-4.5"/>
            </svg>
          </div>
          <?= $f ?>
        </div>
        <?php endforeach; ?>
      </div>
    </div><!-- /.lp-body -->

  </div><!-- /.lp-left -->

  <!-- ════════════ RIGHT PANEL ════════════ -->
  <div class="lp-right">
    <div class="lp-form-wrap">

      <div class="lp-form-hd">
        <h1>Welcome!</h1>
        <p>Sign in to your MediCore account to continue</p>
      </div>


      <!-- Error -->
      <?php if ($error): ?>
      <div class="lp-error">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <circle cx="10" cy="10" r="8"/>
          <path d="M10 6v4.5M10 13.5v.5"/>
        </svg>
        <?= htmlspecialchars($error) ?>
      </div>
      <?php endif; ?>

      <?php if ($success): ?>
      <div class="lp-error" style="background:var(--accent-g);border-color:var(--accent);color:var(--accent-d)">
        <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <path d="M5 10l3 3 7-7"/>
        </svg>
        <?= htmlspecialchars($success) ?>
      </div>
      <?php endif; ?>

      <!-- Form -->
      <form method="POST" id="loginForm" novalidate>

        <div class="lp-field">
          <label for="f-email">Email address</label>
          <div class="lp-input-wrap">
            <span class="lp-input-icon">
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="2" y="5" width="16" height="12" rx="1.5"/>
                <path d="M2 7l8 5.5L18 7"/>
              </svg>
            </span>
            <input
              type="email"
              id="f-email"
              name="email"
              placeholder="you@hospital.com"
              value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
              autocomplete="email"
              required
            >
          </div>
        </div>

        <div class="lp-field">
          <label for="f-password">Password</label>
          <div class="lp-input-wrap has-pw-toggle">
            <span class="lp-input-icon">
              <svg viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <rect x="4" y="9" width="12" height="9" rx="1.5"/>
                <path d="M7 9V6.5a3 3 0 016 0V9"/>
              </svg>
            </span>
            <input
              type="password"
              id="f-password"
              name="password"
              placeholder="Enter your password"
              autocomplete="current-password"
              required
            >
            <button type="button" class="pw-toggle-btn" id="pwBtn" aria-label="Toggle password visibility">
              <!-- Eye open -->
              <svg id="eyeOpen" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                <path d="M2.5 10S5.5 4.5 10 4.5 17.5 10 17.5 10 14.5 15.5 10 15.5 2.5 10 2.5 10z"/>
                <circle cx="10" cy="10" r="2.5"/>
              </svg>
              <!-- Eye closed -->
              <svg id="eyeClosed" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" style="display:none">
                <path d="M3 3l14 14M8.5 8.7A2.5 2.5 0 0013.3 13M6.1 5.8C4.4 7 3 10 3 10s3 5.5 7 5.5c1.4 0 2.7-.5 3.8-1.3"/>
                <path d="M10 4.5c4.5 0 7.5 5.5 7.5 5.5s-.7 1.5-2 3"/>
              </svg>
            </button>
          </div>
        </div>

        <!-- SUBMIT BUTTON -->
        <button type="submit" class="lp-submit" id="submitBtn">
          <span id="btnText">Sign in to MediCore</span>
          <svg id="btnIcon" viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M3 8h10M9.5 4.5L13 8l-3.5 3.5"/>
          </svg>
        </button>

      </form>

      <div style="text-align:center;margin-top:20px;font-size:14px;color:var(--muted)">
        New doctor? <a href="#" id="showRegister" style="color:var(--accent);font-weight:600;text-decoration:none">Create a doctor account</a>
      </div>

      <p class="lp-note">
        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="7" width="12" height="8" rx="1.5"/>
          <path d="M5 7V5a3 3 0 016 0v2"/>
        </svg>
        Protected by role-based access control. All sessions are encrypted and logged.
      </p>

    </div><!-- /.lp-form-wrap -->
  </div><!-- /.lp-right -->

</div><!-- /.login-page -->

<!-- Doctor Registration Modal -->
<div id="registerModal" style="display:none;position:fixed;inset:0;background:rgba(11,18,32,0.85);backdrop-filter:blur(8px);z-index:100;align-items:center;justify-content:center;padding:20px">
  <div class="card" style="width:100%;max-width:500px;background:white;border-radius:12px;overflow:hidden;animation:lp-fade 0.3s ease;box-shadow:0 20px 50px rgba(0,0,0,0.3)">
    <div style="padding:24px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center">
      <h2 style="font-family:var(--font-d);font-size:24px;color:var(--ink);margin:0">Doctor Registration</h2>
      <button id="closeRegister" style="background:none;border:none;cursor:pointer;color:var(--muted);font-size:28px;line-height:1">&times;</button>
    </div>
    <form method="POST" style="padding:24px">
      <input type="hidden" name="action" value="register_doctor">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:15px;margin-bottom:15px">
        <div class="lp-field" style="margin:0"><label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">First Name</label><input type="text" name="firstname" required style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none"></div>
        <div class="lp-field" style="margin:0"><label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">Last Name</label><input type="text" name="lastname" required style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none"></div>
      </div>
      <div class="lp-field" style="margin-bottom:15px"><label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">Email Address</label><input type="email" name="email" required style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none"></div>
      <div class="lp-field" style="margin-bottom:15px"><label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">Password</label><input type="password" name="password" required style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none"></div>
      <div class="lp-field" style="margin-bottom:15px">
        <label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">Specialization</label>
        <select name="specialization" style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none;background:white">
          <option>General Practitioner</option><option>Cardiologist</option><option>Dermatologist</option>
          <option>Neurologist</option><option>Pediatrician</option><option>Orthopedic Surgeon</option>
        </select>
      </div>
      <div class="lp-field" style="margin-bottom:20px"><label style="display:block;font-size:11px;font-weight:600;color:var(--slate);text-transform:uppercase;margin-bottom:5px">Schedule</label><input type="text" name="schedule" placeholder="Mon-Fri, 8AM-5PM" required style="width:100%;padding:10px;border:1.5px solid var(--border);border-radius:8px;outline:none"></div>
      <button type="submit" class="lp-submit">Sign Up as Doctor</button>
    </form>
  </div>
</div>

<script>

// Modal handling
const regModal = document.getElementById('registerModal');
document.getElementById('showRegister').onclick = (e) => { e.preventDefault(); regModal.style.display = 'flex'; };
document.getElementById('closeRegister').onclick = () => { regModal.style.display = 'none'; };
window.onclick = (e) => { if(e.target == regModal) regModal.style.display = 'none'; };

// Password visibility toggle
(function () {
  var btn    = document.getElementById('pwBtn');
  var input  = document.getElementById('f-password');
  var eyeO   = document.getElementById('eyeOpen');
  var eyeC   = document.getElementById('eyeClosed');
  btn.addEventListener('click', function () {
    var show = input.type === 'password';
    input.type = show ? 'text' : 'password';
    eyeO.style.display = show ? 'none' : '';
    eyeC.style.display = show ? '' : 'none';
  });
})();

// Loading state on submit
document.getElementById('loginForm').addEventListener('submit', function () {
  var btn  = document.getElementById('submitBtn');
  var text = document.getElementById('btnText');
  var icon = document.getElementById('btnIcon');
  btn.classList.add('loading');
  text.textContent = 'Signing in…';
  icon.outerHTML   = '<div class="lp-spinner"></div>';
});
</script>
</body>
</html>