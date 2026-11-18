
<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

$namaUser = $_SESSION['nama'] ?? 'Admin';
$roleUser = $_SESSION['role'] ?? 'admin';
$inisial = strtoupper(substr(trim($namaUser), 0, 1));

$cari = $_GET['cari'] ?? '';
?>

<header class="topbar">

    <!-- LEFT -->
    <div class="topbar-left">

        <button
            class="sidebar-toggle"
            id="sidebarToggle"
            type="button"
            aria-label="Toggle Sidebar"
        >
            <i class="fa-solid fa-bars"></i>
        </button>

        <form action="index.php" method="GET" class="global-search">
            <input type="hidden" name="page" value="products">

            <i class="fa-solid fa-magnifying-glass"></i>

            <input
                type="search"
                name="cari"
                placeholder="Cari produk..."
                value="<?= e($cari) ?>"
            >

            <span class="shortcut">Ctrl K</span>
        </form>

    </div>


    <!-- RIGHT -->
    <div class="topbar-right">

        <!-- STORE -->
        <div class="store-selector">
            <span class="store-icon">
                <i class="fa-solid fa-store"></i>
            </span>

            <span class="store-name">TOKOKITA</span>

            <i class="fa-solid fa-chevron-down store-arrow"></i>
        </div>


        <!-- ADD NEW -->
        <a href="index.php?page=create_product" class="btn-add">
            <i class="fa-solid fa-plus"></i>
            <span>Tambah Baru</span>
        </a>


        <!-- POS -->
        <a href="index.php?page=pos" class="btn-pos">
            <i class="fa-solid fa-cash-register"></i>
            <span>POS</span>
        </a>


        <!-- LANGUAGE -->
        <button type="button" class="top-icon" title="Bahasa">
            <i class="fa-solid fa-globe"></i>
        </button>


        <!-- NOTIFICATION -->
        <button
            type="button"
            class="top-icon notification-button"
            title="Notifikasi"
        >
            <i class="fa-regular fa-bell"></i>
            <span class="notification-dot"></span>
        </button>


        <!-- SETTINGS -->
        <a
            href="index.php?page=settings"
            class="top-icon"
            title="Pengaturan"
        >
            <i class="fa-solid fa-gear"></i>
        </a>


        <!-- PROFILE -->
        <div class="dropdown profile-dropdown">

            <button
                type="button"
                class="profile-dropdown-btn"
                data-bs-toggle="dropdown"
                data-bs-auto-close="true"
                aria-expanded="false"
            >

                <div class="profile-avatar">
                    <?= e($inisial) ?>
                </div>

                <div class="profile-info">
                    <strong><?= e($namaUser) ?></strong>
                    <small><?= e(ucfirst($roleUser)) ?></small>
                </div>

                <i class="fa-solid fa-chevron-down profile-arrow"></i>

            </button>


            <ul class="dropdown-menu dropdown-menu-end profile-menu shadow">

                <li class="dropdown-header">
                    <strong><?= e($namaUser) ?></strong>
                    <small><?= e(ucfirst($roleUser)) ?></small>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a class="dropdown-item"
                       href="index.php?page=settings">
                        <i class="fa-regular fa-user"></i>
                        My Profile
                    </a>
                </li>

                <li>
                    <a class="dropdown-item"
                       href="index.php?page=settings">
                        <i class="fa-solid fa-gear"></i>
                        Settings
                    </a>
                </li>

                <li><hr class="dropdown-divider"></li>

                <li>
                    <a
                        class="dropdown-item logout-item"
                        href="logout.php"
                        onclick="return confirm('Yakin ingin logout?')"
                    >
                        <i class="fa-solid fa-right-from-bracket"></i>
                        Logout
                    </a>
                </li>

            </ul>

        </div>

    </div>

</header>
