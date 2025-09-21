<?php
// public/registrasi.php

// Ganti 'include koneksi.php' dengan ini agar terhubung dengan database
require_once '../app/config.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role_id = (int)$_POST['role_id'];

    if (empty($username) || empty($password) || empty($nama_lengkap) || empty($role_id)) {
        $message = "Semua field harus diisi!";
    } else {
        // Hash password sebelum disimpan
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        $sql = "INSERT INTO users (username, password, nama_lengkap, role_id) VALUES (?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssi", $username, $password_hash, $nama_lengkap, $role_id);

        if ($stmt->execute()) {
            $message = "Registrasi user '$username' berhasil!";
        } else {
            $message = "Error: " . $stmt->error;
        }
        $stmt->close();
    }
}

// Ambil data peran (roles) dari database untuk ditampilkan di dropdown
$roles_sql = "SELECT * FROM roles";
$roles_result = $conn->query($roles_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi User (Development)</title>
    <link href="<?php echo BASE_URL; ?>/assets/css/styles.css" rel="stylesheet" />
    <style>
        /* Sedikit style tambahan khusus untuk halaman ini */
        body { background-color: #f4f7f6; }
        .register-container { max-width: 500px; margin: 50px auto; }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="card">
            <div class="card-header"><h3>Registrasi User Baru</h3></div>
            <div class="card-body">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>
                <form method="post" action="registrasi.php">
                    <div class="form-group">
                        <label for="nama_lengkap">Nama Lengkap:</label>
                        <input type="text" id="nama_lengkap" name="nama_lengkap" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="username">Username:</label>
                        <input type="text" id="username" name="username" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="password">Password:</label>
                        <input type="password" id="password" name="password" class="form-control" required>
                    </div>

                    <div class="form-group">
                        <label for="role_id">Peran (Role):</label>
                        <select id="role_id" name="role_id" class="form-control" required>
                            <option value="">-- Pilih Peran --</option>
                            <?php while($row = $roles_result->fetch_assoc()): ?>
                                <option value="<?php echo $row['id']; ?>"><?php echo $row['nama_role']; ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Registrasi</button>
                </form>
            </div>
        </div>
         <a href="<?php echo BASE_URL; ?>/dashboard.php" class="back-to-dashboard d-block text-center mt-3">« Kembali ke Dashboard</a>
    </div>
</body>
</html>