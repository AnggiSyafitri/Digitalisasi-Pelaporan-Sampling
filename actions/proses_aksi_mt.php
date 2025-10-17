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
    $mt_nama = $_SESSION['nama_lengkap']; // Ambil nama MT untuk notifikasi

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

            // === BLOK BARU: Notifikasi untuk Penerima Contoh ===
            $pesan = "Laporan #{$laporan_id} telah disetujui oleh Manajer Teknis dan siap untuk dicetak.";
            // Kirim ke role_id = 4 (Penerima Contoh)
            buatNotifikasiUntukRole($conn, 4, $pesan, $laporan_id);
            // === AKHIR BLOK BARU ===

        } elseif ($aksi == 'revisi') {
            // Jika dikembalikan untuk revisi
            if (empty($catatan_revisi_mt)) {
                throw new Exception("Catatan revisi wajib diisi jika laporan dikembalikan.");
            }

            // Ambil status dan ID PPC pembuat laporan
            $stmt_cek = $conn->prepare("SELECT status, ppc_id FROM laporan WHERE id = ?");
            $stmt_cek->bind_param("i", $laporan_id);
            $stmt_cek->execute();
            $laporan_lama = $stmt_cek->get_result()->fetch_assoc();
            $status_awal = $laporan_lama['status'];
            $ppc_penerima_id = $laporan_lama['ppc_id']; // ID PPC yang akan menerima notifikasi
            $stmt_cek->close();

            $status_tujuan = 'Revisi PPC'; 

            // Update status, KOSONGKAN ID MT, dan simpan catatan revisi
            $sql_update = "UPDATE laporan SET status = ?, mt_id = NULL, catatan_revisi = ? WHERE id = ?";
            $stmt_update = $conn->prepare($sql_update);
            $stmt_update->bind_param("ssi", $status_tujuan, $catatan_revisi_mt, $laporan_id);
            $stmt_update->execute();
            $stmt_update->close();

            // Masukkan catatan ke tabel riwayat_revisi
            $sql_riwayat = "INSERT INTO riwayat_revisi (laporan_id, revisi_oleh_id, catatan_revisi, status_awal, status_tujuan) VALUES (?, ?, ?, ?, ?)";
            $stmt_riwayat = $conn->prepare($sql_riwayat);
            $stmt_riwayat->bind_param("iisss", $laporan_id, $mt_id, $catatan_revisi_mt, $status_awal, $status_tujuan);
            $stmt_riwayat->execute();
            $stmt_riwayat->close();
            
            // === BLOK BARU: Notifikasi untuk PPC ===
            $pesan = "Laporan #{$laporan_id} dikembalikan oleh Manajer Teknis ({$mt_nama}) untuk direvisi.";
            // Kirim notifikasi ke PPC yang bersangkutan
            buatNotifikasi($conn, $ppc_penerima_id, $pesan, $laporan_id);
            // === AKHIR BLOK BARU ===
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