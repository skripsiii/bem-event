<?php
session_start();
require_once 'includes/auth.php';
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    header('Location: events.php');
    exit;
}

function validateUploadedImage($file, $maxSizeMB = 2): array {
    $allowed_mime = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    $allowed_ext  = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'msg' => 'Upload gagal (kode: ' . $file['error'] . ')'];
    }
    if ($file['size'] > $maxSizeMB * 1024 * 1024) {
        return ['ok' => false, 'msg' => "Ukuran file maks {$maxSizeMB}MB."];
    }
    // Cek MIME type yang sebenarnya (bukan dari header)
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!in_array($mime, $allowed_mime)) {
        return ['ok' => false, 'msg' => 'Tipe file tidak didukung. Gunakan JPG, PNG, atau GIF.'];
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_ext)) {
        return ['ok' => false, 'msg' => 'Ekstensi file tidak valid.'];
    }
    return ['ok' => true];
}

$name = trim($_POST['name']);
$description = trim($_POST['description']);
$event_type = $_POST['event_type'];
$quota = intval($_POST['quota']);
$registration_open = $_POST['registration_open'];
$registration_close = $_POST['registration_close'];
$is_active = isset($_POST['is_active']) ? 1 : 0;
$category = $_POST['category'];

// Validasi
if (empty($name) || empty($event_type) || empty($quota) || empty($registration_open) || empty($registration_close)) {
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
    $_SESSION['error'] = "Tanggal buka tidak boleh lebih dari tanggal tutup.";
    header('Location: event_add.php');
    exit;
}

// Proses upload file dokumentasi
$documentation = null;
if (isset($_FILES['documentation']) && $_FILES['documentation']['error'] == 0) {
    $target_dir = "../uploads/";
    // Buat folder jika belum ada
    if (!is_dir($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $file_extension = pathinfo($_FILES['documentation']['name'], PATHINFO_EXTENSION);
    $allowed_ext = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array(strtolower($file_extension), $allowed_ext)) {
        $_SESSION['error'] = "Format file tidak didukung. Gunakan JPG, JPEG, PNG, atau GIF.";
        header('Location: event_add.php');
        exit;
    }
    $new_filename = uniqid() . '.' . $file_extension;
    $target_file = $target_dir . $new_filename;
    if (move_uploaded_file($_FILES['documentation']['tmp_name'], $target_file)) {
        $documentation = $new_filename;
    } else {
        $_SESSION['error'] = "Gagal mengupload gambar.";
        header('Location: event_add.php');
        exit;
    }
}

// Query INSERT dengan kolom documentation
$sql = "INSERT INTO events (name, description, documentation, event_type, category, quota, registration_open, registration_close, is_active) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssssidssi", $name, $description, $documentation, $event_type, $category, $quota, $registration_open, $registration_close, $is_active);

if ($stmt->execute()) {
    $_SESSION['success'] = "Event berhasil ditambahkan.";
    header('Location: events.php');
} else {
    $_SESSION['error'] = "Gagal menambahkan event: " . $conn->error;
    header('Location: event_add.php');
}

$stmt->close();
$conn->close();
?>