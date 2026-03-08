<?php
include 'includes/auth.php';
$page_title = 'Manajemen Event';
include '../includes/header.php';
require_once '../config/database.php';

$sql = "SELECT e.*, (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) as registered FROM events e ORDER BY e.created_at DESC";
$result = $conn->query($sql);
?>

<div class="container fade-in">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary"><i class="fas fa-calendar-alt me-2"></i>Manajemen Event</h2>
        <a href="event_add.php" class="btn btn-primary btn-lg"><i class="fas fa-plus-circle me-2"></i>Tambah Event</a>
    </div>

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

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i>Daftar Event
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-bordered table-hover w-100">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Nama Event</th>
                            <th>Tipe</th>
                            <th>Kuota</th>
                            <th>Pendaftar</th>
                            <th>Sisa</th>
                            <th>Tanggal Buka</th>
                            <th>Tanggal Tutup</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        $no = 1;
                        while ($event = $result->fetch_assoc()): 
                            $registered = $event['registered'];
                            $remaining = $event['quota'] - $registered;
                        ?>
                        <tr>
                            <td><?php echo $no++; ?></td>
                            <td><?php echo htmlspecialchars($event['name']); ?></td>
                            <td><span class="badge <?php echo $event['event_type'] == 'umum' ? 'bg-info' : 'bg-secondary'; ?>"><?php echo ucfirst($event['event_type']); ?></span></td>
                            <td><?php echo $event['quota']; ?></td>
                            <td><?php echo $registered; ?></td>
                            <td>
                                <?php if ($remaining > 0): ?>
                                    <span class="badge bg-success"><?php echo $remaining; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Penuh</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo date('d/m/Y', strtotime($event['registration_open'])); ?></td>
                            <td><?php echo date('d/m/Y', strtotime($event['registration_close'])); ?></td>
                            <td>
                                <?php if ($event['is_active']): ?>
                                    <span class="badge bg-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Tidak Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group" role="group">
                                    <a href="participants.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info" title="Lihat Peserta" data-bs-toggle="tooltip"><i class="fas fa-users"></i></a>
                                    <a href="event_edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-warning" title="Edit" data-bs-toggle="tooltip"><i class="fas fa-edit"></i></a>
                                    <form action="event_delete.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token" 
                                            value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger btn-delete" 
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <a href="toggle_event.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary btn-toggle" title="<?php echo $event['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>" data-bs-toggle="tooltip">
                                        <?php if ($event['is_active']): ?>
                                            <i class="fas fa-ban"></i>
                                        <?php else: ?>
                                            <i class="fas fa-check"></i>
                                        <?php endif; ?>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
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