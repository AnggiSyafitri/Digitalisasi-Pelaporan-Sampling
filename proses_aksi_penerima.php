<?php
session_start();
include 'koneksi.php';

// Cek sesi login & peran Penerima Contoh (role_id = 4)
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) {
    header("Location: login.php");
    exit();
}

// Pastikan metode adalah POST dan ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['laporan_id'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $penerima_id = $_SESSION['user_id'];
    $new_status = 'Selesai';

    // Update status laporan menjadi 'Selesai'
    $sql = "UPDATE laporan SET status = ?, penerima_id = ? WHERE id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param("sii", $new_status, $penerima_id, $laporan_id);
        if ($stmt->execute()) {
            // Berhasil, redirect kembali ke dashboard
            header("Location: dashboard.php?status=cetak_sukses");
            exit();
        }
    }

    // Jika gagal
    die("Error: Gagal memperbarui status laporan.");

} else {
    // Jika diakses secara tidak semestinya
    header("Location: dashboard.php");
    exit();
}
?>