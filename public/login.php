<?php
session_start();
include 'koneksi.php'; // Pastikan file koneksi.php sudah ada

// Jika sudah login, langsung redirect ke dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
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
                // Login berhasil, simpan data ke session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['nama_lengkap'] = $user['nama_lengkap'];
                $_SESSION['role_id'] = $user['role_id'];
                $_SESSION['role_name'] = $user['nama_role'];

                header("Location: dashboard.php");
                exit();
            } else {
                // Password salah
                $error_message = "Login gagal. Username atau password salah.";
            }
        } else {
            // Username tidak ditemukan
            $error_message = "Login gagal. Username atau password salah.";
        }
        $stmt->close();
    }
}
$conn->close();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Pelaporan Sampling</title>
    <link href="css/styles.css" rel="stylesheet" />
</head>
<body class="bg-login">
    <div class="login-container">
        <div class="login-box">
            <div class="login-header">
                <h2>BSPJI MEDAN</h2>
                <h3>Sistem Pelaporan Sampling Mutu Lingkungan</h3>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
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