<?php
// actions/update_laporan.php

require_once '../app/config.php';

// Keamanan: Pastikan hanya PPC yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['laporan_id']) && isset($_POST['form_id'])) {
    $laporan_id = (int)$_POST['laporan_id'];
    $form_id = (int)$_POST['form_id'];
    $ppc_id = $_SESSION['user_id'];

    // Ambil data formulir utama dari POST
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    $perusahaan = $_POST['perusahaan'];
    $alamat = $_POST['alamat'];
    $tanggal = $_POST['tanggal'];
    $pengambil_sampel = $_POST['pengambil_sampel'];
    $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

    $conn->begin_transaction();

    try {
        // 1. Update data utama di tabel `formulir`
        $sql_form = "UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ? WHERE id = ?";
        $stmt_form = $conn->prepare($sql_form);
        $stmt_form->bind_param("ssssssi", $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $form_id);
        $stmt_form->execute();
        $stmt_form->close();

        // 2. Hapus semua contoh lama yang terkait dengan formulir ini
        $sql_delete_contoh = "DELETE FROM contoh WHERE formulir_id = ?";
        $stmt_delete = $conn->prepare($sql_delete_contoh);
        $stmt_delete->bind_param("i", $form_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // 3. Masukkan kembali semua data contoh yang baru dari form
        if (isset($_POST['contoh'])) {
            $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            foreach ($_POST['contoh'] as $item) {
                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                $catatan = $item['catatan'] ?? '';

                $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();
        }

        // 4. Update status laporan kembali ke 'Menunggu Verifikasi' dan bersihkan catatan revisi
        $sql_laporan = "UPDATE laporan SET status = 'Menunggu Verifikasi', catatan_revisi = NULL WHERE id = ?";
        $stmt_laporan = $conn->prepare($sql_laporan);
        $stmt_laporan->bind_param("i", $laporan_id);
        $stmt_laporan->execute();
        $stmt_laporan->close();
        
        // 5. Update tabel riwayat_revisi untuk menandai revisi telah diperbaiki
        // Ambil ID riwayat terakhir yang belum diperbaiki untuk laporan ini
        $sql_get_riwayat = "SELECT id FROM riwayat_revisi WHERE laporan_id = ? AND tanggal_diperbaiki IS NULL ORDER BY tanggal_revisi_diminta DESC LIMIT 1";
        $stmt_get_riwayat = $conn->prepare($sql_get_riwayat);
        $stmt_get_riwayat->bind_param("i", $laporan_id);
        $stmt_get_riwayat->execute();
        $result_riwayat = $stmt_get_riwayat->get_result();
        if ($riwayat_item = $result_riwayat->fetch_assoc()) {
            $riwayat_id = $riwayat_item['id'];
            $sql_update_riwayat = "UPDATE riwayat_revisi SET tanggal_diperbaiki = NOW() WHERE id = ?";
            $stmt_update_riwayat = $conn->prepare($sql_update_riwayat);
            $stmt_update_riwayat->bind_param("i", $riwayat_id);
            $stmt_update_riwayat->execute();
            $stmt_update_riwayat->close();
        }
        $stmt_get_riwayat->close();


        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=update_sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi error saat memperbarui data: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}
?>
