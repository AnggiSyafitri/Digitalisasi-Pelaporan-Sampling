<?php
session_start();
include 'koneksi.php';

// Cek sesi login & peran Manajer Teknis (role_id = 3)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) {
    header("Location: login.php");
    exit();
}

// Pastikan metode adalah POST dan ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $aksi = $_POST['aksi'];
    $catatan_revisi_mt = $_POST['catatan_revisi_mt'] ?? '';
    $mt_id = $_SESSION['user_id'];
    
    $new_status = '';
    $sql = '';
    
    if ($aksi == 'setuju') {
        $new_status = 'Disetujui, Siap Dicetak';
        $sql = "UPDATE laporan SET status = ?, mt_id = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sii", $new_status, $mt_id, $laporan_id);

    } elseif ($aksi == 'revisi') {
        if (empty($catatan_revisi_mt)) {
            // Sesi untuk pesan error jika catatan kosong
            $_SESSION['error_message'] = "Catatan revisi wajib diisi jika laporan dikembalikan ke Penyelia.";
            header("Location: detail_laporan.php?id=" . $laporan_id);
            exit();
        }
        $new_status = 'Revisi Penyelia';
        // Simpan catatan revisi dan set status baru
        $sql = "UPDATE laporan SET status = ?, mt_id = ?, catatan_revisi = ? WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sisi", $new_status, $mt_id, $catatan_revisi_mt, $laporan_id);
    }

    if ($stmt && $stmt->execute()) {
        header("Location: dashboard.php?status=aksi_mt_sukses");
        exit();
    } else {
        die("Error: Gagal memperbarui status laporan.");
    }

} else {
    header("Location: dashboard.php");
    exit();
}
?>