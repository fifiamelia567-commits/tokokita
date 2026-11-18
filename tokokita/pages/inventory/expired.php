
<?php
// Pastikan koneksi PDO tersedia dari index.php

$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? 'all';

$sql = "SELECT * FROM tbl_produk
        WHERE tanggal_kadaluarsa IS NOT NULL";

$params = [];

if ($search !== '') {
    $sql .= " AND (nama_produk LIKE ? OR kode_produk LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($status === 'expired') {
    $sql .= " AND tanggal_kadaluarsa < CURDATE()";
} elseif ($status === 'soon') {
    $sql .= " AND tanggal_kadaluarsa >= CURDATE()
              AND tanggal_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
} elseif ($status === 'safe') {
    $sql .= " AND tanggal_kadaluarsa > DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
}

$sql .= " ORDER BY tanggal_kadaluarsa ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$totalExpired = $pdo->query("
    SELECT COUNT(*) FROM tbl_produk
    WHERE tanggal_kadaluarsa < CURDATE()
")->fetchColumn();

$totalSoon = $pdo->query("
    SELECT COUNT(*) FROM tbl_produk
    WHERE tanggal_kadaluarsa >= CURDATE()
    AND tanggal_kadaluarsa <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();

$totalSafe = $pdo->query("
    SELECT COUNT(*) FROM tbl_produk
    WHERE tanggal_kadaluarsa > DATE_ADD(CURDATE(), INTERVAL 30 DAY)
")->fetchColumn();

$totalProducts = $pdo->query("
    SELECT COUNT(*) FROM tbl_produk
    WHERE tanggal_kadaluarsa IS NOT NULL
")->fetchColumn();
?>

<div class="container-fluid px-4">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 mt-3">
        <div>
            <h4 class="fw-bold mb-1">Expired Products</h4>
            <p class="text-muted mb-0">
                Pantau tanggal kedaluwarsa produk toko Anda.
            </p>
        </div>

        <a href="index.php?page=products"
           class="btn btn-outline-secondary rounded-3">
            <i class="fas fa-arrow-left me-2"></i>
            Kembali ke Produk
        </a>
    </div>

    <!-- Statistics -->
    <div class="row g-3 mb-4">

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Total Produk</p>
                            <h3 class="fw-bold mb-0">
                                <?= number_format($totalProducts) ?>
                            </h3>
                        </div>
                        <div class="rounded-3 p-3 bg-light">
                            <i class="fas fa-boxes fa-lg text-primary"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Sudah Expired</p>
                            <h3 class="fw-bold text-danger mb-0">
                                <?= number_format($totalExpired) ?>
                            </h3>
                        </div>
                        <div class="rounded-3 p-3 bg-danger bg-opacity-10">
                            <i class="fas fa-calendar-times fa-lg text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Segera Expired</p>
                            <h3 class="fw-bold text-warning mb-0">
                                <?= number_format($totalSoon) ?>
                            </h3>
                        </div>
                        <div class="rounded-3 p-3 bg-warning bg-opacity-10">
                            <i class="fas fa-clock fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between">
                        <div>
                            <p class="text-muted mb-2">Masih Aman</p>
                            <h3 class="fw-bold text-success mb-0">
                                <?= number_format($totalSafe) ?>
                            </h3>
                        </div>
                        <div class="rounded-3 p-3 bg-success bg-opacity-10">
                            <i class="fas fa-shield-alt fa-lg text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Product Table -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h5 class="fw-bold mb-1">Daftar Produk Kedaluwarsa</h5>
                    <p class="text-muted small mb-0">
                        Informasi masa berlaku seluruh produk.
                    </p>
                </div>

                <span class="badge rounded-pill bg-light text-dark px-3 py-2">
                    <?= count($products) ?> Produk
                </span>
            </div>

            <!-- Search and Filter -->
            <form method="GET" action="index.php" class="row g-2 mb-4">

                <input type="hidden" name="page" value="expired">

                <div class="col-md-5">
                    <div class="input-group">
                        <span class="input-group-text bg-white">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text"
                               name="search"
                               class="form-control"
                               placeholder="Cari nama atau kode produk..."
                               value="<?= htmlspecialchars($search) ?>">
                    </div>
                </div>

                <div class="col-md-3">
                    <select name="status" class="form-select">
                        <option value="all" <?= $status === 'all' ? 'selected' : '' ?>>
                            Semua Status
                        </option>
                        <option value="expired" <?= $status === 'expired' ? 'selected' : '' ?>>
                            Sudah Expired
                        </option>
                        <option value="soon" <?= $status === 'soon' ? 'selected' : '' ?>>
                            Segera Expired
                        </option>
                        <option value="safe" <?= $status === 'safe' ? 'selected' : '' ?>>
                            Masih Aman
                        </option>
                    </select>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn w-100 text-white"
                            style="background:#f5822b">
                        <i class="fas fa-filter me-1"></i> Filter
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="index.php?page=expired"
                       class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>

            </form>

            <!-- Table -->
            <div class="table-responsive">
                <table class="table align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th>Kategori</th>
                            <th>Stok</th>
                            <th>Tanggal Expired</th>
                            <th>Status</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (count($products) > 0): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($products as $product): ?>

                            <?php
                            $today = new DateTime('today');
                            $expiry = new DateTime($product['tanggal_kadaluarsa']);
                            $days = (int)$today->diff($expiry)->format('%r%a');

                            if ($days < 0) {
                                $badge = 'bg-danger';
                                $label = 'Expired';
                            } elseif ($days <= 30) {
                                $badge = 'bg-warning text-dark';
                                $label = 'Segera Expired';
                            } else {
                                $badge = 'bg-success';
                                $label = 'Aman';
                            }
                            ?>

                            <tr>
                                <td><?= $no++ ?></td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= htmlspecialchars($product['kode_produk']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= htmlspecialchars($product['nama_produk']) ?>
                                    </span>
                                </td>

                                <td>
                                    <?= htmlspecialchars($product['kategori'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= number_format($product['stok']) ?>
                                </td>

                                <td>
                                    <?= date(
                                        'd M Y',
                                        strtotime($product['tanggal_kadaluarsa'])
                                    ) ?>
                                </td>

                                <td>
                                    <span class="badge <?= $badge ?> rounded-pill px-3 py-2">
                                        <?= $label ?>
                                    </span>
                                </td>
                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="7" class="text-center py-5">
                                <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                                <h6 class="fw-bold">Tidak Ada Produk</h6>
                                <p class="text-muted mb-0">
                                    Data produk tidak ditemukan.
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>
            </div>

        </div>
    </div>

</div>
