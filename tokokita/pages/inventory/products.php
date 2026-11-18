
<?php

$cari = trim($_GET['cari'] ?? '');
$kategori = trim($_GET['kategori'] ?? '');

$errorHapus = '';
$pesan = $_GET['pesan'] ?? '';

// ======================================
// HAPUS PRODUK
// ======================================

if (isset($_GET['hapus'])) {

    $id = (int) $_GET['hapus'];

    try {

        $stmt = $pdo->prepare("
            DELETE FROM tbl_produk
            WHERE id_produk = ?
        ");

        $stmt->execute([$id]);

        echo "<script>
            window.location.replace('index.php?page=products&pesan=hapus');
        </script>";

        exit;

    } catch (PDOException $e) {

        $errorHapus = 'Produk tidak dapat dihapus karena sudah digunakan dalam transaksi.';

    }
}

// ======================================
// QUERY DATA PRODUK
// ======================================

$sql = "
    SELECT *
    FROM tbl_produk
    WHERE 1=1
";

$params = [];

if ($cari !== '') {

    $sql .= "
        AND (
            nama_produk LIKE ?
            OR kode_produk LIKE ?
        )
    ";

    $params[] = "%$cari%";
    $params[] = "%$cari%";

}

if ($kategori !== '') {

    $sql .= " AND kategori = ?";
    $params[] = $kategori;

}

$sql .= " ORDER BY id_produk DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$products = $stmt->fetchAll(PDO::FETCH_ASSOC);

// ======================================
// DATA KATEGORI
// ======================================

$stmtKategori = $pdo->query("
    SELECT DISTINCT kategori
    FROM tbl_produk
    WHERE kategori IS NOT NULL
    AND kategori <> ''
    ORDER BY kategori ASC
");

$categories = $stmtKategori->fetchAll(PDO::FETCH_COLUMN);

// ======================================
// STATISTIK PRODUK
// ======================================

$totalProduk = $pdo->query("
    SELECT COUNT(*)
    FROM tbl_produk
")->fetchColumn();

$totalStok = $pdo->query("
    SELECT COALESCE(SUM(stok), 0)
    FROM tbl_produk
")->fetchColumn();

$stokMenipis = $pdo->query("
    SELECT COUNT(*)
    FROM tbl_produk
    WHERE stok <= stok_minimal
")->fetchColumn();

?>

<!-- ======================================
     HEADER HALAMAN
====================================== -->

<div class="page-heading">

    <div>
        <h1>Products</h1>
        <p>Manage your products and inventory.</p>
    </div>

    <a href="index.php?page=create_product" class="btn-add">
        <i class="fa-solid fa-plus"></i>
        Add Product
    </a>

</div>

<!-- ======================================
     NOTIFIKASI
====================================== -->

<?php if ($pesan === 'hapus'): ?>

    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i>
        Produk berhasil dihapus.
    </div>

<?php elseif ($pesan === 'tambah'): ?>

    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i>
        Produk berhasil ditambahkan.
    </div>

<?php elseif ($pesan === 'edit'): ?>

    <div class="alert alert-success">
        <i class="fa-solid fa-check-circle"></i>
        Produk berhasil diperbarui.
    </div>

<?php endif; ?>

<?php if ($errorHapus !== ''): ?>

    <div class="alert alert-danger">
        <i class="fa-solid fa-circle-exclamation"></i>
        <?= e($errorHapus) ?>
    </div>

<?php endif; ?>

<!-- ======================================
     STATISTIK
====================================== -->

<div class="secondary-grid">

    <div class="metric-card">

        <div class="metric-value">
            <?= number_format($totalProduk) ?>
        </div>

        <div class="metric-label">
            Total Products
        </div>

    </div>

    <div class="metric-card">

        <div class="metric-value">
            <?= number_format($totalStok) ?>
        </div>

        <div class="metric-label">
            Total Stock
        </div>

    </div>

    <div class="metric-card">

        <div class="metric-value">
            <?= number_format($stokMenipis) ?>
        </div>

        <div class="metric-label">
            Low Stock Products
        </div>

    </div>

    <div class="metric-card">

        <div class="metric-value">
            <?= number_format(count($categories)) ?>
        </div>

        <div class="metric-label">
            Categories
        </div>

    </div>

</div>

<!-- ======================================
     DAFTAR PRODUK
====================================== -->

<div class="card">

    <div class="card-title">
        Product List
    </div>

    <!-- PENCARIAN DAN FILTER -->

    <form method="GET" class="row g-2 mb-4">

        <input
            type="hidden"
            name="page"
            value="products"
        >

        <div class="col-md-5">

            <input
                type="text"
                name="cari"
                class="form-control"
                placeholder="Search product name or code..."
                value="<?= e($cari) ?>"
            >

        </div>

        <div class="col-md-3">

            <select name="kategori" class="form-select">

                <option value="">
                    All Categories
                </option>

                <?php foreach ($categories as $cat): ?>

                    <option
                        value="<?= e($cat) ?>"
                        <?= $kategori === $cat ? 'selected' : '' ?>
                    >
                        <?= e($cat) ?>
                    </option>

                <?php endforeach; ?>

            </select>

        </div>

        <div class="col-md-2">

            <button
                type="submit"
                class="btn-primary-custom w-100"
            >
                <i class="fa-solid fa-search"></i>
                Search
            </button>

        </div>

        <div class="col-md-2">

            <a
                href="index.php?page=products"
                class="btn btn-light border w-100"
            >
                Reset
            </a>

        </div>

    </form>

    <!-- TABEL PRODUK -->

    <div class="table-wrap">

        <table>

            <thead>

                <tr>
                    <th>No</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Purchase Price</th>
                    <th>Selling Price</th>
                    <th>Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>

            </thead>

            <tbody>

                <?php if (!empty($products)): ?>

                    <?php foreach ($products as $i => $p): ?>

                        <tr>

                            <td>
                                <?= $i + 1 ?>
                            </td>

                            <td>
                                <strong>
                                    <?= e($p['kode_produk']) ?>
                                </strong>
                            </td>

                            <td>
                                <?= e($p['nama_produk']) ?>
                            </td>

                            <td>

                                <span class="badge text-bg-light">
                                    <?= e($p['kategori'] ?? 'Umum') ?>
                                </span>

                            </td>

                            <td>
                                <?= rupiah($p['harga_beli']) ?>
                            </td>

                            <td>
                                <?= rupiah($p['harga_jual']) ?>
                            </td>

                            <td>

                                <strong>
                                    <?= number_format($p['stok']) ?>
                                </strong>

                                <?= e($p['satuan'] ?? 'pcs') ?>

                            </td>

                            <td>

                                <?php if ($p['stok'] <= $p['stok_minimal']): ?>

                                    <span class="badge text-bg-danger">
                                        Low Stock
                                    </span>

                                <?php else: ?>

                                    <span class="badge text-bg-success">
                                        Available
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>

                                <div class="d-flex gap-1">

                                    <!-- EDIT -->

                                    <a
                                        href="index.php?page=create_product&id=<?= (int)$p['id_produk'] ?>"
                                        class="btn btn-sm btn-outline-primary"
                                        title="Edit Product"
                                    >
                                        <i class="fa-solid fa-pen"></i>
                                    </a>

                                    <!-- HAPUS -->

                                    <a
                                        href="index.php?page=products&hapus=<?= (int)$p['id_produk'] ?>"
                                        onclick="return confirm('Yakin ingin menghapus produk ini?')"
                                        class="btn btn-sm btn-outline-danger"
                                        title="Delete Product"
                                    >
                                        <i class="fa-solid fa-trash"></i>
                                    </a>

                                </div>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>

                        <td colspan="9" class="text-center py-4">

                            <i class="fa-solid fa-box-open"></i>

                            <p class="mb-0 mt-2">
                                Produk tidak ditemukan.
                            </p>

                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <!-- JUMLAH DATA -->

    <div class="text-muted small mt-3">

        Menampilkan
        <?= count($products) ?>
        produk.

    </div>

</div>
