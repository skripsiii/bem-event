<?php
/* ════════════════════════════════════════════════════════════════
   admin/event_save.php — Simpan Event Baru (JSON Response)
   ════════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Auth check — kompatibel dengan AJAX (tidak redirect, return JSON)
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Sesi tidak valid. Silakan login kembali.', 'expired' => true]);
    exit;
}

// Perbarui last_activity
$_SESSION['last_activity'] = time();

require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

function respondSave(bool $success, string $message, bool $isAjax, string $redirect = 'event_add.php'): void
{
    if ($isAjax) {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    $_SESSION[$success ? 'success' : 'error'] = $message;
    header('Location: ' . ($success ? 'events.php' : $redirect));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondSave(false, 'Metode request tidak valid.', $isAjax);
}

/* ── Input ── */
$name               = trim($_POST['name'] ?? '');
$description        = trim($_POST['description'] ?? '');
$event_date         = $_POST['event_date'] ?? '';
$event_type         = $_POST['event_type'] ?? '';
$quota              = intval($_POST['quota'] ?? 0);
$registration_open  = $_POST['registration_open'] ?? '';
$registration_close = $_POST['registration_close'] ?? '';
$is_active          = isset($_POST['is_active']) ? 1 : 0;
$category           = $_POST['category'] ?? '';

/* ── Validasi ── */
if (empty($name) || empty($event_type) || empty($category) || empty($event_date)
    || empty($registration_open) || empty($registration_close)) {
    respondSave(false, 'Semua field bertanda wajib harus diisi.', $isAjax);
}

if ($quota < 1) {
    respondSave(false, 'Kuota peserta harus minimal 1.', $isAjax);
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    respondSave(false, 'Tanggal buka pendaftaran tidak boleh lebih dari tanggal tutup.', $isAjax);
}

/* ── Upload Dokumentasi ── */
$documentation = null;
if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] === UPLOAD_ERR_OK) {
    $targetDir   = '../uploads/';
    $allowedMime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowedExt  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);
    if (!in_array($mime, $allowedMime)) {
        respondSave(false, 'Format file tidak didukung. Gunakan JPG, PNG, GIF, atau WebP.', $isAjax);
    }

    if ($_FILES['documentation']['size'] > 2 * 1024 * 1024) {
        respondSave(false, 'Ukuran file terlalu besar. Maksimal 2MB.', $isAjax);
    }

    $ext = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt)) {
        respondSave(false, 'Ekstensi file tidak valid.', $isAjax);
    }

    $filename = 'ev_' . uniqid('', true) . '.' . $ext;
    if (!move_uploaded_file($_FILES['documentation']['tmp_name'], $targetDir . $filename)) {
        respondSave(false, 'Gagal mengupload gambar. Periksa izin folder uploads/.', $isAjax);
    }
    $documentation = $filename;
}

/* ── Simpan ke DB ── */
$sql  = "INSERT INTO events
            (name, description, event_date, documentation, event_type, category,
             quota, registration_open, registration_close, is_active)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
// s=name, s=description, s=event_date, s=documentation, s=event_type, s=category,
// i=quota, s=registration_open, s=registration_close, i=is_active
$stmt->bind_param('ssssssissi',
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active
);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    respondSave(true, "Event \"{$name}\" berhasil ditambahkan.", $isAjax);
} else {
    $errMsg = $conn->error;
    $stmt->close();
    $conn->close();
    respondSave(false, 'Gagal menyimpan event: ' . $errMsg, $isAjax);
}