<?php
include 'includes/auth.php';
$page_title = 'Tambah Event';
include '../includes/header.php';
?>

<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-plus-circle me-2"></i>Tambah Event Baru</h4>
                </div>
                <div class="card-body p-4">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?php 
                                echo $_SESSION['error'];
                                unset($_SESSION['error']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form action="event_save.php" method="POST" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label"><i class="fas fa-tag me-2 text-primary"></i>Nama Event</label>
                            <input type="text" class="form-control form-control-lg" id="name" name="name" placeholder="Contoh: Seminar Nasional AI" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label"><i class="fas fa-align-left me-2 text-primary"></i>Deskripsi</label>
                            <textarea class="form-control" id="description" name="description" rows="4" placeholder="Jelaskan detail event..."></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="documentation" class="form-label">Dokumentasi (Gambar)</label>
                            <input type="file" class="form-control" id="documentation" name="documentation" accept="image/*">
                            <small class="text-muted">Upload gambar dokumentasi kegiatan (max 2MB, format: jpg, jpeg, png)</small>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="event_type" class="form-label"><i class="fas fa-tasks me-2 text-primary"></i>Tipe Event</label>
                                <select class="form-select form-select-lg" id="event_type" name="event_type" required>
                                    <option value="">-- Pilih Tipe --</option>
                                    <option value="umum">Umum (untuk publik)</option>
                                    <option value="internal">Internal (khusus mahasiswa Unsika)</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="quota" class="form-label"><i class="fas fa-users me-2 text-primary"></i>Kuota Peserta</label>
                                <input type="number" class="form-control form-control-lg" id="quota" name="quota" min="1" placeholder="100" required>
                            </div>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="category" class="form-label"><i class="fas fa-tag me-2 text-primary"></i>Kategori Event</label>
                                <select class="form-select form-select-lg" id="category" name="category" required>
                                    <option value="">-- Pilih Kategori --</option>
                                    <option value="Seminar">Seminar</option>
                                    <option value="Workshop">Workshop</option>
                                    <option value="Lomba">Lomba</option>
                                    <option value="Sosial">Sosial</option>
                                    <option value="Pelatihan">Pelatihan</option>
                                    <option value="Lainnya">Lainnya</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="price" class="form-label"><i class="fas fa-money-bill me-2 text-primary"></i>Biaya Pendaftaran (Rp)</label>
                                <input type="number" class="form-control form-control-lg" id="price" name="price" min="0" step="1000" value="0" placeholder="0 = gratis">
                            </div>
                        </div>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="registration_open" class="form-label"><i class="fas fa-calendar-plus me-2 text-primary"></i>Tanggal Buka</label>
                                <input type="date" class="form-control form-control-lg" id="registration_open" name="registration_open" required>
                            </div>
                            <div class="col-md-6">
                                <label for="registration_close" class="form-label"><i class="fas fa-calendar-times me-2 text-primary"></i>Tanggal Tutup</label>
                                <input type="date" class="form-control form-control-lg" id="registration_close" name="registration_close" required>
                            </div>
                        </div>
                        <div class="form-check mb-4">
                            <input class="form-check-input" type="checkbox" id="is_active" name="is_active" value="1" checked>
                            <label class="form-check-label" for="is_active">
                                Aktifkan event (dapat didaftar oleh user)
                            </label>
                        </div>
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="events.php" class="btn btn-outline-secondary btn-lg px-4"><i class="fas fa-times me-2"></i>Batal</a>
                            <button type="submit" class="btn btn-primary btn-lg px-5"><i class="fas fa-save me-2"></i>Simpan</button>
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

<?php include '../includes/footer.php'; ?>