<?php
session_start();
include 'koneksi.php';

// Cek sesi login & peran
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 2) {
    header("Location: login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $aksi = $_POST['aksi'];
    $catatan_revisi = $_POST['catatan_revisi'];
    $penyelia_id = $_SESSION['user_id'];

    $new_status = '';
    $sql = '';

    if ($aksi == 'setuju') {
        $new_status = 'Menunggu Persetujuan MT';
        $sql = "UPDATE laporan SET status = ?, penyelia_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $penyelia_id, $laporan_id);

    } elseif ($aksi == 'revisi') {
        if (empty($catatan_revisi)) {
            die("Catatan revisi wajib diisi jika laporan dikembalikan.");
        }
        $new_status = 'Revisi PPC';
        $sql = "UPDATE laporan SET status = ?, penyelia_id = ?, catatan_revisi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $new_status, $penyelia_id, $catatan_revisi, $laporan_id);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: dashboard.php?status=verifikasi_sukses");
        exit();
    } else {
        echo "Error: Gagal memperbarui status laporan.";
        // echo $conn->error; // Uncomment for debugging
    }

} else {
    header("Location: dashboard.php");
    exit();
}
?>