
<?php
// CATEGORY TOKOKITA

$pesan = '';
$tipePesan = 'success';

// Tambah kategori
if (isset($_POST['tambah_kategori'])) {
    $nama = trim($_POST['nama_kategori'] ?? '');
    $ikon = trim($_POST['ikon'] ?? 'bi-tag');

    if ($nama !== '') {
        $cek = $pdo->prepare(
            "SELECT COUNT(*) FROM tbl_kategori WHERE nama_kategori = ?"
        );
        $cek->execute([$nama]);

        if ($cek->fetchColumn() > 0) {
            $pesan = 'Nama kategori sudah tersedia!';
            $tipePesan = 'danger';
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO tbl_kategori (nama_kategori, ikon)
                 VALUES (?, ?)"
            );
            $stmt->execute([$nama, $ikon]);

            echo "<script>
                window.location.href='index.php?page=category&pesan=tambah';
            </script>";
            exit;
        }
    }
}

// Edit kategori
if (isset($_POST['edit_kategori'])) {
    $id = (int) $_POST['id_kategori'];
    $nama = trim($_POST['nama_kategori'] ?? '');
    $ikon = trim($_POST['ikon'] ?? 'bi-tag');

    if ($nama !== '') {
        $cek = $pdo->prepare(
            "SELECT COUNT(*) FROM tbl_kategori
             WHERE nama_kategori = ? AND id_kategori != ?"
        );
        $cek->execute([$nama, $id]);

        if ($cek->fetchColumn() > 0) {
            $pesan = 'Nama kategori sudah digunakan!';
            $tipePesan = 'danger';
        } else {
            $stmt = $pdo->prepare(
                "UPDATE tbl_kategori
                 SET nama_kategori = ?, ikon = ?
                 WHERE id_kategori = ?"
            );
            $stmt->execute([$nama, $ikon, $id]);

            echo "<script>
                window.location.href='index.php?page=category&pesan=edit';
            </script>";
            exit;
        }
    }
}

// Hapus kategori
if (isset($_POST['hapus_kategori'])) {
    $id = (int) $_POST['id_kategori'];

    $stmt = $pdo->prepare(
        "DELETE FROM tbl_kategori WHERE id_kategori = ?"
    );
    $stmt->execute([$id]);

    echo "<script>
        window.location.href='index.php?page=category&pesan=hapus';
    </script>";
    exit;
}

// Notifikasi
if (isset($_GET['pesan'])) {
    $notifikasi = [
        'tambah' => 'Kategori berhasil ditambahkan!',
        'edit'   => 'Kategori berhasil diperbarui!',
        'hapus'  => 'Kategori berhasil dihapus!'
    ];

    $pesan = $notifikasi[$_GET['pesan']] ?? '';
}

// Pencarian
$search = trim($_GET['search'] ?? '');

$sql = "
    SELECT
        k.id_kategori,
        k.nama_kategori,
        k.ikon,
        COUNT(p.id_produk) AS jumlah_produk
    FROM tbl_kategori k
    LEFT JOIN tbl_produk p
        ON k.id_kategori = p.id_kategori
";

$params = [];

if ($search !== '') {
    $sql .= " WHERE k.nama_kategori LIKE ?";
    $params[] = "%$search%";
}

$sql .= "
    GROUP BY k.id_kategori, k.nama_kategori, k.ikon
    ORDER BY k.id_kategori DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistik
$totalKategori = $pdo->query(
    "SELECT COUNT(*) FROM tbl_kategori"
)->fetchColumn();

$totalProduk = $pdo->query(
    "SELECT COUNT(*) FROM tbl_produk"
)->fetchColumn();

$totalTanpaKategori = $pdo->query(
    "SELECT COUNT(*) FROM tbl_produk WHERE id_kategori IS NULL"
)->fetchColumn();

// Data kategori yang diedit
$editData = null;

if (isset($_GET['edit'])) {
    $stmt = $pdo->prepare(
        "SELECT * FROM tbl_kategori WHERE id_kategori = ?"
    );
    $stmt->execute([(int) $_GET['edit']]);
    $editData = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<div class="container-fluid px-4">

    <!-- HEADER -->
    <div class="page-heading mt-3 mb-4">
        <div>
            <h4 class="fw-bold mb-1">Kategori Produk</h4>
            <p class="text-muted mb-0">
                Kelola dan kelompokkan produk berdasarkan kategori.
            </p>
        </div>
    </div>

    <!-- ALERT -->
    <?php if ($pesan): ?>
        <div class="alert alert-<?= $tipePesan ?> alert-dismissible fade show">
            <?= htmlspecialchars($pesan) ?>
            <button type="button" class="btn-close"
                    data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (isset($_GET['pesan'])): ?>
        <script>
            if (window.history.replaceState) {
                window.history.replaceState(
                    null, '', 'index.php?page=category'
                );
            }
        </script>
    <?php endif; ?>

    <!-- STATISTIK -->
    <div class="row g-3 mb-4">

        <div class="col-md-4">
            <div class="metric-card">
                <div>
                    <p>Total Kategori</p>
                    <h3><?= number_format($totalKategori) ?></h3>
                    <small>Kategori terdaftar</small>
                </div>
                <div class="metric-icon orange">
                    <i class="fas fa-layer-group"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card">
                <div>
                    <p>Total Produk</p>
                    <h3><?= number_format($totalProduk) ?></h3>
                    <small>Seluruh produk toko</small>
                </div>
                <div class="metric-icon blue">
                    <i class="fas fa-boxes"></i>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="metric-card">
                <div>
                    <p>Belum Berkategori</p>
                    <h3><?= number_format($totalTanpaKategori) ?></h3>
                    <small>Produk belum memiliki kategori</small>
                </div>
                <div class="metric-icon red">
                    <i class="fas fa-exclamation-circle"></i>
                </div>
            </div>
        </div>

    </div>

    <!-- FORM KATEGORI -->
    <div class="card border-0 shadow-sm rounded-4 mb-4">

        <div class="card-body p-4">

            <h5 class="fw-bold mb-1">
                <?= $editData ? 'Edit Kategori' : 'Tambah Kategori Baru' ?>
            </h5>

            <p class="text-muted small mb-4">
                Isi informasi kategori produk di bawah ini.
            </p>

            <form method="POST" action="index.php?page=category">

                <?php if ($editData): ?>
                    <input type="hidden" name="id_kategori"
                           value="<?= $editData['id_kategori'] ?>">
                <?php endif; ?>

                <div class="row g-3">

                    <div class="col-md-6">
                        <label class="form-label fw-semibold">
                            Nama Kategori
                        </label>

                        <input type="text"
                               name="nama_kategori"
                               class="form-control"
                               placeholder="Contoh: Makanan & Minuman"
                               value="<?= htmlspecialchars($editData['nama_kategori'] ?? '') ?>"
                               required>
                    </div>

                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            Nama Ikon Bootstrap
                        </label>

                        <input type="text"
                               name="ikon"
                               class="form-control"
                               placeholder="Contoh: bi-basket2-fill"
                               value="<?= htmlspecialchars($editData['ikon'] ?? 'bi-tag') ?>">
                    </div>

                    <div class="col-md-2 d-flex align-items-end">

                        <?php if ($editData): ?>

                            <button type="submit"
                                    name="edit_kategori"
                                    class="btn text-white w-100"
                                    style="background:#f5822b">
                                <i class="fas fa-save me-1"></i>
                                Simpan
                            </button>

                        <?php else: ?>

                            <button type="submit"
                                    name="tambah_kategori"
                                    class="btn text-white w-100"
                                    style="background:#f5822b">
                                <i class="fas fa-plus me-1"></i>
                                Tambah
                            </button>

                        <?php endif; ?>

                    </div>

                </div>

                <?php if ($editData): ?>
                    <a href="index.php?page=category"
                       class="btn btn-light mt-3">
                        Batal Edit
                    </a>
                <?php endif; ?>

            </form>

        </div>
    </div>

    <!-- TABEL KATEGORI -->
    <div class="card border-0 shadow-sm rounded-4">

        <div class="card-body p-4">

            <div class="d-flex justify-content-between
                        align-items-center flex-wrap gap-3 mb-4">

                <div>
                    <h5 class="fw-bold mb-1">Daftar Kategori</h5>
                    <p class="text-muted small mb-0">
                        Semua kategori yang tersedia di TOKOKITA.
                    </p>
                </div>

                <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                    <?= count($categories) ?> Kategori
                </span>

            </div>

            <!-- SEARCH -->
            <form method="GET" action="index.php" class="row g-2 mb-4">

                <input type="hidden" name="page" value="category">

                <div class="col-md-5">
                    <input type="text"
                           name="search"
                           class="form-control"
                           placeholder="Cari kategori..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-2">
                    <button class="btn text-white w-100"
                            style="background:#f5822b">
                        <i class="fas fa-search me-1"></i>
                        Cari
                    </button>
                </div>

                <div class="col-md-2">
                    <a href="index.php?page=category"
                       class="btn btn-outline-secondary w-100">
                        Reset
                    </a>
                </div>

            </form>

            <div class="table-responsive">
                <table class="table align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Ikon</th>
                            <th>Nama Kategori</th>
                            <th>Jumlah Produk</th>
                            <th>ID Kategori</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>

                    <tbody>

                    <?php if ($categories): ?>

                        <?php $no = 1; ?>

                        <?php foreach ($categories as $category): ?>

                            <tr>

                                <td><?= $no++ ?></td>

                                <td>
                                    <div class="rounded-3 bg-warning bg-opacity-10
                                                d-flex align-items-center
                                                justify-content-center"
                                         style="width:42px;height:42px">

                                        <i class="bi <?= htmlspecialchars($category['ikon']) ?>"
                                           style="font-size:20px;color:#f5822b">
                                        </i>

                                    </div>
                                </td>

                                <td>
                                    <span class="fw-semibold">
                                        <?= htmlspecialchars($category['nama_kategori']) ?>
                                    </span>
                                </td>

                                <td>
                                    <span class="badge bg-primary bg-opacity-10
                                                 text-primary rounded-pill px-3 py-2">
                                        <?= number_format($category['jumlah_produk']) ?>
                                        Produk
                                    </span>
                                </td>

                                <td>
                                    <span class="text-muted">
                                        #CAT-<?= str_pad($category['id_kategori'], 3, '0', STR_PAD_LEFT) ?>
                                    </span>
                                </td>

                                <td class="text-center">

                                    <a href="index.php?page=category&edit=<?= $category['id_kategori'] ?>"
                                       class="btn btn-sm btn-light text-primary"
                                       title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>

                                    <form method="POST"
                                          action="index.php?page=category"
                                          class="d-inline"
                                          onsubmit="return confirm('Yakin ingin menghapus kategori ini?')">

                                        <input type="hidden"
                                               name="id_kategori"
                                               value="<?= $category['id_kategori'] ?>">

                                        <button type="submit"
                                                name="hapus_kategori"
                                                class="btn btn-sm btn-light text-danger"
                                                title="Hapus">
                                            <i class="fas fa-trash"></i>
                                        </button>

                                    </form>

                                </td>

                            </tr>

                        <?php endforeach; ?>

                    <?php else: ?>

                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <i class="fas fa-folder-open fa-3x text-muted mb-3"></i>
                                <h6 class="fw-bold">Kategori Tidak Ditemukan</h6>
                                <p class="text-muted mb-0">
                                    Belum ada kategori yang sesuai pencarian.
                                </p>
                            </td>
                        </tr>

                    <?php endif; ?>

                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>
