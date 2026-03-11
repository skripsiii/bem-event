<?php
include 'includes/auth.php';
require_once '../config/database.php';

$event_id = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;
if ($event_id == 0) {
    header('Location: events.php');
    exit;
}

// Ambil data event
$sql_event = "SELECT * FROM events WHERE id = ?";
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$event_result = $stmt_event->get_result();

if ($event_result->num_rows == 0) {
    $_SESSION['error'] = "Event tidak ditemukan.";
    header('Location: events.php');
    exit;
}
$event = $event_result->fetch_assoc();

// Ambil daftar peserta
$sql_participants = "SELECT * FROM registrations WHERE event_id = ? ORDER BY registered_at DESC";
$stmt_participants = $conn->prepare($sql_participants);
$stmt_participants->bind_param("i", $event_id);
$stmt_participants->execute();
$participants = $stmt_participants->get_result();

$page_title = 'Peserta: ' . $event['name'];
include '../includes/header.php';
?>

<div class="container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-users me-2"></i>Peserta Event</h2>
        <div>
            <a href="events.php" class="btn btn-outline-secondary me-2">
                <i class="fas fa-arrow-left me-2"></i>Kembali
            </a>
            <a href="export_excel.php?event_id=<?php echo $event_id; ?>" class="btn btn-success">
                <i class="fas fa-file-csv me-2"></i>Export CSV
            </a>
        </div>
    </div>

    <!-- Info Event -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?php echo htmlspecialchars($event['name']); ?></h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-sm-6">
                    <p><strong>Kategori:</strong> <?php echo htmlspecialchars($event['category'] ?? '-'); ?></p>
                    <p><strong>Tipe:</strong> <?php echo ucfirst($event['event_type']); ?></p>
                </div>
                <div class="col-sm-6">
                    <p><strong>Kuota:</strong> <?php echo $event['quota']; ?></p>
                    <p><strong>Pendaftar:</strong> <?php echo $participants->num_rows; ?></p>
                </div>
            </div>
        </div>
    </div>

    <!-- Notifikasi -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Tabel Peserta -->
    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i>Daftar Peserta
        </div>
        <div class="card-body">
            <?php if ($participants->num_rows > 0): ?>
                <div class="table-responsive">
                    <table id="dataTable" class="table table-bordered table-hover w-100">
                        <thead>
                            <tr>
                                <th>No</th>
                                <th>Nama</th>
                                <?php if ($event['event_type'] == 'umum'): ?>
                                    <th>Instansi</th>
                                <?php else: ?>
                                    <th>NPM</th>
                                    <th>Fakultas</th>
                                <?php endif; ?>
                                <th>Telepon</th>
                                <th>Waktu Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $no = 1; while ($p = $participants->fetch_assoc()): ?>
                            <tr>
                                <td><?= $no++ ?></td>
                                <td><?= htmlspecialchars($p['full_name']) ?></td>
                                <?php if ($event['event_type'] == 'umum'): ?>
                                    <td><?= htmlspecialchars($p['institution'] ?? '-') ?></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars($p['npm'] ?? '-') ?></td>
                                    <td><?= htmlspecialchars($p['faculty'] ?? '-') ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars($p['phone']) ?></td>
                                <td><?= date('d/m/Y H:i', strtotime($p['registered_at'])) ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>Belum ada peserta yang mendaftar.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<?php
$stmt_event->close();
$stmt_participants->close();
$conn->close();
include '../includes/footer.php';
?>