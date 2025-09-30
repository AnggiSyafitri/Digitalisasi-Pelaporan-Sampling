<?php
// actions/update_laporan.php

require_once '../app/config.php';

// Keamanan: Pastikan hanya PPC yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

/**
 * Fungsi helper yang sama dari simpan_sampling.php
 */
function processUploadedFile($file_info, $index, $file_type_name, &$processed_files) {
    if ($file_info && $file_info['error'] == 0) {
        $nama_asli = $file_info['name'];
        $ukuran_file = $file_info['size'];
        $tmp_name = $file_info['tmp_name'];

        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Format file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " tidak diizinkan.");
        }

        if ($ukuran_file > 5 * 1024 * 1024) { // 5 MB
            throw new Exception("Ukuran file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " melebihi 5MB.");
        }

        $nama_file_unik = uniqid(strtolower(str_replace(' ', '_', $file_type_name)) . '_', true) . '.' . $file_ext;
        $upload_path = '../public/uploads/' . $nama_file_unik;
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            $processed_files[] = $upload_path;
            return $nama_file_unik;
        } else {
            throw new Exception("Gagal memindahkan file ${file_type_name} untuk Contoh Uji #" . ($index + 1) . ".");
        }
    }
    return null;
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['laporan_id']) && isset($_POST['form_id'])) {
    
    $laporan_id = (int)$_POST['laporan_id'];
    $form_id = (int)$_POST['form_id'];
    
    // Tentukan status laporan berdasarkan tombol yang diklik
    $status_laporan = 'Draft'; // Default
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'ajukan') {
        $status_laporan = 'Menunggu Verifikasi';
    }

    $processed_files_tracker = [];
    $conn->begin_transaction();

    try {
        // Data formulir utama dari POST
        $jenis_kegiatan = $_POST['jenis_kegiatan'];
        $perusahaan = $_POST['perusahaan'];
        $alamat = $_POST['alamat'];
        $tanggal = $_POST['tanggal'];
        $pengambil_sampel = $_POST['pengambil_sampel'];
        $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

        // 1. Update data utama di tabel `formulir`
        $sql_form = "UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ? WHERE id = ?";
        $stmt_form = $conn->prepare($sql_form);
        $stmt_form->bind_param("ssssssi", $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $form_id);
        $stmt_form->execute();
        $stmt_form->close();

        // 2. Kumpulkan file lama untuk kemungkinan dihapus
        $old_files_map = [];
        $sql_select_old_files = "SELECT id, file_berita_acara, file_sppc FROM contoh WHERE formulir_id = ?";
        $stmt_select = $conn->prepare($sql_select_old_files);
        $stmt_select->bind_param("i", $form_id);
        $stmt_select->execute();
        $result_files = $stmt_select->get_result();
        while ($row = $result_files->fetch_assoc()) {
            $old_files_map[$row['id']] = [
                'file_berita_acara' => $row['file_berita_acara'],
                'file_sppc' => $row['file_sppc']
            ];
        }
        $stmt_select->close();

        // Hapus entri contoh lama dari database
        $sql_delete_contoh = "DELETE FROM contoh WHERE formulir_id = ?";
        $stmt_delete = $conn->prepare($sql_delete_contoh);
        $stmt_delete->bind_param("i", $form_id);
        $stmt_delete->execute();
        $stmt_delete->close();
        
        // 3. Masukkan kembali semua data contoh yang baru dari form
        if (isset($_POST['contoh'])) {
            $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, file_berita_acara, file_sppc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            $files_data = $_FILES['contoh'] ?? [];

            foreach ($_POST['contoh'] as $index => $item) {
                
                // Proses Berita Acara
                $file_info_ba = isset($files_data['name'][$index]['file_berita_acara']) && !empty($files_data['name'][$index]['file_berita_acara']) ? ['name'=>$files_data['name'][$index]['file_berita_acara'], 'error'=>$files_data['error'][$index]['file_berita_acara'], 'tmp_name'=>$files_data['tmp_name'][$index]['file_berita_acara'], 'size'=>$files_data['size'][$index]['file_berita_acara']] : null;
                $nama_file_ba = processUploadedFile($file_info_ba, $index, 'Berita Acara', $processed_files_tracker);
                if ($nama_file_ba === null) { 
                    $nama_file_ba = $item['file_berita_acara_lama'] ?? null;
                } else {
                    // Jika file baru berhasil diupload, tandai file lama untuk dihapus
                    if (!empty($item['file_berita_acara_lama'])) {
                        $files_to_delete[] = '../public/uploads/' . $item['file_berita_acara_lama'];
                    }
                }

                // Proses SPPC
                $file_info_sppc = isset($files_data['name'][$index]['file_sppc']) && !empty($files_data['name'][$index]['file_sppc']) ? ['name'=>$files_data['name'][$index]['file_sppc'], 'error'=>$files_data['error'][$index]['file_sppc'], 'tmp_name'=>$files_data['tmp_name'][$index]['file_sppc'], 'size'=>$files_data['size'][$index]['file_sppc']] : null;
                $nama_file_sppc = processUploadedFile($file_info_sppc, $index, 'SPPC', $processed_files_tracker);
                if ($nama_file_sppc === null) {
                    $nama_file_sppc = $item['file_sppc_lama'] ?? null;
                } else {
                    if (!empty($item['file_sppc_lama'])) {
                        $files_to_delete[] = '../public/uploads/' . $item['file_sppc_lama'];
                    }
                }

                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                $catatan = $item['catatan'] ?? '';

                $stmt_contoh->bind_param("issssssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan, $nama_file_ba, $nama_file_sppc);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();
        }

        // 4. Update status laporan kembali ke status yang ditentukan
        $sql_laporan = "UPDATE laporan SET status = ?, catatan_revisi = NULL WHERE id = ?";
        $stmt_laporan = $conn->prepare($sql_laporan);
        $stmt_laporan->bind_param("si", $status_laporan, $laporan_id);
        $stmt_laporan->execute();
        $stmt_laporan->close();
        
        $conn->commit();

        // Jika commit berhasil, hapus file-file lama yang digantikan
        foreach ($files_to_delete as $filepath) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }

        header("Location: " . BASE_URL . "/dashboard.php?status=update_sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Hapus file baru yang terlanjur di-upload jika ada error
        foreach ($processed_files_tracker as $filepath) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        $_SESSION['flash_error'] = "Terjadi error saat memperbarui data: " . $e->getMessage();
        header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}
?>

