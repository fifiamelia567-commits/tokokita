
<?php
$currentPage = $_GET['page'] ?? 'dashboard';

$menuPelanggan = [
    [
        'judul' => 'MENU UTAMA',
        'items' => [
            ['page' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'fa-home'],
            ['page' => 'produk', 'label' => 'Belanja Produk', 'icon' => 'fa-shopping-bag'],
            ['page' => 'keranjang', 'label' => 'Keranjang', 'icon' => 'fa-shopping-cart'],
            ['page' => 'pesanan', 'label' => 'Riwayat Pesanan', 'icon' => 'fa-receipt']
        ]
    ],
    [
        'judul' => 'AKUN',
        'items' => [
            ['page' => 'profil', 'label' => 'Profil Saya', 'icon' => 'fa-user']
        ]
    ]
];
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-symbol">
            <i class="fas fa-store"></i>
        </div>

        <div class="brand-text">
            <strong>TOKO<span>KITA</span></strong>
            <small>Customer Panel</small>
        </div>
    </div>

    <div class="sidebar-menu">

        <?php foreach ($menuPelanggan as $group): ?>

            <div class="sidebar-section">
                <?= $group['judul'] ?>
            </div>

            <?php foreach ($group['items'] as $item): ?>

                <a
                    href="index.php?page=<?= e($item['page']) ?>"
                    class="sidebar-link <?= $currentPage === $item['page'] ? 'active' : '' ?>"
                >
                    <i class="fas <?= e($item['icon']) ?>"></i>
                    <span><?= e($item['label']) ?></span>
                </a>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </div>

    <div class="sidebar-bottom">

        <div class="sidebar-version">
            <span class="online-dot"></span>
            TokoKita Customer v1.0
        </div>

        <a
            href="logout.php"
            class="sidebar-link"
            style="margin-top:12px;color:#d95032"
            onclick="return confirm('Apakah kamu yakin ingin keluar?')"
        >
            <i class="fas fa-sign-out-alt"></i>
            <span>Keluar</span>
        </a>

    </div>

</aside>
