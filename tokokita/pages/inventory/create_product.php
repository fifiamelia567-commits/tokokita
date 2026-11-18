
<?php

$id = (int)($_GET['id'] ?? 0);

$product = [
    'kode_produk' => '',
    'nama_produk' => '',
    'kategori' => '',
    'harga_beli' => 0,
    'harga_jual' => 0,
    'stok' => 0,
    'stok_minimal' => 5
];

if ($id > 0) {
    $stmt = $pdo->prepare("
        SELECT * FROM tbl_produk
        WHERE id_produk = ?
    ");
    $stmt->execute([$id]);

    $data = $stmt->fetch();

    if ($data) {
        $product = $data;
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $kode = trim($_POST['kode_produk'] ?? '');
    $nama = trim($_POST['nama_produk'] ?? '');
    $kategori = trim($_POST['kategori'] ?? '');
    $hargaBeli = (float)($_POST['harga_beli'] ?? 0);
    $hargaJual = (float)($_POST['harga_jual'] ?? 0);
    $stok = (int)($_POST['stok'] ?? 0);
    $minimal = (int)($_POST['stok_minimal'] ?? 5);

    if ($kode === '' || $nama === '' || $hargaJual <= 0) {
        $error = 'Kode, nama, dan harga jual wajib diisi.';
    } else {

        try {

            if ($id > 0) {

                $stmt = $pdo->prepare("
                    UPDATE tbl_produk SET
                    kode_produk = ?,
                    nama_produk = ?,
                    kategori = ?,
                    harga_beli = ?,
                    harga_jual = ?,
                    stok = ?,
                    stok_minimal = ?
                    WHERE id_produk = ?
                ");

                $stmt->execute([
                    $kode,
                    $nama,
                    $kategori,
                    $hargaBeli,
                    $hargaJual,
                    $stok,
                    $minimal,
                    $id
                ]);

            } else {

                $stmt = $pdo->prepare("
                    INSERT INTO tbl_produk
                    (kode_produk, nama_produk, kategori,
                    harga_beli, harga_jual, stok, stok_minimal)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $kode,
                    $nama,
                    $kategori,
                    $hargaBeli,
                    $hargaJual,
                    $stok,
                    $minimal
                ]);
            }

            $pesan = $id > 0 ? 'edit' : 'tambah';
            header('Location: index.php?page=products&pesan=' . $pesan);
            exit;

        } catch (PDOException $e) {
            if (($e->errorInfo[1] ?? null) === 1062) {
                $error = 'Kode produk sudah digunakan.';
            } else {
                throw $e;
            }
        }
    }
}
?>

<div class="page-heading">
    <div>
        <h1><?= $id ? 'Edit Product' : 'Create Product' ?></h1>
        <p>Manage your product information.</p>
    </div>

    <a href="index.php?page=products" class="btn btn-light border">
        <i class="fa-solid fa-arrow-left"></i>
        Back
    </a>
</div>

<div class="card">

    <div class="card-title">Product Information</div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <?= e($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST">

        <div class="row g-3">

            <div class="col-md-6">
                <label class="form-label">Product Code *</label>
                <input type="text"
                       name="kode_produk"
                       class="form-control"
                       value="<?= e($_POST['kode_produk'] ?? $product['kode_produk']) ?>"
                       placeholder="PRD001"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Product Name *</label>
                <input type="text"
                       name="nama_produk"
                       class="form-control"
                       value="<?= e($_POST['nama_produk'] ?? $product['nama_produk']) ?>"
                       placeholder="Enter product name"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Category</label>
                <input type="text"
                       name="kategori"
                       class="form-control"
                       value="<?= e($_POST['kategori'] ?? $product['kategori']) ?>"
                       placeholder="Example: Electronics">
            </div>

            <div class="col-md-6">
                <label class="form-label">Purchase Price</label>
                <input type="number"
                       name="harga_beli"
                       class="form-control"
                       min="0"
                       value="<?= e($_POST['harga_beli'] ?? $product['harga_beli']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Selling Price *</label>
                <input type="number"
                       name="harga_jual"
                       class="form-control"
                       min="1"
                       value="<?= e($_POST['harga_jual'] ?? $product['harga_jual']) ?>"
                       required>
            </div>

            <div class="col-md-6">
                <label class="form-label">Stock</label>
                <input type="number"
                       name="stok"
                       class="form-control"
                       min="0"
                       value="<?= e($_POST['stok'] ?? $product['stok']) ?>">
            </div>

            <div class="col-md-6">
                <label class="form-label">Minimum Stock</label>
                <input type="number"
                       name="stok_minimal"
                       class="form-control"
                       min="0"
                       value="<?= e($_POST['stok_minimal'] ?? $product['stok_minimal']) ?>">
            </div>

        </div>

        <hr class="my-4">

        <div class="d-flex gap-2">

            <button type="submit" class="btn-add border-0">
                <i class="fa-solid fa-save"></i>
                Save Product
            </button>

            <a href="index.php?page=products"
               class="btn btn-light border">
                Cancel
            </a>

        </div>

    </form>

</div>
