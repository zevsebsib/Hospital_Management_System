<?php
/**
 * Core Business Logic and Database Query Functions
 * 
 * This module contains all core functions for managing doctors, patients,
 * appointments, consultations, prescriptions, and dashboard statistics.
 */

require_once __DIR__ . '/db.php';

/**
 * HTML Entity Escape Function
 * 
 * Escapes HTML special characters to prevent XSS attacks.
 * Use this on all user-facing data output.
 * 
 * @param string $s The string to escape
 * @return string Escaped string safe for HTML output
 */
function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

/**
 * Get Dashboard Statistics Summary
 * 
 * Retrieves count of total patients, doctors, scheduled appointments,
 * and total prescriptions from the database.
 * 
 * @return array Associative array with keys: patients, doctors, appointments, prescriptions
 */
function getDashboardStats(): array {
    $db = getDB();
    return [
        'patients'      => $db->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
        'doctors'       => $db->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
        'appointments'  => $db->query("SELECT COUNT(*) FROM appointments WHERE status='Scheduled'")->fetchColumn(),
        'prescriptions' => $db->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn(),
    ];
}

/**
 * Get All Doctors with User Information
 * 
 * Retrieves complete doctor profiles including user details,
 * ordered by last name.
 * 
 * @return array Array of doctor records with user info (firstname, lastname, email, status)
 */
function getAllDoctors(): array {
    return getDB()->query(
        "SELECT d.*, u.firstname, u.lastname, u.email, u.status
         FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.lastname"
    )->fetchAll();
}

/**
 * Get Doctor by Doctor ID
 * 
 * Retrieves a single doctor record with user information by doctor ID.
 * 
 * @param int $id The doctor ID to retrieve
 * @return ?array Doctor array with user info, or null if not found
 */
function getDoctorById(int $id): ?array {
    $s = getDB()->prepare("SELECT d.*, u.firstname, u.lastname, u.email, u.status FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.id=?");
    $s->execute([$id]); return $s->fetch() ?: null;
}

/**
 * Get Doctor by User ID
 * 
 * Retrieves doctor profile for a given user account ID.
 * 
 * @param int $uid The user ID to lookup
 * @return ?array Doctor array, or null if user is not a doctor
 */
function getDoctorByUserId(int $uid): ?array {
    $s = getDB()->prepare("SELECT * FROM doctors WHERE user_id=?");
    $s->execute([$uid]); return $s->fetch() ?: null;
}

/**
 * Get All Patients with Optional Search
 * 
 * Retrieves all patient records, optionally filtered by name search.
 * Results ordered by last name.
 * 
 * @param string $search Optional search term for patient name (default: empty)
 * @return array Array of patient records matching search criteria
 */
function getAllPatients(string $search = ''): array {
    $db = getDB();
    if ($search) {
        $s = $db->prepare("SELECT * FROM patients WHERE CONCAT(firstname,' ',lastname) LIKE ? ORDER BY lastname");
        $s->execute(["%$search%"]);
    } else {
        $s = $db->query("SELECT * FROM patients ORDER BY lastname");
    }
    return $s->fetchAll();
}

/**
 * Get Patient by Patient ID
 * 
 * Retrieves a single patient record by ID.
 * 
 * @param int $id The patient ID to retrieve
 * @return ?array Patient record, or null if not found
 */
function getPatientById(int $id): ?array {
    $s = getDB()->prepare("SELECT * FROM patients WHERE id=?");
    $s->execute([$id]); return $s->fetch() ?: null;
}

/**
 * Get Appointments with Optional Filtering
 * 
 * Retrieves appointments with related patient and doctor information.
 * Can filter by status (e.g., 'Scheduled', 'Completed') and/or doctor ID.
 * 
 * @param string $status Optional status filter (default: all statuses)
 * @param int $doctorId Optional doctor ID filter (default: all doctors)
 * @return array Array of appointment records with patient and doctor details
 */
function getAppointments(string $status = '', int $doctorId = 0): array {
    $sql = "SELECT a.*, p.firstname AS p_fn, p.lastname AS p_ln,
                   u.firstname AS d_fn, u.lastname AS d_ln, d.specialization
            FROM appointments a
            JOIN patients p ON a.patient_id = p.id
            JOIN doctors  d ON a.doctor_id  = d.id
            JOIN users    u ON d.user_id    = u.id WHERE 1=1";
    $params = [];
    if ($status)   { $sql .= " AND a.status=?";    $params[] = $status; }
    if ($doctorId) { $sql .= " AND a.doctor_id=?"; $params[] = $doctorId; }
    $sql .= " ORDER BY a.appointment_date, a.appointment_time";
    $s = getDB()->prepare($sql); $s->execute($params); return $s->fetchAll();
}

/**
 * Check for Appointment Time Conflicts
 * 
 * Determines if a doctor already has a scheduled appointment at the given date/time.
 * Useful for preventing double-bookings.
 * 
 * @param int $did The doctor ID to check
 * @param string $date Appointment date (YYYY-MM-DD format)
 * @param string $time Appointment time (HH:MM format)
 * @param int $exclude Optional appointment ID to exclude from conflict check (default: 0)
 * @return bool True if conflict exists, false otherwise
 */
function checkConflict(int $did, string $date, string $time, int $exclude = 0): bool {
    $s = getDB()->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status='Scheduled' AND id!=?");
    $s->execute([$did, $date, $time, $exclude]);
    return (int)$s->fetchColumn() > 0;
}

/**
 * Get Consultations with Optional Doctor Filter
 * 
 * Retrieves consultation records with associated patient and doctor information.
 * Can optionally filter by doctor ID for doctor-specific queries.
 * 
 * @param int $doctorId Optional doctor ID filter (default: all consultations)
 * @return array Array of consultation records ordered by date (newest first)
 */
function getConsultations(int $doctorId = 0): array {
    $sql = "SELECT c.*, p.firstname AS p_fn, p.lastname AS p_ln, u.firstname AS d_fn, u.lastname AS d_ln
            FROM consultations c
            JOIN appointments a ON c.appointment_id = a.id
            JOIN patients     p ON a.patient_id     = p.id
            JOIN doctors      d ON a.doctor_id      = d.id
            JOIN users        u ON d.user_id        = u.id WHERE 1=1";
    $params = [];
    if ($doctorId) { $sql .= " AND a.doctor_id=?"; $params[] = $doctorId; }
    $sql .= " ORDER BY c.consultation_date DESC";
    $s = getDB()->prepare($sql); $s->execute($params); return $s->fetchAll();
}

/**
 * Get Single Consultation with Full Details
 * 
 * Retrieves a specific consultation record with all related patient,
 * doctor, and appointment information.
 * 
 * @param int $id The consultation ID to retrieve
 * @return ?array Consultation record with related data, or null if not found
 */
function getConsultationById(int $id): ?array {
    $s = getDB()->prepare(
        "SELECT c.*, p.firstname AS p_fn, p.lastname AS p_ln, u.firstname AS d_fn, u.lastname AS d_ln, a.doctor_id, a.patient_id
         FROM consultations c JOIN appointments a ON c.appointment_id=a.id
         JOIN patients p ON a.patient_id=p.id JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id WHERE c.id=?"
    );
    $s->execute([$id]); return $s->fetch() ?: null;
}

/**
 * Get Prescriptions with Optional Filtering
 * 
 * Retrieves prescription records with patient and doctor information.
 * Can filter by consultation ID and/or doctor ID.
 * 
 * @param int $consultId Optional consultation ID filter (default: all consultations)
 * @param int $doctorId Optional doctor ID filter (default: all doctors)
 * @return array Array of prescription records ordered by ID (newest first)
 */
function getPrescriptions(int $consultId = 0, int $doctorId = 0): array {
    $sql = "SELECT rx.*, p.firstname AS p_fn, p.lastname AS p_ln, u.firstname AS d_fn, u.lastname AS d_ln
            FROM prescriptions rx
            JOIN consultations c ON rx.consultation_id = c.id
            JOIN appointments  a ON c.appointment_id   = a.id
            JOIN patients      p ON a.patient_id       = p.id
            JOIN doctors       d ON a.doctor_id        = d.id
            JOIN users         u ON d.user_id          = u.id WHERE 1=1";
    $params = [];
    if ($consultId) { $sql .= " AND rx.consultation_id=?"; $params[] = $consultId; }
    if ($doctorId)  { $sql .= " AND a.doctor_id=?";      $params[] = $doctorId; }
    $sql .= " ORDER BY rx.id DESC";
    $s = getDB()->prepare($sql); $s->execute($params); return $s->fetchAll();
}

/**
 * Get Complete Patient Medical History
 * 
 * Retrieves all consultations for a patient with associated prescriptions.
 * Provides a complete medical record view for a patient.
 * 
 * @param int $pid The patient ID to get history for
 * @return array Array of consultations (newest first) with nested prescription arrays
 */
function getPatientHistory(int $pid): array {
    $s = getDB()->prepare(
        "SELECT c.*, u.firstname AS d_fn, u.lastname AS d_ln
         FROM consultations c JOIN appointments a ON c.appointment_id=a.id
         JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id
         WHERE a.patient_id=? ORDER BY c.consultation_date DESC"
    );
    $s->execute([$pid]); $rows = $s->fetchAll();
    foreach ($rows as &$row) {
        $rx = getDB()->prepare("SELECT * FROM prescriptions WHERE consultation_id=?");
        $rx->execute([$row['id']]); $row['prescriptions'] = $rx->fetchAll();
    }
    return $rows;
}
