<?php
include 'includes/auth.php';
require_once '../config/database.php';

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($id == 0) {
    header('Location: events.php');
    exit;
}

$sql = "SELECT * FROM events WHERE id = ?";
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
$page_title = 'Edit Event';
include '../includes/header.php';
?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-warning text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Edit Event</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
                    <?php endif; ?>

                    <form action="event_update.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">                        <div class="mb-3">
                            <label for="name" class="form-label"><i class="fas fa-tag me-2 text-primary"></i>Nama Event</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" value="<?php echo htmlspecialchars($event['name']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label"><i class="fas fa-align-left me-2 text-primary"></i>Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4"><?php echo htmlspecialchars($event['description']); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Dokumentasi Saat Ini</label><br>
                            <?php if (!empty($event['documentation'])): ?>
                                <img src="<?php echo BASE_URL; ?>uploads/<?php echo $event['documentation']; ?>" width="200" class="img-thumbnail mb-2">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="delete_documentation" id="delete_documentation" value="1">
                                    <label class="form-check-label" for="delete_documentation">Hapus gambar ini</label>
                                </div>
                            <?php else: ?>
                                <p class="text-muted">Belum ada dokumentasi.</p>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label for="documentation" class="form-label">Upload Dokumentasi Baru (opsional)</label>
                            <input type="file" class="form-control" id="documentation" name="documentation" accept="image/*">
                            <small class="text-muted">Kosongkan jika tidak ingin mengubah.</small>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="event_type" class="form-label"><i class="fas fa-tasks me-2 text-primary"></i>Tipe Event</label>
                                <select class="form-select form-select-lg" id="event_type" name="event_type" required>
                                    <option value="umum" <?php echo $event['event_type'] == 'umum' ? 'selected' : ''; ?>>Umum (untuk publik)</option>
                                    <option value="internal" <?php echo $event['event_type'] == 'internal' ? 'selected' : ''; ?>>Internal (khusus mahasiswa Unsika)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="quota" class="form-label"><i class="fas fa-users me-2 text-primary"></i>Kuota Peserta</label>
                                <input type="number" class="form-control form-control-lg" id="quota" name="quota" min="1" value="<?php echo $event['quota']; ?>" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label"><i class="fas fa-tag me-2 text-primary"></i>Kategori Event</label>
                                <select class="form-select form-select-lg" id="category" name="category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Seminar" <?php echo ($event['category'] == 'Seminar') ? 'selected' : ''; ?>>Seminar</option>
                                    <option value="Workshop" <?php echo ($event['category'] == 'Workshop') ? 'selected' : ''; ?>>Workshop</option>
                                    <option value="Lomba" <?php echo ($event['category'] == 'Lomba') ? 'selected' : ''; ?>>Lomba</option>
                                    <option value="Sosial" <?php echo ($event['category'] == 'Sosial') ? 'selected' : ''; ?>>Sosial</option>
                                    <option value="Pelatihan" <?php echo ($event['category'] == 'Pelatihan') ? 'selected' : ''; ?>>Pelatihan</option>
                                    <option value="Lainnya" <?php echo ($event['category'] == 'Lainnya') ? 'selected' : ''; ?>>Lainnya</option>
                                </select>
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="registration_open" class="form-label"><i class="fas fa-calendar-plus me-2 text-primary"></i>Tanggal Buka</label>
                                <input type="date" class="form-control form-control-lg" id="registration_open" name="registration_open" value="<?php echo $event['registration_open']; ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label"><i class="fas fa-calendar-times me-2 text-primary"></i>Tanggal Tutup</label>
                                <input type="date" class="form-control form-control-lg" id="registration_close" name="registration_close" value="<?php echo $event['registration_close']; ?>" required>
                            </div>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" <?php echo $event['is_active'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>Aktifkan event
                            </label>
                        </div>
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary btn-lg px-4"><i class="fas fa-times me-2"></i>Batal</a>
                            <button type="submit" class="btn btn-warning btn-lg px-5"><i class="fas fa-save me-2"></i>Update</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<?php
$stmt->close();
$conn->close();
include '../includes/footer.php';
?>