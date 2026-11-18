<?php
$pesan = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idProduk = (int)($_POST['id_produk'] ?? 0);
    $jenis = $_POST['jenis'] ?? '';
    $jumlah = (int)($_POST['jumlah'] ?? 0);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($idProduk <= 0 || $jumlah <= 0 ||
        !in_array($jenis, ['masuk', 'keluar', 'penyesuaian'], true)) {
        $error = 'Data penyesuaian stok tidak valid.';
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare(
                "SELECT stok FROM tbl_produk WHERE id_produk = ? FOR UPDATE"
            );
            $stmt->execute([$idProduk]);
            $dataProduk = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$dataProduk) {
                throw new Exception('Produk tidak ditemukan.');
            }

            $stokSebelum = (int)$dataProduk['stok'];

            if ($jenis === 'masuk') {
                $stokSesudah = $stokSebelum + $jumlah;
            } elseif ($jenis === 'keluar') {
                $stokSesudah = $stokSebelum - $jumlah;

                if ($stokSesudah < 0) {
                    throw new Exception('Stok tidak mencukupi untuk dikurangi.');
                }
            } else {
                $stokSesudah = $jumlah;
            }

            $update = $pdo->prepare(
                "UPDATE tbl_produk SET stok = ? WHERE id_produk = ?"
            );
            $update->execute([$stokSesudah, $idProduk]);

            $riwayat = $pdo->prepare(
                "INSERT INTO tbl_riwayat_stok
                (id_produk, jenis, jumlah, stok_sebelum, stok_sesudah, keterangan)
                VALUES (?, ?, ?, ?, ?, ?)"
            );
            $riwayat->execute([
                $idProduk,
                $jenis,
                $jumlah,
                $stokSebelum,
                $stokSesudah,
                $keterangan
            ]);

            $pdo->commit();
            $pesan = 'Penyesuaian stok berhasil disimpan.';
        } catch (Throwable $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $error = $e->getMessage();
        }
    }
}

$idTerpilih = (int)($_GET['id'] ?? 0);

$produk = $pdo->query(
    "SELECT id_produk, kode_produk, nama_produk, stok
     FROM tbl_produk ORDER BY nama_produk"
)->fetchAll(PDO::FETCH_ASSOC);

$riwayat = $pdo->query(
    "SELECT r.*, p.nama_produk, p.kode_produk
     FROM tbl_riwayat_stok r
     JOIN tbl_produk p ON p.id_produk = r.id_produk
     ORDER BY r.id_riwayat DESC LIMIT 20"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Penyesuaian Stok</h4>
        <p class="text-muted mb-0">Catat stok masuk, stok keluar, dan perubahan persediaan.</p>
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
                    <h5 class="fw-bold mb-4">Form Penyesuaian</h5>

                    <form method="POST">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Pilih Produk</label>
                            <select name="id_produk" class="form-select" required>
                                <option value="">-- Pilih Produk --</option>
                                <?php foreach ($produk as $p): ?>
                                    <option value="<?= (int)$p['id_produk'] ?>"
                                        <?= $idTerpilih === (int)$p['id_produk'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($p['nama_produk']) ?>
                                        (Stok: <?= (int)$p['stok'] ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jenis Penyesuaian</label>
                            <select name="jenis" class="form-select" required>
                                <option value="masuk">Stok Masuk</option>
                                <option value="keluar">Stok Keluar</option>
                                <option value="penyesuaian">Atur Jumlah Stok</option>
                            </select>
                            <small class="text-muted">
                                Pada pilihan Atur Jumlah Stok, jumlah yang diisi menjadi stok akhir.
                            </small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-semibold">Jumlah</label>
                            <input type="number" name="jumlah" class="form-control"
                                   min="1" required placeholder="Masukkan jumlah">
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-semibold">Keterangan</label>
                            <textarea name="keterangan" class="form-control" rows="3"
                                      placeholder="Alasan penyesuaian stok..."></textarea>
                        </div>

                        <button type="submit" class="btn text-white w-100"
                                style="background:#f5822b">
                            Simpan Penyesuaian
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-lg-7">
            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Riwayat Stok Terbaru</h5>

                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Produk</th>
                                    <th>Jenis</th>
                                    <th>Perubahan</th>
                                    <th>Stok Akhir</th>
                                </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($riwayat as $r): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold">
                                            <?= htmlspecialchars($r['nama_produk']) ?>
                                        </div>
                                        <small class="text-muted">
                                            <?= htmlspecialchars($r['kode_produk'] ?? '-') ?>
                                        </small>
                                    </td>
                                    <td>
                                        <span class="badge
                                            <?= $r['jenis'] === 'masuk' ? 'bg-success' :
                                                ($r['jenis'] === 'keluar' ? 'bg-danger' : 'bg-warning text-dark') ?>">
                                            <?= ucfirst($r['jenis']) ?>
                                        </span>
                                    </td>
                                    <td><?= number_format((int)$r['jumlah']) ?></td>
                                    <td><?= number_format((int)$r['stok_sesudah']) ?></td>
                                </tr>
                            <?php endforeach; ?>

                            <?php if (!$riwayat): ?>
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-3">
                                        Belum ada riwayat stok.
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