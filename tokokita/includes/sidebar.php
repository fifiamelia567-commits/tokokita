
<?php
$currentPage = $_GET['page'] ?? 'dashboard';

$groups = [
    'Main' => [
        ['dashboard', 'Dashboard', 'fa-house'],
        ['superadmin', 'Super Admin', 'fa-user-shield']
    ],
    'Inventory' => [
        ['products', 'Products', 'fa-box'],
        ['create_product', 'Create Product', 'fa-circle-plus'],
        ['expired', 'Expired Products', 'fa-calendar-xmark'],
        ['low_stock', 'Low Stocks', 'fa-chart-line'],
        ['category', 'Category', 'fa-layer-group'],
        ['subcategory', 'Sub Category', 'fa-list'],
        ['brands', 'Brands', 'fa-tags'],
        ['units', 'Units', 'fa-ruler'],
        ['variants', 'Variant Attributes', 'fa-sliders'],
        ['warranties', 'Warranties', 'fa-shield-halved'],
        ['barcode', 'Print Barcode', 'fa-barcode'],
        ['qrcode', 'Print QR Code', 'fa-qrcode']
    ],
    'Stock' => [
        ['manage_stock', 'Manage Stock', 'fa-boxes-stacked'],
        ['adjustment', 'Stock Adjustment', 'fa-arrows-rotate'],
        ['transfer', 'Stock Transfer', 'fa-truck-fast']
    ],
    'Sales' => [
        ['pos', 'POS / Kasir', 'fa-cash-register'],
        ['sales', 'Sales List', 'fa-receipt'],
        ['sales_return', 'Sales Return', 'fa-rotate-left']
    ],
    'Purchase' => [
        ['purchases', 'Purchases', 'fa-cart-shopping'],
        ['purchase_return', 'Purchase Return', 'fa-rotate-right']
    ],
    'People' => [
        ['customers', 'Customers', 'fa-users'],
        ['members', 'Members', 'fa-id-card'],
        ['suppliers', 'Suppliers', 'fa-truck'],
        ['users', 'Users', 'fa-user-group']
    ],
    'Finance & Reports' => [
        ['expenses', 'Expenses', 'fa-wallet'],
        ['payments', 'Payments', 'fa-money-bill'],
        ['reports', 'Reports', 'fa-chart-pie']
    ],
    'System' => [
        ['settings', 'Settings', 'fa-gear'],
        ['backup', 'Backup & Restore', 'fa-database']
    ]
];
?>

<aside class="sidebar" id="sidebar">

    <div class="sidebar-brand">
        <div class="brand-symbol">
            <i class="fa-solid fa-store"></i>
        </div>
        <div class="brand-text">
            <strong>TOKO<span>KITA</span></strong>
            <small>Point of Sale</small>
        </div>
    </div>

    <div class="sidebar-menu">

        <?php foreach ($groups as $group => $items): ?>

            <div class="sidebar-section">
                <?= htmlspecialchars($group) ?>
            </div>

            <?php foreach ($items as $item): ?>

                <a href="index.php?page=<?= $item[0] ?>"
                   class="sidebar-link <?= $currentPage === $item[0] ? 'active' : '' ?>">

                    <i class="fa-solid <?= $item[2] ?>"></i>
                    <span><?= htmlspecialchars($item[1]) ?></span>

                    <?php if ($currentPage === $item[0]): ?>
                        <i class="fa-solid fa-chevron-down ms-auto arrow"></i>
                    <?php endif; ?>

                </a>

            <?php endforeach; ?>

        <?php endforeach; ?>

    </div>

    <div class="sidebar-bottom">
        <div class="sidebar-version">
            <span class="online-dot"></span>
            TOKOKITA POS v1.0
        </div>
    </div>

</aside>
