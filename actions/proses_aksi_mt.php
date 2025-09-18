<?php
require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) { header("Location: " . BASE_URL . "/login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $aksi = $_POST['aksi'];
    $catatan_revisi_mt = $_POST['catatan_revisi_mt'] ?? '';
    $mt_id = $_SESSION['user_id'];

    if ($aksi == 'setuju') {
        $sql = "UPDATE laporan SET status = 'Disetujui, Siap Dicetak', mt_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $mt_id, $laporan_id);
    } elseif ($aksi == 'revisi') {
        if (empty($catatan_revisi_mt)) {
            $_SESSION['error_message'] = "Catatan revisi wajib diisi.";
            header("Location: " . BASE_URL . "/detail_laporan.php?id=" . $laporan_id);
            exit();
        }
        $sql = "UPDATE laporan SET status = 'Revisi Penyelia', mt_id = ?, catatan_revisi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("isi", $mt_id, $catatan_revisi_mt, $laporan_id);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: " . BASE_URL . "/dashboard.php?status=aksi_mt_sukses");
        exit();
    }
}
header("Location: " . BASE_URL . "/dashboard.php");
exit();
?>