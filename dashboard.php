<?php
session_start();
include 'koneksi.php';

// 1. Pengecekan Sesi
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Ambil data user dari session
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$nama_lengkap = $_SESSION['nama_lengkap'];
$role_id = $_SESSION['role_id'];
$role_name = $_SESSION['role_name'];

// 3. Logika untuk mengambil data laporan sesuai peran
$daftar_laporan = [];
$pesan_dashboard = "Daftar Laporan Terkini";
$sql_laporan = "";

// Kueri disesuaikan berdasarkan peran pengguna
switch ($role_id) {
    case 1: // PPC (Petugas Pengambil Contoh)
        $pesan_dashboard = "Riwayat Laporan yang Anda Buat";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l
                        JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.ppc_id = ?
                        ORDER BY l.updated_at DESC";
        $stmt = $conn->prepare($sql_laporan);
        $stmt->bind_param("i", $user_id);
        break;
        
    case 2: // Penyelia
        $pesan_dashboard = "Laporan yang Membutuhkan Verifikasi Anda";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l
                        JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Menunggu Verifikasi'
                        ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;
        
    // Tambahkan case untuk Manajer Teknis (3) dan Penerima Contoh (4) di sini nanti
    default:
        // Default query untuk peran lain (misal: tampilkan semua yang sudah selesai)
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l
                        JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Selesai'
                        ORDER BY l.updated_at DESC";
        $stmt = $conn->prepare($sql_laporan);
        break;
}

if ($stmt) {
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) {
        $daftar_laporan[] = $row;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - <?php echo htmlspecialchars($role_name); ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; background-color: #f4f7f6; }
        .header { background-color: #2c3e50; color: white; padding: 15px 25px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 2px 5px rgba(0,0,0,0.2); }
        .header h1 { margin: 0; font-size: 22px; }
        .user-info { text-align: right; }
        .user-info span { display: block; }
        .user-info a { color: #e74c3c; text-decoration: none; font-size: 14px; }
        .container { padding: 25px; }
        .card { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { padding: 15px; border-bottom: 1px solid #eee; }
        .card-header h2 { margin: 0; font-size: 18px; }
        .card-body { padding: 15px; }
        .btn { display: inline-block; padding: 8px 12px; background-color: #3498db; color: white; text-decoration: none; border-radius: 5px; margin-right: 10px; font-size: 14px; }
        .btn-green { background-color: #2ecc71; }
        .table-laporan { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-laporan th, .table-laporan td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .table-laporan th { background-color: #f2f2f2; }
        .status { padding: 5px 8px; border-radius: 15px; color: white; font-size: 12px; text-align: center; }
        .status-menunggu { background-color: #f39c12; }
    </style>
</head>
<body>

<header class="header">
    <h1>Sistem Pelaporan Sampling</h1>
    <div class="user-info">
        <span><?php echo htmlspecialchars($nama_lengkap); ?></span>
        <small>Peran: <strong><?php echo htmlspecialchars($role_name); ?></strong></small>
        <div><a href="logout.php">Logout</a></div>
    </div>
</header>

<div class="container">
    
    <?php if ($role_id == 1): ?>
        <div class="card">
            <div class="card-header"><h2>Tugas Anda</h2></div>
            <div class="card-body">
                <p>Silakan buat laporan baru atau lihat riwayat laporan Anda.</p>
                <a href="formulir_sampling.php" class="btn btn-green">Buat Laporan Sampling Baru</a>
            </div>
        </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($pesan_dashboard); ?></h2>
        </div>
        <div class="card-body">
            <?php if (empty($daftar_laporan)): ?>
                <p>Belum ada laporan yang tersedia untuk Anda saat ini.</p>
            <?php else: ?>
                <table class="table-laporan">
                    <thead>
                        <tr>
                            <th>ID Laporan</th>
                            <th>Jenis</th>
                            <th>Perusahaan</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daftar_laporan as $laporan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($laporan['id']); ?></td>
                            <td><?php echo ucfirst(htmlspecialchars($laporan['jenis_laporan'])); ?></td>
                            <td><?php echo htmlspecialchars($laporan['perusahaan']); ?></td>
                            <td><?php echo date('d M Y', strtotime($laporan['tanggal'])); ?></td>
                            <td>
                                <span class="status status-menunggu"><?php echo htmlspecialchars($laporan['status']); ?></span>
                            </td>
                            <td>
                                <a href="detail_laporan.php?id=<?php echo $laporan['id']; ?>" class="btn">Lihat Detail</a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
