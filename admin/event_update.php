<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: events.php');
    exit;
}

$id = intval($_POST['id']);
$name = trim($_POST['name']);
$description = trim($_POST['description']);
$event_type = $_POST['event_type'];
$quota = intval($_POST['quota']);
$registration_open = $_POST['registration_open'];
$registration_close = $_POST['registration_close'];
$is_active = isset($_POST['is_active']) ? 1 : 0;
$category = $_POST['category'];

// Ambil dokumentasi lama
$sql_old = "SELECT documentation FROM events WHERE id = ?";
$stmt_old = $conn->prepare($sql_old);
$stmt_old->bind_param("i", $id);
$stmt_old->execute();
$result_old = $stmt_old->get_result();
$old = $result_old->fetch_assoc();
$old_doc = $old['documentation'];
$documentation = $old_doc; // default

// Cek apakah user ingin menghapus dokumentasi
$delete_doc = isset($_POST['delete_documentation']) && $_POST['delete_documentation'] == 1;

// Cek apakah ada file baru diupload
$upload_new = isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0;

if ($upload_new) {
    // Proses upload baru
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_ext)) {
        $_SESSION['error'] = "Format file tidak didukung.";
        header("Location: event_edit.php?id=$id");
        exit;
    }
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $target_file)) {
        // Hapus file lama jika ada (dan tidak sedang dihapus terpisah)
        if (!empty($old_doc) && file_exists($target_dir . $old_doc)) {
            unlink($target_dir . $old_doc);
        }
        $documentation = $new_filename;
    } else {
        $_SESSION['error'] = "Gagal mengupload gambar.";
        header("Location: event_edit.php?id=$id");
        exit;
    }
} elseif ($delete_doc) {
    // Hanya hapus file lama
    if (!empty($old_doc) && file_exists("../uploads/" . $old_doc)) {
        unlink("../uploads/" . $old_doc);
    }
    $documentation = null;
}
// Jika tidak upload baru dan tidak hapus, dokumentasi tetap seperti sebelumnya

// Validasi
if (empty($name) || empty($event_type) || empty($quota) || empty($registration_open) || empty($registration_close)) {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header("Location: event_edit.php?id=$id");
    exit;
}

if ($quota < 1) {
    $_SESSION['error'] = "Kuota harus minimal 1.";
    header("Location: event_edit.php?id=$id");
    exit;
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    $_SESSION['error'] = "Tanggal buka tidak boleh lebih dari tanggal tutup.";
    header("Location: event_edit.php?id=$id");
    exit;
}

$sql = "UPDATE events SET name=?, description=?, documentation=?, event_type=?, category=?, quota=?, registration_open=?, registration_close=?, is_active=? WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssidssii", $name, $description, $documentation, $event_type, $category, $quota, $registration_open, $registration_close, $is_active, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil diperbarui.";
    header('Location: events.php');
} else {
    $_SESSION['error'] = "Gagal memperbarui event: " . $conn->error;
    header("Location: event_edit.php?id=$id");
}

$stmt->close();
$conn->close();
?>