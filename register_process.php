<?php
session_start();
require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: index.php');
    exit;
}

// CSRF check
if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    $_SESSION['error'] = "Token CSRF tidak valid.";
    header('Location: index.php');
    exit;
}

$event_id  = intval($_POST['event_id']);
$full_name = trim($_POST['full_name']);
$email     = trim($_POST['email']);
$phone     = trim($_POST['phone']);

// Validasi dasar
if (empty($full_name) || empty($email) || empty($phone)) {
    $_SESSION['error'] = "Nama, email, dan nomor telepon wajib diisi.";
    header("Location: register.php?event_id=$event_id");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Format email tidak valid.";
    header("Location: register.php?event_id=$event_id");
    exit;
}

if (!preg_match('/^[0-9]{10,13}$/', $phone)) {
    $_SESSION['error'] = "Nomor telepon harus 10–13 digit angka.";
    header("Location: register.php?event_id=$event_id");
    exit;
}

// Ambil data event dari database
$sql  = "SELECT * FROM events
         WHERE id = ? AND is_active = 1
           AND registration_open <= CURDATE()
           AND registration_close >= CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event tidak ditemukan atau sudah ditutup.";
    header('Location: index.php');
    exit;
}

$event      = $result->fetch_assoc();
$event_type = $event['event_type'];
$quota      = $event['quota'];

// Rate limiting (cek IP)
$ip = $_SERVER['REMOTE_ADDR'];
if (!checkRateLimit($ip, $conn)) {
    $_SESSION['error'] = "Terlalu banyak pendaftaran dari IP Anda. Silakan coba lagi nanti.";
    header("Location: index.php");
    exit;
}

// Validasi berdasarkan tipe event
$institution = null;
$npm         = null;
$faculty     = null;

if ($event_type == 'umum') {
    $institution = trim($_POST['institution'] ?? '');
    if (empty($institution)) {
        $_SESSION['error'] = "Instansi wajib diisi.";
        header("Location: register.php?event_id=$event_id");
        exit;
    }
} else {
    $npm    = trim($_POST['npm'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');
    if (empty($npm)) {
        $_SESSION['error'] = "NPM wajib diisi.";
        header("Location: register.php?event_id=$event_id");
        exit;
    }
    if (empty($faculty)) {
        $faculty = 'Fakultas Ilmu Komputer';
    }
    if (!preg_match('/^[0-9]{13}$/', $npm)) {
        $_SESSION['error'] = "NPM harus 13 digit angka.";
        header("Location: register.php?event_id=$event_id");
        exit;
    }
}

// Cek kuota
$stmt_count = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE event_id = ?");
$stmt_count->bind_param("i", $event_id);
$stmt_count->execute();
$registered = $stmt_count->get_result()->fetch_assoc()['total'];
$stmt_count->close();

if ($registered >= $quota) {
    $_SESSION['error'] = "Maaf, kuota untuk event ini sudah penuh.";
    header("Location: register.php?event_id=$event_id");
    exit;
}

// Cek duplikasi
if ($event_type == 'internal') {
    $sql_check = "SELECT id FROM registrations WHERE event_id = ? AND npm = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $event_id, $npm);
} else {
    $sql_check = "SELECT id FROM registrations WHERE event_id = ? AND email = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("is", $event_id, $email);
}
$stmt_check->execute();
$stmt_check->store_result();
if ($stmt_check->num_rows > 0) {
    $_SESSION['error'] = "Anda sudah terdaftar pada event ini.";
    header("Location: register.php?event_id=$event_id");
    exit;
}
$stmt_check->close();

// Simpan ke database (sertakan ip_address bila kolom sudah ada)
$hasIpCol = $conn->query("SHOW COLUMNS FROM registrations LIKE 'ip_address'");
if ($hasIpCol && $hasIpCol->num_rows > 0) {
    $sql_insert = "INSERT INTO registrations
                   (event_id, full_name, email, institution, npm, faculty, phone, ip_address)
                   VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("isssssss",
        $event_id, $full_name, $email, $institution, $npm, $faculty, $phone, $ip);
} else {
    $sql_insert = "INSERT INTO registrations
                   (event_id, full_name, email, institution, npm, faculty, phone)
                   VALUES (?, ?, ?, ?, ?, ?, ?)";
    $stmt_insert = $conn->prepare($sql_insert);
    $stmt_insert->bind_param("issssss",
        $event_id, $full_name, $email, $institution, $npm, $faculty, $phone);
}

if ($stmt_insert->execute()) {
    // Kirim email konfirmasi (non-blocking: error email tidak gagalkan pendaftaran)
    sendRegistrationEmail($email, $full_name, $event['name'], $event);

    $_SESSION['success'] = "Pendaftaran berhasil! Email konfirmasi telah dikirim ke <strong>{$email}</strong>.";
    header("Location: index.php");
    exit;
} else {
    $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
    header("Location: register.php?event_id=$event_id");
    exit;
}