<?php
// actions/tandai_dibaca.php

require_once '../app/config.php';

// Pastikan user sudah login
if (!isset($_SESSION['user_id'])) {
    // Kirim response error jika tidak ada sesi
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Akses ditolak.']);
    exit();
}

// Mengambil data JSON yang dikirim dari JavaScript
$data = json_decode(file_get_contents('php://input'), true);
$user_id = $_SESSION['user_id'];
$mode = $data['mode'] ?? 'satu'; // 'satu' atau 'semua'
$notif_id = $data['notif_id'] ?? null;

header('Content-Type: application/json');

if ($mode === 'semua') {
    // Tandai semua notifikasi milik user sebagai sudah dibaca
    $sql = "UPDATE notifikasi SET sudah_dibaca = 1 WHERE user_id = ? AND sudah_dibaca = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menandai semua notifikasi.']);
    }
    $stmt->close();

} elseif ($mode === 'satu' && !empty($notif_id)) {
    // Tandai satu notifikasi spesifik sebagai sudah dibaca
    $sql = "UPDATE notifikasi SET sudah_dibaca = 1 WHERE id = ? AND user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $notif_id, $user_id);
    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Gagal menandai notifikasi.']);
    }
    $stmt->close();

} else {
    echo json_encode(['success' => false, 'message' => 'Permintaan tidak valid.']);
}

exit();
?>