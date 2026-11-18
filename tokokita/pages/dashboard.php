
<?php
$today = date('Y-m-d');

function getValue($pdo, $sql, $params = []) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchColumn() ?: 0;
}

$totalSales = getValue($pdo,
    "SELECT SUM(total_belanja) FROM tbl_transaksi WHERE tanggal=?",
    [$today]
);

$totalOrders = getValue($pdo,
    "SELECT COUNT(*) FROM tbl_transaksi WHERE tanggal=?",
    [$today]
);

$totalProducts = getValue($pdo,
    "SELECT COUNT(*) FROM tbl_produk"
);

$totalCustomers = getValue($pdo,
    "SELECT COUNT(*) FROM tbl_pelanggan"
);

$totalStock = getValue($pdo,
    "SELECT SUM(stok) FROM tbl_produk"
);

$lowStocks = $pdo->query("
    SELECT * FROM tbl_produk
    WHERE stok <= stok_minimal
    ORDER BY stok ASC
    LIMIT 5
")->fetchAll();

$recentTransactions = $pdo->query("
    SELECT t.*, p.nama_pelanggan
    FROM tbl_transaksi t
    LEFT JOIN tbl_pelanggan p
    ON t.id_pelanggan=p.id_pelanggan
    ORDER BY t.id_transaksi DESC
    LIMIT 7
")->fetchAll();

$monthlyLabels = [];
$monthlySales = [];
$monthlyPurchases = [];

for ($i = 1; $i <= 12; $i++) {
    $monthlyLabels[] = date('M', mktime(0,0,0,$i,1));

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(total_belanja),0)
        FROM tbl_transaksi
        WHERE MONTH(tanggal)=?
        AND YEAR(tanggal)=?
    ");
    $stmt->execute([$i,date('Y')]);
    $monthlySales[] = (float)$stmt->fetchColumn();

    $monthlyPurchases[] = 0;
}

$stockValue = getValue($pdo,
    "SELECT SUM(stok*harga_beli) FROM tbl_produk"
);

$profitEstimate = getValue($pdo,
    "SELECT SUM(d.subtotal - (d.qty * p.harga_beli))
     FROM tbl_detail_transaksi d
     LEFT JOIN tbl_produk p ON d.id_produk=p.id_produk
     JOIN tbl_transaksi t ON d.id_transaksi=t.id_transaksi
     WHERE t.tanggal=?",
    [$today]
);
?>

<div class="page-heading">
    <div>
        <h1>Welcome, Admin</h1>
        <p>You have <strong style="color:#ff9435">
            <?= number_format($totalOrders) ?>
        </strong> Orders, Today</p>
    </div>

    <div class="date-filter">
        <i class="fa-regular fa-calendar"></i>
        <?= date('d M Y') ?>
    </div>
</div>

<?php if ($lowStocks): ?>
<div class="stock-alert">
    <div>
        <i class="fa-solid fa-circle-exclamation"></i>
        Your Product
        <strong><?= htmlspecialchars($lowStocks[0]['nama_produk']) ?></strong>
        is running Low, already below stock limit.
        <a href="index.php?page=low_stock">Add Stock</a>
    </div>
    <a href="index.php?page=low_stock">×</a>
</div>
<?php endif; ?>

<div class="stats-grid">

    <div class="stat-card orange">
        <div class="stat-icon">
            <i class="fa-solid fa-file-invoice-dollar"></i>
        </div>
        <div>
            <div class="stat-label">Total Sales Today</div>
            <div class="stat-value"><?= rupiah($totalSales) ?></div>
        </div>
    </div>

    <div class="stat-card navy">
        <div class="stat-icon">
            <i class="fa-solid fa-arrow-rotate-left"></i>
        </div>
        <div>
            <div class="stat-label">Total Sales Return</div>
            <div class="stat-value">Rp 0</div>
        </div>
    </div>

    <div class="stat-card green">
        <div class="stat-icon">
            <i class="fa-solid fa-gift"></i>
        </div>
        <div>
            <div class="stat-label">Total Purchase</div>
            <div class="stat-value">Rp 0</div>
        </div>
    </div>

    <div class="stat-card blue">
        <div class="stat-icon">
            <i class="fa-solid fa-shield-halved"></i>
        </div>
        <div>
            <div class="stat-label">Purchase Return</div>
            <div class="stat-value">Rp 0</div>
        </div>
    </div>

</div>

<div class="secondary-grid">

    <div class="metric-card">
        <div class="metric-value"><?= rupiah($profitEstimate) ?></div>
        <div class="metric-label">Estimated Profit Today</div>
        <div class="metric-bottom">
            <span>Today's profit</span>
            <i class="fa-solid fa-layer-group" style="color:#109b86"></i>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?= number_format($totalOrders) ?></div>
        <div class="metric-label">Total Orders Today</div>
        <div class="metric-bottom">
            <span>Sales activity</span>
            <i class="fa-solid fa-clock" style="color:#109b86"></i>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?= rupiah($stockValue) ?></div>
        <div class="metric-label">Stock Purchase Value</div>
        <div class="metric-bottom">
            <span>Inventory value</span>
            <i class="fa-solid fa-boxes-stacked" style="color:#ff9435"></i>
        </div>
    </div>

    <div class="metric-card">
        <div class="metric-value"><?= number_format($totalStock) ?></div>
        <div class="metric-label">Total Stock Items</div>
        <div class="metric-bottom">
            <span>All products</span>
            <i class="fa-solid fa-hashtag" style="color:#7356df"></i>
        </div>
    </div>

</div>

<div class="dashboard-grid">

    <div>

        <div class="card">

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="card-title mb-0">
                    <i class="fa-solid fa-cart-shopping"
                       style="color:#ff9435"></i>
                    Sales & Purchase
                </div>

                <span class="badge text-bg-light">This Year</span>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-auto">
                    <div class="metric-card">
                        <small>Total Sales</small>
                        <h5><?= number_format(array_sum($monthlySales)/1000,0) ?>K</h5>
                    </div>
                </div>
                <div class="col-auto">
                    <div class="metric-card">
                        <small>Total Purchase</small>
                        <h5>0</h5>
                    </div>
                </div>
            </div>

            <div class="chart-area">
                <canvas id="salesChart"></canvas>
            </div>

        </div>

        <div class="card">
            <div class="card-title">Recent Transactions</div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>Invoice</th>
                            <th>Customer</th>
                            <th>Date</th>
                            <th>Amount</th>
                            <th>Action</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php foreach ($recentTransactions as $t): ?>
                        <tr>
                            <td><?= htmlspecialchars($t['no_faktur']) ?></td>
                            <td><?= htmlspecialchars($t['nama_pelanggan'] ?? 'Umum') ?></td>
                            <td><?= htmlspecialchars($t['tanggal']) ?></td>
                            <td><?= rupiah($t['total_belanja']) ?></td>
                            <td>
                                <a href="struk.php?id=<?= $t['id_transaksi'] ?>"
                                   class="btn btn-sm btn-outline-warning">
                                    <i class="fa-solid fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$recentTransactions): ?>
                        <tr>
                            <td colspan="5" class="text-center">
                                Belum ada transaksi.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    <div>

        <div class="card">
            <div class="card-title">
                <i class="fa-solid fa-circle-info text-primary"></i>
                Overall Information
            </div>

            <div class="info-boxes">
                <div class="info-box">
                    <i class="fa-solid fa-box"></i>
                    <small>Products</small>
                    <strong><?= number_format($totalProducts) ?></strong>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-users"></i>
                    <small>Customers</small>
                    <strong><?= number_format($totalCustomers) ?></strong>
                </div>

                <div class="info-box">
                    <i class="fa-solid fa-cart-shopping"></i>
                    <small>Orders</small>
                    <strong><?= number_format($totalOrders) ?></strong>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-title">Low Stock Overview</div>

            <?php if ($lowStocks): ?>
                <?php foreach ($lowStocks as $p): ?>
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <div>
                            <strong><?= htmlspecialchars($p['nama_produk']) ?></strong>
                            <div class="text-muted" style="font-size:10px">
                                <?= htmlspecialchars($p['kode_produk']) ?>
                            </div>
                        </div>

                        <span class="badge text-bg-warning">
                            <?= $p['stok'] ?> Left
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="text-muted">Semua stok produk aman.</div>
            <?php endif; ?>

            <a href="index.php?page=low_stock"
               class="btn btn-outline-warning btn-sm w-100 mt-3">
                View All
            </a>
        </div>

    </div>

</div>

<script>
const chartLabels = <?= json_encode($monthlyLabels) ?>;
const salesData = <?= json_encode($monthlySales) ?>;
const purchaseData = <?= json_encode($monthlyPurchases) ?>;

new Chart(document.getElementById('salesChart'), {
    type: 'bar',
    data: {
        labels: chartLabels,
        datasets: [
            {
                label: 'Total Sales',
                data: salesData,
                backgroundColor: '#ff9d36',
                borderRadius: 5,
                barPercentage: .65
            },
            {
                label: 'Total Purchase',
                data: purchaseData,
                backgroundColor: '#ffe2c5',
                borderRadius: 5,
                barPercentage: .65
            }
        ]
    },
    options: {
        maintainAspectRatio: false,
        responsive: true,
        plugins: {
            legend: {
                position: 'top',
                align: 'start',
                labels: {boxWidth: 8, font: {size: 10}}
            }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    font: {size: 9},
                    callback: value => 'Rp ' + value.toLocaleString('id-ID')
                },
                grid: {color: '#edf0f3'}
            },
            x: {
                ticks: {font: {size: 9}},
                grid: {display: false}
            }
        }
    }
});
</script>
