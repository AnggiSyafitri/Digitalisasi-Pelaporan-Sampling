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

    case 3: // Manajer Teknis
        $pesan_dashboard = "Laporan yang Membutuhkan Persetujuan Anda";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l
                        JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Menunggu Persetujuan MT'
                        ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;

    case 4: // Penerima Contoh
        $pesan_dashboard = "Laporan Final yang Siap Dicetak";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l
                        JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Disetujui, Siap Dicetak'
                        ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;

    default:
        // Jika ada peran lain, tampilkan daftar kosong
        $daftar_laporan = [];
        $stmt = null; // Tidak ada statement yang perlu dieksekusi
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
    <link href="css/styles.css" rel="stylesheet" />
</head>
<body>

<header class="header-dashboard">
    <div class="header-title">
        <img src="https://yt3.googleusercontent.com/7uw0pH3SFyMHSYFo0OqwrLmv9LE28VF3TCK2dotW-Ruee1A6VVDYI8fiB0HEcYDb7WQYWcqU5w=s900-c-k-c0x00ffffff-no-rj" alt="Logo BSPJI" class="header-logo">
        <h1 style="font-size:2.1em;">Sistem Pelaporan Sampling</h1>
    </div>
    <div class="user-info">
        <span style="font-size:1.3em;">Selamat Datang, <strong><?php echo htmlspecialchars($nama_lengkap); ?></strong></span>
        <span style="font-size:1.3em; font-weight:400;">Peran: <?php echo htmlspecialchars($role_name); ?></span>
        <a href="logout.php">Logout</a>
    </div>
</header>

<div class="container-dashboard">
    
    <?php if ($role_id == 1): // Tampilkan hanya untuk PPC ?>
        <div class="card card-cta">
            <div class="card-body">
                <h2>Siap untuk Laporan Baru?</h2>
                <p>Klik tombol di bawah ini untuk memulai pengisian formulir pengambilan contoh yang baru.</p>
                <a href="formulir_sampling.php" class="btn btn-primary-dashboard">
                     <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-plus-lg" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M8 2a.5.5 0 0 1 .5.5v5h5a.5.5 0 0 1 0 1h-5v5a.5.5 0 0 1-1 0v-5h-5a.5.5 0 0 1 0-1h5v-5A.5.5 0 0 1 8 2Z"/></svg>
                    Buat Laporan Sampling Baru
                </a>
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
                <div class="table-responsive">
                    <table class="table-laporan">
                        <thead>
                            <tr>
                                <th>ID Laporan</th>
                                <th>Jenis</th>
                                <th>Perusahaan</th>
                                <th>Tanggal Sampling</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($daftar_laporan as $laporan): ?>
                            <tr>
                                <td>#<?php echo htmlspecialchars($laporan['id']); ?></td>
                                <td><?php echo ucfirst(htmlspecialchars($laporan['jenis_laporan'])); ?></td>
                                <td><?php echo htmlspecialchars($laporan['perusahaan']); ?></td>
                                <td><?php echo date('d M Y', strtotime($laporan['tanggal'])); ?></td>
                                <td>
                                    <span class="status <?php echo 'status-' . strtolower(str_replace(' ', '-', $laporan['status'])); ?>">
                                        <?php echo htmlspecialchars($laporan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="detail_laporan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary-dashboard">Lihat Detail</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

</body>
</html>
