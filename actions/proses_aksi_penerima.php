<?php
require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) { header("Location: " . BASE_URL . "/login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['laporan_id'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $penerima_id = $_SESSION['user_id'];
    $penerima_nama = $_SESSION['nama_lengkap']; // Ambil nama untuk notifikasi

    // === BLOK BARU: Ambil ID PPC untuk notifikasi ===
    $stmt_get_ppc = $conn->prepare("SELECT ppc_id FROM laporan WHERE id = ?");
    $stmt_get_ppc->bind_param("i", $laporan_id);
    $stmt_get_ppc->execute();
    $result = $stmt_get_ppc->get_result();
    $laporan_data = $result->fetch_assoc();
    $ppc_penerima_id = $laporan_data['ppc_id'] ?? null;
    $stmt_get_ppc->close();
    // === AKHIR BLOK BARU ===

    $sql = "UPDATE laporan SET status = 'Selesai', penerima_id = ?, waktu_penyelesaian_penerima = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $penerima_id, $laporan_id);

    if ($stmt->execute()) {
        // === BLOK BARU: Buat Notifikasi untuk PPC ===
        $pesan = "Laporan #{$laporan_id} yang Anda buat telah diselesaikan oleh {$penerima_nama}.";
        buatNotifikasi($conn, $ppc_penerima_id, $pesan, $laporan_id);
        // === AKHIR BLOK BARU ===

        header("Location: " . BASE_URL . "/dashboard.php?status=cetak_sukses");
        exit();
    }
}
header("Location: " . BASE_URL . "/dashboard.php");
exit();
?>