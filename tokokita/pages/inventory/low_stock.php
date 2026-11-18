
<?php

$stmt = $pdo->query("
    SELECT * FROM tbl_produk
    WHERE stok <= stok_minimal
    ORDER BY stok ASC
");

$products = $stmt->fetchAll();

$total = count($products);
?>

<div class="page-heading">
    <div>
        <h1>Low Stock Products</h1>
        <p>Products that need stock replenishment.</p>
    </div>

    <a href="index.php?page=products" class="btn-add">
        <i class="fa-solid fa-box"></i>
        All Products
    </a>
</div>

<div class="card">

    <div class="d-flex justify-content-between align-items-center mb-3">
        <div class="card-title mb-0">Stock Alert List</div>

        <span class="badge text-bg-danger">
            <?= $total ?> Products Need Attention
        </span>
    </div>

    <div class="table-wrap">

        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Product Code</th>
                    <th>Product Name</th>
                    <th>Category</th>
                    <th>Current Stock</th>
                    <th>Minimum Stock</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>

            <tbody>

            <?php if ($products): ?>

                <?php foreach ($products as $i => $p): ?>

                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= e($p['kode_produk']) ?></td>
                    <td><strong><?= e($p['nama_produk']) ?></strong></td>
                    <td><?= e($p['kategori']) ?></td>

                    <td>
                        <strong class="text-danger">
                            <?= $p['stok'] ?>
                        </strong>
                    </td>

                    <td><?= $p['stok_minimal'] ?></td>

                    <td>
                        <span class="badge text-bg-danger">
                            Low Stock
                        </span>
                    </td>

                    <td>
                        <a href="index.php?page=create_product&id=<?= $p['id_produk'] ?>"
                           class="btn btn-sm btn-outline-warning">
                            <i class="fa-solid fa-pen"></i>
                            Update
                        </a>
                    </td>
                </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="8" class="text-center py-4">
                        <i class="fa-solid fa-circle-check text-success"></i>
                        Semua stok produk dalam kondisi aman.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>
        </table>

    </div>

</div>
