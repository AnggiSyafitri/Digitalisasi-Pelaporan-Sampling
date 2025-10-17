<?php
// app/functions.php

/**
 * Membuat notifikasi baru untuk seorang user.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $user_id ID user yang akan menerima notifikasi.
 * @param string $pesan Isi pesan notifikasi.
 * @param int $laporan_id ID laporan yang terkait dengan notifikasi.
 * @return void
 */
function buatNotifikasi($conn, $user_id, $pesan, $laporan_id) {
    // Pastikan user_id valid sebelum membuat notifikasi
    if (empty($user_id)) {
        return; // Jangan buat notifikasi jika tidak ada penerima
    }

    $sql = "INSERT INTO notifikasi (user_id, pesan, laporan_id) VALUES (?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isi", $user_id, $pesan, $laporan_id);
    $stmt->execute();
    $stmt->close();
}

/**
 * Membuat notifikasi untuk semua user dengan role tertentu.
 *
 * @param mysqli $conn Koneksi database.
 * @param int $role_id ID role yang akan menerima notifikasi.
 * @param string $pesan Isi pesan notifikasi.
 * @param int $laporan_id ID laporan yang terkait.
 * @param int|null $exclude_user_id ID user yang dikecualikan (opsional).
 * @return void
 */
function buatNotifikasiUntukRole($conn, $role_id, $pesan, $laporan_id, $exclude_user_id = null) {
    // Ambil semua ID user yang memiliki role_id yang dituju
    $sql = "SELECT id FROM users WHERE role_id = ?";
    
    // Jika ada user yang perlu dikecualikan
    if ($exclude_user_id !== null) {
        $sql .= " AND id != ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $role_id, $exclude_user_id);
    } else {
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $role_id);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Loop dan kirim notifikasi ke setiap user yang ditemukan
    while ($user = $result->fetch_assoc()) {
        buatNotifikasi($conn, $user['id'], $pesan, $laporan_id);
    }
    
    $stmt->close();
}

?>
