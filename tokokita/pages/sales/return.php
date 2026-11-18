
<?php
require_once __DIR__ . '/../../config/database.php';

$pesan = '';
$error = '';

$escapeReturn = function ($value) {
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
};

// Proses retur
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['proses_retur'])) {

    $id_penjualan = (int) ($_POST['id_penjualan'] ?? 0);
    $id_produk = (int) ($_POST['id_produk'] ?? 0);
    $jumlah = (int) ($_POST['jumlah'] ?? 0);
    $alasan = trim($_POST['alasan'] ?? '');

    if ($id_penjualan <= 0 || $id_produk <= 0 || $jumlah <= 0 || $alasan === '') {
        $error = 'Lengkapi seluruh data retur dengan benar.';
    } else {
        try {
            $pdo->beginTransaction();

            // Periksa transaksi
            $stmt = $pdo->prepare("
                SELECT id_penjualan
                FROM tbl_penjualan
                WHERE id_penjualan = ?
                FOR UPDATE
            ");
            $stmt->execute([$id_penjualan]);

            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                throw new Exception('Transaksi tidak ditemukan.');
            }

            // Periksa produk dalam transaksi
            $stmt = $pdo->prepare("
                SELECT
                    d.id_produk,
                    d.jumlah,
                    pr.nama_produk
                FROM tbl_detail_penjualan d
                LEFT JOIN tbl_produk pr
                    ON pr.id_produk = d.id_produk
                WHERE d.id_penjualan = ?
                  AND d.id_produk = ?
            ");
            $stmt->execute([$id_penjualan, $id_produk]);

            $item = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$item) {
                throw new Exception('Produk tidak ditemukan pada transaksi tersebut.');
            }

            // Hitung jumlah retur sebelumnya
            $stmt = $pdo->prepare("
                SELECT COALESCE(SUM(jumlah), 0)
                FROM tbl_retur_penjualan
                WHERE id_penjualan = ?
                  AND id_produk = ?
            ");
            $stmt->execute([$id_penjualan, $id_produk]);

            $sudahDiretur = (int) $stmt->fetchColumn();
            $sisa = (int) $item['jumlah'] - $sudahDiretur;

            if ($jumlah > $sisa) {
                throw new Exception(
                    'Jumlah retur melebihi sisa barang yang dapat dikembalikan. Sisa: ' . $sisa
                );
            }

            // Simpan data retur
            $stmt = $pdo->prepare("
                INSERT INTO tbl_retur_penjualan
                (id_penjualan, id_produk, jumlah, alasan, tanggal)
                VALUES (?, ?, ?, ?, NOW())
            ");

            $stmt->execute([
                $id_penjualan,
                $id_produk,
                $jumlah,
                $alasan
            ]);

            // Kembalikan stok produk
            $stmt = $pdo->prepare("
                UPDATE tbl_produk
                SET stok = stok + ?
                WHERE id_produk = ?
            ");

            $stmt->execute([$jumlah, $id_produk]);

            if ($stmt->rowCount() === 0) {
                throw new Exception('Stok produk gagal diperbarui.');
            }

            $pdo->commit();

            $pesan = 'Retur berhasil disimpan dan stok produk telah diperbarui.';

        } catch (Throwable $ex) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            $error = $ex->getMessage();
        }
    }
}

// Ambil riwayat retur
$retur = [];
$totalRetur = 0;

try {
    $stmt = $pdo->query("
        SELECT
            r.*,
            p.kode_penjualan,
            pr.nama_produk
        FROM tbl_retur_penjualan r
        LEFT JOIN tbl_penjualan p
            ON p.id_penjualan = r.id_penjualan
        LEFT JOIN tbl_produk pr
            ON pr.id_produk = r.id_produk
        ORDER BY r.id_retur DESC
    ");

    $retur = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $totalRetur = count($retur);

} catch (PDOException $ex) {

    if (!$error) {
        $error = 'Gagal mengambil riwayat retur. Pastikan tabel tbl_retur_penjualan sudah dibuat.';
    }
}
?>

<style>
.sales-return {
    color: #292524;
}

.sales-return .heading {
    display: flex;
    justify-content: space-between;
    margin-bottom: 23px;
    flex-wrap: wrap;
    gap: 12px;
}

.sales-return h2 {
    font-size: 25px;
    font-weight: 750;
    margin: 0 0 5px;
}

.sales-return .subtitle {
    color: #999;
    font-size: 13px;
}

.sales-return .return-grid {
    display: grid;
    grid-template-columns: 350px minmax(0, 1fr);
    gap: 18px;
    align-items: start;
}

.sales-return .panel {
    background: white;
    border: 1px solid #eee;
    border-radius: 15px;
    padding: 21px;
}

.sales-return .panel h3 {
    font-size: 16px;
    font-weight: 750;
    margin: 0 0 20px;
}

.sales-return label {
    display: block;
    font-size: 12px;
    font-weight: 700;
    margin: 14px 0 7px;
}

.sales-return input,
.sales-return textarea {
    width: 100%;
    padding: 11px 12px;
    border: 1px solid #e8e8e8;
    border-radius: 9px;
    outline: none;
    font-size: 13px;
    box-sizing: border-box;
}

.sales-return input:focus,
.sales-return textarea:focus {
    border-color: #f5822b;
}

.sales-return textarea {
    resize: vertical;
    min-height: 90px;
}

.sales-return .submit-btn {
    width: 100%;
    background: #f5822b;
    border: 0;
    color: white;
    padding: 13px;
    border-radius: 9px;
    margin-top: 18px;
    cursor: pointer;
    font-weight: 750;
}

.sales-return .submit-btn:hover {
    background: #e66e18;
}

.sales-return .return-count {
    background: #fff1e6;
    color: #ed7925;
    border-radius: 12px;
    padding: 15px;
    margin-bottom: 15px;
}

.sales-return .return-count strong {
    font-size: 23px;
    display: block;
}

.sales-return .table-responsive {
    overflow-x: auto;
}

.sales-return table {
    width: 100%;
    min-width: 650px;
    border-collapse: collapse;
}

.sales-return th {
    text-align: left;
    background: #fafafa;
    color: #999;
    font-size: 11px;
    padding: 13px;
}

.sales-return td {
    padding: 14px 13px;
    border-bottom: 1px solid #eee;
    font-size: 12px;
}

.sales-return .alert {
    padding: 13px;
    border-radius: 9px;
    margin-bottom: 15px;
    font-size: 13px;
}

.sales-return .success {
    background: #e9f9ef;
    color: #168449;
}

.sales-return .danger {
    background: #fff0ee;
    color: #d64b3f;
}

.sales-return .empty {
    text-align: center;
    padding: 30px;
    color: #999;
}

@media(max-width: 850px) {
    .sales-return .return-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<div class="sales-return">

    <div class="heading">
        <div>
            <h2>Sales Return</h2>
            <div class="subtitle">
                Kelola pengembalian barang dari transaksi penjualan.
            </div>
        </div>
    </div>

    <?php if ($pesan): ?>
        <div class="alert success">
            <?= $escapeReturn($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert danger">
            <?= $escapeReturn($error) ?>
        </div>
    <?php endif; ?>

    <div class="return-grid">

        <div class="panel">

            <h3>
                <i class="fas fa-undo" style="color:#f5822b"></i>
                Form Pengembalian
            </h3>

            <form method="POST">

                <label>ID Transaksi</label>
                <input
                    type="number"
                    name="id_penjualan"
                    min="1"
                    placeholder="Masukkan ID transaksi"
                    required
                >

                <label>ID Produk</label>
                <input
                    type="number"
                    name="id_produk"
                    min="1"
                    placeholder="Masukkan ID produk"
                    required
                >

                <label>Jumlah Dikembalikan</label>
                <input
                    type="number"
                    name="jumlah"
                    min="1"
                    placeholder="Jumlah barang"
                    required
                >

                <label>Alasan Pengembalian</label>
                <textarea
                    name="alasan"
                    placeholder="Tuliskan alasan pengembalian..."
                    required
                ></textarea>

                <button
                    type="submit"
                    name="proses_retur"
                    class="submit-btn"
                >
                    <i class="fas fa-check-circle"></i>
                    Simpan Pengembalian
                </button>

            </form>

        </div>

        <div class="panel">

            <div class="return-count">
                <span style="font-size:12px">Total Riwayat Retur</span>
                <strong><?= number_format($totalRetur) ?></strong>
                <small>Data pengembalian barang</small>
            </div>

            <h3>Riwayat Pengembalian</h3>

            <div class="table-responsive">

                <table>
                    <thead>
                        <tr>
                            <th>NO</th>
                            <th>KODE TRANSAKSI</th>
                            <th>PRODUK</th>
                            <th>JUMLAH</th>
                            <th>ALASAN</th>
                            <th>TANGGAL</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if (!empty($retur)): ?>

                        <?php foreach ($retur as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>

                                <td>
                                    <strong style="color:#ed7925">
                                        <?= $escapeReturn($row['kode_penjualan'] ?? '-') ?>
                                    </strong>
                                </td>

                                <td>
                                    <?= $escapeReturn($row['nama_produk'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= (int) $row['jumlah'] ?>
                                </td>

                                <td>
                                    <?= $escapeReturn($row['alasan'] ?? '-') ?>
                                </td>

                                <td>
                                    <?= !empty($row['tanggal'])
                                        ? date('d/m/Y H:i', strtotime($row['tanggal']))
                                        : '-' ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="empty">
                                Belum ada riwayat pengembalian.
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>
                </table>

            </div>

        </div>

    </div>
</div>
