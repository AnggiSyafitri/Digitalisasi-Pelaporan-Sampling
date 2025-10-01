<?php
require_once '../app/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_FILES['tanda_tangan'])) {
    $user_id = $_SESSION['user_id'];
    $file = $_FILES['tanda_tangan'];

    if ($file['error'] === UPLOAD_ERR_OK) {
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) {
            $_SESSION['flash_error'] = "Format file tidak diizinkan. Gunakan PNG, JPG, atau JPEG.";
        } elseif ($file['size'] > 1 * 1024 * 1024) { // 1MB
            $_SESSION['flash_error'] = "Ukuran file terlalu besar. Maksimal 1MB.";
        } else {
            // Buat folder jika belum ada
            $upload_dir = '../public/uploads/ttd/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            // Hapus file lama jika ada
            $stmt_old = $conn->prepare("SELECT tanda_tangan FROM users WHERE id = ?");
            $stmt_old->bind_param("i", $user_id);
            $stmt_old->execute();
            $old_file = $stmt_old->get_result()->fetch_assoc()['tanda_tangan'];
            if ($old_file && file_exists($upload_dir . $old_file)) {
                unlink($upload_dir . $old_file);
            }

            // Simpan file baru
            $new_filename = 'ttd_' . $user_id . '_' . time() . '.' . $file_ext;
            if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
                $stmt = $conn->prepare("UPDATE users SET tanda_tangan = ? WHERE id = ?");
                $stmt->bind_param("si", $new_filename, $user_id);
                $stmt->execute();
                $_SESSION['flash_success'] = "Tanda tangan berhasil diperbarui.";
            } else {
                $_SESSION['flash_error'] = "Gagal mengunggah file tanda tangan.";
            }
        }
    } else {
        $_SESSION['flash_error'] = "Terjadi error saat mengunggah file.";
    }
}
header("Location: " . BASE_URL . "/profil.php");
exit();
?>