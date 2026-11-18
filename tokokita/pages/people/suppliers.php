
<?php
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $stmt = $pdo->prepare("
            INSERT INTO tbl_supplier
            (nama_supplier, telepon, email, alamat)
            VALUES (?, ?, ?, ?)
        ");

        $stmt->execute([
            trim($_POST['nama_supplier']),
            trim($_POST['telepon']),
            trim($_POST['email']),
            trim($_POST['alamat'])
        ]);

        $pesan = 'Supplier berhasil ditambahkan.';
    }

    if ($aksi === 'hapus') {
        $stmt = $pdo->prepare(
            "DELETE FROM tbl_supplier WHERE id_supplier = ?"
        );
        $stmt->execute([(int)$_POST['id']]);
        $pesan = 'Supplier berhasil dihapus.';
    }
}

$supplier = $pdo->query("
    SELECT * FROM tbl_supplier
    ORDER BY id_supplier DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold">Data Supplier</h4>
        <p class="text-muted">Kelola pemasok barang TOKOKITA.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Tambah Supplier</h5>

            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Supplier</label>
                        <input type="text" name="nama_supplier"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email"
                               class="form-control">
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Alamat</label>
                        <input type="text" name="alamat"
                               class="form-control">
                    </div>
                </div>

                <button class="btn text-white mt-3" style="background:#f5822b">
                    Simpan Supplier
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Supplier</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Supplier</th>
                            <th>Telepon</th>
                            <th>Email</th>
                            <th>Alamat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($supplier as $i => $s): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($s['nama_supplier']) ?></td>
                            <td><?= htmlspecialchars($s['telepon'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['email'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($s['alamat'] ?? '-') ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus supplier ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= (int)$s['id_supplier'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$supplier): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted">
                                Belum ada data supplier.
                            </td>
                        </tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
