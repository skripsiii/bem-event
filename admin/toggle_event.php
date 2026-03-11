<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    $_SESSION['error'] = "ID event tidak valid.";
    header('Location: events.php');
    exit;
}

// Ambil status saat ini
$sql = "SELECT is_active FROM events WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event tidak ditemukan.";
    header('Location: events.php');
    exit;
}

$event = $result->fetch_assoc();
$new_status = $event['is_active'] ? 0 : 1;

$sql_update = "UPDATE events SET is_active = ? WHERE id = ?";
$stmt_update = $conn->prepare($sql_update);
$stmt_update->bind_param("ii", $new_status, $id);

if ($stmt_update->execute()) {
    $_SESSION['success'] = "Status event berhasil diubah.";
} else {
    $_SESSION['error'] = "Gagal mengubah status event.";
}

$stmt->close();
$stmt_update->close();
$conn->close();
header('Location: events.php');
exit;
?>