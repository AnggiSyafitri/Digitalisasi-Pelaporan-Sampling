<?php
require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) { header("Location: " . BASE_URL . "/login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $aksi = $_POST['aksi'];
    $catatan_revisi = $_POST['catatan_revisi'];
    $penyelia_id = $_SESSION['user_id'];

    if ($aksi == 'setuju') {
        $sql = "UPDATE laporan SET status = 'Menunggu Persetujuan MT', penyelia_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $penyelia_id, $laporan_id);
    } elseif ($aksi == 'revisi') {
        if (empty($catatan_revisi)) { die("Catatan revisi wajib diisi jika laporan dikembalikan."); }
        $sql = "UPDATE laporan SET status = 'Revisi PPC', penyelia_id = ?, catatan_revisi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $penyelia_id, $catatan_revisi, $laporan_id);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: " . BASE_URL . "/dashboard.php?status=verifikasi_sukses");
        exit();
    }
}
header("Location: " . BASE_URL . "/dashboard.php");
exit();
?>