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
  <title>Login - Sistem Pelaporan Sampling</title>
  <style>
    body { font-family: Arial, sans-serif; background-color: #f4f4f4; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
    .login-box { width: 320px; background: white; border: 1px solid #ddd; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
    .login-box h3 { text-align: center; margin-bottom: 20px; color: #333; }
    .error-msg { color: #d9534f; background-color: #f2dede; border: 1px solid #ebccd1; padding: 10px; border-radius: 4px; margin-bottom: 15px; text-align: center; font-size: 14px; }
    input[type="text"], input[type="password"] { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; }
    button { width: 100%; padding: 10px; background: #2196f3; color: white; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; }
    button:hover { background: #1976d2; }
  </style>
</head>
<body>
  <div class="login-box">
    <h3>Sistem Pelaporan Sampling</h3>
    <?php if (!empty($error_message)): ?>
        <p class="error-msg"><?php echo $error_message; ?></p>
    <?php endif; ?>
    <form method="post" action="login.php">
      <input type="text" name="username" placeholder="Username" required>
      <input type="password" name="password" placeholder="Password" required>
      <button type="submit" name="login">Login</button>
    </form>
  </div>
</body>
</html>