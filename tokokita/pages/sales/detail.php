
<?php
require_once __DIR__ . '/../../config/database.php';

function rupiah($angka) {
    return 'Rp ' . number_format((float)$angka, 0, ',', '.');
}

function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare("
    SELECT *
    FROM tbl_penjualan
    WHERE id_penjualan = ?
");
$stmt->execute([$id]);
$transaksi = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$transaksi) {
    echo '<div style="padding:25px;background:white;border-radius:12px">
        Transaksi tidak ditemukan.
        <a href="index.php?page=sales/index">Kembali</a>
    </div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT d.*, p.nama_produk
    FROM tbl_detail_penjualan d
    LEFT JOIN tbl_produk p ON p.id_produk = d.id_produk
    WHERE d.id_penjualan = ?
");
$stmt->execute([$id]);
$detail = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.sale-detail {
    color: #292524;
}

.sale-detail .heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 22px;
}

.sale-detail h2 {
    font-size: 24px;
    font-weight: 750;
    margin: 0 0 5px;
}

.sale-detail .muted {
    color: #999;
    font-size: 13px;
}

.sale-detail .detail-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 15px;
    padding: 23px;
    margin-bottom: 18px;
}

.sale-detail .info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 20px;
}

.sale-detail .info-label {
    display: block;
    color: #999;
    font-size: 12px;
    margin-bottom: 7px;
}

.sale-detail .info-value {
    font-weight: 700;
    font-size: 14px;
}

.sale-detail .code {
    color: #f5822b;
}

.sale-detail table {
    width: 100%;
    border-collapse: collapse;
    min-width: 550px;
}

.sale-detail th {
    background: #fafafa;
    color: #999;
    font-size: 11px;
    text-align: left;
    padding: 14px;
}

.sale-detail td {
    padding: 15px 14px;
    font-size: 13px;
    border-bottom: 1px solid #eee;
}

.sale-detail .table-responsive {
    overflow-x: auto;
}

.sale-detail .summary {
    max-width: 360px;
    margin-left: auto;
    margin-top: 20px;
}

.sale-detail .summary-row {
    display: flex;
    justify-content: space-between;
    margin-bottom: 13px;
    font-size: 13px;
}

.sale-detail .summary-total {
    padding-top: 15px;
    border-top: 1px dashed #ddd;
    color: #f5822b;
    font-weight: 800;
    font-size: 18px;
}

.sale-detail .btn {
    display: inline-block;
    padding: 11px 16px;
    border-radius: 9px;
    text-decoration: none;
    border: 0;
    cursor: pointer;
    font-size: 13px;
    font-weight: 700;
}

.sale-detail .btn-orange {
    background: #f5822b;
    color: white;
}

.sale-detail .btn-light {
    background: #fff1e6;
    color: #ed7925;
}

@media print {
    body * {
        visibility: hidden !important;
    }

    #detail-print,
    #detail-print * {
        visibility: visible !important;
    }

    #detail-print {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        box-shadow: none;
        border: none;
    }

    .no-print {
        display: none !important;
    }
}
</style>

<div class="sale-detail">

    <div class="heading">
        <div>
            <h2>Detail Penjualan</h2>
            <p class="muted">Informasi lengkap transaksi TOKOKITA.</p>
        </div>

        <div class="no-print" style="display:flex;gap:9px;flex-wrap:wrap">
            <a href="index.php?page=sales/index" class="btn btn-light">
                <i class="fas fa-arrow-left"></i> Kembali
            </a>

            <button onclick="window.print()" class="btn btn-orange">
                <i class="fas fa-print"></i> Cetak
            </button>
        </div>
    </div>

    <div id="detail-print">

        <div class="detail-card">
            <h3 style="font-size:16px;margin:0 0 20px">
                Informasi Transaksi
            </h3>

            <div class="info-grid">

                <div>
                    <span class="info-label">Kode Transaksi</span>
                    <div class="info-value code">
                        <?= e($transaksi['kode_penjualan']) ?>
                    </div>
                </div>

                <div>
                    <span class="info-label">Tanggal Transaksi</span>
                    <div class="info-value">
                        <?= date('d F Y H:i', strtotime($transaksi['tanggal'])) ?>
                    </div>
                </div>

                <div>
                    <span class="info-label">Status</span>
                    <div class="info-value" style="color:#168449">
                        Lunas
                    </div>
                </div>

                <div>
                    <span class="info-label">ID Transaksi</span>
                    <div class="info-value">
                        #<?= (int)$transaksi['id_penjualan'] ?>
                    </div>
                </div>

            </div>
        </div>

        <div class="detail-card">

            <h3 style="font-size:16px;margin:0 0 20px">
                Produk yang Dibeli
            </h3>

            <div class="table-responsive">
                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>NAMA PRODUK</th>
                            <th>HARGA</th>
                            <th>JUMLAH</th>
                            <th>SUBTOTAL</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php foreach ($detail as $i => $item): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>

                            <td>
                                <strong><?= e($item['nama_produk'] ?? 'Produk tidak tersedia') ?></strong>
                            </td>

                            <td><?= rupiah($item['harga']) ?></td>

                            <td><?= (int)$item['jumlah'] ?></td>

                            <td>
                                <strong><?= rupiah($item['subtotal']) ?></strong>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    </tbody>
                </table>
            </div>

            <div class="summary">

                <div class="summary-row">
                    <span>Total Belanja</span>
                    <strong><?= rupiah($transaksi['total']) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Uang Pembayaran</span>
                    <strong><?= rupiah($transaksi['bayar']) ?></strong>
                </div>

                <div class="summary-row">
                    <span>Kembalian</span>
                    <strong><?= rupiah($transaksi['kembalian']) ?></strong>
                </div>

                <div class="summary-row summary-total">
                    <span>Total</span>
                    <span><?= rupiah($transaksi['total']) ?></span>
                </div>

            </div>

            <div style="text-align:center;margin-top:30px;color:#999;font-size:12px">
                Terima kasih telah berbelanja di TOKOKITA.
            </div>

        </div>

    </div>
</div>
