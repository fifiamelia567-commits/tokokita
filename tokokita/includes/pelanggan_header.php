<?php
if (!isset($pageTitle)) {
    $pageTitle = 'Pelanggan';
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= htmlspecialchars($pageTitle) ?> - TOKOKITA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .pelanggan-topbar { background: #fff; border-bottom: 1px solid #edf0f5; height: 70px; }
        .pelanggan-nav a { color: #344054; text-decoration: none; font-weight: 600; }
        .pelanggan-nav a.active, .pelanggan-nav a:hover { color: #f28b39; }
        .pelanggan-main { min-height: calc(100vh - 130px); background: #f8fafc; }
    </style>
</head>
<body>
<header class="pelanggan-topbar d-flex align-items-center">
    <div class="container-fluid px-4 d-flex align-items-center justify-content-between">
        <a href="index.php?page=pelanggan_dashboard" class="brand fw-bold text-dark text-decoration-none">
            TOKO            <span style="color:#f5822b">KITA</span>
        </a>
        <nav class="pelanggan-nav d-flex gap-3 d-none d-md-flex">
            <a href="index.php?page=pelanggan_dashboard">Beranda</a>
            <a href="index.php?page=pelanggan_produk">Produk</a>
            <a href="index.php?page=pelanggan_keranjang">Keranjang</a>
            <a href="index.php?page=pelanggan_pesanan">Pesanan</a>
        </nav>
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                <?= htmlspecialchars($_SESSION['nama'] ?? 'Pelanggan') ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item" href="index.php?page=pelanggan_profil">Profil</a></li>
                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
            </ul>
        </div>
        <a class="btn btn-light ms-2" href="logout.php">Logout</a>
    </div>
</header>
<main class="pelanggan-main">
