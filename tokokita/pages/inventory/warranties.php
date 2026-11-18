
<?php
if (isset($_POST['simpan'])) {
    $stmt = $pdo->prepare("
        INSERT INTO tbl_garansi
        (nama_garansi, durasi, satuan_durasi, deskripsi)
        VALUES (?, ?, ?, ?)
    ");

    $stmt->execute([
        trim($_POST['nama_garansi']),
        (int)$_POST['durasi'],
        $_POST['satuan_durasi'],
        trim($_POST['deskripsi'])
    ]);

    header('Location: index.php?page=warranties');
    exit;
}

if (isset($_POST['update'])) {
    $stmt = $pdo->prepare("
        UPDATE tbl_garansi
        SET nama_garansi=?, durasi=?, satuan_durasi=?, deskripsi=?
        WHERE id_garansi=?
    ");

    $stmt->execute([
        trim($_POST['nama_garansi']),
        (int)$_POST['durasi'],
        $_POST['satuan_durasi'],
        trim($_POST['deskripsi']),
        (int)$_POST['id_garansi']
    ]);

    header('Location: index.php?page=warranties');
    exit;
}

if (isset($_POST['hapus'])) {
    $stmt = $pdo->prepare("DELETE FROM tbl_garansi WHERE id_garansi=?");
    $stmt->execute([(int)$_POST['id_garansi']]);

    header('Location: index.php?page=warranties');
    exit;
}

$edit = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare("SELECT * FROM tbl_garansi WHERE id_garansi=?");
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch(PDO::FETCH_ASSOC);
}

$data = $pdo->query(
    "SELECT * FROM tbl_garansi ORDER BY id_garansi DESC"
)->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-4">
        <h4 class="fw-bold">Warranty Management</h4>
        <p class="text-muted">Kelola informasi garansi produk.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3"><?= $edit ? 'Edit Garansi' : 'Tambah Garansi' ?></h5>

            <form method="POST" action="index.php?page=warranties">
                <?php if ($edit): ?>
                    <input type="hidden" name="id_garansi" value="<?= $edit['id_garansi'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Nama Garansi</label>
                        <input name="nama_garansi" class="form-control"
                               value="<?= htmlspecialchars($edit['nama_garansi'] ?? '') ?>"
                               placeholder="Garansi Resmi" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Durasi</label>
                        <input type="number" name="durasi" min="0"
                               class="form-control"
                               value="<?= htmlspecialchars($edit['durasi'] ?? '12') ?>" required>
                    </div>

                    <div class="col-md-2">
                        <label class="form-label">Satuan</label>
                        <select name="satuan_durasi" class="form-select">
                            <option value="bulan" <?= ($edit['satuan_durasi'] ?? '') === 'bulan' ? 'selected' : '' ?>>Bulan</option>
                            <option value="tahun" <?= ($edit['satuan_durasi'] ?? '') === 'tahun' ? 'selected' : '' ?>>Tahun</option>
                            <option value="hari" <?= ($edit['satuan_durasi'] ?? '') === 'hari' ? 'selected' : '' ?>>Hari</option>
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Deskripsi</label>
                        <input name="deskripsi" class="form-control"
                               value="<?= htmlspecialchars($edit['deskripsi'] ?? '') ?>"
                               placeholder="Keterangan garansi">
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
                <a href="index.php?page=warranties" class="btn btn-light mt-3">Batal</a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Garansi</h5>

            <div class="table-responsive">
                <table class="table align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama Garansi</th>
                            <th>Durasi</th>
                            <th>Deskripsi</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($data): ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td class="fw-semibold"><?= htmlspecialchars($row['nama_garansi']) ?></td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <?= $row['durasi'] . ' ' . htmlspecialchars($row['satuan_durasi']) ?>
                                    </span>
                                </td>
                                <td><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                                <td>
                                    <a href="index.php?page=warranties&edit=<?= $row['id_garansi'] ?>"
                                       class="btn btn-sm btn-light text-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <form method="POST" action="index.php?page=warranties"
                                          class="d-inline"
                                          onsubmit="return confirm('Hapus garansi?')">
                                        <input type="hidden" name="id_garansi" value="<?= $row['id_garansi'] ?>">
                                        <button name="hapus" class="btn btn-sm btn-light text-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="5" class="text-center py-4">Belum ada data garansi.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
