<?php
require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 4) { header("Location: " . BASE_URL . "/login.php"); exit(); }

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['laporan_id'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $penerima_id = $_SESSION['user_id'];

    $sql = "UPDATE laporan SET status = 'Selesai', penerima_id = ?, waktu_penyelesaian_penerima = NOW() WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $penerima_id, $laporan_id);

    if ($stmt->execute()) {
        header("Location: " . BASE_URL . "/dashboard.php?status=cetak_sukses");
        exit();
    }
}
header("Location: " . BASE_URL . "/dashboard.php");
exit();
?>