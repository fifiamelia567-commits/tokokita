
<?php
$idPelanggan = (int)($_SESSION['id_pelanggan'] ?? 0);

$pesan = '';
$error = '';

if ($idPelanggan <= 0) {
    echo '<div class="card">ID pelanggan belum tersedia di session login.</div>';
    return;
}

$stmt = $pdo->prepare("
    SELECT *
    FROM tbl_pelanggan
    WHERE id_pelanggan = ?
    LIMIT 1
");
$stmt->execute([$idPelanggan]);
$pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$pelanggan) {
    echo '<div class="card">Data pelanggan tidak ditemukan.</div>';
    return;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_profil'])) {
    $nama = trim($_POST['nama_pelanggan'] ?? '');
    $telepon = trim($_POST['telepon'] ?? '');
    $kota = trim($_POST['kota'] ?? '');
    $alamat = trim($_POST['alamat'] ?? '');

    if ($nama === '') {
        $error = 'Nama pelanggan wajib diisi.';
    } else {
        $stmt = $pdo->prepare("
            UPDATE tbl_pelanggan
            SET nama_pelanggan = ?,
                telepon = ?,
                kota = ?,
                alamat = ?
            WHERE id_pelanggan = ?
        ");

        $stmt->execute([
            $nama,
            $telepon,
            $kota,
            $alamat,
            $idPelanggan
        ]);

        $_SESSION['nama'] = $nama;

        $pesan = 'Profil berhasil diperbarui.';

        $stmt = $pdo->prepare("
            SELECT *
            FROM tbl_pelanggan
            WHERE id_pelanggan = ?
        ");
        $stmt->execute([$idPelanggan]);
        $pelanggan = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

$inisial = strtoupper(substr($pelanggan['nama_pelanggan'], 0, 1));
?>

<div class="page-heading">
    <div>
        <h1>Profil Saya</h1>
        <p>Kelola informasi akun dan data pelanggan.</p>
    </div>
</div>

<?php if ($pesan): ?>
    <div class="stock-alert">
        <?= e($pesan) ?>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="stock-alert" style="background:#fff0e9;color:#d95032">
        <?= e($error) ?>
    </div>
<?php endif; ?>

<div class="profile-layout">

    <div class="card profile-card">

        <div class="profile-avatar-large">
            <?= e($inisial) ?>
        </div>

        <h3><?= e($pelanggan['nama_pelanggan']) ?></h3>

        <p><?= e($pelanggan['kota'] ?? 'Kota belum diisi') ?></p>

        <div style="margin-top:18px;border-top:1px solid #e9edf1;padding-top:15px">

            <div class="total-row">
                <span>Member</span>
                <strong><?= e($pelanggan['tipe_member'] ?? 'Regular') ?></strong>
            </div>

            <div class="total-row">
                <span>Poin</span>
                <strong><?= number_format((int)($pelanggan['poin'] ?? 0)) ?></strong>
            </div>

            <div class="total-row">
                <span>Status Kartu</span>
                <strong><?= e($pelanggan['status_kartu'] ?? '-') ?></strong>
            </div>

        </div>

    </div>

    <div class="card">

        <div class="card-title">Informasi Pribadi</div>

        <form method="POST">

            <div style="margin-bottom:14px">
                <label>Nama Lengkap</label>
                <input
                    type="text"
                    name="nama_pelanggan"
                    class="form-control"
                    style="width:100%;margin-top:7px"
                    value="<?= e($pelanggan['nama_pelanggan']) ?>"
                    required
                >
            </div>

            <div style="margin-bottom:14px">
                <label>Nomor Telepon</label>
                <input
                    type="text"
                    name="telepon"
                    class="form-control"
                    style="width:100%;margin-top:7px"
                    value="<?= e($pelanggan['telepon'] ?? '') ?>"
                >
            </div>

            <div style="margin-bottom:14px">
                <label>Kota</label>
                <input
                    type="text"
                    name="kota"
                    class="form-control"
                    style="width:100%;margin-top:7px"
                    value="<?= e($pelanggan['kota'] ?? '') ?>"
                >
            </div>

            <div style="margin-bottom:18px">
                <label>Alamat Lengkap</label>
                <textarea
                    name="alamat"
                    class="form-control"
                    style="width:100%;margin-top:7px"
                    rows="4"
                ><?= e($pelanggan['alamat'] ?? '') ?></textarea>
            </div>

            <button
                type="submit"
                name="simpan_profil"
                class="btn-primary-custom"
            >
                <i class="fas fa-save"></i>
                Simpan Perubahan
            </button>

        </form>

    </div>

</div>
