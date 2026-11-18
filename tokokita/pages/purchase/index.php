<?php
$keyword = trim($_GET['cari'] ?? '');

$sql = "SELECT * FROM tbl_pembelian";
$params = [];

if ($keyword !== '') {
    $sql .= " WHERE no_pembelian LIKE ?";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY id_pembelian DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$pembelian = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalPembelian = (int)$pdo->query(
    "SELECT COUNT(*) FROM tbl_pembelian"
)->fetchColumn();

$totalNominal = (float)$pdo->query(
    "SELECT COALESCE(SUM(total_pembelian), 0) FROM tbl_pembelian"
)->fetchColumn();
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Pembelian Barang</h4>
            <p class="text-muted mb-0">Kelola transaksi pembelian produk.</p>
        </div>
        <a href="index.php?page=purchase_create"
           class="btn text-white" style="background:#f5822b">
            <i class="bi bi-plus-circle me-1"></i> Tambah Pembelian
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <small class="text-muted">Total Transaksi</small>
                <h3 class="fw-bold mb-0"><?= number_format($totalPembelian) ?></h3>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <small class="text-muted">Total Nominal Pembelian</small>
                <h3 class="fw-bold mb-0">
                    Rp <?= number_format($totalNominal, 0, ',', '.') ?>
                </h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-2 mb-4">
                <input type="hidden" name="page" value="purchase">
                <div class="col-md-5">
                    <input type="text" name="cari" class="form-control"
                           placeholder="Cari nomor pembelian..."
                           value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-auto">
                    <button class="btn text-white" style="background:#f5822b">
                        Cari
                    </button>
                    <a href="index.php?page=purchase" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>No. Pembelian</th>
                            <th>Tanggal</th>
                            <th>Supplier</th>
                            <th>Total</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($pembelian): ?>
                        <?php foreach ($pembelian as $i => $p): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($p['no_pembelian']) ?>
                                </td>
                                <td>
                                    <?= date('d/m/Y', strtotime($p['tanggal'])) ?>
                                </td>
                                <td>
                                    <?= htmlspecialchars($p['nama_supplier'] ?? '-') ?>
                                </td>
                                <td>
                                    Rp <?= number_format($p['total_pembelian'], 0, ',', '.') ?>
                                </td>
                                <td>
                                    <span class="badge bg-success">
                                        <?= htmlspecialchars($p['status'] ?? 'Selesai') ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Belum ada transaksi pembelian.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>