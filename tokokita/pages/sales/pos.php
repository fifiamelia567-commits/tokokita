
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['keranjang'])) {
    $_SESSION['keranjang'] = [];
}

$pesan = '';
$struk = null;

// ==========================================
// TAMBAH PRODUK
// ==========================================

if (isset($_POST['tambah_produk'])) {

    $id = (int)($_POST['id_produk'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    $stmt = $pdo->prepare("
        SELECT id_produk, kode_produk, nama_produk,
               harga_jual, stok
        FROM tbl_produk
        WHERE id_produk = ?
    ");

    $stmt->execute([$id]);
    $produk = $stmt->fetch();

    if (!$produk) {

        $pesan = 'Produk tidak ditemukan.';

    } elseif ($produk['stok'] <= 0) {

        $pesan = 'Stok produk habis.';

    } else {

        $jumlahLama = $_SESSION['keranjang'][$id]['qty'] ?? 0;

        if ($jumlahLama + $qty > $produk['stok']) {

            $pesan = 'Stok produk tidak mencukupi.';

        } else {

            $_SESSION['keranjang'][$id] = [
                'id_produk' => $id,
                'kode_produk' => $produk['kode_produk'],
                'nama_produk' => $produk['nama_produk'],
                'harga' => (float)$produk['harga_jual'],
                'qty' => $jumlahLama + $qty
            ];

            $pesan = 'Produk berhasil ditambahkan.';
        }
    }
}

// ==========================================
// UBAH QTY
// ==========================================

if (isset($_POST['ubah_qty'])) {

    $id = (int)($_POST['id_produk'] ?? 0);
    $qty = max(1, (int)($_POST['qty'] ?? 1));

    if (isset($_SESSION['keranjang'][$id])) {

        $stmt = $pdo->prepare("
            SELECT stok
            FROM tbl_produk
            WHERE id_produk = ?
        ");

        $stmt->execute([$id]);
        $stok = $stmt->fetchColumn();

        if ($stok !== false && $qty <= $stok) {

            $_SESSION['keranjang'][$id]['qty'] = $qty;
            $pesan = 'Jumlah produk berhasil diubah.';

        } else {

            $pesan = 'Jumlah melebihi stok tersedia.';
        }
    }
}

// ==========================================
// HAPUS PRODUK
// ==========================================

if (isset($_POST['hapus_produk'])) {

    $id = (int)($_POST['id_produk'] ?? 0);

    unset($_SESSION['keranjang'][$id]);

    $pesan = 'Produk berhasil dihapus.';
}

// ==========================================
// KOSONGKAN KERANJANG
// ==========================================

if (isset($_POST['kosongkan'])) {

    $_SESSION['keranjang'] = [];

    $pesan = 'Keranjang berhasil dikosongkan.';
}

// ==========================================
// HITUNG TOTAL
// ==========================================

$total = 0;

foreach ($_SESSION['keranjang'] as $item) {
    $total += $item['harga'] * $item['qty'];
}

// ==========================================
// PROSES PEMBAYARAN
// ==========================================

if (isset($_POST['proses_bayar'])) {

    try {

        if (empty($_SESSION['keranjang'])) {
            throw new Exception('Keranjang masih kosong.');
        }

        $bayar = (float)($_POST['bayar'] ?? 0);

        $metode = $_POST['metode_pembayaran'] ?? 'Tunai';

        $metodeValid = [
            'Tunai',
            'QRIS',
            'Debit',
            'Transfer Bank'
        ];

        if (!in_array($metode, $metodeValid, true)) {
            throw new Exception('Metode pembayaran tidak valid.');
        }

        if ($bayar <= 0) {
            throw new Exception('Jumlah pembayaran tidak valid.');
        }

        $pdo->beginTransaction();

        // Validasi stok dan harga terbaru

        foreach ($_SESSION['keranjang'] as $id => $item) {

            $stmt = $pdo->prepare("
                SELECT id_produk, nama_produk,
                       harga_jual, stok
                FROM tbl_produk
                WHERE id_produk = ?
                FOR UPDATE
            ");

            $stmt->execute([$id]);

            $produk = $stmt->fetch();

            if (!$produk) {
                throw new Exception(
                    'Produk ' . $item['nama_produk'] . ' tidak ditemukan.'
                );
            }

            if ($produk['stok'] < $item['qty']) {
                throw new Exception(
                    'Stok ' . $produk['nama_produk'] . ' tidak mencukupi.'
                );
            }

            $_SESSION['keranjang'][$id]['harga'] =
                (float)$produk['harga_jual'];

            $_SESSION['keranjang'][$id]['nama_produk'] =
                $produk['nama_produk'];
        }

        // Hitung ulang total

        $total = 0;

        foreach ($_SESSION['keranjang'] as $item) {
            $total += $item['harga'] * $item['qty'];
        }

        if ($bayar < $total) {
            throw new Exception('Uang pembayaran kurang.');
        }

        $kembalian = $bayar - $total;

        $noFaktur = 'TRX-' . date('Ymd-His');

        $tanggal = date('Y-m-d');
        $waktu = date('H:i:s');

        $kasir = $_SESSION['nama_lengkap']
            ?? $_SESSION['nama']
            ?? $_SESSION['username']
            ?? 'Kasir';

        // Simpan transaksi

        $stmt = $pdo->prepare("
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
            VALUES (
                ?, NULL, ?, ?, ?, ?, 0, 0, ?, ?, ?, ?, NULL, 0
            )
        ");

        $stmt->execute([
            $tanggal,
            $total,
            $noFaktur,
            $waktu,
            $total,
            $bayar,
            $kembalian,
            $metode,
            $kasir
        ]);

        $idTransaksi = $pdo->lastInsertId();

        // Siapkan detail transaksi

        $detail = $pdo->prepare("
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

        // Siapkan pengurangan stok

        $updateStok = $pdo->prepare("
            UPDATE tbl_produk
            SET stok = stok - ?
            WHERE id_produk = ?
            AND stok >= ?
        ");

        // Simpan detail dan kurangi stok

        foreach ($_SESSION['keranjang'] as $item) {

            $subtotal = $item['harga'] * $item['qty'];

            $detail->execute([
                $idTransaksi,
                $item['id_produk'],
                $item['nama_produk'],
                $item['harga'],
                $item['qty'],
                $subtotal
            ]);

            $updateStok->execute([
                $item['qty'],
                $item['id_produk'],
                $item['qty']
            ]);

            if ($updateStok->rowCount() !== 1) {
                throw new Exception(
                    'Gagal memperbarui stok ' . $item['nama_produk']
                );
            }
        }

        // Siapkan data struk sebelum commit

        $struk = [
            'id' => $idTransaksi,
            'faktur' => $noFaktur,
            'tanggal' => $tanggal,
            'waktu' => $waktu,
            'kasir' => $kasir,
            'metode' => $metode,
            'items' => $_SESSION['keranjang'],
            'total' => $total,
            'bayar' => $bayar,
            'kembalian' => $kembalian
        ];

        $pdo->commit();

        $_SESSION['keranjang'] = [];

    } catch (Throwable $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        $pesan = $e->getMessage();
        $struk = null;
    }
}

// ==========================================
// AMBIL PRODUK
// ==========================================

$produkList = $pdo->query("
    SELECT
        id_produk,
        kode_produk,
        nama_produk,
        harga_jual,
        stok
    FROM tbl_produk
    ORDER BY nama_produk ASC
")->fetchAll();

// ==========================================
// HITUNG TOTAL AKHIR
// ==========================================

$total = 0;

foreach ($_SESSION['keranjang'] as $item) {
    $total += $item['harga'] * $item['qty'];
}

?>

<style>

/* HALAMAN POS */

.pos-wrapper {
    display: grid;
    grid-template-columns: 1.5fr 1fr;
    gap: 22px;
}

.pos-card {
    background: #fff;
    border: 1px solid #eee;
    border-radius: 16px;
    padding: 22px;
    margin-bottom: 20px;
}

.pos-card h3 {
    margin: 0 0 18px;
    color: #252525;
}

.pos-input {
    width: 100%;
    padding: 11px 13px;
    border: 1px solid #ddd;
    border-radius: 9px;
    margin: 7px 0 13px;
    box-sizing: border-box;
}

.pos-btn {
    border: none;
    border-radius: 9px;
    padding: 10px 15px;
    cursor: pointer;
    font-weight: 600;
    text-decoration: none;
    display: inline-block;
}

.pos-btn:disabled {
    opacity: .5;
    cursor: not-allowed;
}

.pos-primary {
    background: #f5822b;
    color: white;
}

.pos-danger {
    background: #ffe8e8;
    color: #d33;
}

.pos-table {
    width: 100%;
    border-collapse: collapse;
}

.pos-table th,
.pos-table td {
    padding: 12px 8px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 13px;
}

.pos-table th {
    color: #777;
}

.pos-total {
    display: flex;
    justify-content: space-between;
    padding: 10px 0;
}

.pos-grand {
    border-top: 1px solid #ddd;
    margin-top: 10px;
    padding-top: 18px;
    font-size: 20px;
    font-weight: bold;
}

.pos-alert {
    padding: 12px;
    background: #fff0e5;
    color: #a84b0c;
    border-radius: 9px;
    margin-bottom: 15px;
}

.pos-success {
    padding: 12px;
    background: #e8f8ed;
    color: #187a39;
    border-radius: 9px;
    margin-bottom: 15px;
}

/* STRUK MODERN */

.receipt-wrapper {
    max-width: 650px;
    margin: 25px auto;
}

.receipt-success {
    text-align: center;
    margin-bottom: 22px;
}

.success-icon {
    width: 65px;
    height: 65px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    border-radius: 50%;
    background: #e8f8ee;
    color: #20a45a;
    font-size: 30px;
}

.receipt-success h2 {
    margin: 0 0 7px;
    color: #202938;
}

.receipt-success p {
    color: #84909f;
    font-size: 14px;
}

.modern-receipt {
    background: #fff;
    border: 1px solid #edf0f3;
    border-radius: 20px;
    overflow: hidden;
    box-shadow: 0 12px 40px rgba(0,0,0,.07);
}

.receipt-header {
    background: linear-gradient(135deg, #f5822b, #f5a04e);
    padding: 30px;
    color: white;
    text-align: center;
}

.receipt-logo {
    width: 55px;
    height: 55px;
    background: rgba(255,255,255,.2);
    border: 1px solid rgba(255,255,255,.4);
    border-radius: 16px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 12px;
    font-size: 25px;
    font-weight: bold;
}

.receipt-header h1 {
    font-size: 25px;
    margin: 0;
    letter-spacing: 2px;
}

.receipt-header p {
    margin: 8px 0 0;
    font-size: 13px;
    opacity: .9;
}

.receipt-body {
    padding: 25px 30px;
}

.receipt-status {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 10px;
    margin-bottom: 22px;
}

.receipt-status span:first-child {
    font-size: 13px;
    color: #788493;
}

.status-paid {
    background: #e8f8ee;
    color: #16864a;
    padding: 7px 13px;
    border-radius: 30px;
    font-size: 12px;
    font-weight: bold;
}

.receipt-info {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 17px;
    padding: 18px;
    background: #f8fafc;
    border-radius: 12px;
    margin-bottom: 25px;
}

.receipt-info small {
    display: block;
    color: #8993a0;
    font-size: 11px;
    margin-bottom: 5px;
}

.receipt-info strong {
    color: #293342;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.receipt-section-title {
    font-size: 14px;
    color: #293342;
    font-weight: bold;
    margin-bottom: 14px;
}

.receipt-items {
    width: 100%;
    border-collapse: collapse;
}

.receipt-items th {
    color: #929ba7;
    font-size: 11px;
    text-transform: uppercase;
    text-align: left;
    padding: 10px 5px;
    border-bottom: 1px solid #edf0f3;
}

.receipt-items td {
    padding: 15px 5px;
    border-bottom: 1px solid #f0f2f5;
    font-size: 12px;
    color: #465160;
}

.receipt-items td strong {
    display: block;
    color: #293342;
    margin-bottom: 5px;
}

.receipt-items small {
    color: #929ba7;
}

.receipt-items .right {
    text-align: right;
    white-space: nowrap;
}

.receipt-summary {
    margin-top: 22px;
    padding: 18px;
    background: #f8fafc;
    border-radius: 12px;
}

.summary-row {
    display: flex;
    justify-content: space-between;
    gap: 12px;
    margin-bottom: 13px;
    font-size: 13px;
    color: #697585;
}

.summary-row strong {
    color: #293342;
}

.total-row {
    border-top: 1px dashed #d9dfe6;
    padding-top: 17px;
    margin-top: 15px;
    font-size: 16px;
    font-weight: bold;
}

.total-row strong {
    color: #f5822b;
    font-size: 21px;
}

.change-row {
    color: #16864a;
    margin-bottom: 0;
}

.receipt-footer {
    padding: 25px 20px;
    text-align: center;
    border-top: 1px dashed #e1e5ea;
}

.receipt-footer h4 {
    margin: 0 0 7px;
    color: #293342;
}

.receipt-footer p {
    margin: 0;
    color: #929ba7;
    font-size: 12px;
    line-height: 1.7;
}

.receipt-actions {
    display: flex;
    justify-content: center;
    gap: 12px;
    margin-top: 22px;
    flex-wrap: wrap;
}

.receipt-actions button,
.receipt-actions a {
    border: none;
    border-radius: 10px;
    padding: 13px 20px;
    font-size: 13px;
    font-weight: 600;
    cursor: pointer;
    text-decoration: none;
}

.btn-print {
    background: #f5822b;
    color: white;
}

.btn-new {
    background: #edf0f3;
    color: #374151;
}

@media(max-width: 850px) {
    .pos-wrapper {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 500px) {
    .receipt-body {
        padding: 18px;
    }

    .receipt-header {
        padding: 25px 15px;
    }

    .receipt-info {
        padding: 13px;
    }

    .receipt-items td {
        font-size: 11px;
    }
}

/* CETAK STRUK */

@media print {

    @page {
        size: 80mm auto;
        margin: 4mm;
    }

    body * {
        visibility: hidden !important;
    }

    #struk,
    #struk * {
        visibility: visible !important;
    }

    #struk {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        max-width: none;
        margin: 0;
        border: none;
        border-radius: 0;
        box-shadow: none;
    }

    .receipt-header {
        background: white !important;
        color: black !important;
        border-bottom: 1px dashed #777;
    }

    .receipt-logo {
        border: 1px solid #777;
        color: black;
        background: white;
    }

    .receipt-info,
    .receipt-summary {
        background: white !important;
        border: 1px solid #ddd;
    }

    .status-paid {
        background: white !important;
        color: black !important;
        border: 1px solid #777;
    }

    .receipt-actions,
    .receipt-success,
    .no-print {
        display: none !important;
    }
}

</style>

<h2 style="margin-bottom:20px;">
    Kasir & Keranjang Belanja
</h2>

<?php if ($pesan): ?>

    <div class="<?= strpos($pesan, 'berhasil') !== false ? 'pos-success' : 'pos-alert' ?>">
        <?= e($pesan) ?>
    </div>

<?php endif; ?>

<?php if ($struk): ?>

<!-- ======================================
     STRUK PEMBAYARAN MODERN
======================================= -->

<div class="receipt-wrapper">

    <div class="receipt-success no-print">

        <div class="success-icon">✓</div>

        <h2>Pembayaran Berhasil!</h2>

        <p>
            Transaksi berhasil disimpan.
            Struk belanja kamu sudah siap.
        </p>

    </div>

    <div class="modern-receipt" id="struk">

        <div class="receipt-header">

            <div class="receipt-logo">T</div>

            <h1>TOKOKITA</h1>

            <p>Belanja Mudah, Harga Bersahabat</p>

        </div>

        <div class="receipt-body">

            <div class="receipt-status">

                <span>Status Pembayaran</span>

                <span class="status-paid">
                    ✓ LUNAS
                </span>

            </div>

            <div class="receipt-info">

                <div>
                    <small>Nomor Faktur</small>
                    <strong><?= e($struk['faktur']) ?></strong>
                </div>

                <div>
                    <small>Tanggal Transaksi</small>
                    <strong><?= e($struk['tanggal']) ?></strong>
                </div>

                <div>
                    <small>Waktu</small>
                    <strong><?= e($struk['waktu']) ?></strong>
                </div>

                <div>
                    <small>Nama Kasir</small>
                    <strong><?= e($struk['kasir']) ?></strong>
                </div>

            </div>

            <div class="receipt-section-title">
                Detail Belanja
            </div>

            <table class="receipt-items">

                <thead>

                    <tr>
                        <th>Produk</th>
                        <th>Qty</th>
                        <th class="right">Subtotal</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($struk['items'] as $item): ?>

                    <tr>

                        <td>

                            <strong>
                                <?= e($item['nama_produk']) ?>
                            </strong>

                            <small>
                                <?= rupiah($item['harga']) ?> / item
                            </small>

                        </td>

                        <td>
                            <?= (int)$item['qty'] ?>
                        </td>

                        <td class="right">
                            <?= rupiah(
                                $item['harga'] * $item['qty']
                            ) ?>
                        </td>

                    </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

            <div class="receipt-summary">

                <div class="summary-row">

                    <span>Subtotal Belanja</span>

                    <strong>
                        <?= rupiah($struk['total']) ?>
                    </strong>

                </div>

                <div class="summary-row">

                    <span>Metode Pembayaran</span>

                    <strong>
                        <?= e($struk['metode']) ?>
                    </strong>

                </div>

                <div class="summary-row total-row">

                    <span>Total Pembayaran</span>

                    <strong>
                        <?= rupiah($struk['total']) ?>
                    </strong>

                </div>

                <div class="summary-row">

                    <span>Uang Dibayarkan</span>

                    <strong>
                        <?= rupiah($struk['bayar']) ?>
                    </strong>

                </div>

                <div class="summary-row change-row">

                    <span>Kembalian</span>

                    <strong>
                        <?= rupiah($struk['kembalian']) ?>
                    </strong>

                </div>

            </div>

        </div>

        <div class="receipt-footer">

            <h4>Terima Kasih Telah Berbelanja!</h4>

            <p>
                Kepuasan pelanggan adalah prioritas kami.<br>
                Simpan struk ini sebagai bukti transaksi.<br><br>

                <strong>TOKOKITA</strong><br>
                Your Daily Shopping Partner
            </p>

        </div>

    </div>

    <div class="receipt-actions no-print">

        <button
            type="button"
            class="btn-print"
            onclick="window.print()">

            Cetak Struk

        </button>

        <a
            href="index.php?page=pos"
            class="btn-new">

            + Transaksi Baru

        </a>

    </div>

</div>

<?php else: ?>

<!-- ======================================
     HALAMAN KASIR DAN PRODUK
======================================= -->

<div class="pos-wrapper">

    <!-- DAFTAR PRODUK -->

    <div>

        <div class="pos-card">

            <h3>Pilih Produk</h3>

            <input
                type="text"
                id="cariProduk"
                class="pos-input"
                placeholder="Cari nama atau kode produk..."
            >

            <div style="max-height:430px;overflow:auto;">

                <table class="pos-table" id="tabelProduk">

                    <thead>

                        <tr>
                            <th>Produk</th>
                            <th>Harga</th>
                            <th>Stok</th>
                            <th>Aksi</th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($produkList as $p): ?>

                        <tr>

                            <td>

                                <b><?= e($p['nama_produk']) ?></b>

                                <br>

                                <small>
                                    <?= e($p['kode_produk']) ?>
                                </small>

                            </td>

                            <td>
                                <?= rupiah($p['harga_jual']) ?>
                            </td>

                            <td>
                                <?= (int)$p['stok'] ?>
                            </td>

                            <td>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$p['id_produk'] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="qty"
                                        value="1"
                                        min="1"
                                        max="<?= (int)$p['stok'] ?>"
                                        style="width:55px;padding:6px;"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        name="tambah_produk"
                                        class="pos-btn pos-primary"
                                        <?= $p['stok'] <= 0 ? 'disabled' : '' ?>
                                    >

                                        Tambah

                                    </button>

                                </form>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

    <!-- KERANJANG BELANJA -->

    <div>

        <div class="pos-card">

            <h3>Keranjang Belanja</h3>

            <?php if (empty($_SESSION['keranjang'])): ?>

                <p style="color:#888;text-align:center;padding:30px 0;">

                    Keranjang masih kosong.

                </p>

            <?php else: ?>

                <table class="pos-table">

                    <thead>

                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Subtotal</th>
                            <th></th>
                        </tr>

                    </thead>

                    <tbody>

                        <?php foreach ($_SESSION['keranjang'] as $item): ?>

                        <tr>

                            <td>

                                <b>
                                    <?= e($item['nama_produk']) ?>
                                </b>

                                <br>

                                <small>
                                    <?= rupiah($item['harga']) ?>
                                </small>

                            </td>

                            <td>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$item['id_produk'] ?>"
                                    >

                                    <input
                                        type="number"
                                        name="qty"
                                        value="<?= (int)$item['qty'] ?>"
                                        min="1"
                                        style="width:55px;padding:5px;"
                                        required
                                    >

                                    <button
                                        type="submit"
                                        name="ubah_qty"
                                        class="pos-btn"
                                        style="background:#eee;padding:6px;"
                                    >

                                        OK

                                    </button>

                                </form>

                            </td>

                            <td>

                                <?= rupiah(
                                    $item['harga'] * $item['qty']
                                ) ?>

                            </td>

                            <td>

                                <form method="POST">

                                    <input
                                        type="hidden"
                                        name="id_produk"
                                        value="<?= (int)$item['id_produk'] ?>"
                                    >

                                    <button
                                        type="submit"
                                        name="hapus_produk"
                                        class="pos-btn pos-danger"
                                    >

                                        X

                                    </button>

                                </form>

                            </td>

                        </tr>

                        <?php endforeach; ?>

                    </tbody>

                </table>

                <form method="POST" style="margin-top:12px;">

                    <button
                        type="submit"
                        name="kosongkan"
                        class="pos-btn pos-danger"
                    >

                        Kosongkan Keranjang

                    </button>

                </form>

            <?php endif; ?>

            <div class="pos-total pos-grand">

                <span>Total Belanja</span>

                <span>
                    <?= rupiah($total) ?>
                </span>

            </div>

            <?php if (!empty($_SESSION['keranjang'])): ?>

                <form method="POST">

                    <label>Metode Pembayaran</label>

                    <select
                        name="metode_pembayaran"
                        class="pos-input"
                        required
                    >

                        <option value="Tunai">Tunai</option>

                        <option value="QRIS">QRIS</option>

                        <option value="Debit">Debit</option>

                        <option value="Transfer Bank">
                            Transfer Bank
                        </option>

                    </select>

                    <label>Uang Dibayarkan</label>

                    <input
                        type="number"
                        name="bayar"
                        class="pos-input"
                        min="<?= (int)$total ?>"
                        step="1"
                        required
                        placeholder="Masukkan jumlah pembayaran"
                    >

                    <button
                        type="submit"
                        name="proses_bayar"
                        class="pos-btn pos-primary"
                        style="width:100%;padding:14px;"
                    >

                        Proses Pembayaran & Cetak Struk

                    </button>

                </form>

            <?php endif; ?>

        </div>

    </div>

</div>

<?php endif; ?>

<script>

// PENCARIAN PRODUK

document.getElementById('cariProduk')?.addEventListener(
    'input',
    function () {

        const kata = this.value.toLowerCase();

        document.querySelectorAll(
            '#tabelProduk tbody tr'
        ).forEach(function (baris) {

            baris.style.display =
                baris.innerText.toLowerCase().includes(kata)
                    ? ''
                    : 'none';

        });

    }
);

</script>
