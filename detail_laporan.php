<?php
session_start();
include 'koneksi.php';

// Cek sesi login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Ambil ID Laporan dari URL
if (!isset($_GET['id'])) {
    die("Error: ID Laporan tidak ditemukan.");
}
$laporan_id = (int)$_GET['id'];
$role_id = $_SESSION['role_id'];

// Ambil data Laporan & Formulir Utama
$sql_utama = "SELECT l.*, f.*, u_ppc.nama_lengkap as nama_ppc
              FROM laporan l
              JOIN formulir_air f ON l.form_id = f.id AND l.jenis_laporan = 'air'
              JOIN users u_ppc ON l.ppc_id = u_ppc.id
              WHERE l.id = ?";
$stmt_utama = $conn->prepare($sql_utama);
$stmt_utama->bind_param("i", $laporan_id);
$stmt_utama->execute();
$result_utama = $stmt_utama->get_result();
$data_laporan = $result_utama->fetch_assoc();

if (!$data_laporan) {
    die("Data laporan tidak ditemukan.");
}

// Ambil data detail contoh air
$sql_contoh = "SELECT * FROM contoh_air WHERE formulir_id = ?";
$stmt_contoh = $conn->prepare($sql_contoh);
$stmt_contoh->bind_param("i", $data_laporan['form_id']);
$stmt_contoh->execute();
$result_contoh = $stmt_contoh->get_result();
$data_contoh = [];
while($row = $result_contoh->fetch_assoc()){
    $data_contoh[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Detail Laporan #<?php echo $laporan_id; ?></title>
    <link rel="stylesheet" href="css/styles.css"> <style>
        body { font-family: Arial, sans-serif; background-color: #f4f7f6; }
        .container { max-width: 960px; margin: 20px auto; padding: 20px; background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
        .report-header h1 { margin-top: 0; }
        .meta-info { background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .meta-info p { margin: 5px 0; }
        .table-detail { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table-detail th, .table-detail td { border: 1px solid #ddd; padding: 10px; text-align: left; }
        .action-box { margin-top: 30px; padding: 20px; border: 1px solid #ddd; border-radius: 5px; background: #f9f9f9; }
        .action-box h3 { margin-top: 0; }
        .btn { padding: 10px 15px; color: white; text-decoration: none; border-radius: 5px; border: none; cursor: pointer; }
        .btn-success { background-color: #28a745; }
        .btn-danger { background-color: #dc3545; }
        textarea { width: 100%; padding: 8px; min-height: 80px; border: 1px solid #ccc; border-radius: 4px; }
    </style>
</head>
<body>
<div class="container">
    <a href="dashboard.php">&laquo; Kembali ke Dashboard</a>
    <div class="report-header">
        <h1>Detail Laporan #<?php echo htmlspecialchars($data_laporan['id']); ?></h1>
        <p>Status Saat Ini: <strong><?php echo htmlspecialchars($data_laporan['status']); ?></strong></p>
    </div>

    <div class="meta-info">
        <p><strong>Perusahaan:</strong> <?php echo htmlspecialchars($data_laporan['perusahaan']); ?></p>
        <p><strong>Alamat:</strong> <?php echo htmlspecialchars($data_laporan['alamat']); ?></p>
        <p><strong>Tanggal Sampling:</strong> <?php echo date('d F Y', strtotime($data_laporan['tanggal'])); ?></p>
        <p><strong>Dibuat oleh (PPC):</strong> <?php echo htmlspecialchars($data_laporan['nama_ppc']); ?></p>
    </div>

    <h3>Data Contoh Uji</h3>
    <?php foreach ($data_contoh as $contoh): ?>
    <table class="table-detail">
        <tr><th width="30%">Nama Contoh</th><td><?php echo htmlspecialchars($contoh['nama_contoh']); ?></td></tr>
        <tr><th>Jenis Contoh</th><td><?php echo htmlspecialchars($contoh['jenis_contoh']); ?></td></tr>
        <tr><th>Etiket / Merek</th><td><?php echo htmlspecialchars($contoh['merek']); ?></td></tr>
        <tr><th>Kode</th><td><?php echo htmlspecialchars($contoh['kode']); ?></td></tr>
        <tr><th>Prosedur</th><td><?php echo htmlspecialchars($contoh['prosedur']); ?></td></tr>
        <tr><th>Parameter</th><td><?php echo htmlspecialchars($contoh['parameter']); ?></td></tr>
        <tr><th>Baku Mutu</th><td><?php echo htmlspecialchars($contoh['baku_mutu']); ?></td></tr>
        <tr><th>Catatan</th><td><?php echo nl2br(htmlspecialchars($contoh['catatan'])); ?></td></tr>
    </table>
    <hr>
    <?php endforeach; ?>

    <?php // Tampilkan kotak aksi hanya untuk Penyelia dan jika statusnya 'Menunggu Verifikasi'
    if ($role_id == 2 && $data_laporan['status'] == 'Menunggu Verifikasi'): ?>
    <div class="action-box">
        <h3>Tindakan Verifikasi</h3>
        <p>Silakan periksa kelengkapan dan kebenaran data laporan ini.</p>
        <form action="proses_verifikasi.php" method="POST">
            <input type="hidden" name="laporan_id" value="<?php echo $laporan_id; ?>">
            <label for="catatan_revisi">Catatan (wajib diisi jika dikembalikan):</label>
            <textarea name="catatan_revisi" id="catatan_revisi"></textarea><br><br>
            <button type="submit" name="aksi" value="setuju" class="btn btn-success">Setujui & Teruskan ke Manajer Teknis</button>
            <button type="submit" name="aksi" value="revisi" class="btn btn-danger">Kembalikan ke PPC untuk Revisi</button>
        </form>
    </div>
    <?php endif; ?>
</div>
</body>
</html>