
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM tbl_produk
    WHERE id_produk = ?
    LIMIT 1
");
$stmt->execute([$id]);
$produk = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$produk) {
    echo '<div class="card">Produk tidak ditemukan.</div>';
    return;
}

?>

<div class="page-heading">
    <div>
        <h1>Detail Produk</h1>
        <p>Lihat informasi lengkap produk pilihanmu.</p>
    </div>
    <a href="index.php?page=pelanggan_produk" class="btn-primary-custom">
        Kembali
    </a>
</div>

<div class="card">
    <div class="detail-product">

        <div class="detail-image">
            <?php if (!empty($produk['gambar'])): ?>
                <img
                    src="assets/uploads/produk/<?= e($produk['gambar']) ?>"
                    alt="<?= e($produk['nama_produk']) ?>"
                >
            <?php else: ?>
                <i class="fas fa-image product-placeholder"></i>
            <?php endif; ?>
        </div>

        <div class="detail-info">

            <div class="product-category">
                <?= e($produk['kategori'] ?: 'Produk') ?>
            </div>

            <h2><?= e($produk['nama_produk']) ?></h2>

            <div class="detail-price">
                <?= rupiah($produk['harga_jual']) ?>
            </div>

            <p class="detail-description">
                Kode Produk: <?= e($produk['kode_produk']) ?>
            </p>

            <p class="detail-description">
                Satuan: <?= e($produk['satuan'] ?? '-') ?>
            </p>

            <p class="detail-description">
                Stok tersedia: <?= (int)$produk['stok'] ?>
            </p>

            <?php if ((int)$produk['stok'] > 0): ?>

                <form method="POST" action="index.php?page=pelanggan_keranjang">
                    <input type="hidden" name="id_produk" value="<?= (int)$produk['id_produk'] ?>">

                    <div class="quantity-control">
                        <label for="jumlah">Jumlah</label>
                        <input
                            type="number"
                            id="jumlah"
                            name="jumlah"
                            value="1"
                            min="1"
                            max="<?= (int)$produk['stok'] ?>"
                            required
                        >
                    </div>

                    <button
                        type="submit"
                        name="tambah_keranjang"
                        class="btn-primary-custom"
                    >
                        <i class="fas fa-shopping-cart"></i>
                        Tambah ke Keranjang
                    </button>

                </form>

            <?php else: ?>

                <div class="stock-alert">
                    Produk sedang habis.
                </div>

            <?php endif; ?>

        </div>
    </div>
</div>
