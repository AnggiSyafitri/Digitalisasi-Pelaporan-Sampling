<?php
require_once '../app/config.php';

// Keamanan: Pastikan hanya Manajer Teknis (role_id 3) yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 3) { 
    header("Location: " . BASE_URL . "/login.php"); 
    exit(); 
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $laporan_id = (int)$_POST['laporan_id'];
    $aksi = $_POST['aksi'];
    $catatan_revisi_mt = $_POST['catatan_revisi_mt'] ?? '';
    $mt_id = $_SESSION['user_id'];

    $conn->begin_transaction();

    try {
        if ($aksi == 'setuju') {
            // Ambil TTD MT dari tabel user
            $stmt_ttd = $conn->prepare("SELECT tanda_tangan FROM users WHERE id = ?");
            $stmt_ttd->bind_param("i", $mt_id);
            $stmt_ttd->execute();
            $ttd_file = $stmt_ttd->get_result()->fetch_assoc()['tanda_tangan'];
            $stmt_ttd->close();

            if (empty($ttd_file)) {
                throw new Exception("Aksi ditolak. Anda harus mengunggah tanda tangan di halaman profil terlebih dahulu.");
            }

            // Jika disetujui, update status, catat waktu, simpan TTD, dan pastikan catatan revisi bersih
            $sql = "UPDATE laporan SET status = 'Disetujui, Siap Dicetak', mt_id = ?, waktu_persetujuan_mt = NOW(), ttd_mt = ?, catatan_revisi = NULL WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isi", $mt_id, $ttd_file, $laporan_id);
            $stmt->execute();

        } elseif ($aksi == 'revisi') {
            // Jika dikembalikan untuk revisi
            if (empty($catatan_revisi_mt)) {
                // Gunakan exception agar bisa ditangkap oleh blok catch
                throw new Exception("Catatan revisi wajib diisi jika laporan dikembalikan.");
            }

            // 1. Ambil status laporan saat ini sebelum diubah
            $stmt_cek = $conn->prepare("SELECT status FROM laporan WHERE id = ?");
            $stmt_cek->bind_param("i", $laporan_id);
            $stmt_cek->execute();
            $result_cek = $stmt_cek->get_result();
            $laporan_lama = $result_cek->fetch_assoc();
            $status_awal = $laporan_lama['status'];
            $stmt_cek->close();

            // Sesuai alur, laporan langsung kembali ke PPC untuk diedit
            $status_tujuan = 'Revisi PPC'; 

            // 2. Update status, tapi KOSONGKAN ID MT karena direvisi
            $sql_update = "UPDATE laporan SET status = ?, mt_id = NULL, catatan_revisi = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssi", $status_tujuan, $catatan_revisi_mt, $laporan_id);
            $stmt_update->execute();
            $stmt_update->close();

            // 3. Masukkan catatan ke tabel riwayat_revisi
            $sql_riwayat = "INSERT INTO riwayat_revisi (laporan_id, revisi_oleh_id, catatan_revisi, status_awal, status_tujuan) VALUES (?, ?, ?, ?, ?)";
            $stmt_riwayat = $conn->prepare($sql_riwayat);
            $stmt_riwayat->bind_param("iisss", $laporan_id, $mt_id, $catatan_revisi_mt, $status_awal, $status_tujuan);
            $stmt_riwayat->execute();
            $stmt_riwayat->close();
        }

        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=aksi_mt_sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Simpan pesan error ke session untuk ditampilkan di halaman detail
        $_SESSION['error_message'] = $e->getMessage();
        header("Location: " . BASE_URL . "/detail_laporan.php?id=" . $laporan_id);
        exit();
    }
}

// Jika akses langsung tanpa metode POST, kembalikan ke dashboard
header("Location: " . BASE_URL . "/dashboard.php");
exit();
?>