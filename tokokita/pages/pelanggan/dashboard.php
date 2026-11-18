
<?php
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$nama = $_SESSION['nama'] ?? $_SESSION['username'] ?? 'Pelanggan';
$idPelanggan = (int)($_SESSION['id_pelanggan'] ?? 0);

$totalPesanan = 0;
$totalBelanja = 0;
$poin = 0;

if ($idPelanggan > 0) {
    $stmt = $pdo->prepare("
        SELECT COUNT(*) AS jumlah,
               COALESCE(SUM(total_belanja), 0) AS total
        FROM tbl_transaksi
        WHERE id_pelanggan = ?
    ");
    $stmt->execute([$idPelanggan]);
    $dataTransaksi = $stmt->fetch(PDO::FETCH_ASSOC);

    $totalPesanan = $dataTransaksi['jumlah'] ?? 0;
    $totalBelanja = $dataTransaksi['total'] ?? 0;

    $stmt = $pdo->prepare("
        SELECT poin
        FROM tbl_pelanggan
        WHERE id_pelanggan = ?
    ");
    $stmt->execute([$idPelanggan]);
    $poin = $stmt->fetchColumn() ?: 0;
}

$stmt = $pdo->query("
    SELECT id_produk, kode_produk, nama_produk,
           kategori, harga_jual, stok, gambar
    FROM tbl_produk
    WHERE stok > 0
    ORDER BY id_produk DESC
    LIMIT 8
");
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="page-heading">
    <div>
        <h1>Dashboard Pelanggan</h1>
        <p>Selamat datang kembali, <?= e($nama) ?>. Selamat berbelanja!</p>
    </div>
</div>

<div class="customer-banner">
    <div>
        <h2>Belanja Lebih Mudah di TokoKita</h2>
        <p>
            Temukan berbagai kebutuhan harianmu.
            Belanja praktis, mudah, dan nyaman.
        </p>
        <a href="index.php?page=pelanggan_produk" class="banner-button">
            Mulai Belanja
        </a>
    </div>
    <div class="customer-banner-icon">
        <i class="fas fa-shopping-basket"></i>
    </div>
</div>

<div class="stats-grid">

    <div class="stat-card orange">
        <div class="stat-icon">
            <i class="fas fa-shopping-bag"></i>
        </div>
        <div>
            <div class="stat-label">Total Pesanan</div>
            <div class="stat-value"><?= $totalPesanan ?></div>
        </div>
    </div>

    <div class="stat-card navy">
        <div class="stat-icon">
            <i class="fas fa-wallet"></i>
        </div>
        <div>
            <div class="stat-label">Total Belanja</div>
            <div class="stat-value"><?= rupiah($totalBelanja) ?></div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fas fa-star"></i>
        </div>
        <div>
            <div class="stat-label">Poin Member</div>
            <div class="stat-value"><?= number_format($poin) ?></div>
        </div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon">
            <i class="fas fa-box"></i>
        </div>
        <div>
            <div class="stat-label">Produk Tersedia</div>
            <div class="stat-value"><?= count($produk) ?></div>
        </div>
    </div>

</div>

<div class="customer-section">
    <h3>Produk Terbaru</h3>
    <a href="index.php?page=pelanggan_produk">Lihat Semua</a>
</div>

<div class="product-grid">

    <?php foreach ($produk as $item): ?>

        <div class="product-card">

            <div class="product-image">
                <?php if (!empty($item['gambar'])): ?>
                    <img
                        src="assets/uploads/produk/<?= e($item['gambar']) ?>"
                        alt="<?= e($item['nama_produk']) ?>"
                        onerror="this.style.display='none'"
                    >
                <?php else: ?>
                    <i class="fas fa-image product-placeholder"></i>
                <?php endif; ?>
            </div>

            <div class="product-info">

                <div class="product-category">
                    <?= e($item['kategori'] ?: 'Produk') ?>
                </div>

                <div class="product-name">
                    <?= e($item['nama_produk']) ?>
                </div>

                <div class="product-price">
                    <?= rupiah($item['harga_jual']) ?>
                </div>

                <div class="product-stock">
                    Stok tersedia: <?= (int)$item['stok'] ?>
                </div>

                <div class="product-actions">
                    <a
                        href="index.php?page=pelanggan_detail&id=<?= (int)$item['id_produk'] ?>"
                        class="btn-detail"
                    >
                        Detail
                    </a>

                    <a
                        href="index.php?page=pelanggan_detail&id=<?= (int)$item['id_produk'] ?>"
                        class="btn-cart"
                    >
                        Beli
                    </a>
                </div>

            </div>
        </div>

    <?php endforeach; ?>

    <?php if (empty($produk)): ?>
        <div class="card">
            Belum ada produk tersedia.
        </div>
    <?php endif; ?>

</div>
