
<?php
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_pelanggan
            (nama_pelanggan, kota, telepon, alamat, no_kartu, tipe_member)
            VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($_POST['nama']),
            trim($_POST['kota']),
            trim($_POST['telepon']),
            trim($_POST['alamat']),
            trim($_POST['no_kartu']) ?: null,
            $_POST['tipe_member']
        ]);

        $pesan = 'Pelanggan berhasil ditambahkan.';
    }

    if ($aksi === 'hapus') {
        $stmt = $pdo->prepare(
            "DELETE FROM tbl_pelanggan WHERE id_pelanggan = ?"
        );
        $stmt->execute([(int)$_POST['id']]);
        $pesan = 'Data pelanggan berhasil dihapus.';
    }
}

$pelanggan = $pdo->query("
    SELECT * FROM tbl_pelanggan
    ORDER BY id_pelanggan DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold">Data Pelanggan</h4>
        <p class="text-muted">Kelola informasi pelanggan TOKOKITA.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Tambah Pelanggan</h5>

            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Pelanggan</label>
                        <input type="text" name="nama" class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Kota</label>
                        <input type="text" name="kota" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Nomor Kartu</label>
                        <input type="text" name="no_kartu" class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tipe Member</label>
                        <select name="tipe_member" class="form-select">
                            <option value="Regular">Regular</option>
                            <option value="Silver">Silver</option>
                            <option value="Gold">Gold</option>
                            <option value="Platinum">Platinum</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="alamat" class="form-control">
                    </div>
                </div>

                <button class="btn text-white mt-3" style="background:#f5822b">
                    Simpan Pelanggan
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Pelanggan</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Kota</th>
                            <th>Telepon</th>
                            <th>Member</th>
                            <th>Poin</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($pelanggan as $i => $p): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($p['nama_pelanggan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['kota'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($p['telepon'] ?? '-') ?></td>
                            <td>
                                <span class="badge bg-warning text-dark">
                                    <?= htmlspecialchars($p['tipe_member'] ?? 'Regular') ?>
                                </span>
                            </td>
                            <td><?= number_format((int)$p['poin']) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus pelanggan ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= (int)$p['id_pelanggan'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$pelanggan): ?>
                        <tr>
                            <td colspan="7" class="text-center text-muted">
                                Belum ada data pelanggan.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
