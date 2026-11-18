
<?php
if (isset($_POST['simpan'])) {
    $stmt = $pdo->prepare(
        "INSERT INTO tbl_varian (nama_varian, nilai_varian) VALUES (?, ?)"
    );
    $stmt->execute([
        trim($_POST['nama_varian']),
        trim($_POST['nilai_varian'])
    ]);

    header('Location: index.php?page=variants');
    exit;
}

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare(
        "UPDATE tbl_varian SET nama_varian=?, nilai_varian=? WHERE id_varian=?"
    );
    $stmt->execute([
        trim($_POST['nama_varian']),
        trim($_POST['nilai_varian']),
        (int)$_POST['id_varian']
    ]);

    header('Location: index.php?page=variants');
    exit;
}

if (isset($_POST['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM tbl_varian WHERE id_varian=?");
    $stmt->execute([(int)$_POST['id_varian']]);

    header('Location: index.php?page=variants');
    exit;
}

$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_varian WHERE id_varian=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$data = $pdo->query(
    "SELECT * FROM tbl_varian ORDER BY id_varian DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-4">
        <h4 class="fw-bold">Product Variants</h4>
        <p class="text-muted">Kelola variasi produk seperti warna dan ukuran.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Variasi' : 'Tambah Variasi' ?></h5>

            <form method="POST" action="index.php?page=variants">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_varian" value="<?= $edit['id_varian'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-5">
                        <label class="form-label">Nama Variasi</label>
                        <input name="nama_varian" class="form-control"
                               placeholder="Contoh: Warna"
                               value="<?= htmlspecialchars($edit['nama_varian'] ?? '') ?>" required>
                    </div>

                    <div class="col-md-5">
                        <label class="form-label">Nilai Variasi</label>
                        <input name="nilai_varian" class="form-control"
                               placeholder="Contoh: Merah, Biru, Hitam"
                               value="<?= htmlspecialchars($edit['nilai_varian'] ?? '') ?>" required>
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
                <a href="index.php?page=variants" class="btn btn-light mt-3">Batal</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Variasi</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Variasi</th>
                            <th>Nilai Variasi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_varian']) ?></td>
                                <td><?= htmlspecialchars($row['nilai_varian']) ?></td>
                                <td>
                                    <a href="index.php?page=variants&edit=<?= $row['id_varian'] ?>"
                                       class="btn btn-sm btn-light text-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="index.php?page=variants"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus variasi ini?')">
                                        <input type="hidden" name="id_varian" value="<?= $row['id_varian'] ?>">
                                        <button name="hapus" class="btn btn-sm btn-light text-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="4" class="text-center py-4">Belum ada variasi.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
