
<?php
$pesan = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $aksi = $_POST['aksi'] ?? '';

    if ($aksi === 'tambah') {
        $nama = trim($_POST['nama']);
        $username = trim($_POST['username']);
        $password = $_POST['password'];
        $role = $_POST['role'];

        if ($nama && $username && $password && in_array($role, ['admin', 'kasir'], true)) {
            $stmt = $pdo->prepare("
                INSERT INTO tbl_user
                (nama_lengkap, username, password, role)
                VALUES (?, ?, ?, ?)
            ");

            try {
                $stmt->execute([
                    $nama,
                    $username,
                    password_hash($password, PASSWORD_DEFAULT),
                    $role
                ]);
                $pesan = 'Pengguna berhasil ditambahkan.';
            } catch (PDOException $e) {
                $pesan = 'Gagal menambahkan pengguna. Username mungkin sudah digunakan.';
            }
        }
    }

    if ($aksi === 'hapus') {
        $id = (int)$_POST['id'];

        // Jangan menghapus akun yang sedang digunakan.
        if ($id !== (int)($_SESSION['user_id'] ?? 0)) {
            $stmt = $pdo->prepare(
                "DELETE FROM tbl_user WHERE id_user = ?"
            );
            $stmt->execute([$id]);
            $pesan = 'Pengguna berhasil dihapus.';
        } else {
            $pesan = 'Akun yang sedang digunakan tidak dapat dihapus.';
        }
    }
}

$users = $pdo->query("
    SELECT id_user, nama_lengkap, username, role, created_at
    FROM tbl_user
    ORDER BY id_user DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid py-3">
    <div class="mb-4">
        <h4 class="fw-bold">Manajemen Pengguna</h4>
        <p class="text-muted">Kelola akun administrator dan kasir.</p>
    </div>

    <?php if ($pesan): ?>
        <div class="alert alert-info">
            <?= htmlspecialchars($pesan) ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Tambah Pengguna</h5>

            <form method="POST">
                <input type="hidden" name="aksi" value="tambah">

                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Username</label>
                        <input type="text" name="username"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password"
                               class="form-control" required>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Role</label>
                        <select name="role" class="form-select">
                            <option value="kasir">Kasir</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                </div>

                <button class="btn text-white mt-3" style="background:#f5822b">
                    Simpan Pengguna
                </button>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-3">Daftar Pengguna</h5>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>No</th>
                            <th>Nama</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Tanggal Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($users as $i => $u): ?>
                        <tr>
                            <td><?= $i + 1 ?></td>
                            <td><?= htmlspecialchars($u['nama_lengkap']) ?></td>
                            <td><?= htmlspecialchars($u['username']) ?></td>
                            <td>
                                <span class="badge <?= $u['role'] === 'admin' ? 'bg-primary' : 'bg-success' ?>">
                                    <?= htmlspecialchars(ucfirst($u['role'])) ?>
                                </span>
                            </td>
                            <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                            <td>
                                <form method="POST" onsubmit="return confirm('Hapus pengguna ini?')">
                                    <input type="hidden" name="aksi" value="hapus">
                                    <input type="hidden" name="id" value="<?= (int)$u['id_user'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">
                                        Hapus
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
