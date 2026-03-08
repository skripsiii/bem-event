<?php
$page_title = 'Form Pendaftaran Event';
include 'includes/header.php';
require_once 'config/database.php';

if (!isset($_GET['event_id']) || empty($_GET['event_id'])) {
    header('Location: index.php');
    exit;
}

$event_id = intval($_GET['event_id']);

$sql = "SELECT * FROM events WHERE id = ? AND is_active = 1 AND registration_open <= CURDATE() AND registration_close >= CURDATE()";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    $_SESSION['error'] = "Event tidak ditemukan atau sudah ditutup.";
    header('Location: index.php');
    exit;
}

$event = $result->fetch_assoc();

// Hitung sisa kuota
$sql_quota = "SELECT COUNT(*) as total FROM registrations WHERE event_id = ?";
$stmt_quota = $conn->prepare($sql_quota);
$stmt_quota->bind_param("i", $event_id);
$stmt_quota->execute();
$quota_result = $stmt_quota->get_result();
$registered = $quota_result->fetch_assoc()['total'];
$remaining = $event['quota'] - $registered;

if ($remaining <= 0) {
    $_SESSION['error'] = "Maaf, kuota untuk event ini sudah penuh.";
    header('Location: index.php');
    exit;
}

$csrf_token=generateCsrfToken();
?>


<div class="container fade-in">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white text-center py-3">
                    <h4 class="mb-0"><i class="fas fa-edit me-2"></i>Form Pendaftaran Event</h4>
                </div>
                <div class="card-body p-4">
                    <div class="mb-4 p-3 bg-light rounded">
                        <h5 class="text-primary mb-2"><?php echo htmlspecialchars($event['name']); ?></h5>
                        <div class="row">
                            <div class="col-sm-6">
                                <small><i class="fas fa-tag me-1 text-primary"></i> Tipe: <?php echo ucfirst($event['event_type']); ?></small><br>
                                <small><i class="fas fa-list me-1 text-primary"></i> Kategori: <?php echo htmlspecialchars($event['category']); ?></small>
                            </div>
                            <div class="col-sm-6">
                                <small><i class="fas fa-users me-1 text-primary"></i> Sisa kuota: <span class="fw-bold"><?php echo $remaining; ?></span> dari <?php echo $event['quota']; ?></small>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($event['documentation'])): ?>
                        <div class="mb-4 text-center">
                            <img src="<?php echo BASE_URL; ?>uploads/<?php echo $event['documentation']; ?>" 
                                class="img-fluid rounded shadow" alt="Dokumentasi Kegiatan" 
                                style="max-height: 300px;">
                            <p class="text-muted mt-2"><i class="fas fa-image me-2"></i>Dokumentasi Kegiatan</p>
                        </div>
                    <?php endif; ?>

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

                    <?php if (isset($_SESSION['success'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?php 
                                echo $_SESSION['success'];
                                unset($_SESSION['success']);
                            ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <form id="registerForm" action="register_process.php" method="POST" enctype="multipart/form-data">
                        
                        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf_token); ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        
                        <div class="mb-3">
                            <label for="full_name" class="form-label">Nama Lengkap</label>
                            <input type="text" class="form-control" id="full_name" name="full_name" required>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="email" name="email" required 
                                placeholder="contoh@email.com">
                        </div>

                        <!-- Field untuk event umum -->
                        <div class="umum-field" <?php echo ($event['event_type'] != 'umum') ? 'style="display:none;"' : ''; ?>>
                            <div class="mb-3">
                                <label for="institution" class="form-label">Instansi/Asal</label>
                                <input type="text" class="form-control" id="institution" name="institution">
                            </div>
                        </div>

                        <!-- Field untuk event internal -->
                        <div class="internal-field" <?php echo ($event['event_type'] != 'internal') ? 'style="display:none;"' : ''; ?>>
                            <div class="mb-3">
                                <label for="npm" class="form-label">NPM</label>
                                <input type="text" class="form-control" id="npm" name="npm">
                            </div>
                            <div class="mb-3">
                                <label for="faculty" class="form-label">Fakultas</label>
                                <input type="text" class="form-control" id="faculty" name="faculty" value="Fakultas Ilmu Komputer">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="phone" class="form-label">Nomor Telepon</label>
                            <input type="tel" class="form-control" id="phone" name="phone" required>
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
            </div>
        </div>
    </div>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<?php
$stmt->close();
$stmt_quota->close();
$conn->close();
include 'includes/footer.php';
?>