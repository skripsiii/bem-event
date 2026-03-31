<?php
/* ════════════════════════════════════════════════════════════════
   register.php — Form Pendaftaran Event
   ════════════════════════════════════════════════════════════════ */

$page_title = 'Form Pendaftaran Event';
include 'includes/header.php';
require_once 'config/database.php';

if (empty($_GET['event_id'])) {
    header('Location: index.php');
    exit;
}

$event_id = intval($_GET['event_id']);

/* ── Ambil event yang aktif & dalam masa pendaftaran ── */
$stmt = $conn->prepare(
    "SELECT * FROM events
      WHERE id = ? AND is_active = 1
        AND registration_open  <= CURDATE()
        AND registration_close >= CURDATE()"
);
$stmt->bind_param('i', $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Event tidak ditemukan atau pendaftaran sudah ditutup.';
    header('Location: index.php');
    exit;
}
$event = $result->fetch_assoc();
$stmt->close();

/* ── Cek sisa kuota ── */
$stmtQ = $conn->prepare('SELECT COUNT(*) AS total FROM registrations WHERE event_id = ?');
$stmtQ->bind_param('i', $event_id);
$stmtQ->execute();
$registered = (int) $stmtQ->get_result()->fetch_assoc()['total'];
$remaining  = $event['quota'] - $registered;
$stmtQ->close();

if ($remaining <= 0) {
    $_SESSION['error'] = 'Maaf, kuota untuk event ini sudah penuh.';
    header('Location: index.php');
    exit;
}

$csrf_token = generateCsrfToken();
$conn->close();
?>

<div class="container fade-in py-2">
    <div class="row justify-content-center">
        <div class="col-lg-8">

            <!-- ── Header Card ── -->
            <div class="card shadow-sm border-0 mb-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0">
                        <i class="fas fa-edit me-2"></i>Form Pendaftaran Event
                    </h4>
                </div>

                <div class="card-body p-4">

                    <!-- ── Info Event ── -->
                    <div class="p-3 rounded mb-4" style="background:var(--c-canvas);border:1px solid var(--c-border);">
                        <h5 class="text-primary fw-bold mb-3">
                            <i class="fas fa-calendar-alt me-2"></i><?= htmlspecialchars($event['name']) ?>
                        </h5>
                        <div class="row g-2 small">
                            <div class="col-sm-6">
                                <i class="fas fa-tag me-1 text-primary"></i>
                                <strong>Tipe:</strong> <?= ucfirst($event['event_type']) ?>
                            </div>
                            <div class="col-sm-6">
                                <i class="fas fa-list me-1 text-primary"></i>
                                <strong>Kategori:</strong> <?= htmlspecialchars($event['category']) ?>
                            </div>
                            <div class="col-sm-6">
                                <i class="fas fa-calendar-day me-1 text-primary"></i>
                                <strong>Diselenggarakan:</strong>
                                <?= !empty($event['event_date'])
                                    ? date('d M Y', strtotime($event['event_date']))
                                    : '-' ?>
                            </div>
                            <div class="col-sm-6">
                                <i class="fas fa-users me-1 text-primary"></i>
                                <strong>Sisa kuota:</strong>
                                <span class="fw-bold text-<?= $remaining <= 10 ? 'danger' : 'success' ?>">
                                    <?= $remaining ?>
                                </span>
                                dari <?= $event['quota'] ?>
                            </div>
                            <div class="col-12">
                                <i class="fas fa-calendar-check me-1 text-primary"></i>
                                <strong>Pendaftaran:</strong>
                                <?= date('d M Y', strtotime($event['registration_open'])) ?>
                                &ndash;
                                <?= date('d M Y', strtotime($event['registration_close'])) ?>
                            </div>
                        </div>
                    </div>

                    <!-- ── Gambar Event ── -->
                    <?php if (!empty($event['documentation'])): ?>
                        <div class="mb-4 text-center">
                            <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                                 class="img-fluid rounded shadow-sm" alt="Dokumentasi Kegiatan"
                                 style="max-height:280px; object-fit:cover;">
                        </div>
                    <?php endif; ?>

                    <!-- ── Fallback flash (non-AJAX) ── -->
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle me-2"></i>
                            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- ═══════════════════════════════════════
                         FORM PENDAFTARAN
                         data-event-type dipakai oleh JS untuk validasi tipe event
                    ═══════════════════════════════════════ -->
                    <form id="registerForm"
                          action="register_process.php"
                          method="POST"
                          data-event-type="<?= htmlspecialchars($event['event_type']) ?>">

                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token) ?>">
                        <input type="hidden" name="event_id"   value="<?= $event_id ?>">

                        <div class="mb-3">
                            <label for="full_name" class="form-label">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <input type="text" class="form-control" id="full_name" name="full_name"
                                   placeholder="Masukkan nama lengkap Anda" required autocomplete="name">
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">
                                Email <span class="text-danger">*</span>
                            </label>
                            <input type="email" class="form-control" id="email" name="email"
                                   placeholder="contoh@email.com" required autocomplete="email">
                            <div class="form-text">Email konfirmasi akan dikirim ke alamat ini.</div>
                        </div>

                        <!-- Field Event Umum -->
                        <div class="umum-field"
                             <?= ($event['event_type'] !== 'umum') ? 'style="display:none;"' : '' ?>>
                            <div class="mb-3">
                                <label for="institution" class="form-label">
                                    Instansi / Asal <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="institution" name="institution"
                                       placeholder="Nama universitas, perusahaan, atau institusi Anda">
                            </div>
                        </div>

                        <!-- Field Event Internal -->
                        <div class="internal-field"
                             <?= ($event['event_type'] !== 'internal') ? 'style="display:none;"' : '' ?>>
                            <div class="mb-3">
                                <label for="npm" class="form-label">
                                    NPM <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="npm" name="npm"
                                       placeholder="13 digit NPM Anda" maxlength="13"
                                       pattern="[0-9]{13}">
                            </div>
                            <div class="mb-3">
                                <label for="faculty" class="form-label">
                                    Fakultas <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="faculty" name="faculty"
                                       placeholder="Masukkan nama fakultas secara lengkap" required autocomplete="faculty">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="phone" class="form-label">
                                Nomor Telepon <span class="text-danger">*</span>
                            </label>
                            <input type="tel" class="form-control" id="phone" name="phone"
                                   placeholder="08xxxxxxxxxx" maxlength="13"
                                   pattern="[0-9]{10,13}" autocomplete="tel">
                            <div class="form-text">Format: 10–13 digit angka tanpa spasi.</div>
                        </div>

                        <div class="d-flex gap-3">
                            <a href="index.php" class="btn btn-outline-secondary w-50">
                                <i class="fas fa-arrow-left me-2"></i>Kembali
                            </a>
                            <button type="submit" class="btn btn-primary w-50">
                                <i class="fas fa-paper-plane me-2"></i>Daftar Sekarang
                            </button>
                        </div>

                    </form>
                </div>
            </div><!-- /card -->

        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>