<?php
$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProduk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $supplier = trim($_POST['supplier'] ?? '');
    $alasan = trim($_POST['alasan'] ?? '');

    if ($idProduk <= 0 || $jumlah <= 0 || $supplier === '') {
        $error = 'Lengkapi data pengembalian terlebih dahulu.';
    } else {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO tbl_pengembalian_pembelian
                (id_produk, jumlah, supplier, alasan)
                VALUES (?, ?, ?, ?)"
            );

            $stmt->execute([
                $idProduk,
                $jumlah,
                $supplier,
                $alasan
            ]);

            $pesan = 'Data pengembalian berhasil disimpan.';
        } catch (PDOException $e) {
            $error = 'Gagal menyimpan pengembalian. Periksa tabel database.';
        }
    }
}

$produk = $pdo->query(
    "SELECT id_produk, nama_produk, kode_produk, stok
     FROM tbl_produk ORDER BY nama_produk"
)->fetchAll(PDO::FETCH_ASSOC);

$riwayat = $pdo->query(
    "SELECT r.*, p.nama_produk
     FROM tbl_pengembalian_pembelian r
     JOIN tbl_produk p ON p.id_produk = r.id_produk
     ORDER BY r.id_pengembalian DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Pengembalian Pembelian</h4>
        <p class="text-muted mb-0">Catat barang yang dikembalikan kepada supplier.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Form Pengembalian</h5>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Produk</label>
                            <select name="id_produk" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produk as $p): ?>
                                    <option value="<?= (int)$p['id_produk'] ?>">
                                        <?= htmlspecialchars($p['nama_produk']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" name="jumlah" min="1"
                                   class="form-control" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Nama Supplier</label>
                            <input type="text" name="supplier"
                                   class="form-control" required>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Alasan Pengembalian</label>
                            <textarea name="alasan" class="form-control"
                                      rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn text-white w-100"
                                style="background:#f5822b">
                            Simpan Pengembalian
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Riwayat Pengembalian</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>Supplier</th>
                                    <th>Alasan</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($riwayat as $r): ?>
                                <tr>
                                    <td><?= htmlspecialchars($r['nama_produk']) ?></td>
                                    <td><?= number_format((int)$r['jumlah']) ?></td>
                                    <td><?= htmlspecialchars($r['supplier']) ?></td>
                                    <td><?= htmlspecialchars($r['alasan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$riwayat): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Belum ada data pengembalian.
                                    </td>
                                </tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>