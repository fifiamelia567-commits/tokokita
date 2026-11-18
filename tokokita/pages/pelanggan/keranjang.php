
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (isset($_POST['tambah_keranjang'])) {
        $id = (int)($_POST['id_produk'] ?? 0);
        $jumlah = (int)($_POST['jumlah'] ?? 0);

        $stmt = $pdo->prepare("
            SELECT stok FROM tbl_produk WHERE id_produk = ?
        ");
        $stmt->execute([$id]);
        $stok = $stmt->fetchColumn();

        if ($stok === false || (int)$stok <= 0) {
            $pesan = 'Produk tidak tersedia.';
        } elseif ($jumlah < 1) {
            $pesan = 'Jumlah produk harus minimal 1.';
        } else {
            $jumlahLama = (int)($_SESSION['keranjang'][$id] ?? 0);

            if ($jumlahLama + $jumlah > (int)$stok) {
                $pesan = 'Jumlah di keranjang melebihi stok.';
            } else {
                $_SESSION['keranjang'][$id] = $jumlahLama + $jumlah;
                $pesan = 'Produk berhasil ditambahkan ke keranjang.';
            }
        }
    } elseif (isset($_POST['update_keranjang'])) {
        foreach ($_POST['jumlah'] ?? [] as $id => $jumlah) {
            $id = (int)$id;
            $jumlah = (int)$jumlah;

            if ($jumlah <= 0) {
                unset($_SESSION['keranjang'][$id]);
                continue;
            }

            $stmt = $pdo->prepare("
                SELECT stok FROM tbl_produk WHERE id_produk = ?
            ");
            $stmt->execute([$id]);
            $stok = $stmt->fetchColumn();

            if ($stok !== false) {
                $_SESSION['keranjang'][$id] = min($jumlah, (int)$stok);
            } else {
                unset($_SESSION['keranjang'][$id]);
            }
        }

        $pesan = 'Keranjang berhasil diperbarui.';
    }

    if (isset($_POST['hapus_produk'])) {
        $id = (int)$_POST['hapus_produk'];
        unset($_SESSION['keranjang'][$id]);
        $pesan = 'Produk berhasil dihapus.';
    }

    if (isset($_POST['kosongkan_keranjang'])) {
        $_SESSION['keranjang'] = [];
        $pesan = 'Keranjang berhasil dikosongkan.';
    }
}

$items = [];
$total = 0;

if (!empty($_SESSION['keranjang'])) {
    $ids = array_map('intval', array_keys($_SESSION['keranjang']));
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("
        SELECT id_produk, nama_produk, harga_jual, stok, gambar
        FROM tbl_produk
        WHERE id_produk IN ($placeholders)
    ");
    $stmt->execute($ids);

    $dataProduk = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($dataProduk as $item) {
        $id = (int)$item['id_produk'];
        $qty = min(
            (int)$_SESSION['keranjang'][$id],
            (int)$item['stok']
        );

        if ($qty <= 0) {
            unset($_SESSION['keranjang'][$id]);
            continue;
        }

        $_SESSION['keranjang'][$id] = $qty;

        $item['qty'] = $qty;
        $item['subtotal'] = $qty * (float)$item['harga_jual'];

        $total += $item['subtotal'];
        $items[] = $item;
    }
}
?>

<div class="page-heading">
    <div>
        <h1>Keranjang Belanja</h1>
        <p>Periksa kembali produk yang ingin kamu beli.</p>
    </div>
</div>

<?php if ($pesan): ?>
    <div class="stock-alert"><?= e($pesan) ?></div>
<?php endif; ?>

<?php if (empty($items)): ?>

    <div class="card empty-state">
        <i class="fas fa-shopping-cart"></i>
        <h3>Keranjang Masih Kosong</h3>
        <p>Silakan pilih produk terlebih dahulu.</p>
        <br>
        <a href="index.php?page=pelanggan_produk" class="btn-primary-custom">
            Mulai Belanja
        </a>
    </div>

<?php else: ?>

    <div class="cart-layout">

        <div class="card">

            <div class="card-title">Daftar Belanja</div>

            <form method="POST">

                <?php foreach ($items as $item): ?>

                    <div class="cart-item">

                        <div class="cart-item-image">
                            <?php if (!empty($item['gambar'])): ?>
                                <img
                                    src="assets/uploads/produk/<?= e($item['gambar']) ?>"
                                    alt="<?= e($item['nama_produk']) ?>"
                                >
                            <?php else: ?>
                                <i class="fas fa-image"></i>
                            <?php endif; ?>
                        </div>

                        <div class="cart-item-info">
                            <strong><?= e($item['nama_produk']) ?></strong>
                            <small><?= rupiah($item['harga_jual']) ?></small>
                            <small>Subtotal: <?= rupiah($item['subtotal']) ?></small>
                        </div>

                        <input
                            type="number"
                            name="jumlah[<?= (int)$item['id_produk'] ?>]"
                            value="<?= (int)$item['qty'] ?>"
                            min="1"
                            max="<?= (int)$item['stok'] ?>"
                            style="width:60px;padding:8px;border:1px solid #e9edf1;border-radius:6px"
                        >

                        <button
                            type="submit"
                            name="hapus_produk"
                            value="<?= (int)$item['id_produk'] ?>"
                            style="border:0;background:#fff0e9;color:#d95032;padding:8px;border-radius:6px;cursor:pointer"
                        >
                            <i class="fas fa-trash"></i>
                        </button>

                    </div>

                <?php endforeach; ?>

                <div style="display:flex;gap:8px;margin-top:15px">
                    <button
                        type="submit"
                        name="update_keranjang"
                        class="btn-primary-custom"
                    >
                        Perbarui Keranjang
                    </button>

                    <button
                        type="submit"
                        name="kosongkan_keranjang"
                        class="btn-primary-custom"
                        style="background:#e05252"
                        onclick="return confirm('Kosongkan semua keranjang?')"
                    >
                        Kosongkan
                    </button>
                </div>

            </form>

        </div>

        <div class="cart-total">

            <h3>Ringkasan Belanja</h3>

            <div class="total-row">
                <span>Jumlah produk</span>
                <strong><?= count($items) ?> produk</strong>
            </div>

            <div class="total-row">
                <span>Total barang</span>
                <strong>
                    <?= array_sum(array_column($items, 'qty')) ?>
                </strong>
            </div>

            <div class="total-row total-final">
                <span>Total Pembayaran</span>
                <strong><?= rupiah($total) ?></strong>
            </div>

            <a href="index.php?page=pelanggan_checkout" class="btn-checkout">
                Lanjut ke Checkout
            </a>

            <a
                href="index.php?page=pelanggan_produk"
                style="display:block;text-align:center;font-size:10px;color:#89919d;margin-top:12px"
            >
                Tambah Produk Lain
            </a>

        </div>

    </div>

<?php endif; ?>
