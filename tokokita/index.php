
<?php

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    ob_start();
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

require_once __DIR__ . '/config/database.php';

$role = strtolower($_SESSION['role'] ?? '');

$adminPages = [
    'dashboard' => ['Dashboard', 'pages/dashboard.php'],
    'products' => ['Products', 'pages/inventory/products.php'],
    'create_product' => ['Create Product', 'pages/inventory/create_product.php'],
    'expired' => ['Expired Products', 'pages/inventory/expired.php'],
    'low_stock' => ['Low Stocks', 'pages/inventory/low_stock.php'],
    'category' => ['Category', 'pages/inventory/category.php'],
    'brands' => ['Brands', 'pages/inventory/brands.php'],
    'units' => ['Units', 'pages/inventory/units.php'],
    'variants' => ['Variant Attributes', 'pages/inventory/variants.php'],
    'warranties' => ['Warranties', 'pages/inventory/warranties.php'],
    'barcode' => ['Print Barcode', 'pages/inventory/barcode.php'],
    'qrcode' => ['Print QR Code', 'pages/inventory/qrcode.php'],

    'manage_stock' => ['Manage Stock', 'pages/stock/manage.php'],
    'adjustment' => ['Stock Adjustment', 'pages/stock/adjustment.php'],
    'transfer' => ['Stock Transfer', 'pages/stock/transfer.php'],

    'pos' => ['POS', 'pages/sales/pos.php'],
    'sales' => ['Sales List', 'pages/sales/index.php'],
    'detail' => ['Transaction Detail', 'pages/sales/detail.php'],
    'sales_return' => ['Sales Return', 'pages/sales/return.php'],

    'purchases' => ['Purchases', 'pages/purchase/index.php'],
    'purchase_return' => ['Purchase Return', 'pages/purchase/return.php'],

    'customers' => ['Customers', 'pages/people/customers.php'],
    'suppliers' => ['Suppliers', 'pages/people/suppliers.php'],
    'users' => ['Users', 'pages/people/users.php'],

    'expenses' => ['Expenses', 'pages/finance/expenses.php'],
    'payments' => ['Payments', 'pages/finance/payments.php'],

    'report_sales' => ['Sales Report', 'pages/reports/sales.php'],
    'report_purchases' => ['Purchase Report', 'pages/reports/purchases.php'],
    'report_stock' => ['Stock Report', 'pages/reports/stock.php'],
    'report_profit' => ['Profit Report', 'pages/reports/profit.php'],

    'settings' => ['Store Settings', 'pages/settings/store.php'],
    'backup' => ['Backup & Restore', 'pages/settings/backup.php']
];

$kasirPages = [
    'pos' => ['POS', 'pages/sales/pos.php'],
    'sales' => ['Sales List', 'pages/sales/index.php'],
    'detail' => ['Transaction Detail', 'pages/sales/detail.php'],
    'sales_return' => ['Sales Return', 'pages/sales/return.php'],
    'customers' => ['Customers', 'pages/people/customers.php']
];

$pelangganPages = [
    'pelanggan_dashboard' => ['Beranda', 'pages/pelanggan/dashboard.php'],
    'pelanggan_produk' => ['Katalog Produk', 'pages/pelanggan/produk.php'],
    'pelanggan_detail' => ['Detail Produk', 'pages/pelanggan/detail_produk.php'],
    'pelanggan_keranjang' => ['Keranjang Saya', 'pages/pelanggan/keranjang.php'],
    'pelanggan_checkout' => ['Checkout', 'pages/pelanggan/checkout.php'],
    'pelanggan_pesanan' => ['Pesanan Saya', 'pages/pelanggan/pesanan.php'],
    'pelanggan_profil' => ['Profil Saya', 'pages/pelanggan/profil.php']
];

switch ($role) {
    case 'admin':
        $pages = $adminPages;
        $defaultPage = 'dashboard';
        break;

    case 'kasir':
        $pages = $kasirPages;
        $defaultPage = 'pos';
        break;

    case 'pelanggan':
        $pages = $pelangganPages;
        $defaultPage = 'pelanggan_dashboard';
        break;

    default:
        session_unset();
        session_destroy();
        header('Location: login.php');
        exit;
}

$page = $_GET['page'] ?? $defaultPage;

if (!isset($pages[$page])) {
    http_response_code(403);
    $page = $defaultPage;
}

$pageTitle = $pages[$page][0];

$file = __DIR__ . '/' . $pages[$page][1];

if (!file_exists($file)) {
    http_response_code(404);
    exit('Halaman tidak ditemukan: ' . htmlspecialchars($pageTitle));
}

if ($role === 'pelanggan') {
    require __DIR__ . '/includes/pelanggan_header.php';
    require $file;
    require __DIR__ . '/includes/pelanggan_footer.php';

} else {

    require __DIR__ . '/includes/header.php';
    require $file;
    require __DIR__ . '/includes/footer.php';

}
