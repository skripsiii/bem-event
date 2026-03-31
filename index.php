<?php
/* ════════════════════════════════════════════════════════════════
   index.php — Halaman Utama Daftar Event
   ════════════════════════════════════════════════════════════════ */

$page_title = 'Daftar Event BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$today = date('Y-m-d');

/* ── Pagination ── */
$page   = max(1, (int) ($_GET['page'] ?? 1));
$limit  = 6;
$offset = ($page - 1) * $limit;

/* ── Search & Filter ── */
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

/* ── WHERE clause ── */
$where      = 'e.is_active = 1 AND e.registration_open <= ? AND e.registration_close >= ?';
$mainTypes  = 'ss';
$mainParams = [$today, $today];

if (!empty($search)) {
    $where        .= ' AND (e.name LIKE ? OR e.description LIKE ?)';
    $searchParam   = '%' . $search . '%';
    $mainTypes    .= 'ss';
    $mainParams[]  = $searchParam;
    $mainParams[]  = $searchParam;
}

if (!empty($category)) {
    $where        .= ' AND e.category = ?';
    $mainTypes    .= 's';
    $mainParams[]  = $category;
}

/* ── Count untuk pagination ── */
$stmtCount = $conn->prepare("SELECT COUNT(*) AS total FROM events e WHERE {$where}");
$stmtCount->bind_param($mainTypes, ...$mainParams);
$stmtCount->execute();
$totalRows  = (int) $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages = (int) ceil($totalRows / $limit);
$stmtCount->close();

/* ── Query utama ── */
$mainSql = "SELECT e.*,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered
            FROM events e
            WHERE {$where}
            ORDER BY e.registration_close ASC
            LIMIT ? OFFSET ?";

$stmt = $conn->prepare($mainSql);
$stmt->bind_param($mainTypes . 'ii', ...[...$mainParams, $limit, $offset]);
$stmt->execute();
$result = $stmt->get_result();

/* ── Konstanta untuk read more ── */
define('DESC_LIMIT', 130);
?>

<div class="container">

    <!-- ── Header ── -->
    <div class="row mb-4">
        <div class="col text-center">
            <h1 class="display-5 fw-bold">Selamat Datang di Sistem Pendaftaran Event</h1>
            <p class="lead text-muted">BEM Fakultas Ilmu Komputer Universitas Singaperbangsa Karawang</p>
        </div>
    </div>

    <!-- ── Search & Filter ── -->
    <div class="row mb-4">
        <div class="col-md-9 mx-auto">
            <form method="GET" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white"><i class="fas fa-search text-muted fa-sm"></i></span>
                        <input type="text" name="search" class="form-control border-start-0 ps-0"
                               placeholder="Cari nama event"
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php foreach (['Seminar','Workshop','Lomba','Sosial','Pelatihan','Lainnya'] as $cat): ?>
                            <option value="<?= $cat ?>" <?= ($category === $cat) ? 'selected' : '' ?>>
                                <?= $cat ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($category)): ?>
                    <div class="col-md-1">
                        <a href="index.php" class="btn btn-outline-secondary w-100" title="Reset filter">
                            <i class="fas fa-times"></i>
                        </a>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- ── Daftar Event ── -->
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($event = $result->fetch_assoc()):
                $registered = (int) $event['registered'];
                $remaining  = $event['quota'] - $registered;
                $isFull     = $remaining <= 0;
                $percent    = $event['quota'] > 0
                              ? min(100, round($registered / $event['quota'] * 100))
                              : 100;

                /* ── Read more prep ── */
                $desc      = $event['description'];
                $shortDesc = mb_strlen($desc) > DESC_LIMIT
                             ? mb_substr($desc, 0, DESC_LIMIT)
                             : $desc;
                $hasMore   = mb_strlen($desc) > DESC_LIMIT;
            ?>
            <div class="col-md-4 mb-4">
                <div class="card event-card h-100 shadow-sm">

                    <!-- Gambar / Placeholder -->
                    <?php if (!empty($event['documentation'])): ?>
                        <img src="<?= BASE_URL ?>uploads/<?= htmlspecialchars($event['documentation']) ?>"
                             class="card-img-top" alt="<?= htmlspecialchars($event['name']) ?>"
                             style="height:185px; object-fit:cover;">
                    <?php else: ?>
                        <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                             style="height:185px;">
                            <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                        </div>
                    <?php endif; ?>

                    <!-- Badge kategori -->
                    <span class="badge bg-info position-absolute top-0 end-0 m-2">
                        <?= htmlspecialchars($event['category']) ?>
                    </span>

                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title"><?= htmlspecialchars($event['name']) ?></h5>

                        <!-- ────── READ MORE DESCRIPTION ────── -->
                        <div class="event-desc-wrap mb-2">
                            <p class="event-desc-text mb-1"
                               data-full="<?= htmlspecialchars($desc) ?>"
                               data-short="<?= htmlspecialchars($shortDesc) ?>">
                                <?= htmlspecialchars($shortDesc) ?><?= $hasMore ? '…' : '' ?>
                            </p>
                            <?php if ($hasMore): ?>
                                <button class="btn-read-more" type="button">
                                    Selengkapnya <i class="fas fa-chevron-down fa-xs ms-1"></i>
                                </button>
                            <?php endif; ?>
                        </div>
                        <!-- ─────────────────────────────────── -->

                        <ul class="list-unstyled small text-muted mt-auto mb-3">
                            <li>
                                <i class="fas fa-calendar me-1 text-primary"></i>
                                <?= date('d M Y', strtotime($event['registration_open'])) ?> &ndash;
                                <?= date('d M Y', strtotime($event['registration_close'])) ?>
                            </li>
                            <li class="mt-1">
                                <i class="fas fa-hourglass-half me-1 text-primary"></i>
                                Sisa waktu:
                                <span class="countdown fw-bold"
                                      data-closing="<?= $event['registration_close'] ?>"></span>
                            </li>
                        </ul>

                        <!-- Progress Bar Kuota -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small mb-1">
                                <span><?= $registered ?> / <?= $event['quota'] ?> peserta</span>
                                <span class="fw-semibold <?= $percent >= 90 ? 'text-danger' : 'text-success' ?>">
                                    <?= $percent ?>%
                                </span>
                            </div>
                            <div class="progress" style="height:6px;">
                                <div class="progress-bar <?= $percent >= 90 ? 'bg-danger' : ($percent >= 70 ? 'bg-warning' : 'bg-success') ?>"
                                     role="progressbar"
                                     style="width:<?= $percent ?>%"
                                     aria-valuenow="<?= $percent ?>" aria-valuemin="0" aria-valuemax="100">
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Daftar -->
                        <?php if ($isFull): ?>
                            <button class="btn btn-secondary w-100" disabled>
                                <i class="fas fa-times-circle me-1"></i> Kuota Penuh
                            </button>
                        <?php else: ?>
                            <a href="register.php?event_id=<?= $event['id'] ?>"
                               class="btn btn-primary w-100">
                                <i class="fas fa-edit me-1"></i> Daftar Sekarang
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endwhile; ?>

            <!-- ── Pagination ── -->
            <?php if ($totalPages > 1): ?>
            <div class="col-12 mt-2 mb-4">
                <nav aria-label="Navigasi halaman">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                &laquo;
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?= ($i === $page) ? 'active' : '' ?>">
                                <a class="page-link"
                                   href="?page=<?= $i ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                            <a class="page-link"
                               href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>&category=<?= urlencode($category) ?>">
                                &raquo;
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="col">
                <div class="alert alert-info text-center py-5">
                    <i class="fas fa-calendar-times fa-3x mb-3 d-block"></i>
                    <h5>Belum ada event yang tersedia</h5>
                    <p class="mb-0">
                        <?php if (!empty($search) || !empty($category)): ?>
                            Tidak ada event yang cocok dengan filter Anda.
                            <a href="index.php">Tampilkan semua event</a>.
                        <?php else: ?>
                            Pantau terus halaman ini untuk event terbaru!
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- ── Flash message sukses (fallback non-AJAX) ── -->
    <?php if (isset($_SESSION['success'])): ?>
        <script>
        $(function() {
            Swal.fire({
                icon             : 'success',
                title            : 'Berhasil!',
                html             : <?= json_encode($_SESSION['success']) ?>,
                confirmButtonColor: '#2563eb'
            });
        });
        </script>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

</div>

<?php
$stmt->close();
$conn->close();
include 'includes/footer.php';
?>