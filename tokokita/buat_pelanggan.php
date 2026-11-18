
<?php
require_once 'config/database.php';

$password = password_hash('pelanggan123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    INSERT INTO tbl_user
    (nama_lengkap, username, password, role)
    VALUES (?, ?, ?, ?)
");

$stmt->execute([
    'Pelanggan TOKOKITA',
    'pelanggan',
    $password,
    'pelanggan'
]);

echo 'Akun pelanggan berhasil dibuat!';
