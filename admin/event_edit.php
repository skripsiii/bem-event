<?php
/* ════════════════════════════════════════════════════════════════
   admin/event_edit.php — Form Edit Event
   ════════════════════════════════════════════════════════════════ */

include 'includes/auth.php';
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: events.php');
    exit;
}

$stmt = $conn->prepare('SELECT * FROM events WHERE id = ?');
$stmt->bind_param('i', $id);
$stmt->execute();
$event = $stmt->get_result()->fetch_assoc();
$stmt->close();
$conn->close();

if (!$event) {
    $_SESSION['error'] = 'Event tidak ditemukan.';
    header('Location: events.php');
    exit;
}

$page_title = 'Edit Event — ' . htmlspecialchars($event['name']);
include '../includes/header.php';
?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-warning text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Edit Event
                    </h4>
                </div>

                <div class="card-body p-4">

                    <!-- ══════════════════════════════════════════
                         id="eventEditForm" → digunakan oleh AJAX di script.js
                    ═══════════════════════════════════════════ -->
                    <form id="eventEditForm"
                          action="event_update.php"
                          method="POST"
                          enctype="multipart/form-data">

                        <input type="hidden" name="id" value="<?= $event['id'] ?>">

                        <!-- Nama Event -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-tag me-2 text-primary"></i>Nama Event
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                   id="name" name="name"
                                   value="<?= htmlspecialchars($event['name']) ?>" required>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2 text-primary"></i>Deskripsi
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="4"><?= htmlspecialchars($event['description']) ?></textarea>
                        </div>

                        <!-- Tanggal Penyelenggaraan -->
                        <div class="mb-3">
                            <label for="event_date" class="form-label">
                                <i class="fas fa-calendar-day me-2 text-primary"></i>Tanggal Penyelenggaraan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-lg"
                                   id="event_date" name="event_date"
                                   value="<?= htmlspecialchars($event['event_date'] ?? '') ?>" required>
                        </div>

                        <!-- Dokumentasi Saat Ini -->
                        <div class="mb-3">
                            <label class="form-label">
                                <i class="fas fa-image me-2 text-primary"></i>Gambar Saat Ini
                            </label><br>
                            <?php if (!empty($event['documentation'])): ?>
                                <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                                     class="img-thumbnail mb-2" style="max-width:200px; max-height:130px; object-fit:cover;"
                                     alt="Dokumentasi">
                                <div class="form-check mt-1">
                                    <input class="form-check-input" type="checkbox"
                                           id="delete_documentation" name="delete_documentation" value="1">
                                    <label class="form-check-label text-danger small" for="delete_documentation">
                                        Hapus gambar ini
                                    </label>
                                </div>
                            <?php else: ?>
                                <p class="text-muted small mb-1">Belum ada gambar untuk event ini.</p>
                            <?php endif; ?>
                        </div>

                        <!-- Upload Gambar Baru -->
                        <div class="mb-3">
                            <label for="documentation" class="form-label">
                                Upload Gambar Baru
                                <span class="text-muted">(opsional)</span>
                            </label>
                            <input type="file" class="form-control" id="documentation"
                                   name="documentation" accept="image/*">
                            <div class="form-text">Kosongkan jika tidak ingin mengubah gambar. Max 2MB.</div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Tipe Event -->
                            <div class="col-md-6">
                                <label for="event_type" class="form-label">
                                    <i class="fas fa-tasks me-2 text-primary"></i>Tipe Event
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg"
                                        id="event_type" name="event_type" required>
                                    <option value="umum"     <?= $event['event_type'] === 'umum'     ? 'selected' : '' ?>>
                                        Umum (terbuka untuk publik)
                                    </option>
                                    <option value="internal" <?= $event['event_type'] === 'internal' ? 'selected' : '' ?>>
                                        Internal (khusus mahasiswa Unsika)
                                    </option>
                                </select>
                            </div>
                            <!-- Kuota -->
                            <div class="col-md-6">
                                <label for="quota" class="form-label">
                                    <i class="fas fa-users me-2 text-primary"></i>Kuota Peserta
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="number" class="form-control form-control-lg"
                                       id="quota" name="quota" min="1"
                                       value="<?= $event['quota'] ?>" required>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Kategori -->
                            <div class="col-md-6">
                                <label for="category" class="form-label">
                                    <i class="fas fa-folder me-2 text-primary"></i>Kategori Event
                                    <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-lg"
                                        id="category" name="category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <?php foreach (['Seminar','Workshop','Lomba','Sosial','Pelatihan','Lainnya'] as $cat): ?>
                                        <option value="<?= $cat ?>"
                                            <?= ($event['category'] === $cat) ? 'selected' : '' ?>>
                                            <?= $cat ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <div class="row g-3 mb-3">
                            <!-- Tanggal Buka -->
                            <div class="col-md-6">
                                <label for="registration_open" class="form-label">
                                    <i class="fas fa-calendar-plus me-2 text-primary"></i>Pendaftaran Dibuka
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg"
                                       id="registration_open" name="registration_open"
                                       value="<?= htmlspecialchars($event['registration_open']) ?>" required>
                            </div>
                            <!-- Tanggal Tutup -->
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label">
                                    <i class="fas fa-calendar-times me-2 text-primary"></i>Pendaftaran Ditutup
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg"
                                       id="registration_close" name="registration_close"
                                       value="<?= htmlspecialchars($event['registration_close']) ?>" required>
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox"
                                   id="is_active" name="is_active" value="1"
                                   <?= $event['is_active'] ? 'checked' : '' ?>>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Aktifkan event (dapat didaftarkan oleh peserta)
                            </label>
                        </div>

                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-warning btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Perbarui Event
                            </button>
                        </div>

                    </form>
                </div>
            </div><!-- /card -->

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>