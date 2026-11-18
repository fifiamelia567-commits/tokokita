
<?php
$pesan = '';
$tipe = 'success';

if (isset($_POST['simpan'])) {
    $nama = trim($_POST['nama_brand']);
    $deskripsi = trim($_POST['deskripsi']);

    if ($nama !== '') {
        $stmt = $pdo->prepare(
            "INSERT INTO tbl_brand (nama_brand, deskripsi) VALUES (?, ?)"
        );
        $stmt->execute([$nama, $deskripsi]);

        header('Location: index.php?page=brands&status=tambah');
        exit;
    }
}

if (isset($_POST['update'])) {
    $id = (int)$_POST['id_brand'];

    $stmt = $pdo->prepare(
        "UPDATE tbl_brand SET nama_brand=?, deskripsi=? WHERE id_brand=?"
    );
    $stmt->execute([
        trim($_POST['nama_brand']),
        trim($_POST['deskripsi']),
        $id
    ]);

    header('Location: index.php?page=brands&status=edit');
    exit;
}

if (isset($_POST['hapus'])) {
    $stmt = $pdo->prepare(
        "DELETE FROM tbl_brand WHERE id_brand=?"
    );
    $stmt->execute([(int)$_POST['id_brand']]);

    header('Location: index.php?page=brands&status=hapus');
    exit;
}

$pesanStatus = [
    'tambah' => 'Brand berhasil ditambahkan!',
    'edit' => 'Brand berhasil diperbarui!',
    'hapus' => 'Brand berhasil dihapus!'
];

$pesan = $pesanStatus[$_GET['status'] ?? ''] ?? '';

$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM tbl_brand WHERE id_brand=?"
    );
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$search = trim($_GET['search'] ?? '');

$stmt = $pdo->prepare("
    SELECT * FROM tbl_brand
    WHERE nama_brand LIKE ?
    ORDER BY id_brand DESC
");
$stmt->execute(["%$search%"]);
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

$total = $pdo->query("SELECT COUNT(*) FROM tbl_brand")->fetchColumn();
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-4">
        <h4 class="fw-bold">Brand Management</h4>
        <p class="text-muted">Kelola merek produk TOKOKITA.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-success"><?= htmlspecialchars($pesan) ?></div>
    <?php endif; ?>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <p class="text-muted mb-1">Total Brand</p>
                <h3 class="fw-bold"><?= number_format($total) ?></h3>
                <i class="fas fa-tags text-warning fa-2x"></i>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Brand' : 'Tambah Brand' ?></h5>

            <form method="POST" action="index.php?page=brands">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_brand" value="<?= $edit['id_brand'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nama Brand</label>
                        <input type="text" name="nama_brand" class="form-control"
                               value="<?= htmlspecialchars($edit['nama_brand'] ?? '') ?>"
                               placeholder="Masukkan nama brand" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Deskripsi</label>
                        <input type="text" name="deskripsi" class="form-control"
                               value="<?= htmlspecialchars($edit['deskripsi'] ?? '') ?>"
                               placeholder="Deskripsi brand">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button type="submit"
                                name="<?= $edit ? 'update' : 'simpan' ?>"
                                class="btn text-white w-100"
                                style="background:#f5822b">
                            <?= $edit ? 'Update' : 'Tambah' ?>
                        </button>
                    </div>
                </div>
            </form>

            <?php if ($edit): ?>
                <a href="index.php?page=brands" class="btn btn-light mt-3">Batal</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Brand</h5>

            <form method="GET" action="index.php" class="mb-3">
                <input type="hidden" name="page" value="brands">
                <div class="input-group" style="max-width:400px">
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari brand..."
                           value="<?= htmlspecialchars($search) ?>">
                    <button class="btn text-white" style="background:#f5822b">
                        <i class="fas fa-search"></i>
                    </button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Brand</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_brand']) ?></td>
                                <td><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                                <td>
                                    <a href="index.php?page=brands&edit=<?= $row['id_brand'] ?>"
                                       class="btn btn-sm btn-light text-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST" action="index.php?page=brands"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus brand ini?')">
                                        <input type="hidden" name="id_brand"
                                               value="<?= $row['id_brand'] ?>">
                                        <button name="hapus" class="btn btn-sm btn-light text-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4">Belum ada data brand.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
