<?php
// public/dashboard.php

// 1. Panggil file config.php
// Path '../app/config.php' artinya "keluar dari folder public, lalu masuk ke folder app"
require_once '../app/config.php';

// 2. Pengecekan Sesi (Keamanan)
// Jika tidak ada user_id di session, tendang ke halaman login
if (!isset($_SESSION['user_id'])) {
    // Gunakan BASE_URL untuk redirect yang pasti benar
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// 3. Ambil data user dari session (sudah aman karena sudah dicek di atas)
$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

// 4. Logika untuk mengambil data laporan sesuai peran (sama seperti kodemu sebelumnya)
$sql_laporan = "";
// ... (Kode query SELECT berdasarkan role_id tetap sama seperti yang sudah kamu buat) ...
// NOTE: Seluruh blok 'switch ($role_id)' kamu dari file dashboard.php lama bisa ditaruh di sini.
// Pastikan tidak ada duplikasi variabel.

// (Untuk contoh, saya salin kembali logikanya ke sini)
$daftar_laporan = [];
$pesan_dashboard = "Daftar Laporan Terkini";
switch ($role_id) {
    case 1: // PPC
        $pesan_dashboard = "Riwayat Laporan yang Anda Buat";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.ppc_id = ? ORDER BY l.updated_at DESC";
        $stmt = $conn->prepare($sql_laporan);
        $stmt->bind_param("i", $user_id);
        break;
    case 2: // Penyelia
        $pesan_dashboard = "Laporan yang Membutuhkan Verifikasi Anda";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Menunggu Verifikasi' ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;
    // ... (case 3 dan 4 sama seperti sebelumnya) ...
    case 3: // Manajer Teknis
        $pesan_dashboard = "Laporan yang Membutuhkan Persetujuan Anda";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Menunggu Persetujuan MT' ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;
    case 4: // Penerima Contoh
        $pesan_dashboard = "Laporan Final yang Siap Dicetak";
        $sql_laporan = "SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal 
                        FROM laporan l JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
                        WHERE l.status = 'Disetujui, Siap Dicetak' ORDER BY l.updated_at ASC";
        $stmt = $conn->prepare($sql_laporan);
        break;
    default:
        $stmt = null;
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

// 5. Atur judul halaman
$page_title = 'Dashboard';

// 6. Panggil template header
require_once '../templates/header.php';
?>

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
<?php
// 7. Panggil template footer (untuk saat ini footer bisa kosong atau hanya berisi tag penutup)
// require_once '../templates/footer.php'; 
// Note: Kita akan buat file footer di langkah selanjutnya, untuk sekarang bisa di-comment dulu.
?>
</body>
</html>