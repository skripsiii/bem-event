<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: events.php'); exit;
}
if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Token tidak valid.";
    header('Location: events.php'); exit;
}
$id = intval($_POST['id']);

// Hapus event (registrasi akan terhapus otomatis karena ON DELETE CASCADE)
$sql = "DELETE FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil dihapus.";
} else {
    $_SESSION['error'] = "Gagal menghapus event: " . $conn->error;
}

$stmt->close();
$conn->close();
header('Location: events.php');
exit;

?>