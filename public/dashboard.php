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
$pesan_riwayat = "Riwayat Laporan";

// TAMBAHAN UNTUK FITUR PENCARIAN DAN FILTER
$search_conditions = [];
$search_params = [];
$search_types = "";

// Proses filter dari GET parameters
if (!empty($_GET['search_id'])) {
    $search_conditions[] = "l.id LIKE ?";
    $search_params[] = "%" . $_GET['search_id'] . "%";
    $search_types .= "s";
}

if (!empty($_GET['filter_jenis_laporan'])) {
    $search_conditions[] = "l.jenis_laporan = ?"; // Mengambil dari tabel laporan
    $search_params[] = $_GET['filter_jenis_laporan'];
    $search_types .= "s";
}

if (!empty($_GET['search_perusahaan'])) {
    $search_conditions[] = "f.perusahaan LIKE ?";
    $search_params[] = "%" . $_GET['search_perusahaan'] . "%";
    $search_types .= "s";
}

if (!empty($_GET['tanggal_mulai'])) {
    // Laporan yang tanggal mulainya setelah atau sama dengan tanggal filter
    $search_conditions[] = "f.tanggal_mulai >= ?";
    $search_params[] = $_GET['tanggal_mulai'];
    $search_types .= "s";
}

if (!empty($_GET['tanggal_akhir'])) {
    // Laporan yang tanggal selesainya sebelum atau sama dengan tanggal filter
    $search_conditions[] = "f.tanggal_selesai <= ?";
    $search_params[] = $_GET['tanggal_akhir'];
    $search_types .= "s";
}

if (!empty($_GET['filter_status'])) {
    $search_conditions[] = "l.status = ?";
    $search_params[] = $_GET['filter_status'];
    $search_types .= "s";
}

$query_part = "
    SELECT l.id, l.jenis_laporan, l.status, f.perusahaan, f.tanggal_mulai, f.tanggal_selesai, f.jenis_kegiatan, l.ppc_id, l.updated_at
    FROM laporan l
    JOIN formulir f ON l.form_id = f.id
";

$where_clause = "";
$params = [];
$types = "";

switch ($role_id) {
    case 1: // PPC
        $pesan_dashboard = "Riwayat Laporan yang Anda Buat";
        $base_where = " WHERE l.ppc_id = ?";
        $base_order = " ORDER BY l.status = 'Revisi PPC' DESC, l.updated_at DESC";
        $types = "i";
        $params[] = $user_id;
        break;

    case 2: // Penyelia
        $pesan_dashboard = "Laporan yang Membutuhkan Verifikasi Anda";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Proses";
        $base_where = " WHERE l.status = 'Menunggu Verifikasi'";
        $base_order = " ORDER BY l.updated_at ASC";
        
        $sql_riwayat = $query_part . " WHERE l.penyelia_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;

    case 3: // Manajer Teknis
        $pesan_dashboard = "Laporan yang Membutuhkan Persetujuan Anda";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Setujui/Revisi";
        $base_where = " WHERE l.status = 'Menunggu Persetujuan MT'";
        $base_order = " ORDER BY l.updated_at ASC";

        $sql_riwayat = $query_part . " WHERE l.mt_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;

    case 4: // Penerima Contoh
        $pesan_dashboard = "Laporan Final yang Siap Dicetak";
        $pesan_riwayat = "Riwayat Laporan yang Telah Anda Selesaikan";
        $base_where = " WHERE l.status = 'Disetujui, Siap Dicetak'";
        $base_order = " ORDER BY l.updated_at ASC";

        $sql_riwayat = $query_part . " WHERE l.penerima_id = ? ORDER BY l.updated_at DESC";
        $stmt_riwayat = $conn->prepare($sql_riwayat);
        $stmt_riwayat->bind_param("i", $user_id);
        break;
}

// LOGIKA PENGGABUNGAN PENCARIAN
$where_clause = $base_where;

// Gabungkan kondisi pencarian jika ada
if (!empty($search_conditions)) {
    $where_clause .= " AND " . implode(" AND ", $search_conditions);
    $params = array_merge($params, $search_params);
    $types .= $search_types;
}

// Tambahkan ORDER BY di akhir
$where_clause .= $base_order;


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
    
    <!-- FORM PENCARIAN DAN FILTER -->
    <div class="card mb-4">
        <div class="card-header">
            <h5><i class="fas fa-search"></i> Pencarian dan Filter Laporan</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="dashboard.php" class="row g-3">
                <!-- Pencarian ID Laporan -->
                <div class="col-md-2">
                    <label for="search_id" class="form-label">ID Laporan</label>
                    <input type="text" class="form-control" id="search_id" name="search_id" 
                           placeholder="Cari ID..." value="<?php echo isset($_GET['search_id']) ? htmlspecialchars($_GET['search_id']) : ''; ?>">
                </div>
                
                <!-- Filter Jenis Kegiatan -->
                <div class="col-md-2">
                    <label for="filter_jenis_laporan" class="form-label">Jenis Laporan</label>
                    <select class="form-control" id="filter_jenis_laporan" name="filter_jenis_laporan">
                        <option value="">Semua Jenis</option>
                        <option value="air" <?php echo (isset($_GET['filter_jenis_laporan']) && $_GET['filter_jenis_laporan'] == 'air') ? 'selected' : ''; ?>>Air</option>
                        <option value="udara" <?php echo (isset($_GET['filter_jenis_laporan']) && $_GET['filter_jenis_laporan'] == 'udara') ? 'selected' : ''; ?>>Udara</option>
                        <option value="kebisingan" <?php echo (isset($_GET['filter_jenis_laporan']) && $_GET['filter_jenis_laporan'] == 'kebisingan') ? 'selected' : ''; ?>>Kebisingan</option>
                        <option value="getaran" <?php echo (isset($_GET['filter_jenis_laporan']) && $_GET['filter_jenis_laporan'] == 'getaran') ? 'selected' : ''; ?>>Getaran</option>
                    </select>
                </div>
                
                <!-- Pencarian Perusahaan -->
                <div class="col-md-2">
                    <label for="search_perusahaan" class="form-label">Perusahaan</label>
                    <input type="text" class="form-control" id="search_perusahaan" name="search_perusahaan" 
                           placeholder="Nama perusahaan..." value="<?php echo isset($_GET['search_perusahaan']) ? htmlspecialchars($_GET['search_perusahaan']) : ''; ?>">
                </div>
                
                <!-- Filter Tanggal Mulai -->
                <div class="col-md-2">
                    <label for="tanggal_mulai" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="tanggal_mulai" name="tanggal_mulai" 
                           value="<?php echo isset($_GET['tanggal_mulai']) ? $_GET['tanggal_mulai'] : ''; ?>">
                </div>
                
                <!-- Filter Tanggal Akhir -->
                <div class="col-md-2">
                    <label for="tanggal_akhir" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="tanggal_akhir" name="tanggal_akhir" 
                           value="<?php echo isset($_GET['tanggal_akhir']) ? $_GET['tanggal_akhir'] : ''; ?>">
                </div>
                
                <!-- Filter Status -->
                <div class="col-md-2">
                    <label for="filter_status" class="form-label">Status</label>
                    <select class="form-control" id="filter_status" name="filter_status">
                        <option value="">Semua Status</option>
                        <option value="Draft" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Draft') ? 'selected' : ''; ?>>Draft</option>
                        <option value="Menunggu Verifikasi" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Menunggu Verifikasi') ? 'selected' : ''; ?>>Menunggu Verifikasi</option>
                        <option value="Revisi PPC" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Revisi PPC') ? 'selected' : ''; ?>>Revisi PPC</option>
                        <option value="Revisi Penyelia" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Revisi Penyelia') ? 'selected' : ''; ?>>Revisi Penyelia</option>
                        <option value="Menunggu Persetujuan MT" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Menunggu Persetujuan MT') ? 'selected' : ''; ?>>Menunggu Persetujuan MT</option>
                        <option value="Disetujui, Siap Dicetak" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Disetujui, Siap Dicetak') ? 'selected' : ''; ?>>Disetujui, Siap Dicetak</option>
                        <option value="Selesai" <?php echo (isset($_GET['filter_status']) && $_GET['filter_status'] == 'Selesai') ? 'selected' : ''; ?>>Selesai</option>
                    </select>
                </div>
                
                <!-- Tombol Action -->
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                        </svg>
                        Cari
                    </button>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-arrow-clockwise" viewBox="0 0 16 16">
                            <path fill-rule="evenodd" d="M8 3a5 5 0 1 0 4.546 2.914.5.5 0 0 1 .908-.417A6 6 0 1 1 8 2v1z"/>
                            <path d="M8 4.466V.534a.25.25 0 0 1 .41-.192l2.36 1.966c.12.1.12.284 0 .384L8.41 4.658A.25.25 0 0 1 8 4.466z"/>
                        </svg>
                        Reset
                    </a>
                    <span class="ms-3 text-muted">
                        <small>
                            <?php 
                            $total_results = count($daftar_laporan);
                            if (!empty($_GET['search_id']) || !empty($_GET['filter_jenis']) || !empty($_GET['search_perusahaan']) || 
                                !empty($_GET['tanggal_mulai']) || !empty($_GET['tanggal_akhir']) || !empty($_GET['filter_status'])) {
                                echo "Hasil pencarian: {$total_results} laporan";
                            } else {
                                echo "Total: {$total_results} laporan";
                            }
                            ?>
                        </small>
                    </span>
                </div>
            </form>
        </div>
    </div>

    <?php if ($role_id == 1): ?>

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
                                <td><?php echo date('d M Y', strtotime($laporan['tanggal_mulai'])); ?></td>
                                <td>
                                    <span class="status <?php echo 'status-' . strtolower(str_replace(' ', '-', $laporan['status'])); ?>">
                                        <?php echo htmlspecialchars($laporan['status']); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php // Tampilkan tombol 'Edit' hanya untuk PPC jika statusnya 'Revisi PPC' ?>
                                    <?php if ($role_id == 1 && in_array($laporan['status'], ['Draft', 'Revisi PPC'])): ?>
                                        <a href="edit_laporan.php?laporan_id=<?php echo $laporan['id']; ?>" class="btn btn-warning">
                                            Edit
                                        </a>
                                    <?php elseif ($laporan['status'] == 'Selesai'): ?>
                                        <a href="detail_laporan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary-dashboard">
                                            Lihat Detail
                                        </a>
                                        <a href="cetak_laporan.php?id=<?php echo $laporan['id']; ?>" target="_blank" class="btn btn-info btn-sm" style="margin-left: 5px;">
                                            Cetak PDF
                                        </a>
                                    <?php else: ?>
                                        <a href="detail_laporan.php?id=<?php echo $laporan['id']; ?>" class="btn btn-secondary-dashboard">
                                            Lihat Detail
                                        </a>
                                    <?php endif; ?>
                                </td>

                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Informasi Hasil Pencarian -->
                <?php if (!empty($_GET['search_id']) || !empty($_GET['filter_jenis']) || !empty($_GET['search_perusahaan']) || 
                           !empty($_GET['tanggal_mulai']) || !empty($_GET['tanggal_akhir']) || !empty($_GET['filter_status'])): ?>
                    <div class="mt-3">
                        <small class="text-muted">
                            <strong>Kriteria Pencarian:</strong>
                            <?php if (!empty($_GET['search_id'])): ?>
                                ID: "<?php echo htmlspecialchars($_GET['search_id']); ?>"
                            <?php endif; ?>
                            <?php if (!empty($_GET['filter_jenis'])): ?>
                                | Jenis: <?php echo htmlspecialchars($_GET['filter_jenis']); ?>
                            <?php endif; ?>
                            <?php if (!empty($_GET['search_perusahaan'])): ?>
                                | Perusahaan: "<?php echo htmlspecialchars($_GET['search_perusahaan']); ?>"
                            <?php endif; ?>
                            <?php if (!empty($_GET['tanggal_mulai']) && !empty($_GET['tanggal_akhir'])): ?>
                                | Periode: <?php echo date('d M Y', strtotime($_GET['tanggal_mulai'])); ?> - <?php echo date('d M Y', strtotime($_GET['tanggal_akhir'])); ?>
                            <?php elseif (!empty($_GET['tanggal_mulai'])): ?>
                                | Dari: <?php echo date('d M Y', strtotime($_GET['tanggal_mulai'])); ?>
                            <?php elseif (!empty($_GET['tanggal_akhir'])): ?>
                                | Sampai: <?php echo date('d M Y', strtotime($_GET['tanggal_akhir'])); ?>
                            <?php endif; ?>
                            <?php if (!empty($_GET['filter_status'])): ?>
                                | Status: <?php echo htmlspecialchars($_GET['filter_status']); ?>
                            <?php endif; ?>
                        </small>
                    </div>
                <?php endif; ?>
                
                <?php if (empty($daftar_laporan) && (!empty($_GET['search_id']) || !empty($_GET['filter_jenis']) || !empty($_GET['search_perusahaan']) || 
                           !empty($_GET['tanggal_mulai']) || !empty($_GET['tanggal_akhir']) || !empty($_GET['filter_status']))): ?>
                    <div class="alert alert-info mt-3">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-info-circle" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z"/>
                            <path d="m8.93 6.588-2.29.287-.082.38.45.083c.294.07.352.176.288.469l-.738 3.468c-.194.897.105 1.319.808 1.319.545 0 1.178-.252 1.465-.598l.088-.416c-.2.176-.492.246-.686.246-.275 0-.375-.193-.304-.533L8.93 6.588zM9 4.5a1 1 0 1 1-2 0 1 1 0 0 1 2 0z"/>
                        </svg>
                        Tidak ditemukan laporan yang sesuai kriteria pencarian. <a href="dashboard.php">Reset filter</a> untuk melihat semua laporan.
                    </div>
                <?php endif; ?>
                
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
                                <td><?php echo date('d M Y', strtotime($laporan['tanggal_mulai'])); ?></td>
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
