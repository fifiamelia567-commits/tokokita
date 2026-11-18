
<?php
if (isset($_POST['simpan'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_satuan (nama_satuan, singkatan) VALUES (?, ?)"
    );
    $stmt->execute([
        trim($_POST['nama_satuan']),
        trim($_POST['singkatan'])
    ]);

    header('Location: index.php?page=units');
    exit;
}

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare(
        "UPDATE tbl_satuan SET nama_satuan=?, singkatan=? WHERE id_satuan=?"
    );
    $stmt->execute([
        trim($_POST['nama_satuan']),
        trim($_POST['singkatan']),
        (int)$_POST['id_satuan']
    ]);

    header('Location: index.php?page=units');
    exit;
}

if (isset($_POST['hapus'])) {
    $stmt = $pdo->prepare(
        "DELETE FROM tbl_satuan WHERE id_satuan=?"
    );
    $stmt->execute([(int)$_POST['id_satuan']]);

    header('Location: index.php?page=units');
    exit;
}

$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_satuan WHERE id_satuan=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$data = $pdo->query(
    "SELECT * FROM tbl_satuan ORDER BY id_satuan DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$total = count($data);
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-4">
        <h4 class="fw-bold">Unit Management</h4>
        <p class="text-muted">Kelola satuan produk toko.</p>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <p class="text-muted mb-1">Total Satuan</p>
                <h3 class="fw-bold"><?= $total ?></h3>
                <i class="fas fa-ruler text-warning fa-2x"></i>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Satuan' : 'Tambah Satuan' ?></h5>

            <form method="POST" action="index.php?page=units">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_satuan" value="<?= $edit['id_satuan'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nama Satuan</label>
                        <input name="nama_satuan" class="form-control"
                               placeholder="Contoh: Kilogram"
                               value="<?= htmlspecialchars($edit['nama_satuan'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Singkatan</label>
                        <input name="singkatan" class="form-control"
                               placeholder="Contoh: Kg"
                               value="<?= htmlspecialchars($edit['singkatan'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-2 d-flex align-items-end">
                        <button name="<?= $edit ? 'update' : 'simpan' ?>"
                                class="btn text-white w-100"
                                style="background:#f5822b">
                            <?= $edit ? 'Update' : 'Tambah' ?>
                        </button>
                    </div>
                </div>
            </form>
            <?php if ($edit): ?>
                <a href="index.php?page=units" class="btn btn-light mt-3">Batal</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Satuan Produk</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Satuan</th>
                            <th>Singkatan</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_satuan']) ?></td>
                                <td><span class="badge bg-warning text-dark"><?= htmlspecialchars($row['singkatan']) ?></span></td>
                                <td>
                                    <a href="index.php?page=units&edit=<?= $row['id_satuan'] ?>"
                                       class="btn btn-sm btn-light text-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="index.php?page=units"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus satuan?')">
                                        <input type="hidden" name="id_satuan" value="<?= $row['id_satuan'] ?>">
                                        <button name="hapus" class="btn btn-sm btn-light text-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4">Belum ada satuan.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
