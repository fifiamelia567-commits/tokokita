<?php
require_once __DIR__ . '/config/database.php';

$username = 'pelanggan';
$password = password_hash('pelanggan123', PASSWORD_DEFAULT);

$stmt = $pdo->prepare("
    UPDATE tbl_user
    SET password = ?, role = 'pelanggan'
    WHERE username = ?
");

$stmt->execute([$password, $username]);

if ($stmt->rowCount() > 0) {
    echo "Password pelanggan berhasil diperbarui.";
} else {
    echo "Username pelanggan tidak ditemukan atau data tidak berubah.";
}