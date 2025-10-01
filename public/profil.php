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
<div class="container-dashboard">
    <h2>Profil Saya</h2>
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
            <?php if (!empty($user['tanda_tangan'])): ?>
                <p>Tanda tangan Anda saat ini:</p>
                <img src="<?php echo BASE_URL . '/uploads/ttd/' . htmlspecialchars($user['tanda_tangan']); ?>" alt="Tanda Tangan" style="max-width: 200px; border: 1px solid #ccc; padding: 5px;">
            <?php else: ?>
                <p class="text-danger">Anda belum mengunggah tanda tangan. Anda tidak akan bisa menyetujui laporan sebelum mengunggahnya.</p>
            <?php endif; ?>

            <form action="../actions/simpan_profil.php" method="post" enctype="multipart/form-data" class="mt-3">
                <div class="form-group">
                    <label for="tanda_tangan"><?php echo !empty($user['tanda_tangan']) ? 'Ganti' : 'Upload'; ?> Tanda Tangan:</label>
                    <input type="file" name="tanda_tangan" id="tanda_tangan" class="form-control-file" required>
                    <small class="form-text text-muted">Format: PNG, JPG, JPEG. Ukuran Maks: 1MB. Latar belakang transparan lebih disarankan.</small>
                </div>
                <button type="submit" class="btn btn-primary">Simpan Tanda Tangan</button>
            </form>
        </div>
    </div>
</div>
<?php require_once '../templates/footer.php'; ?>