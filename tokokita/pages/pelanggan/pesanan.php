
<?php
$idPelanggan = (int)($_SESSION['id_pelanggan'] ?? 0);

if ($idPelanggan <= 0) {
    echo '<div class="card">ID pelanggan belum tersedia di session login.</div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM tbl_transaksi
    WHERE id_pelanggan = ?
    ORDER BY id_transaksi DESC
");
$stmt->execute([$idPelanggan]);
$pesanan = $stmt->fetchAll(PDO::FETCH_ASSOC);

$detailPesanan = null;

if (isset($_GET['detail'])) {
    $idTransaksi = (int)$_GET['detail'];

    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_transaksi
        WHERE id_transaksi = ? AND id_pelanggan = ?
        LIMIT 1
    ");
    $stmt->execute([$idTransaksi, $idPelanggan]);
    $detailPesanan = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($detailPesanan) {
        $stmtDetail = $pdo->prepare("
            SELECT *
            FROM tbl_detail_transaksi
            WHERE id_transaksi = ?
        ");
        $stmtDetail->execute([$idTransaksi]);
        $barangPesanan = $stmtDetail->fetchAll(PDO::FETCH_ASSOC);
    }
}
?>

<div class="page-heading">
    <div>
        <h1>Riwayat Pesanan</h1>
        <p>Lihat daftar transaksi dan rincian belanjamu.</p>
    </div>
</div>

<?php if ($detailPesanan): ?>

    <div class="card">

        <div class="page-heading">
            <div>
                <h2 style="font-size:15px">Detail Transaksi</h2>
                <p><?= e($detailPesanan['no_faktur']) ?></p>
            </div>

            <a href="index.php?page=pelanggan_pesanan" class="btn-primary-custom">
                Kembali
            </a>
        </div>

        <div class="total-row">
            <span>Tanggal</span>
            <strong><?= e($detailPesanan['tanggal']) ?></strong>
        </div>

        <div class="total-row">
            <span>Waktu</span>
            <strong><?= e($detailPesanan['waktu']) ?></strong>
        </div>

        <div class="total-row">
            <span>Metode Pembayaran</span>
            <strong><?= e($detailPesanan['metode_pembayaran']) ?></strong>
        </div>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Produk</th>
                        <th>Harga</th>
                        <th>Jumlah</th>
                        <th>Subtotal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($barangPesanan as $barang): ?>
                        <tr>
                            <td><?= e($barang['nama_produk']) ?></td>
                            <td><?= rupiah($barang['harga_satuan']) ?></td>
                            <td><?= (int)$barang['qty'] ?></td>
                            <td><?= rupiah($barang['subtotal']) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="total-row total-final">
            <span>Total Pembayaran</span>
            <strong><?= rupiah($detailPesanan['total_belanja']) ?></strong>
        </div>

    </div>

<?php else: ?>

    <div class="card">

        <div class="card-title">Daftar Transaksi</div>

        <?php if (empty($pesanan)): ?>

            <div class="empty-state">
                <i class="fas fa-receipt"></i>
                <h3>Belum Ada Pesanan</h3>
                <p>Transaksi yang kamu lakukan akan muncul di sini.</p>
                <br>
                <a href="index.php?page=pelanggan_produk" class="btn-primary-custom">
                    Mulai Belanja
                </a>
            </div>

        <?php else: ?>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>No.</th>
                            <th>No. Faktur</th>
                            <th>Tanggal</th>
                            <th>Pembayaran</th>
                            <th>Total</th>
                            <th>Detail</th>
                        </tr>
                    </thead>

                    <tbody>
                        <?php foreach ($pesanan as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($item['no_faktur']) ?></td>
                                <td><?= e($item['tanggal']) ?></td>
                                <td><?= e($item['metode_pembayaran']) ?></td>
                                <td><strong><?= rupiah($item['total_belanja']) ?></strong></td>
                                <td>
                                    <a
                                        href="index.php?page=pelanggan_pesanan&detail=<?= (int)$item['id_transaksi'] ?>"
                                        class="btn-primary-custom"
                                    >
                                        Lihat
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        <?php endif; ?>

    </div>

<?php endif; ?>
