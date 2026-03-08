<?php
// includes/functions.php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

/**
 * Generate CSRF token dan simpan di session
 */
function generateCsrfToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verifikasi CSRF token
 */
function verifyCsrfToken($token) {
    if (!isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
        return false;
    }
    return true;
}

/**
 * Kirim email notifikasi pendaftaran
 */
function sendRegistrationEmail($email, $name, $event_name) {
    $subject  = "Konfirmasi Pendaftaran Event BEM Fasilkom Unsika";
    $message  = "Halo $name,\n\n";
    $message .= "Terima kasih telah mendaftar pada event \"$event_name\".\n";
    $message .= "Pendaftaran Anda sedang diproses.\n\n";
    $message .= "Salam,\nBEM Fasilkom Unsika";
    $headers  = "From: no-reply@bemfasilkom.unsika.ac.id\r\n";
    return @mail($email, $subject, $message, $headers);
}

/**
 * Rate limiting: maksimal 3 pendaftaran per IP dalam 1 jam
 */
function checkRateLimit($ip, $conn) {
    $check = $conn->query("SHOW COLUMNS FROM registrations LIKE 'ip_address'");
    if (!$check || $check->num_rows === 0) {
        // Kolom belum ada — buat dulu otomatis, lalu izinkan request
        $conn->query("ALTER TABLE registrations ADD COLUMN ip_address VARCHAR(45) NULL");
        $conn->query("CREATE INDEX idx_registrations_ip ON registrations(ip_address, registered_at)");
        return true;
    }

    $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
    $sql = "SELECT COUNT(*) as total FROM registrations 
            WHERE ip_address = ? AND registered_at >= ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $ip, $oneHourAgo);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = $result->fetch_assoc()['total'];
    $stmt->close();
    return $count < 30; // izinkan maks 5 pendaftaran per IP per jam
}

function flash($key) {
    if (isset($_SESSION[$key])) {
        $msg = htmlspecialchars($_SESSION[$key], ENT_QUOTES, 'UTF-8');
        unset($_SESSION[$key]);
        return $msg;
    }
    return '';
}

// Juga simpan IP saat insert di register_process.php:
// $ip = $_SERVER['REMOTE_ADDR'];
// Tambahkan kolom: ALTER TABLE registrations ADD COLUMN ip_address VARCHAR(45);
?>