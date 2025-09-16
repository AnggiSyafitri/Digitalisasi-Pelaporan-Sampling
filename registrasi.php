<?php
// registrasi.php
// File ini hanya untuk development, untuk membuat user dengan password ter-hash.
// Hapus atau amankan file ini pada production.

include 'koneksi.php';

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $nama_lengkap = $_POST['nama_lengkap'];
    $role_id = (int)$_POST['role_id'];

    // Validasi dasar
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

$roles_sql = "SELECT * FROM roles";
$roles_result = $conn->query($roles_sql);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Registrasi User (Sementara)</title>
    <style>
        body { font-family: Arial; margin: 50px; }
        .register-box { width: 400px; margin: auto; border: 1px solid #ccc; padding: 20px; border-radius: 8px; }
        input, select { width: 100%; padding: 8px; margin: 6px 0; }
        button { width: 100%; padding: 8px; background: #28a745; color: white; border: none; border-radius: 4px; }
        .message { margin-bottom: 15px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="register-box">
        <h3>Registrasi User Baru</h3>
        <?php if (!empty($message)): ?>
            <p class="message"><?php echo $message; ?></p>
        <?php endif; ?>
        <form method="post" action="">
            <label for="nama_lengkap">Nama Lengkap:</label>
            <input type="text" name="nama_lengkap" required>

            <label for="username">Username:</label>
            <input type="text" name="username" required>
            
            <label for="password">Password:</label>
            <input type="password" name="password" required>
            
            <label for="role_id">Peran (Role):</label>
            <select name="role_id" required>
                <option value="">-- Pilih Peran --</option>
                <?php while($row = $roles_result->fetch_assoc()): ?>
                    <option value="<?php echo $row['id']; ?>"><?php echo $row['nama_role']; ?></option>
                <?php endwhile; ?>
            </select>
            
            <button type="submit">Registrasi</button>
        </form>
    </div>
</body>
</html>