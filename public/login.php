<?php
// public/login.php

// Gantikan session_start() dan include 'koneksi.php' dengan satu baris ini.
require_once '../app/config.php';

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}

$error_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Username dan password harus diisi.";
    } else {
        // Ambil data user dari database
        $sql = "SELECT users.*, roles.nama_role FROM users 
                JOIN roles ON users.role_id = roles.id 
                WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($user = $result->fetch_assoc()) {
            // Verifikasi password
            if (password_verify($password, $user['password'])) {
                // Pengecekan waktu akses sebelum membuat session
                if (cekWaktuAkses($user['role_id'])) {
                    // Waktu diizinkan, login berhasil, simpan data ke session
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                    $_SESSION['role_id'] = $user['role_id'];
                    $_SESSION['role_name'] = $user['nama_role'];
                    
                    header("Location: " . BASE_URL . "/dashboard.php");
                    exit();
                } else {
                    // Waktu tidak diizinkan
                    $error_message = "Akses ditolak. Anda mencoba login di luar jam kerja yang telah ditentukan.";
                }
            } else {
                $error_message = "Login gagal. Username atau password salah.";
            }
        } else {
            $error_message = "Login gagal. Username atau password salah.";
        }
        $stmt->close();
    }
}

// Set judul halaman untuk template
$page_title = 'Login - Sistem Pelaporan Sampling';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?></title>
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet" />
</head>
<body class="bg-login">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>BSPJI MEDAN</h2>
                <h3>Sistem Pelaporan Sampling Mutu Lingkungan</h3>
            </div>
            
            <?php 
            // Mengambil pesan error dari proses login ATAU dari redirect sesi (flash message)
            $display_error = $error_message ?: ($_SESSION['flash_error'] ?? '');
            if (!empty($display_error)): 
            ?>
                <div class="alert alert-danger"><?php echo $display_error; ?></div>
            <?php 
                // Hapus flash error dari session setelah ditampilkan agar tidak muncul lagi
                unset($_SESSION['flash_error']);
            endif; 
            ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" class="form-control" required>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                <button type="submit" name="login" class="btn btn-primary btn-block">Login</button>
            </form>
        </div>
    </div>
</body>
</html>