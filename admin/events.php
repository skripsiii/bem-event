<?php
include 'includes/auth.php';
$page_title = 'Manajemen Event';
include '../includes/header.php';
require_once '../config/database.php';

$sql = "SELECT e.*, COUNT(r.id) as registered
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.id
        GROUP BY e.id
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
$today  = date('Y-m-d');
?>

<div class="container-fluid fade-in px-3 px-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="fw-bold text-primary fs-4 mb-0">
            <i class="fas fa-calendar-alt me-2"></i>Manajemen Event
        </h2>
        <a href="event_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus-circle me-1"></i>Tambah
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <i class="fas fa-list me-2"></i>Daftar Event
        </div>
        <div class="card-body p-0 p-md-3">
            <div class="table-responsive">
                <table id="dataTable" class="table table-bordered table-hover table-sm w-100 align-middle">
                    <thead>
                        <tr>
                            <th class="text-center col-no">No</th>
                            <th>Nama Event</th>
                            <th class="text-center col-tipe">Tipe</th>
                            <th class="text-center col-num">Kuota</th>
                            <th class="text-center col-num">Daftar</th>
                            <th class="text-center col-num">Sisa</th>
                            <th class="text-center col-date d-none d-lg-table-cell">Tgl Event</th>
                            <th class="text-center col-status">Status</th>
                            <th class="text-center col-aksi">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $no = 1;
                        while ($event = $result->fetch_assoc()):
                            $registered = $event['registered'];
                            $remaining  = $event['quota'] - $registered;
                        ?>
                        <tr>
                            <td class="text-center"><?php echo $no++; ?></td>
                            <td>
                                <span class="fw-semibold"><?php echo htmlspecialchars($event['name']); ?></span>
                                <div class="d-lg-none text-muted small mt-1">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    <?php echo !empty($event['event_date'])
                                        ? date('d/m/Y', strtotime($event['event_date']))
                                        : '-'; ?>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="badge <?php echo $event['event_type'] == 'umum' ? 'bg-info' : 'bg-secondary'; ?> badge-sm">
                                    <?php echo ucfirst($event['event_type']); ?>
                                </span>
                            </td>
                            <td class="text-center"><?php echo $event['quota']; ?></td>
                            <td class="text-center"><?php echo $registered; ?></td>
                            <td class="text-center">
                                <?php if ($remaining > 0): ?>
                                    <span class="badge bg-success badge-sm"><?php echo $remaining; ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger badge-sm">Penuh</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center d-none d-lg-table-cell">
                                <?php echo !empty($event['event_date'])
                                    ? date('d/m/Y', strtotime($event['event_date']))
                                    : '-'; ?>
                            </td>
                            <td class="text-center">
                                <?php if (!$event['is_active']): ?>
                                    <span class="badge bg-danger badge-sm">Nonaktif</span>
                                <?php elseif ($remaining <= 0): ?>
                                    <span class="badge bg-danger badge-sm">Kuota Penuh</span>
                                <?php elseif ($today > $event['registration_close']): ?>
                                    <span class="badge bg-danger badge-sm">Ditutup</span>
                                <?php else: ?>
                                    <span class="badge bg-success badge-sm">Aktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="action-btns">
                                    <a href="participants.php?event_id=<?php echo $event['id']; ?>"
                                       class="action-btn btn-act-info"
                                       title="Lihat Peserta" data-bs-toggle="tooltip">
                                        <i class="fas fa-users"></i>
                                    </a>
                                    <a href="event_edit.php?id=<?php echo $event['id']; ?>"
                                       class="action-btn btn-act-warning"
                                       title="Edit" data-bs-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form action="event_delete.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token"
                                               value="<?php echo htmlspecialchars(generateCsrfToken()); ?>">
                                        <input type="hidden" name="id" value="<?php echo $event['id']; ?>">
                                        <button type="submit"
                                                class="action-btn btn-act-danger btn-delete"
                                                title="Hapus" data-bs-toggle="tooltip">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                    <a href="toggle_event.php?id=<?php echo $event['id']; ?>"
                                       class="action-btn <?php echo $event['is_active'] ? 'btn-act-secondary' : 'btn-act-success'; ?> btn-toggle"
                                       title="<?php echo $event['is_active'] ? 'Nonaktifkan' : 'Aktifkan'; ?>"
                                       data-bs-toggle="tooltip">
                                        <i class="fas <?php echo $event['is_active'] ? 'fa-ban' : 'fa-check'; ?>"></i>
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