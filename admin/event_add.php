<?php
/* ════════════════════════════════════════════════════════════════
   admin/event_add.php — Form Tambah Event
   ════════════════════════════════════════════════════════════════ */

include 'includes/auth.php';
$page_title = 'Tambah Event';
include '../includes/header.php';
?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <div class="card shadow-sm border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>Tambah Event Baru
                    </h4>
                </div>

                <div class="card-body p-4">

                    <!-- ══════════════════════════════════════════
                         id="eventAddForm" → digunakan oleh AJAX di script.js
                         enctype wajib ada agar FormData menangkap file upload
                    ═══════════════════════════════════════════ -->
                    <form id="eventAddForm"
                          action="event_save.php"
                          method="POST"
                          enctype="multipart/form-data">

                        <!-- Nama Event -->
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                <i class="fas fa-tag me-2 text-primary"></i>Nama Event
                                <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control form-control-lg"
                                   id="name" name="name"
                                   placeholder="Contoh: Seminar Nasional AI 2025" required>
                        </div>

                        <!-- Deskripsi -->
                        <div class="mb-3">
                            <label for="description" class="form-label">
                                <i class="fas fa-align-left me-2 text-primary"></i>Deskripsi
                            </label>
                            <textarea class="form-control" id="description" name="description"
                                      rows="4" placeholder="Jelaskan detail, tujuan, dan manfaat event…"></textarea>
                        </div>

                        <!-- Tanggal Penyelenggaraan -->
                        <div class="mb-3">
                            <label for="event_date" class="form-label">
                                <i class="fas fa-calendar-day me-2 text-primary"></i>Tanggal Penyelenggaraan
                                <span class="text-danger">*</span>
                            </label>
                            <input type="date" class="form-control form-control-lg"
                                   id="event_date" name="event_date" required>
                            <div class="form-text">Tanggal pelaksanaan acara/kegiatan.</div>
                        </div>

                        <!-- Upload Dokumentasi -->
                        <div class="mb-3">
                            <label for="documentation" class="form-label">
                                <i class="fas fa-image me-2 text-primary"></i>Gambar Event
                            </label>
                            <input type="file" class="form-control" id="documentation"
                                   name="documentation" accept="image/*">
                            <div class="form-text">Max 2MB. Format: JPG, PNG, GIF, WebP.</div>
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
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="umum">Umum (terbuka untuk publik)</option>
                                    <option value="internal">Internal (khusus mahasiswa Unsika)</option>
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
                                       placeholder="100" required>
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
                                        <option value="<?= $cat ?>"><?= $cat ?></option>
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
                                       id="registration_open" name="registration_open" required>
                            </div>
                            <!-- Tanggal Tutup -->
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label">
                                    <i class="fas fa-calendar-times me-2 text-primary"></i>Pendaftaran Ditutup
                                    <span class="text-danger">*</span>
                                </label>
                                <input type="date" class="form-control form-control-lg"
                                       id="registration_close" name="registration_close" required>
                            </div>
                        </div>

                        <!-- Status Aktif -->
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox"
                                   id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                <i class="fas fa-check-circle text-success me-1"></i>
                                Aktifkan event (langsung dapat didaftarkan)
                            </label>
                        </div>

                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary btn-lg px-4">
                                <i class="fas fa-times me-2"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i>Simpan Event
                            </button>
                        </div>

                    </form>
                </div>
            </div><!-- /card -->

        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>