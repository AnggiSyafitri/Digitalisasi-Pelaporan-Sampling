<?php
require_once '../app/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT nama_lengkap, username, tanda_tangan FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();

$page_title = 'Profil Saya';
require_once '../templates/header.php';
?>
<style>
    .signature-modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.6); }
    .signature-modal-content { background-color: #fefefe; margin: 10% auto; padding: 20px; border: 1px solid #888; width: 80%; max-width: 500px; border-radius: 8px; }
    .signature-pad-canvas { border: 1px solid #ccc; border-radius: 4px; width: 100%; height: 200px; }
    .signature-modal-footer { margin-top: 15px; text-align: right; }
</style>

<div class="container-dashboard">
    <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard">« Kembali ke Dashboard</a>
    <h2 class="mt-3">Profil Saya</h2>

    <?php if (isset($_SESSION['flash_success'])): ?>
        <div class="alert alert-success"><?php echo $_SESSION['flash_success']; unset($_SESSION['flash_success']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['flash_error']; unset($_SESSION['flash_error']); ?></div>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <p><strong>Nama Lengkap:</strong> <?php echo htmlspecialchars($user['nama_lengkap']); ?></p>
            <p><strong>Username:</strong> <?php echo htmlspecialchars($user['username']); ?></p>
            <hr>
            <h5>Tanda Tangan Digital</h5>
            <div class="mb-3">
                <?php if (!empty($user['tanda_tangan'])): ?>
                    <p>Tanda tangan Anda saat ini:</p>
                    <img src="<?php echo BASE_URL . '/uploads/ttd/' . htmlspecialchars($user['tanda_tangan']); ?>" alt="Tanda Tangan" style="max-width: 200px; border: 1px solid #ccc; padding: 5px; background-color: #f8f9fa;">
                <?php else: ?>
                    <p class="text-danger">Anda belum memiliki tanda tangan. Anda tidak akan bisa menyetujui laporan sebelum menyiapkannya.</p>
                <?php endif; ?>
            </div>

            <button type="button" id="openSignatureModalBtn" class="btn btn-primary">Gambar Tanda Tangan Baru</button>
            <hr>

            <form action="../actions/simpan_profil.php" method="post" enctype="multipart/form-data" class="mt-3">
                <p>Atau, upload gambar tanda tangan Anda:</p>
                <div class="form-group">
                    <input type="file" name="tanda_tangan" id="tanda_tangan_upload" class="form-control-file">
                    <small class="form-text text-muted">Format: PNG, JPG. Maks 1MB. Latar belakang transparan disarankan.</small>
                </div>
                <button type="submit" class="btn btn-secondary">Simpan dari File</button>
            </form>
        </div>
    </div>
</div>

<div id="signatureModal" class="signature-modal">
    <div class="signature-modal-content">
        <h4>Gambar Tanda Tangan Anda di Kotak Berikut</h4>
        <canvas id="signature-pad" class="signature-pad-canvas"></canvas>
        <div class="signature-modal-footer">
            <button type="button" id="clearSignatureBtn" class="btn btn-warning">Hapus</button>
            <button type="button" id="saveSignatureBtn" class="btn btn-success">Simpan</button>
            <button type="button" id="closeSignatureModalBtn" class="btn btn-secondary">Tutup</button>
        </div>
    </div>
</div>

<form id="signatureForm" action="../actions/simpan_profil.php" method="post" style="display:none;">
    <input type="hidden" name="tanda_tangan_base64" id="tanda_tangan_base64">
</form>

<script>
    // Jalankan semua script setelah halaman selesai dimuat sepenuhnya
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('signatureModal');
        const openBtn = document.getElementById('openSignatureModalBtn');
        const closeBtn = document.getElementById('closeSignatureModalBtn');
        const saveBtn = document.getElementById('saveSignatureBtn');
        const clearBtn = document.getElementById('clearSignatureBtn');
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgb(255, 255, 255)'
        });

        // Fungsi untuk menyesuaikan ukuran canvas
        function resizeCanvas() {
            const ratio =  Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear(); // Hapus TTD setelah resize
        }

        window.addEventListener("resize", resizeCanvas);

        openBtn.onclick = function() {
            modal.style.display = "block";
            resizeCanvas();
        }
        closeBtn.onclick = function() {
            modal.style.display = "none";
        }
        clearBtn.onclick = function() {
            signaturePad.clear();
        }
        saveBtn.onclick = function() {
            if (signaturePad.isEmpty()) {
                alert("Mohon bubuhkan tanda tangan Anda terlebih dahulu.");
            } else {
                const dataURL = signaturePad.toDataURL("image/png");
                document.getElementById('tanda_tangan_base64').value = dataURL;
                document.getElementById('signatureForm').submit();
            }
        }
        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }
    });
</script>
<?php require_once '../templates/footer.php'; ?>