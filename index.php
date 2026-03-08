<?php
$page_title = 'Daftar Event BEM Fasilkom Unsika';
include 'includes/header.php';
require_once 'config/database.php';

$today = date('Y-m-d');

// ===== PAGINATION =====
$page   = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$limit  = 6;
$offset = ($page - 1) * $limit;

// ===== SEARCH & FILTER =====
$search   = isset($_GET['search'])   ? trim($_GET['search'])   : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';

// ===== BUILD WHERE CLAUSE =====
// Selalu sertakan is_active = 1 agar event nonaktif tidak muncul
$where      = "e.is_active = 1 AND e.registration_open <= ? AND e.registration_close >= ?";
$mainTypes  = "ss";
$mainParams = [$today, $today];

if (!empty($search)) {
    $where        .= " AND (e.name LIKE ? OR e.description LIKE ?)";
    $searchParam   = "%" . $search . "%";
    $mainTypes    .= "ss";
    $mainParams[]  = $searchParam;
    $mainParams[]  = $searchParam;
}

if (!empty($category)) {
    $where        .= " AND e.category = ?";
    $mainTypes    .= "s";
    $mainParams[]  = $category;
}

// ===== HITUNG TOTAL UNTUK PAGINATION (pakai params yang sama) =====
$countSql    = "SELECT COUNT(*) as total FROM events e WHERE $where";
$stmtCount   = $conn->prepare($countSql);
$stmtCount->bind_param($mainTypes, ...$mainParams);
$stmtCount->execute();
$totalRows   = $stmtCount->get_result()->fetch_assoc()['total'];
$totalPages  = ceil($totalRows / $limit);
$stmtCount->close();

// ===== QUERY UTAMA: tambahkan LIMIT & OFFSET di akhir =====
$mainSql = "SELECT e.*,
                (SELECT COUNT(*) FROM registrations r WHERE r.event_id = e.id) AS registered
            FROM events e
            WHERE $where
            ORDER BY e.registration_close ASC
            LIMIT ? OFFSET ?";

$finalTypes    = $mainTypes . "ii";
$finalParams   = array_merge($mainParams, [$limit, $offset]);

$stmt = $conn->prepare($mainSql);
$stmt->bind_param($finalTypes, ...$finalParams);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
    <div class="row mb-4">
        <div class="col text-center">
            <h1 class="display-4 fw-bold">Selamat Datang di Sistem Pendaftaran Event</h1>
            <p class="lead text-muted">BEM Fakultas Ilmu Komputer Universitas Singaperbangsa Karawang</p>
        </div>
    </div>

    <!-- Form Pencarian dan Filter -->
    <div class="row mb-4">
        <div class="col-md-8 mx-auto">
            <form method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari event..."
                           value="<?php echo htmlspecialchars($search); ?>">
                </div>
                <div class="col-md-4">
                    <select name="category" class="form-select">
                        <option value="">Semua Kategori</option>
                        <?php
                        $categories = ['Seminar','Workshop','Lomba','Sosial','Pelatihan','Lainnya'];
                        foreach ($categories as $cat):
                        ?>
                            <option value="<?php echo $cat; ?>"
                                <?php echo ($category === $cat) ? 'selected' : ''; ?>>
                                <?php echo $cat; ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Filter
                    </button>
                </div>
                <?php if (!empty($search) || !empty($category)): ?>
                <div class="col-md-1">
                    <a href="index.php" class="btn btn-outline-secondary w-100" title="Reset">
                        <i class="fas fa-times"></i>
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Daftar Event -->
    <div class="row">
        <?php if ($result->num_rows > 0): ?>
            <?php while ($event = $result->fetch_assoc()):
                $registered = (int)$event['registered'];
                $remaining  = $event['quota'] - $registered;
                $isFull     = ($remaining <= 0);
                $percent    = $event['quota'] > 0
                              ? min(100, round($registered / $event['quota'] * 100))
                              : 100;
            ?>
                <div class="col-md-4 mb-4">
                    <div class="card event-card h-100 shadow-sm">
                        <?php if (!empty($event['documentation'])): ?>
                            <img src="<?php echo BASE_URL; ?>uploads/<?php echo htmlspecialchars($event['documentation']); ?>"
                                 class="card-img-top" alt="Dokumentasi"
                                 style="height:180px; object-fit:cover;">
                        <?php else: ?>
                            <div class="card-img-top d-flex align-items-center justify-content-center bg-light"
                                 style="height:180px;">
                                <i class="fas fa-calendar-alt fa-3x text-muted"></i>
                            </div>
                        <?php endif; ?>

                        <!-- Badge kategori -->
                        <span class="badge bg-info position-absolute top-0 end-0 m-2">
                            <?php echo htmlspecialchars($event['category']); ?>
                        </span>

                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h5>
                            <p class="card-text text-muted small">
                                <?php echo nl2br(htmlspecialchars(mb_substr($event['description'], 0, 100))); ?>...
                            </p>

                            <ul class="list-unstyled small text-muted mt-auto mb-3">
                                <li><i class="fas fa-money-bill me-1 text-primary"></i>
                                    <?php echo $event['price'] > 0
                                        ? 'Rp ' . number_format($event['price'], 0, ',', '.')
                                        : '<span class="text-success fw-bold">Gratis</span>'; ?>
                                </li>
                                <li><i class="fas fa-calendar me-1 text-primary"></i>
                                    <?php echo date('d M Y', strtotime($event['registration_open'])); ?> &ndash;
                                    <?php echo date('d M Y', strtotime($event['registration_close'])); ?>
                                </li>
                                <li><i class="fas fa-hourglass-half me-1 text-primary"></i>
                                    Sisa waktu:
                                    <span class="countdown fw-bold"
                                          data-closing="<?php echo $event['registration_close']; ?>">
                                    </span>
                                </li>
                            </ul>

                            <!-- Progress bar kuota -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span><?php echo $registered; ?> / <?php echo $event['quota']; ?> peserta</span>
                                    <span><?php echo $percent; ?>%</span>
                                </div>
                                <div class="progress" style="height:6px;">
                                    <div class="progress-bar <?php echo $percent >= 90 ? 'bg-danger' : 'bg-success'; ?>"
                                         role="progressbar"
                                         style="width:<?php echo $percent; ?>%"></div>
                                </div>
                            </div>

                            <?php if ($isFull): ?>
                                <button class="btn btn-secondary w-100" disabled>
                                    <i class="fas fa-times-circle me-1"></i> Kuota Penuh
                                </button>
                            <?php else: ?>
                                <a href="register.php?event_id=<?php echo $event['id']; ?>"
                                   class="btn btn-primary w-100">
                                    <i class="fas fa-edit me-1"></i> Daftar Sekarang
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>

            <!-- Pagination -->
            <?php if ($totalPages > 1): ?>
            <div class="col-12 mt-2 mb-4">
                <nav aria-label="Navigasi halaman">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="?page=<?php echo $page - 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                               &laquo;
                            </a>
                        </li>
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                                <a class="page-link"
                                   href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
                                   <?php echo $i; ?>
                                </a>
                            </li>
                        <?php endfor; ?>
                        <li class="page-item <?php echo ($page >= $totalPages) ? 'disabled' : ''; ?>">
                            <a class="page-link"
                               href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&category=<?php echo urlencode($category); ?>">
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
</div>

<?php
$stmt->close();
$conn->close();
include 'includes/footer.php';
?>