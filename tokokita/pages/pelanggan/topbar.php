
<?php
$namaPelanggan = $_SESSION['nama']
    ?? $_SESSION['username']
    ?? 'Pelanggan';

$inisial = strtoupper(substr($namaPelanggan, 0, 1));
$jumlahKeranjang = array_sum($_SESSION['keranjang'] ?? []);
?>

<header class="topbar">

    <div class="topbar-left">

        <button
            type="button"
            class="sidebar-toggle"
            id="sidebarToggle"
        >
            <i class="fas fa-bars"></i>
        </button>

        <div class="global-search">
            <i class="fas fa-search"></i>

            <form action="index.php" method="GET" style="width:100%">
                <input
                    type="hidden"
                    name="page"
                    value="produk"
                >
                <input
                    type="text"
                    name="cari"
                    placeholder="Cari produk..."
                >
            </form>
        </div>

    </div>

    <div class="topbar-right">

        <a href="index.php?page=keranjang" class="top-icon">
            <i class="fas fa-shopping-cart"></i>

            <?php if ($jumlahKeranjang > 0): ?>
                <span class="notification-dot"></span>
            <?php endif; ?>
        </a>

        <a href="index.php?page=profil" class="profile-avatar">
            <?= htmlspecialchars($inisial) ?>
        </a>

        <div style="font-size:10px">
            <strong><?= htmlspecialchars($namaPelanggan) ?></strong>
            <div style="font-size:9px;color:#9199a5">
                Pelanggan
            </div>
        </div>

    </div>

</header>
