<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

if (isset($_SESSION['user_id'])) {
    $role = strtolower(trim($_SESSION['role'] ?? ''));

    if ($role === 'admin') {
        header('Location: index.php?page=dashboard');
        exit;
    }

    if ($role === 'kasir') {
        header('Location: index.php?page=pos');
        exit;
    }

    if ($role === 'pelanggan') {
        header('Location: index.php?page=pelanggan_dashboard');
        exit;
    }

    session_unset();
    session_destroy();
    header('Location: login.php');
    exit;
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    try {
        $stmt = $pdo->prepare("
            SELECT id_user, nama_lengkap, username, password, role
            FROM tbl_user
            WHERE username = ?
            LIMIT 1
        ");

        $stmt->execute([$username]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            $error = 'Username tidak ditemukan!';
        } elseif (!password_verify($password, $user['password'])) {
            $error = 'Password yang kamu masukkan salah!';
        } else {

            $role = strtolower(trim($user['role']));

            if (!in_array($role, ['admin', 'kasir', 'pelanggan'], true)) {
                $error = 'Role akun tidak valid: ' . htmlspecialchars($role);
            } else {

                session_regenerate_id(true);

                $_SESSION['user_id'] = $user['id_user'];
                $_SESSION['nama'] = $user['nama_lengkap'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $role;

                switch ($role) {
                    case 'admin':
                        header('Location: index.php?page=dashboard');
                        exit;

                    case 'kasir':
                        header('Location: index.php?page=pos');
                        exit;

                    case 'pelanggan':
                        header('Location: index.php?page=pelanggan_dashboard');
                        exit;
                }
            }
        }

    } catch (PDOException $e) {
        $error = 'Kesalahan database: ' . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">

<title>Login TOKOKITA</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css" rel="stylesheet">

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    min-height: 100vh;
    background: #f8f9fb;
    font-family: Arial, sans-serif;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 20px;
}

.login-container {
    width: 900px;
    max-width: 100%;
    min-height: 520px;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: white;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 10px 40px #0000000c;
}

.login-banner {
    background: linear-gradient(145deg,#ffad55,#ed792d);
    color: white;
    padding: 55px 40px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.login-banner h1 {
    font-size: 34px;
    font-weight: 800;
}

.login-banner p {
    line-height: 1.8;
    opacity: .9;
}

.login-icon {
    font-size: 65px;
    margin-bottom: 25px;
}

.login-form {
    padding: 55px 45px;
    display: flex;
    flex-direction: column;
    justify-content: center;
}

.brand {
    font-size: 25px;
    font-weight: 800;
    color: #222;
}

.brand span {
    color: #f28b39;
}

.form-control {
    padding: 13px;
    font-size: 13px;
    border-radius: 8px;
}

.btn-login {
    width: 100%;
    background: #f28b39;
    color: white;
    border: 0;
    padding: 13px;
    border-radius: 8px;
    font-weight: bold;
}

.btn-login:hover {
    background: #e47727;
    color: white;
}

@media(max-width:650px) {
    .login-container {
        grid-template-columns: 1fr;
    }

    .login-banner {
        padding: 30px;
    }

    .login-banner h1 {
        font-size: 25px;
    }

    .login-form {
        padding: 35px 25px;
    }
}
</style>
</head>

<body>

<div class="login-container">

    <div class="login-banner">
        <div class="login-icon">
            <i class="fa-solid fa-store"></i>
        </div>

        <h1>TOKOKITA POS</h1>

        <p>
            Sistem manajemen toko modern untuk mengelola
            produk, stok, transaksi, pelanggan, dan laporan
            penjualan dalam satu aplikasi.
        </p>
    </div>

    <div class="login-form">

        <div class="brand mb-2">
            TOKO<span>KITA</span>
        </div>

        <h4 class="fw-bold mt-3">Welcome Back!</h4>

        <p class="text-muted small mb-4">
            Silakan login untuk melanjutkan.
        </p>

        <?php if ($error): ?>
            <div class="alert alert-danger small">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST">

            <label class="form-label small fw-bold">
                Username
            </label>

            <input
                type="text"
                name="username"
                class="form-control mb-3"
                placeholder="Masukkan username"
                autocomplete="username"
                required
            >

            <label class="form-label small fw-bold">
                Password
            </label>

            <input
                type="password"
                name="password"
                class="form-control mb-4"
                placeholder="Masukkan password"
                autocomplete="current-password"
                required
            >

            <button type="submit" class="btn-login">
                <i class="fa-solid fa-right-to-bracket me-2"></i>
                Login Sekarang
            </button>

        </form>

        <div class="text-center text-muted small mt-4">
            © <?= date('Y') ?> TOKOKITA POS
        </div>

    </div>

</div>

</body>
</html>
