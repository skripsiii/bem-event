<?php
/* ════════════════════════════════════════════════════════════════
   admin/events.php — Manajemen Event
   ════════════════════════════════════════════════════════════════ */

include 'includes/auth.php';
$page_title = 'Manajemen Event';
include '../includes/header.php';
require_once '../config/database.php';

$sql = "SELECT e.*, COUNT(r.id) AS registered
        FROM events e
        LEFT JOIN registrations r ON r.event_id = e.id
        GROUP BY e.id
        ORDER BY e.created_at DESC";
$result = $conn->query($sql);
$today  = date('Y-m-d');

?>

<div class="container-fluid fade-in px-3 px-md-4 mt-4">

    <!-- ── Header ── -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-primary fs-4 mb-0">
                <i class="fas fa-calendar-alt me-2"></i>Manajemen Event
            </h2>
        </div>
        <a href="event_add.php" class="btn btn-primary btn-sm px-3">
            <i class="fas fa-plus-circle me-1"></i>Tambah Event
        </a>
    </div>

    <!-- ── Flash Messages (fallback non-AJAX) ── -->
    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['success']); unset($_SESSION['success']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i>
            <?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- ── Tabel Event ── -->
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <span><i class="fas fa-list me-2"></i>Daftar Event</span>
            <span class="badge" style="background:#eff6ff;color:var(--c-blue);font-size:.72rem;">
                <?= $result->num_rows ?> event
            </span>
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
                        <?php $no = 1; while ($event = $result->fetch_assoc()):
                            $registered = (int) $event['registered'];
                            $remaining  = $event['quota'] - $registered;

                            // Satu variabel status — dipakai oleh badge DAN tombol toggle
                            if (!$event['is_active']) {
                                $statusLabel = 'nonaktif';
                            } elseif ($today < $event['registration_open']) {
                                $statusLabel = 'belum_buka';
                            } elseif ($today > $event['registration_close']) {
                                $statusLabel = 'ditutup';
                            } elseif ($remaining <= 0) {
                                $statusLabel = 'penuh';
                            } else {
                                $statusLabel = 'aktif';
                            }
                        ?>
                        <tr>
                            <td class="text-center text-muted small"><?= $no++ ?></td>

                            <td>
                                <span class="fw-semibold"><?= htmlspecialchars($event['name']) ?></span>
                                <!-- Tgl event di mobile -->
                                <div class="d-lg-none text-muted small mt-1">
                                    <i class="fas fa-calendar-day me-1"></i>
                                    <?= !empty($event['event_date'])
                                        ? date('d/m/Y', strtotime($event['event_date']))
                                        : '-' ?>
                                </div>
                            </td>

                            <td class="text-center">
                                <span class="badge <?= $event['event_type'] === 'umum' ? 'bg-info' : 'bg-secondary' ?> badge-sm">
                                    <?= ucfirst($event['event_type']) ?>
                                </span>
                            </td>

                            <td class="text-center"><?= $event['quota'] ?></td>
                            <td class="text-center"><?= $registered ?></td>

                            <td class="text-center">
                                <?php if ($remaining > 0): ?>
                                    <span class="badge bg-success badge-sm"><?= $remaining ?></span>
                                <?php else: ?>
                                    <span class="badge bg-danger badge-sm">Penuh</span>
                                <?php endif; ?>
                            </td>

                            <td class="text-center d-none d-lg-table-cell small">
                                <?= !empty($event['event_date'])
                                    ? date('d/m/Y', strtotime($event['event_date']))
                                    : '-' ?>
                            </td>

                            <td class="text-center">
                                <?php
                                $badgeMap = [
                                    'nonaktif'   => ['bg-secondary', 'Nonaktif'],
                                    'belum_buka' => ['bg-info',      'Belum Buka'],
                                    'ditutup'    => ['bg-warning',   'Ditutup'],
                                    'penuh'      => ['bg-danger',    'Kuota Penuh'],
                                    'aktif'      => ['bg-success',   'Aktif'],
                                ];
                                [$badgeClass, $badgeText] = $badgeMap[$statusLabel];
                                ?>
                                <span class="badge <?= $badgeClass ?> badge-sm"><?= $badgeText ?></span>
                            </td>

                            <!-- ── Tombol Aksi (em-btn adalah class yang terdefinisi di CSS) ── -->
                            <td class="text-center">
                                <div class="em-actions">

                                    <!-- Lihat Peserta -->
                                    <a href="participants.php?event_id=<?= $event['id'] ?>"
                                       class="em-btn em-btn-info"
                                       title="Lihat Peserta" data-bs-toggle="tooltip">
                                        <i class="fas fa-users"></i>
                                        <span>Peserta</span>
                                    </a>

                                    <!-- Edit -->
                                    <a href="event_edit.php?id=<?= $event['id'] ?>"
                                       class="em-btn em-btn-warning"
                                       title="Edit Event" data-bs-toggle="tooltip">
                                        <i class="fas fa-edit"></i>
                                        <span>Edit</span>
                                    </a>

                                    <!-- Hapus (AJAX via .btn-delete) -->
                                    <form action="event_delete.php" method="POST" class="d-inline">
                                        <input type="hidden" name="csrf_token"
                                               value="<?= htmlspecialchars(generateCsrfToken()) ?>">
                                        <input type="hidden" name="id" value="<?= $event['id'] ?>">
                                        <button type="button"
                                                class="em-btn em-btn-danger btn-delete"
                                                title="Hapus Event" data-bs-toggle="tooltip">
                                            <i class="fas fa-trash"></i>
                                            <span>Hapus</span>
                                        </button>
                                    </form>

                                    <!-- Toggle Status (AJAX via .btn-toggle) -->
                                    <?php
                                    // Hanya status 'aktif', 'penuh', dan 'belum_buka' yang bisa dinonaktifkan
                                    // 'ditutup' dan 'nonaktif' ditampilkan sebagai tombol aktifkan
                                    $canDeactivate = in_array($statusLabel, ['aktif', 'penuh', 'belum_buka']);
                                    ?>
                                    <a href="toggle_event.php?id=<?= $event['id'] ?>"
                                    class="em-btn <?= $canDeactivate ? 'em-btn-muted' : 'em-btn-success' ?> btn-toggle"
                                    title="<?= $canDeactivate ? 'Nonaktifkan' : 'Aktifkan' ?>"
                                    data-bs-toggle="tooltip">
                                        <i class="fas <?= $canDeactivate ? 'fa-ban' : 'fa-check' ?>"></i>
                                        <span><?= $canDeactivate ? 'Off' : 'On' ?></span>
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

<?php
$conn->close();
include '../includes/footer.php';
?>