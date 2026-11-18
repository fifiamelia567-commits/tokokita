
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$idPelanggan = (int)($_SESSION['id_pelanggan'] ?? 0);
$keranjang = $_SESSION['keranjang'] ?? [];
$error = '';

if ($idPelanggan <= 0) {
    echo '<div class="card">ID pelanggan belum tersedia di session login. Sesuaikan proses login agar menyimpan id_pelanggan.</div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM tbl_pelanggan
    WHERE id_pelanggan = ?
    LIMIT 1
");
$stmt->execute([$idPelanggan]);
$pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pelanggan) {
    echo '<div class="card">Data pelanggan tidak ditemukan.</div>';
    return;
}

if (empty($keranjang)) {
    echo '<div class="card empty-state">
        <h3>Keranjang masih kosong</h3>
        <a href="index.php?page=pelanggan_produk" class="btn-primary-custom">Belanja Sekarang</a>
    </div>';
    return;
}

$ids = array_map('intval', array_keys($keranjang));
$placeholders = implode(',', array_fill(0, count($ids), '?'));

$stmt = $pdo->prepare("
    SELECT id_produk, kode_produk, nama_produk,
           harga_jual, stok, gambar
    FROM tbl_produk
    WHERE id_produk IN ($placeholders)
");
$stmt->execute($ids);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$items = [];
$subtotal = 0;

foreach ($produk as $item) {
    $id = (int)$item['id_produk'];
    $qty = (int)($keranjang[$id] ?? 0);

    if ($qty <= 0) {
        continue;
    }

    $item['qty'] = $qty;
    $item['jumlah'] = $qty * (float)$item['harga_jual'];

    $subtotal += $item['jumlah'];
    $items[] = $item;
}

$diskonPersen = (float)($pelanggan['diskon_member'] ?? 0);
$diskonPersen = max(0, min(100, $diskonPersen));
$diskon = $subtotal * $diskonPersen / 100;
$pajak = 0;
$total = max(0, $subtotal - $diskon + $pajak);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_pesanan'])) {
    $metode = $_POST['metode_pembayaran'] ?? '';

    $metodeDiizinkan = [
        'Tunai',
        'Transfer Bank',
        'QRIS',
        'Debit'
    ];

    if (!in_array($metode, $metodeDiizinkan, true)) {
        $error = 'Metode pembayaran tidak valid.';
    } elseif (empty($items)) {
        $error = 'Tidak ada produk dalam keranjang.';
    } else {
        try {
            $pdo->beginTransaction();

            // Periksa dan kunci stok produk.
            $stmtStok = $pdo->prepare("
                SELECT nama_produk, harga_jual, stok
                FROM tbl_produk
                WHERE id_produk = ?
                FOR UPDATE
            ");

            foreach ($items as $item) {
                $stmtStok->execute([$item['id_produk']]);
                $stokData = $stmtStok->fetch(PDO::FETCH_ASSOC);

                if (
                    !$stokData ||
                    (int)$stokData['stok'] < (int)$item['qty']
                ) {
                    throw new Exception(
                        'Stok ' . $item['nama_produk'] . ' tidak mencukupi.'
                    );
                }
            }

            $noFaktur = 'TRX-' . date('YmdHis') . '-' . random_int(100, 999);
            $poinDidapat = (int)floor($total / 10000);

            $stmtTransaksi = $pdo->prepare("
                INSERT INTO tbl_transaksi
                (
                    tanggal,
                    id_pelanggan,
                    total_belanja,
                    no_faktur,
                    waktu,
                    subtotal,
                    diskon,
                    pajak,
                    bayar,
                    kembalian,
                    metode_pembayaran,
                    kasir,
                    catatan,
                    poin_didapat
                )
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");

            $stmtTransaksi->execute([
                date('Y-m-d'),
                $idPelanggan,
                $total,
                $noFaktur,
                date('H:i:s'),
                $subtotal,
                $diskon,
                $pajak,
                $total,
                0,
                $metode,
                'Online',
                'Pesanan pelanggan melalui website',
                $poinDidapat
            ]);

            $idTransaksi = (int)$pdo->lastInsertId();

            $stmtDetail = $pdo->prepare("
                INSERT INTO tbl_detail_transaksi
                (
                    id_transaksi,
                    id_produk,
                    nama_produk,
                    harga_satuan,
                    qty,
                    subtotal
                )
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $stmtUpdateStok = $pdo->prepare("
                UPDATE tbl_produk
                SET stok = stok - ?
                WHERE id_produk = ? AND stok >= ?
            ");

            foreach ($items as $item) {
                $stmtDetail->execute([
                    $idTransaksi,
                    $item['id_produk'],
                    $item['nama_produk'],
                    $item['harga_jual'],
                    $item['qty'],
                    $item['jumlah']
                ]);

                $stmtUpdateStok->execute([
                    $item['qty'],
                    $item['id_produk'],
                    $item['qty']
                ]);

                if ($stmtUpdateStok->rowCount() !== 1) {
                    throw new Exception(
                        'Stok ' . $item['nama_produk'] . ' berubah. Silakan ulangi checkout.'
                    );
                }
            }

            $stmtPoin = $pdo->prepare("
                UPDATE tbl_pelanggan
                SET poin = COALESCE(poin, 0) + ?
                WHERE id_pelanggan = ?
            ");
            $stmtPoin->execute([$poinDidapat, $idPelanggan]);

            $pdo->commit();

            $_SESSION['keranjang'] = [];
            $_SESSION['transaksi_terakhir'] = $idTransaksi;

            header('Location: index.php?page=pelanggan_pesanan');
            exit;

        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $e->getMessage();
        }
    }
}
?>

<div class="page-heading">
    <div>
        <h1>Checkout</h1>
        <p>Periksa pesanan dan pilih metode pembayaran.</p>
    </div>
</div>

<?php if ($error): ?>
    <div class="stock-alert"><?= e($error) ?></div>
<?php endif; ?>

<div class="checkout-layout">

    <div class="card">

        <div class="card-title">Informasi Pelanggan</div>

        <div class="form-group" style="margin-bottom:13px">
            <label>Nama Pelanggan</label>
            <input
                class="form-control"
                type="text"
                value="<?= e($pelanggan['nama_pelanggan']) ?>"
                readonly
            >
        </div>

        <div class="form-group" style="margin-bottom:13px">
            <label>Nomor Telepon</label>
            <input
                class="form-control"
                type="text"
                value="<?= e($pelanggan['telepon'] ?? '') ?>"
                readonly
            >
        </div>

        <div class="form-group" style="margin-bottom:18px">
            <label>Alamat</label>
            <textarea class="form-control" rows="3" readonly><?= e($pelanggan['alamat'] ?? '') ?></textarea>
        </div>

        <form method="POST">

            <div class="card-title">Metode Pembayaran</div>

            <select
                name="metode_pembayaran"
                class="form-select"
                required
            >
                <option value="">Pilih metode pembayaran</option>
                <option value="Tunai">Tunai</option>
                <option value="Transfer Bank">Transfer Bank</option>
                <option value="QRIS">QRIS</option>
                <option value="Debit">Kartu Debit</option>
            </select>

            <p style="font-size:10px;color:#8999a5;margin-top:9px">
                Pembayaran pada aplikasi ini dicatat sebagai transaksi.
                Untuk pembayaran transfer atau QRIS, konfirmasi pembayaran
                tetap perlu ditambahkan jika ingin menggunakan pembayaran sungguhan.
            </p>

            <button
                type="submit"
                name="buat_pesanan"
                class="btn-checkout"
                onclick="return confirm('Buat pesanan sekarang?')"
            >
                Buat Pesanan
            </button>

        </form>

    </div>

    <div class="cart-total">

        <h3>Ringkasan Pesanan</h3>

        <?php foreach ($items as $item): ?>
            <div class="total-row">
                <span>
                    <?= e($item['nama_produk']) ?>
                    (<?= (int)$item['qty'] ?>)
                </span>
                <strong><?= rupiah($item['jumlah']) ?></strong>
            </div>
        <?php endforeach; ?>

        <div class="total-row">
            <span>Subtotal</span>
            <strong><?= rupiah($subtotal) ?></strong>
        </div>

        <div class="total-row">
            <span>Diskon Member</span>
            <strong><?= rupiah($diskon) ?></strong>
        </div>

        <div class="total-row">
            <span>Pajak</span>
            <strong><?= rupiah($pajak) ?></strong>
        </div>

        <div class="total-row total-final">
            <span>Total</span>
            <strong><?= rupiah($total) ?></strong>
        </div>

    </div>

</div>
