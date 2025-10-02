<?php
require_once '../app/config.php';
if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$upload_dir = '../public/uploads/ttd/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0755, true);
}

$new_filename = null;

try {
    // Hapus file TTD lama terlebih dahulu
    $stmt_old = $conn->prepare("SELECT tanda_tangan FROM users WHERE id = ?");
    $stmt_old->bind_param("i", $user_id);
    $stmt_old->execute();
    $old_file = $stmt_old->get_result()->fetch_assoc()['tanda_tangan'];
    if ($old_file && file_exists($upload_dir . $old_file)) {
        unlink($upload_dir . $old_file);
    }
    $stmt_old->close();

    // LOGIKA 1: Jika data berasal dari gambar canvas (Base64)
    if (isset($_POST['tanda_tangan_base64']) && !empty($_POST['tanda_tangan_base64'])) {
        $data_url = $_POST['tanda_tangan_base64'];
        list($type, $data) = explode(';', $data_url);
        list(, $data) = explode(',', $data);
        $data = base64_decode($data);

        $new_filename = 'ttd_' . $user_id . '_' . time() . '.png';
        if (file_put_contents($upload_dir . $new_filename, $data)) {
            $_SESSION['flash_success'] = "Tanda tangan dari gambar berhasil disimpan.";
        } else {
            throw new Exception("Gagal menyimpan file gambar tanda tangan.");
        }
    } 
    // LOGIKA 2: Jika data berasal dari upload file
    elseif (isset($_FILES['tanda_tangan']) && $_FILES['tanda_tangan']['error'] === UPLOAD_ERR_OK) {
        $file = $_FILES['tanda_tangan'];
        $allowed_ext = ['jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

        if (!in_array($file_ext, $allowed_ext)) throw new Exception("Format file tidak diizinkan. Gunakan PNG atau JPG.");
        if ($file['size'] > 1 * 1024 * 1024) throw new Exception("Ukuran file terlalu besar. Maksimal 1MB.");

        $new_filename = 'ttd_' . $user_id . '_' . time() . '.' . $file_ext;
        if (move_uploaded_file($file['tmp_name'], $upload_dir . $new_filename)) {
            $_SESSION['flash_success'] = "Tanda tangan dari file berhasil diunggah.";
        } else {
            throw new Exception("Gagal mengunggah file tanda tangan.");
        }
    }

    // Jika ada file baru yang berhasil dibuat, update database
    if ($new_filename) {
        $stmt = $conn->prepare("UPDATE users SET tanda_tangan = ? WHERE id = ?");
        $stmt->bind_param("si", $new_filename, $user_id);
        $stmt->execute();
        $stmt->close();
    }

} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}

header("Location: " . BASE_URL . "/profil.php");
exit();
?>