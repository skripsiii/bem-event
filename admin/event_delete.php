<?php
/* ════════════════════════════════════════════════════════════════
   admin/event_delete.php — Hapus Event (JSON Response)
   ════════════════════════════════════════════════════════════════ */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Tidak terotorisasi.']);
    exit;
}

$_SESSION['last_activity'] = time();

require_once '../config/database.php';

$isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
       && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

if ($isAjax) {
    header('Content-Type: application/json; charset=utf-8');
}

function respondDelete(bool $success, string $message, bool $isAjax): void
{
    if ($isAjax) {
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }
    $_SESSION[$success ? 'success' : 'error'] = $message;
    header('Location: events.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respondDelete(false, 'Metode request tidak valid.', $isAjax);
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    respondDelete(false, 'Token keamanan tidak valid. Silakan muat ulang halaman.', $isAjax);
}

$id = intval($_POST['id'] ?? 0);
if (!$id) {
    respondDelete(false, 'ID event tidak valid.', $isAjax);
}

/* ── Ambil nama event untuk pesan konfirmasi ── */
$stmtName = $conn->prepare('SELECT name FROM events WHERE id = ?');
$stmtName->bind_param('i', $id);
$stmtName->execute();
$eventRow = $stmtName->get_result()->fetch_assoc();
$stmtName->close();

if (!$eventRow) {
    respondDelete(false, 'Event tidak ditemukan.', $isAjax);
}
$eventName = $eventRow['name'];

/* ── Hapus event (registrasi ikut terhapus via ON DELETE CASCADE) ── */
$stmt = $conn->prepare('DELETE FROM events WHERE id = ?');
$stmt->bind_param('i', $id);

if ($stmt->execute()) {
    $stmt->close();
    $conn->close();
    respondDelete(true, "Event \"{$eventName}\" berhasil dihapus.", $isAjax);
} else {
    $errMsg = $conn->error;
    $stmt->close();
    $conn->close();
    respondDelete(false, 'Gagal menghapus event: ' . $errMsg, $isAjax);
}