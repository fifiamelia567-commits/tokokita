<?php
$keyword = trim($_GET['cari'] ?? '');

$sql = "SELECT id_produk, kode_produk, nama_produk, stok
        FROM tbl_produk";
$params = [];

if ($keyword !== '') {
    $sql .= " WHERE nama_produk LIKE ? OR kode_produk LIKE ?";
    $params = ["%$keyword%", "%$keyword%"];
}

$sql .= " ORDER BY nama_produk ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$produk = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalProduk = (int) $pdo->query("SELECT COUNT(*) FROM tbl_produk")->fetchColumn();
$totalStok = (int) $pdo->query("SELECT COALESCE(SUM(stok),0) FROM tbl_produk")->fetchColumn();
$stokRendah = (int) $pdo->query("SELECT COUNT(*) FROM tbl_produk WHERE stok <= 5")->fetchColumn();
?>

<div class="container-fluid py-3">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manajemen Stok</h4>
            <p class="text-muted mb-0">Pantau persediaan produk TokoKita.</p>
        </div>
        <a href="index.php?page=adjustment" class="btn text-white"
           style="background:#f5822b">
            <i class="bi bi-plus-circle me-1"></i> Penyesuaian Stok
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <small class="text-muted">Total Produk</small>
                <h3 class="fw-bold mb-0"><?= number_format($totalProduk) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <small class="text-muted">Total Stok</small>
                <h3 class="fw-bold mb-0"><?= number_format($totalStok) ?></h3>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-3">
                <small class="text-muted">Stok Rendah (maks. 5)</small>
                <h3 class="fw-bold text-danger mb-0"><?= number_format($stokRendah) ?></h3>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <form method="GET" class="row g-2 mb-4">
                <input type="hidden" name="page" value="manage">
                <div class="col-md-5">
                    <input type="text" name="cari" class="form-control"
                           placeholder="Cari kode atau nama produk..."
                           value="<?= htmlspecialchars($keyword) ?>">
                </div>
                <div class="col-auto">
                    <button class="btn text-white" style="background:#f5822b">
                        <i class="bi bi-search"></i> Cari
                    </button>
                    <a href="index.php?page=manage" class="btn btn-light">Reset</a>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Kode Produk</th>
                            <th>Nama Produk</th>
                            <th>Stok</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($produk): ?>
                        <?php foreach ($produk as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= htmlspecialchars($item['kode_produk'] ?? '-') ?></td>
                                <td class="fw-semibold">
                                    <?= htmlspecialchars($item['nama_produk'] ?? '-') ?>
                                </td>
                                <td><?= number_format((int)$item['stok']) ?></td>
                                <td>
                                    <?php if ((int)$item['stok'] <= 5): ?>
                                        <span class="badge bg-danger">Stok Rendah</span>
                                    <?php elseif ((int)$item['stok'] <= 15): ?>
                                        <span class="badge bg-warning text-dark">Perlu Dipantau</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Tersedia</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="index.php?page=adjustment&id=<?= (int)$item['id_produk'] ?>"
                                       class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-pencil-square"></i> Sesuaikan
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">
                                Produk tidak ditemukan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>