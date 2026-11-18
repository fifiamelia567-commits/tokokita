
<?php
require_once __DIR__ . '/../../config/database.php';

$cari = trim($_GET['cari'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$sql = "
    SELECT id_produk, kode_produk, nama_produk,
           kategori, harga_jual, stok, gambar
    FROM tbl_produk
    WHERE stok > 0
";

$params = [];

if ($cari !== '') {
    $sql .= " AND (nama_produk LIKE ? OR kode_produk LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

if ($kategori !== '') {
    $sql .= " AND kategori = ?";
    $params[] = $kategori;
}

$sql .= " ORDER BY nama_produk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmtKategori = $pdo->query("
    SELECT DISTINCT kategori
    FROM tbl_produk
    WHERE kategori IS NOT NULL AND kategori != ''
    ORDER BY kategori
");
$daftarKategori = $stmtKategori->fetchAll(PDO::FETCH_COLUMN);
?>

<div class="page-heading">
    <div>
        <h1>Katalog Produk</h1>
        <p>Temukan dan pilih produk kebutuhanmu.</p>
    </div>
</div>

<div class="card">

    <form method="GET" action="index.php" class="product-toolbar">

        <input type="hidden" name="page" value="pelanggan_produk">

        <input
            type="text"
            name="cari"
            placeholder="Cari nama atau kode produk..."
            value="<?= e($cari) ?>"
        >

        <select name="kategori" onchange="this.form.submit()">
            <option value="">Semua Kategori</option>

            <?php foreach ($daftarKategori as $kat): ?>
                <option
                    value="<?= e($kat) ?>"
                    <?= $kategori === $kat ? 'selected' : '' ?>
                >
                    <?= e($kat) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <button type="submit" class="btn-primary-custom">
            Cari
        </button>

    </form>

    <div class="customer-section">
        <h3>Daftar Produk</h3>
        <span><?= count($produk) ?> produk ditemukan</span>
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
                        Stok: <?= (int)$item['stok'] ?>
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

    </div>

    <?php if (empty($produk)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>Produk Tidak Ditemukan</h3>
            <p>Coba gunakan kata kunci pencarian lainnya.</p>
        </div>
    <?php endif; ?>

</div>
