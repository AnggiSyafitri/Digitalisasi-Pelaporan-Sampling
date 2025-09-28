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

    // ===== TAHAP 1: VALIDASI FILE DI AWAL SEBELUM KE DATABASE =====
    if (isset($_FILES['contoh'])) {
        $files_data = $_FILES['contoh'];
        foreach ($_POST['contoh'] as $index => $item) {
            // Cek hanya jika ada file baru yang di-upload
            if (isset($files_data['name'][$index]['dokumen_pendukung']) && $files_data['error'][$index]['dokumen_pendukung'] == 0) {
                $nama_asli = $files_data['name'][$index]['dokumen_pendukung'];
                $ukuran_file = $files_data['size'][$index]['dokumen_pendukung'];

                $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
                $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

                // Validasi Format
                if (!in_array($file_ext, $allowed_ext)) {
                    $_SESSION['flash_error'] = "Validasi Gagal: Format file pada Contoh Uji #${index + 1} tidak diizinkan (hanya PDF, JPG, PNG).";
                    // Redirect kembali ke halaman edit
                    header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
                    exit();
                }

                // Validasi Ukuran
                if ($ukuran_file > 5 * 1024 * 1024) { // 5 MB
                    $_SESSION['flash_error'] = "Validasi Gagal: Ukuran file pada Contoh Uji #${index + 1} melebihi batas 5MB.";
                    // Redirect kembali ke halaman edit
                    header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
                    exit();
                }
            }
        }
    }
    // ===== AKHIR TAHAP 1 =====


    // Ambil data formulir utama dari POST
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    $perusahaan = $_POST['perusahaan'];
    $alamat = $_POST['alamat'];
    $tanggal = $_POST['tanggal'];
    $pengambil_sampel = $_POST['pengambil_sampel'];
    $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

    $conn->begin_transaction();

    try {
        // Update data utama di tabel `formulir` (tanpa kolom dokumen_pendukung)
        $sql_form = "UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ? WHERE id = ?";
        $stmt_form = $conn->prepare($sql_form);
        $stmt_form->bind_param("ssssssi", $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $form_id);
        $stmt_form->execute();
        $stmt_form->close();

        // Hapus semua contoh lama yang terkait dengan formulir ini
        // Kita juga perlu menghapus file fisiknya dari server
        $sql_select_old_files = "SELECT dokumen_pendukung FROM contoh WHERE formulir_id = ?";
        $stmt_select = $conn->prepare($sql_select_old_files);
        $stmt_select->bind_param("i", $form_id);
        $stmt_select->execute();
        $result_files = $stmt_select->get_result();
        while ($row = $result_files->fetch_assoc()) {
            if (!empty($row['dokumen_pendukung']) && file_exists('../public/uploads/' . $row['dokumen_pendukung'])) {
                unlink('../public/uploads/' . $row['dokumen_pendukung']);
            }
        }
        $stmt_select->close();

        $sql_delete_contoh = "DELETE FROM contoh WHERE formulir_id = ?";
        $stmt_delete = $conn->prepare($sql_delete_contoh);
        $stmt_delete->bind_param("i", $form_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // Masukkan kembali semua data contoh yang baru dari form
        if (isset($_POST['contoh'])) {
            $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, dokumen_pendukung) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            $files_data = $_FILES['contoh'] ?? [];

            foreach ($_POST['contoh'] as $index => $item) {
                $nama_file_unik = null;
                // Cek apakah file baru diupload untuk item ini (setelah lolos validasi)
                if (isset($files_data['name'][$index]['dokumen_pendukung']) && $files_data['error'][$index]['dokumen_pendukung'] == 0) {
                    $nama_asli = $files_data['name'][$index]['dokumen_pendukung'];
                    $tmp_name = $files_data['tmp_name'][$index]['dokumen_pendukung'];
                    $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
                    $nama_file_unik = uniqid() . '-' . time() . '.' . $file_ext;
                    $upload_path = '../public/uploads/' . $nama_file_unik;
                    if (!move_uploaded_file($tmp_name, $upload_path)) {
                        throw new Exception("Gagal memindahkan file untuk Contoh Uji #${index + 1}.");
                    }
                }

                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                $catatan = $item['catatan'] ?? '';

                $stmt_contoh->bind_param("isssssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan, $nama_file_unik);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();
        }

        // Update status laporan kembali ke 'Menunggu Verifikasi'
        $sql_laporan = "UPDATE laporan SET status = 'Menunggu Verifikasi', catatan_revisi = NULL WHERE id = ?";
        $stmt_laporan = $conn->prepare($sql_laporan);
        $stmt_laporan->bind_param("i", $laporan_id);
        $stmt_laporan->execute();
        $stmt_laporan->close();
        
        // Update tabel riwayat_revisi
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
        $_SESSION['flash_error'] = "Terjadi error saat memperbarui data: " . $e->getMessage();
        header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}
?>
