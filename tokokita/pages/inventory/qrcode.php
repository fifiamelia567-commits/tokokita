
<?php
$search = trim($_GET['search'] ?? '');

$stmt = $pdo->prepare("
    SELECT id_produk, kode_produk, nama_produk
    FROM tbl_produk
    WHERE kode_produk LIKE ? OR nama_produk LIKE ?
    ORDER BY id_produk DESC
");

$stmt->execute(["%$search%", "%$search%"]);
$products = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid px-4">
    <div class="mt-3 mb-4">
        <h4 class="fw-bold">QR Code Generator</h4>
        <p class="text-muted">Buat QR Code untuk identifikasi produk.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-body p-4">

            <form method="GET" action="index.php" class="row g-2 mb-4">
                <input type="hidden" name="page" value="qrcode">

                <div class="col-md-5">
                    <input type="text" name="search" class="form-control"
                           placeholder="Cari produk..."
                           value="<?= htmlspecialchars($search) ?>">
                </div>

                <div class="col-md-2">
                    <button class="btn text-white w-100" style="background:#f5822b">
                        Cari Produk
                    </button>
                </div>
            </form>

            <div class="row g-3">

                <?php foreach ($products as $product): ?>
                    <div class="col-md-4 col-lg-3">
                        <div class="border rounded-4 p-3 text-center h-100">

                            <h6 class="fw-bold">
                                <?= htmlspecialchars($product['nama_produk']) ?>
                            </h6>

                            <div class="qr-code d-flex justify-content-center my-3"
                                 data-code="<?= htmlspecialchars($product['kode_produk']) ?>">
                            </div>

                            <p class="text-muted small">
                                <?= htmlspecialchars($product['kode_produk']) ?>
                            </p>

                            <button type="button"
                                    class="btn btn-sm text-white"
                                    style="background:#f5822b"
                                    onclick="printQR(this)">
                                <i class="fas fa-print me-1"></i> Cetak QR
                            </button>

                        </div>
                    </div>
                <?php endforeach; ?>

                <?php if (!$products): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                        <p>Produk tidak ditemukan.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>

<script>
document.querySelectorAll('.qr-code').forEach(function (el) {
    new QRCode(el, {
        text: el.dataset.code,
        width: 140,
        height: 140,
        correctLevel: QRCode.CorrectLevel.H
    });
});

function printQR(button) {
    const card = button.closest('.border.rounded-4');
    const qr = card.querySelector('.qr-code canvas');

    const image = qr
        ? qr.toDataURL('image/png')
        : card.querySelector('.qr-code img').src;

    const printWindow = window.open('', '_blank');

    printWindow.document.write(`
        <html>
        <head>
            <title>Cetak QR Code</title>
            <style>
                body { font-family: Arial; text-align: center; padding: 25px; }
                img { width: 160px; }
            </style>
        </head>
        <body>
            ${card.querySelector('h6').outerHTML}
            <img src="${image}">
            <p>${card.querySelector('.text-muted').textContent}</p>
            <script>
                window.onload = function() {
                    window.print();
                };
            <\/script>
        </body>
        </html>
    `);

    printWindow.document.close();
}
</script>
