<?php
/* ════════════════════════════════════════════════════════════════
   register_process.php — Proses Pendaftaran Event
   Mengembalikan JSON untuk AJAX, redirect untuk non-AJAX (fallback)
   ════════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config/database.php';

/* ── Deteksi apakah request dari AJAX ── */
$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

/* ── Helper: kirim respons dan exit ── */
function respond(bool $success, string $message, bool $isAjax): void
{
    if ($isAjax) {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    // Fallback non-AJAX
    $_SESSION[$success ? 'success' : 'error'] = $message;
    header('Location: ' . ($success ? 'index.php' : 'register.php?event_id=' . (int) ($_POST['event_id'] ?? 0)));
    exit;
}

/* ═══════════════════════════════════════
   Validasi Method & CSRF
═══════════════════════════════════════ */
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Metode request tidak valid.', $isAjax);
}

if (!isset($_POST['csrf_token']) || !verifyCsrfToken($_POST['csrf_token'])) {
    respond(false, 'Token keamanan tidak valid. Silakan muat ulang halaman dan coba lagi.', $isAjax);
}

/* ═══════════════════════════════════════
   Ambil & Sanitasi Input
═══════════════════════════════════════ */
$event_id  = intval($_POST['event_id'] ?? 0);
$full_name = trim($_POST['full_name'] ?? '');
$email     = trim($_POST['email'] ?? '');
$phone     = trim($_POST['phone'] ?? '');

/* ── Validasi dasar ── */
if (!$event_id) {
    respond(false, 'ID event tidak valid.', $isAjax);
}

if (empty($full_name) || empty($email) || empty($phone)) {
    respond(false, 'Nama lengkap, email, dan nomor telepon wajib diisi.', $isAjax);
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Format email tidak valid.', $isAjax);
}

if (!preg_match('/^[0-9]{10,13}$/', $phone)) {
    respond(false, 'Nomor telepon harus 10–13 digit angka (tanpa tanda +/-/spasi).', $isAjax);
}

/* ═══════════════════════════════════════
   Ambil Data Event
═══════════════════════════════════════ */
$stmtEv = $conn->prepare(
    "SELECT * FROM events
      WHERE id = ? AND is_active = 1
        AND registration_open  <= CURDATE()
        AND registration_close >= CURDATE()"
);
$stmtEv->bind_param('i', $event_id);
$stmtEv->execute();
$event = $stmtEv->get_result()->fetch_assoc();
$stmtEv->close();

if (!$event) {
    respond(false, 'Event tidak ditemukan atau pendaftaran sudah ditutup.', $isAjax);
}

$event_type = $event['event_type'];

/* ═══════════════════════════════════════
   Rate Limiting (per IP)
═══════════════════════════════════════ */
$ip = $_SERVER['REMOTE_ADDR'];
if (!checkRateLimit($ip, $conn)) {
    respond(false, 'Terlalu banyak pendaftaran dari IP Anda dalam 1 jam. Silakan coba lagi nanti.', $isAjax);
}

/* ═══════════════════════════════════════
   Validasi Spesifik Tipe Event
═══════════════════════════════════════ */
$institution = null;
$npm         = null;
$faculty     = null;

if ($event_type === 'umum') {
    $institution = trim($_POST['institution'] ?? '');
    if (empty($institution)) {
        respond(false, 'Nama instansi/asal wajib diisi untuk event umum.', $isAjax);
    }
} else {
    $npm     = trim($_POST['npm'] ?? '');
    $faculty = trim($_POST['faculty'] ?? '');

    if (empty($npm)) {
        respond(false, 'NPM wajib diisi untuk event internal.', $isAjax);
    }
    if (!preg_match('/^[0-9]{13}$/', $npm)) {
        respond(false, 'NPM harus tepat 13 digit angka.', $isAjax);
    }
    if (empty($faculty)) {
        respond(false, 'Fakultas wajib diisi untuk event internal.', $isAjax);
    }
}

/* ═══════════════════════════════════════
   Cek Kuota, Duplikasi & Simpan
   — dalam satu transaksi dengan row locking
     untuk mencegah race condition overbooking
═══════════════════════════════════════ */
$conn->begin_transaction();

try {
    /* ── Cek sisa kuota (FOR UPDATE mengunci baris sampai transaksi selesai) ── */
    $stmtCount = $conn->prepare(
        'SELECT COUNT(*) AS total FROM registrations WHERE event_id = ? FOR UPDATE'
    );
    $stmtCount->bind_param('i', $event_id);
    $stmtCount->execute();
    $registered = (int) $stmtCount->get_result()->fetch_assoc()['total'];
    $stmtCount->close();

    if ($registered >= (int) $event['quota']) {
        $conn->rollback();
        respond(false, 'Maaf, kuota untuk event ini sudah penuh.', $isAjax);
    }

    /* ── Cek duplikasi pendaftaran ── */
    if ($event_type === 'internal') {
        $stmtDup = $conn->prepare('SELECT id FROM registrations WHERE event_id = ? AND npm = ?');
        $stmtDup->bind_param('is', $event_id, $npm);
    } else {
        $stmtDup = $conn->prepare('SELECT id FROM registrations WHERE event_id = ? AND email = ?');
        $stmtDup->bind_param('is', $event_id, $email);
    }
    $stmtDup->execute();
    $stmtDup->store_result();
    if ($stmtDup->num_rows > 0) {
        $conn->rollback();
        respond(false, 'Anda sudah terdaftar pada event ini sebelumnya.', $isAjax);
    }
    $stmtDup->close();

    /* ── Simpan pendaftaran ke database ── */
    $hasIpCol = (bool) $conn->query("SHOW COLUMNS FROM registrations LIKE 'ip_address'")->num_rows;

    if ($hasIpCol) {
        $stmtIns = $conn->prepare(
            "INSERT INTO registrations (event_id, full_name, email, institution, npm, faculty, phone, ip_address)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtIns->bind_param('isssssss', $event_id, $full_name, $email, $institution, $npm, $faculty, $phone, $ip);
    } else {
        $stmtIns = $conn->prepare(
            "INSERT INTO registrations (event_id, full_name, email, institution, npm, faculty, phone)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmtIns->bind_param('issssss', $event_id, $full_name, $email, $institution, $npm, $faculty, $phone);
    }

    if (!$stmtIns->execute()) {
        throw new RuntimeException('Execute gagal: ' . $stmtIns->error);
    }

    $conn->commit();

    // Kirim email konfirmasi (error email tidak gagalkan pendaftaran)
    sendRegistrationEmail($email, $full_name, $event['name'], $event);
    respond(true, "Pendaftaran berhasil! Email konfirmasi telah dikirim ke {$email}.", $isAjax);

} catch (RuntimeException $e) {
    $conn->rollback();
    respond(false, 'Terjadi kesalahan sistem saat menyimpan data. Silakan coba lagi.', $isAjax);
}