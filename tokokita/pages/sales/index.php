
<?php
require_once __DIR__ . '/../../config/database.php';

// Escape HTML tanpa membuat fungsi global baru
$escapeSales = function ($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
};

$cari = trim($_GET['cari'] ?? '');
$tanggal = trim($_GET['tanggal'] ?? '');

$where = [];
$params = [];

if ($cari !== '') {
    $where[] = "(kode_penjualan LIKE ? OR CAST(id_penjualan AS CHAR) LIKE ?)";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}

if ($tanggal !== '') {
    $dateObject = DateTime::createFromFormat('Y-m-d', $tanggal);

    if ($dateObject && $dateObject->format('Y-m-d') === $tanggal) {
        $where[] = "DATE(tanggal) = ?";
        $params[] = $tanggal;
    } else {
        $tanggal = '';
    }
}

$sqlWhere = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$errorSales = '';

try {
    // Daftar transaksi
    $stmt = $pdo->prepare("
        SELECT *
        FROM tbl_penjualan
        $sqlWhere
        ORDER BY id_penjualan DESC
    ");

    $stmt->execute($params);
    $transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Statistik keseluruhan
    $totalTransaksi = $pdo->query("
        SELECT COUNT(*)
        FROM tbl_penjualan
    ")->fetchColumn();

    $totalPenjualan = $pdo->query("
        SELECT COALESCE(SUM(total), 0)
        FROM tbl_penjualan
    ")->fetchColumn();

    $penjualanHariIni = $pdo->query("
        SELECT COALESCE(SUM(total), 0)
        FROM tbl_penjualan
        WHERE DATE(tanggal) = CURDATE()
    ")->fetchColumn();

} catch (PDOException $ex) {
    $transaksi = [];
    $totalTransaksi = 0;
    $totalPenjualan = 0;
    $penjualanHariIni = 0;

    $errorSales = $ex->getMessage();
}
?>

<style>
.sales-list {
    color: #292524;
    padding-bottom: 25px;
}

.sales-list * {
    box-sizing: border-box;
}

.sales-list .page-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 24px;
    flex-wrap: wrap;
    gap: 15px;
}

.sales-list h2 {
    font-size: 25px;
    font-weight: 750;
    margin: 0 0 5px;
}

.sales-list .subtitle {
    font-size: 13px;
    color: #999;
    margin: 0;
}

.sales-list .add-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    background: #f5822b;
    color: white;
    padding: 12px 18px;
    border-radius: 10px;
    text-decoration: none;
    font-size: 13px;
    font-weight: 700;
    transition: .2s;
}

.sales-list .add-btn:hover {
    background: #e66c16;
    color: white;
}

.sales-list .stats {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 16px;
    margin-bottom: 22px;
}

.sales-list .stat-card {
    background: white;
    border: 1px solid #eee;
    border-radius: 14px;
    padding: 20px;
    display: flex;
    align-items: center;
    gap: 14px;
    box-shadow: 0 3px 15px rgba(0,0,0,.025);
    min-width: 0;
}

.sales-list .stat-icon {
    width: 45px;
    height: 45px;
    flex-shrink: 0;
    border-radius: 12px;
    display: grid;
    place-items: center;
    background: #fff1e6;
    color: #f5822b;
    font-size: 18px;
}

.sales-list .stat-card small {
    display: block;
    color: #999;
    font-size: 12px;
    margin-bottom: 6px;
}

.sales-list .stat-card strong {
    font-size: 17px;
    overflow-wrap: anywhere;
}

.sales-list .table-panel {
    background: white;
    border: 1px solid #eee;
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 3px 15px rgba(0,0,0,.025);
}

.sales-list .panel-heading {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 18px;
}

.sales-list .panel-heading h3 {
    font-size: 16px;
    font-weight: 750;
    margin: 0;
}

.sales-list .filter-form {
    display: flex;
    flex-wrap: wrap;
    gap: 10px;
    margin-bottom: 20px;
}

.sales-list .filter-form input {
    border: 1px solid #eee;
    padding: 11px 13px;
    border-radius: 9px;
    outline: none;
    font-size: 13px;
    min-width: 180px;
}

.sales-list .filter-form input:focus {
    border-color: #f5822b;
}

.sales-list .filter-form button {
    background: #f5822b;
    color: white;
    border: 0;
    padding: 0 20px;
    border-radius: 9px;
    cursor: pointer;
    font-weight: 700;
}

.sales-list .reset-btn {
    display: inline-flex;
    align-items: center;
    padding: 0 14px;
    border-radius: 9px;
    background: #f5f5f5;
    color: #666;
    text-decoration: none;
    font-size: 12px;
}

.sales-list .table-responsive {
    overflow-x: auto;
}

.sales-list table {
    width: 100%;
    border-collapse: collapse;
    min-width: 720px;
}

.sales-list th {
    text-align: left;
    color: #999;
    font-size: 11px;
    font-weight: 700;
    padding: 14px 12px;
    background: #fafafa;
    white-space: nowrap;
}

.sales-list td {
    padding: 15px 12px;
    border-bottom: 1px solid #f1f1f1;
    font-size: 12px;
}

.sales-list tbody tr:hover {
    background: #fffaf6;
}

.sales-list .code {
    color: #ed7925;
    font-weight: 750;
}

.sales-list .status {
    display: inline-block;
    background: #e9f9ef;
    color: #168449;
    padding: 6px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: 700;
}

.sales-list .action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 11px;
    border-radius: 8px;
    background: #fff1e6;
    color: #ed7925;
    text-decoration: none;
    font-weight: 700;
    white-space: nowrap;
}

.sales-list .action-btn:hover {
    background: #f5822b;
    color: white;
}

.sales-list .empty {
    text-align: center;
    color: #999;
    padding: 35px;
}

.sales-list .error-box {
    background: #fff0ee;
    color: #c7473c;
    border-radius: 10px;
    padding: 15px;
    margin-bottom: 18px;
    font-size: 13px;
    overflow-wrap: anywhere;
}

.sales-list .table-footer {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-top: 17px;
    color: #999;
    font-size: 12px;
    flex-wrap: wrap;
    gap: 10px;
}

@media(max-width: 850px) {
    .sales-list .stats {
        grid-template-columns: 1fr;
    }
}

@media(max-width: 500px) {
    .sales-list .table-panel {
        padding: 13px;
    }

    .sales-list .filter-form {
        flex-direction: column;
    }

    .sales-list .filter-form input,
    .sales-list .filter-form button,
    .sales-list .reset-btn {
        width: 100%;
        min-height: 40px;
    }

    .sales-list .page-heading h2 {
        font-size: 21px;
    }
}
</style>

<div class="sales-list">

    <div class="page-heading">
        <div>
            <h2>Sales Transactions</h2>
            <p class="subtitle">
                Kelola dan pantau seluruh transaksi penjualan TOKOKITA.
            </p>
        </div>

        <a href="index.php?page=sales/pos" class="add-btn">
            <i class="fas fa-plus"></i>
            Transaksi Baru
        </a>
    </div>

    <?php if ($errorSales !== ''): ?>

        <div class="error-box">
            <strong>Gagal mengambil data transaksi.</strong>
            <br>
            <?= $escapeSales($errorSales) ?>
        </div>

    <?php endif; ?>

    <div class="stats">

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-receipt"></i>
            </div>

            <div>
                <small>Total Transaksi</small>
                <strong>
                    <?= number_format((int)$totalTransaksi, 0, ',', '.') ?>
                </strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-wallet"></i>
            </div>

            <div>
                <small>Total Penjualan</small>
                <strong><?= rupiah($totalPenjualan) ?></strong>
            </div>
        </div>

        <div class="stat-card">
            <div class="stat-icon">
                <i class="fas fa-calendar-day"></i>
            </div>

            <div>
                <small>Penjualan Hari Ini</small>
                <strong><?= rupiah($penjualanHariIni) ?></strong>
            </div>
        </div>

    </div>

    <div class="table-panel">

        <div class="panel-heading">
            <h3>Daftar Transaksi</h3>
        </div>

        <form method="GET" action="index.php" class="filter-form">

            <input
                type="hidden"
                name="page"
                value="sales/index"
            >

            <input
                type="text"
                name="cari"
                placeholder="Cari kode atau ID transaksi..."
                value="<?= $escapeSales($cari) ?>"
            >

            <input
                type="date"
                name="tanggal"
                value="<?= $escapeSales($tanggal) ?>"
            >

            <button type="submit">
                <i class="fas fa-search"></i>
                Filter
            </button>

            <?php if ($cari !== '' || $tanggal !== ''): ?>
                <a href="index.php?page=sales/index" class="reset-btn">
                    Reset
                </a>
            <?php endif; ?>

        </form>

        <div class="table-responsive">

            <table>

                <thead>
                    <tr>
                        <th>NO</th>
                        <th>KODE TRANSAKSI</th>
                        <th>TANGGAL</th>
                        <th>TOTAL</th>
                        <th>PEMBAYARAN</th>
                        <th>STATUS</th>
                        <th>AKSI</th>
                    </tr>
                </thead>

                <tbody>

                <?php if ($transaksi): ?>

                    <?php foreach ($transaksi as $i => $row): ?>

                        <tr>

                            <td><?= $i + 1 ?></td>

                            <td class="code">
                                <?= $escapeSales($row['kode_penjualan'] ?? '-') ?>
                            </td>

                            <td>
                                <?php
                                $waktu = strtotime($row['tanggal'] ?? '');
                                echo $waktu
                                    ? date('d/m/Y H:i', $waktu)
                                    : '-';
                                ?>
                            </td>

                            <td>
                                <strong>
                                    <?= rupiah($row['total'] ?? 0) ?>
                                </strong>
                            </td>

                            <td>
                                <?= rupiah($row['bayar'] ?? 0) ?>
                            </td>

                            <td>
                                <span class="status">
                                    Lunas
                                </span>
                            </td>

                            <td>
                                <a
                                    class="action-btn"
                                    href="index.php?page=sales/detail&id=<?= (int)$row['id_penjualan'] ?>"
                                >
                                    <i class="fas fa-eye"></i>
                                    Detail
                                </a>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="7" class="empty">
                            <i class="fas fa-receipt"
                               style="font-size:30px;color:#ddd;display:block;margin-bottom:12px">
                            </i>

                            Belum ada transaksi penjualan.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

        <div class="table-footer">
            <span>
                Menampilkan <?= count($transaksi) ?> transaksi
            </span>

            <span>
                TOKOKITA Sales Management
            </span>
        </div>

    </div>

</div>
