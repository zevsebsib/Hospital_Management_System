<?php
// includes/functions.php
require_once __DIR__ . '/db.php';

function e(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES, 'UTF-8');
}

function getDashboardStats(): array {
    $db = getDB();
    return [
        'patients'      => $db->query("SELECT COUNT(*) FROM patients")->fetchColumn(),
        'doctors'       => $db->query("SELECT COUNT(*) FROM doctors")->fetchColumn(),
        'appointments'  => $db->query("SELECT COUNT(*) FROM appointments WHERE status='Scheduled'")->fetchColumn(),
        'prescriptions' => $db->query("SELECT COUNT(*) FROM prescriptions")->fetchColumn(),
    ];
}

function getAllDoctors(): array {
    return getDB()->query(
        "SELECT d.*, u.firstname, u.lastname, u.email, u.status
         FROM doctors d JOIN users u ON d.user_id = u.id ORDER BY u.lastname"
    )->fetchAll();
}

function getDoctorById(int $id): ?array {
    $s = getDB()->prepare("SELECT d.*, u.firstname, u.lastname, u.email, u.status FROM doctors d JOIN users u ON d.user_id=u.id WHERE d.id=?");
    $s->execute([$id]); return $s->fetch() ?: null;
}

function getDoctorByUserId(int $uid): ?array {
    $s = getDB()->prepare("SELECT * FROM doctors WHERE user_id=?");
    $s->execute([$uid]); return $s->fetch() ?: null;
}

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

function getPatientById(int $id): ?array {
    $s = getDB()->prepare("SELECT * FROM patients WHERE id=?");
    $s->execute([$id]); return $s->fetch() ?: null;
}

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

function checkConflict(int $did, string $date, string $time, int $exclude = 0): bool {
    $s = getDB()->prepare("SELECT COUNT(*) FROM appointments WHERE doctor_id=? AND appointment_date=? AND appointment_time=? AND status='Scheduled' AND id!=?");
    $s->execute([$did, $date, $time, $exclude]);
    return (int)$s->fetchColumn() > 0;
}

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

function getConsultationById(int $id): ?array {
    $s = getDB()->prepare(
        "SELECT c.*, p.firstname AS p_fn, p.lastname AS p_ln, u.firstname AS d_fn, u.lastname AS d_ln, a.doctor_id, a.patient_id
         FROM consultations c JOIN appointments a ON c.appointment_id=a.id
         JOIN patients p ON a.patient_id=p.id JOIN doctors d ON a.doctor_id=d.id JOIN users u ON d.user_id=u.id WHERE c.id=?"
    );
    $s->execute([$id]); return $s->fetch() ?: null;
}

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
