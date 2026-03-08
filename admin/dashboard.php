<?php
include 'includes/auth.php';
$page_title = 'Dashboard Admin';
include '../includes/header.php';
require_once '../config/database.php';

// Statistik
$total_events        = $conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'];
$active_events       = $conn->query("SELECT COUNT(*) as c FROM events WHERE is_active = 1")->fetch_assoc()['c'];
$total_registrations = $conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'];
$active_registrations = $conn->query("
    SELECT COUNT(*) as c FROM registrations r 
    INNER JOIN events e ON r.event_id = e.id 
    WHERE e.is_active = 1
")->fetch_assoc()['c'];

// Event terbaru (5)
$latest_events = $conn->query("SELECT * FROM events ORDER BY created_at DESC LIMIT 5");
?>

<div class="container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Admin</h2>
        <div class="text-muted">
            <i class="fas fa-calendar-alt me-1"></i> <?php echo date('l, d F Y'); ?>
        </div>
    </div>
    
    <p class="lead mb-4">Selamat datang, <span class="fw-bold text-primary"><?php echo htmlspecialchars($_SESSION['admin_username']); ?></span>! Kelola event dan peserta dengan mudah.</p>

    <!-- Statistik Cards -->
    <div class="row g-4 mb-5">
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card">
                <div class="stat-content">
                    <p class="text-muted mb-1">Total Event</p>
                    <h3 class="fw-bold"><?php echo $total_events; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-calendar-alt"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card">
                <div class="stat-content">
                    <p class="text-muted mb-1">Event Aktif</p>
                    <h3 class="fw-bold"><?php echo $active_events; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-check-circle"></i>
                </div>
            </div>
        </div>
        <div class="col-sm-6 col-xl-4">
            <div class="dashboard-stat-card">
                <div class="stat-content">
                    <p class="text-muted mb-1">Total Pendaftar</p>
                    <h3 class="fw-bold"><?php echo $total_registrations; ?></h3>
                </div>
                <div class="stat-icon">
                    <i class="fas fa-users"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Aksi Cepat & Event Terbaru -->
    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-bolt me-2"></i>Aksi Cepat
                </div>
                <div class="card-body">
                    <div class="d-grid gap-3">
                        <a href="events.php" class="btn btn-outline-primary btn-lg"><i class="fas fa-list me-2"></i>Kelola Semua Event</a>
                        <a href="event_add.php" class="btn btn-primary btn-lg"><i class="fas fa-plus-circle me-2"></i>Tambah Event Baru</a>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header">
                    <i class="fas fa-clock me-2"></i>Event Terbaru
                </div>
                <div class="card-body p-0">
                    <ul class="list-group list-group-flush">
                        <?php if ($latest_events->num_rows > 0): ?>
                            <?php while($event = $latest_events->fetch_assoc()): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <i class="fas fa-calendar-day text-primary me-2"></i>
                                        <?php echo htmlspecialchars($event['name']); ?>
                                        <span class="badge <?php echo $event['is_active'] ? 'bg-success' : 'bg-danger'; ?> ms-2">
                                            <?php echo $event['is_active'] ? 'Aktif' : 'Tidak Aktif'; ?>
                                        </span>
                                    </div>
                                    <small class="text-muted"><?php echo date('d M Y', strtotime($event['created_at'])); ?></small>
                                </li>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <li class="list-group-item text-muted">Belum ada event.</li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($msg = flash('error')): ?>
    <div class="alert alert-danger"><?= $msg ?></div>
<?php endif; ?>

<?php
$conn->close();
include '../includes/footer.php';
?>