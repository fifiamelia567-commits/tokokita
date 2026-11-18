<?php
$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProduk = (int)($_POST['id_produk'] ?? 0);
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $asal = trim($_POST['lokasi_asal'] ?? '');
    $tujuan = trim($_POST['lokasi_tujuan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($idProduk <= 0 || $jumlah <= 0 || $asal === '' || $tujuan === '') {
        $error = 'Lengkapi semua data yang wajib diisi.';
    } elseif (strtolower($asal) === strtolower($tujuan)) {
        $error = 'Lokasi asal dan tujuan tidak boleh sama.';
    } else {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_transfer_stok
            (id_produk, jumlah, lokasi_asal, lokasi_tujuan, keterangan)
            VALUES (?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $idProduk,
            $jumlah,
            $asal,
            $tujuan,
            $keterangan
        ]);

        $pesan = 'Data pemindahan stok berhasil dicatat.';
    }
}

$produk = $pdo->query(
    "SELECT id_produk, nama_produk, kode_produk, stok
     FROM tbl_produk ORDER BY nama_produk"
)->fetchAll(PDO::FETCH_ASSOC);

$transfer = $pdo->query(
    "SELECT t.*, p.nama_produk, p.kode_produk
     FROM tbl_transfer_stok t
     JOIN tbl_produk p ON p.id_produk = t.id_produk
     ORDER BY t.id_transfer DESC LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Pemindahan Stok</h4>
        <p class="text-muted mb-0">Catat pemindahan produk antar lokasi penyimpanan.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-4">Form Pemindahan</h5>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Produk</label>
                            <select name="id_produk" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produk as $p): ?>
                                    <option value="<?= (int)$p['id_produk'] ?>">
                                        <?= htmlspecialchars($p['nama_produk']) ?>
                                        (Stok: <?= (int)$p['stok'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control"
                                   min="1" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lokasi Asal</label>
                            <input type="text" name="lokasi_asal" class="form-control"
                                   required placeholder="Contoh: Gudang Utama">
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Lokasi Tujuan</label>
                            <input type="text" name="lokasi_tujuan" class="form-control"
                                   required placeholder="Contoh: Toko Depan">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"></textarea>
                        </div>

                        <button type="submit" class="btn text-white w-100"
                                style="background:#f5822b">
                            Simpan Pemindahan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Riwayat Pemindahan</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Jumlah</th>
                                    <th>Asal</th>
                                    <th>Tujuan</th>
                                    <th>Tanggal</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($transfer as $t): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($t['nama_produk']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($t['kode_produk'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td><?= number_format((int)$t['jumlah']) ?></td>
                                    <td><?= htmlspecialchars($t['lokasi_asal']) ?></td>
                                    <td><?= htmlspecialchars($t['lokasi_tujuan']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$transfer): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-3">
                                        Belum ada data pemindahan.
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