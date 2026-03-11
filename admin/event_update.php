<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: events.php');
    exit;
}

$id                 = intval($_POST['id']);
$name               = trim($_POST['name']);
$description        = trim($_POST['description']);
$event_date         = $_POST['event_date'];
$event_type         = $_POST['event_type'];
$quota              = intval($_POST['quota']);
$registration_open  = $_POST['registration_open'];
$registration_close = $_POST['registration_close'];
$is_active          = isset($_POST['is_active']) ? 1 : 0;
$category           = $_POST['category'];

// Ambil dokumentasi lama
$stmt_old = $conn->prepare("SELECT documentation FROM events WHERE id = ?");
$stmt_old->bind_param("i", $id);
$stmt_old->execute();
$old          = $stmt_old->get_result()->fetch_assoc();
$documentation = $old['documentation']; // default: tetap pakai yang lama
$stmt_old->close();

$delete_doc = isset($_POST['delete_documentation']) && $_POST['delete_documentation'] == 1;
$upload_new = isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0;

if ($upload_new) {
    $target_dir     = "../uploads/";
    $allowed_ext    = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $allowed_mime   = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);
    if (!in_array($mime, $allowed_mime)) {
        $_SESSION['error'] = "Tipe file tidak didukung.";
        header("Location: event_edit.php?id=$id");
        exit;
    }

    $file_extension = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_ext)) {
        $_SESSION['error'] = "Ekstensi file tidak valid.";
        header("Location: event_edit.php?id=$id");
        exit;
    }

    $new_filename = uniqid() . '.' . $file_extension;
    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $target_dir . $new_filename)) {
        // Hapus file lama jika ada
        if (!empty($old['documentation']) && file_exists($target_dir . $old['documentation'])) {
            unlink($target_dir . $old['documentation']);
        }
        $documentation = $new_filename;
    } else {
        $_SESSION['error'] = "Gagal mengupload gambar.";
        header("Location: event_edit.php?id=$id");
        exit;
    }
} elseif ($delete_doc) {
    if (!empty($old['documentation']) && file_exists("../uploads/" . $old['documentation'])) {
        unlink("../uploads/" . $old['documentation']);
    }
    $documentation = null;
}

// Validasi
if (empty($name) || empty($event_type) || empty($quota) ||
    empty($registration_open) || empty($registration_close) || empty($event_date)) {
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
    $_SESSION['error'] = "Tanggal buka pendaftaran tidak boleh lebih dari tanggal tutup.";
    header("Location: event_edit.php?id=$id");
    exit;
}

$sql  = "UPDATE events
         SET name=?, description=?, event_date=?, documentation=?,
             event_type=?, category=?, quota=?,
             registration_open=?, registration_close=?, is_active=?
         WHERE id=?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssissii",
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active,
    $id
);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil diperbarui.";
    header('Location: events.php');
} else {
    $_SESSION['error'] = "Gagal memperbarui event: " . $conn->error;
    header("Location: event_edit.php?id=$id");
}

$stmt->close();
$conn->close();