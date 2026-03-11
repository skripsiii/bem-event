<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: events.php');
    exit;
}

$name               = trim($_POST['name']);
$description        = trim($_POST['description']);
$event_date         = $_POST['event_date'];
$event_type         = $_POST['event_type'];
$quota              = intval($_POST['quota']);
$registration_open  = $_POST['registration_open'];
$registration_close = $_POST['registration_close'];
$is_active          = isset($_POST['is_active']) ? 1 : 0;
$category           = $_POST['category'];

// Validasi
if (empty($name) || empty($event_type) || empty($quota) ||
    empty($registration_open) || empty($registration_close) || empty($event_date)) {
    $_SESSION['error'] = "Semua field wajib diisi.";
    header('Location: event_add.php');
    exit;
}

if ($quota < 1) {
    $_SESSION['error'] = "Kuota harus minimal 1.";
    header('Location: event_add.php');
    exit;
}

if (strtotime($registration_open) > strtotime($registration_close)) {
    $_SESSION['error'] = "Tanggal buka pendaftaran tidak boleh lebih dari tanggal tutup.";
    header('Location: event_add.php');
    exit;
}

// Proses upload file dokumentasi
$documentation = null;
if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0) {
    $target_dir = "../uploads/";
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($_FILES['documentation']['tmp_name']);
    if (!in_array($mime, $allowed_mime)) {
        $_SESSION['error'] = "Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.";
        header('Location: event_add.php');
        exit;
    }

    $file_extension = strtolower(pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION));
    if (!in_array($file_extension, $allowed_ext)) {
        $_SESSION['error'] = "Ekstensi file tidak valid.";
        header('Location: event_add.php');
        exit;
    }

    if ($_FILES['documentation']['size'] > 2 * 1024 * 1024) {
        $_SESSION['error'] = "Ukuran file maksimal 2MB.";
        header('Location: event_add.php');
        exit;
    }

    $new_filename = uniqid() . '.' . $file_extension;
    if (!move_uploaded_file($_FILES['documentation']['tmp_name'], $target_dir . $new_filename)) {
        $_SESSION['error'] = "Gagal mengupload gambar.";
        header('Location: event_add.php');
        exit;
    }
    $documentation = $new_filename;
}

$sql = "INSERT INTO events
            (name, description, event_date, documentation, event_type, category,
             quota, registration_open, registration_close, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ssssssidsi",
    $name, $description, $event_date, $documentation,
    $event_type, $category, $quota,
    $registration_open, $registration_close, $is_active
);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil ditambahkan.";
    header('Location: events.php');
} else {
    $_SESSION['error'] = "Gagal menambahkan event: " . $conn->error;
    header('Location: event_add.php');
}

$stmt->close();
$conn->close();