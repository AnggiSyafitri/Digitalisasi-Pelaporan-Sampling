<?php
// public/dashboard.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$role_id = $_SESSION['role_id'];

$daftar_laporan = [];
$laporan_riwayat = []; 
$pesan_dashboard = "Daftar Laporan Terkini";
$pesan_riwayat = "Riwayat Laporan"; // Pesan default untuk riwayat

$query_part = "
    SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal, l.ppc_id, l.updated_at
    FROM laporan l
    JOIN (
        SELECT id as form_id, perusahaan, tanggal FROM formulir_air
        UNION ALL
        SELECT id as form_id, perusahaan, tanggal FROM formulir_udara
    ) f ON l.form_id = f.form_id AND l.jenis_laporan IN ('air', 'udara')
";

$where_clause = "";
$params = [];
$types = "";

switch ($role_id) {
    case 1: // PPC
        $pesan_dashboard = "Riwayat Laporan yang Anda Buat";
        $where_clause = " WHERE l.ppc_id = ? ORDER BY l.updated_at DESC";
        $types = "i";
        $params[] = $user_id;
        break;

    case 2: // Penyelia
        $pesan_dashboard = "Laporan yang Membutuhkan Verifikasi Anda";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Proses";
        $where_clause = " WHERE l.status = 'Menunggu Verifikasi' ORDER BY l.updated_at ASC";
        
        $sql_riwayat = $query_part . " WHERE l.penyelia_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;

    case 3: // Manajer Teknis
        $pesan_dashboard = "Laporan yang Membutuhkan Persetujuan Anda";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Setujui/Revisi";
        $where_clause = " WHERE l.status = 'Menunggu Persetujuan MT' ORDER BY l.updated_at ASC";

        $sql_riwayat = $query_part . " WHERE l.mt_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;

    case 4: // Penerima Contoh
        $pesan_dashboard = "Laporan Final yang Siap Dicetak";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Selesaikan";
        $where_clause = " WHERE l.status = 'Disetujui, Siap Dicetak' ORDER BY l.updated_at ASC";

        $sql_riwayat = $query_part . " WHERE l.penerima_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;
}

// Eksekusi query utama untuk tugas aktif
$sql_laporan = $query_part . $where_clause;
$stmt = $conn->prepare($sql_laporan);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $daftar_laporan[] = $row;
}
$stmt->close();

// Eksekusi query riwayat jika statementnya ada (untuk role 2, 3, dan 4)
if (isset($stmt_riwayat)) {
    $stmt_riwayat->execute();
    $result_riwayat = $stmt_riwayat->get_result();
    while ($row = $result_riwayat->fetch_assoc()) {
        $laporan_riwayat[] = $row;
    }
    $stmt_riwayat->close();
}

$page_title = 'Dashboard';
require_once '../templates/header.php';
?>

<div class="container-dashboard">
    <?php if ($role_id == 1): ?>
        <div class="card card-cta">
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

    <?php if (in_array($role_id, [2, 3, 4])): ?>
    <div class="card mt-4">
        <div class="card-header">
            <h2><?php echo htmlspecialchars($pesan_riwayat); ?></h2>
        </div>
        <div class="card-body">
            <?php if (empty($laporan_riwayat)): ?>
                <p>Anda belum memiliki riwayat laporan.</p>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table-laporan">
                        <thead>
                            <tr>
                                <th>ID Laporan</th>
                                <th>Jenis</th>
                                <th>Perusahaan</th>
                                <th>Tanggal Sampling</th>
                                <th>Status Terakhir</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($laporan_riwayat as $laporan): ?>
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
    <?php endif; ?>
    
</div>

<?php
require_once '../templates/footer.php';
?>