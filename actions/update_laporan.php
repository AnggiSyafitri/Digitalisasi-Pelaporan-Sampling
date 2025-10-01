<?php

require_once '../app/config.php';

// Enable error logging
ini_set('display_errors', 1);
ini_set('log_errors', 1);
error_log("Starting update_laporan.php process");

// Security check
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Debug logging
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

// Validate required parameters
if (!isset($_POST['laporan_id']) || !isset($_POST['form_id']) || !isset($_POST['aksi'])) {
    $_SESSION['flash_error'] = "Parameter tidak lengkap";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}

function processUploadedFile($file_info, $index, $file_type_name, &$processed_files) {
    if (!$file_info || $file_info['error'] !== 0) {
        return null;
    }

    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    
    if (!in_array($file_ext, $allowed_ext)) {
        throw new Exception("Format file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " tidak diizinkan");
    }
    
    if ($file_info['size'] > 5 * 1024 * 1024) {
        throw new Exception("Ukuran file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " melebihi 5MB");
    }

    $nama_file_unik = uniqid(strtolower(str_replace(' ', '_', $file_type_name)) . '_', true) . '.' . $file_ext;
    $upload_path = '../public/uploads/' . $nama_file_unik;
    
    if (!move_uploaded_file($file_info['tmp_name'], $upload_path)) {
        throw new Exception("Gagal mengunggah file ${file_type_name} untuk Contoh Uji #" . ($index + 1));
    }

    $processed_files[] = $upload_path;
    return $nama_file_unik;
}

try {
    $laporan_id = (int)$_POST['laporan_id'];
    $form_id = (int)$_POST['form_id'];
    $status_laporan = ($_POST['aksi'] === 'ajukan') ? 'Menunggu Verifikasi' : 'Draft';

    // Start transaction
    $conn->begin_transaction();

    // Early validation for 'ajukan' action
    if ($_POST['aksi'] === 'ajukan') {
        $required_fields = ['jenis_kegiatan', 'perusahaan', 'alamat', 'tanggal', 'pengambil_sampel'];
        $missing_fields = [];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                $missing_fields[] = $field;
            }
        }
        
        if (!empty($missing_fields)) {
            throw new Exception("Field berikut harus diisi untuk pengajuan: " . implode(", ", $missing_fields));
        }

        if (!isset($_POST['contoh']) || empty($_POST['contoh'])) {
            throw new Exception("Minimal satu contoh uji harus diisi");
        }
    }

    $processed_files_tracker = [];
    $files_to_delete_on_success = [];
    $new_contoh_data_for_db = [];

    // Process form data
    $jenis_kegiatan = $_POST['jenis_kegiatan'] ?? '';
    $perusahaan = $_POST['perusahaan'] ?? '';
    $alamat = $_POST['alamat'] ?? '';
    $tanggal = $_POST['tanggal'] ?? '';
    $pengambil_sampel = $_POST['pengambil_sampel'] ?? '';
    $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

    // Process samples
    $files_data = $_FILES['contoh'] ?? [];
    foreach ($_POST['contoh'] as $index => $item) {

        // Ambil data parameter, default ke array kosong jika tidak ada
        $parameters = $item['parameter'] ?? [];

        // Lakukan validasi parameter HANYA JIKA aksi adalah 'ajukan'
        if ($_POST['aksi'] === 'ajukan' && empty($parameters)) {
            throw new Exception("Parameter Uji wajib dipilih minimal satu untuk Contoh Uji #" . ($index + 1));
        }

        // Process BA file
        $file_info_ba = isset($files_data['name']['file_berita_acara'][$index]) ? [
            'name' => $files_data['name']['file_berita_acara'][$index],
            'error' => $files_data['error']['file_berita_acara'][$index],
            'tmp_name' => $files_data['tmp_name']['file_berita_acara'][$index],
            'size' => $files_data['size']['file_berita_acara'][$index]
        ] : null;

        $nama_file_ba_baru = processUploadedFile($file_info_ba, $index, 'Berita Acara', $processed_files_tracker);
        if ($nama_file_ba_baru && !empty($item['file_berita_acara_lama'])) {
            $files_to_delete_on_success[] = '../public/uploads/' . $item['file_berita_acara_lama'];
        }

        // Process SPPC file
        $file_info_sppc = isset($files_data['name']['file_sppc'][$index]) ? [
            'name' => $files_data['name']['file_sppc'][$index],
            'error' => $files_data['error']['file_sppc'][$index],
            'tmp_name' => $files_data['tmp_name']['file_sppc'][$index],
            'size' => $files_data['size']['file_sppc'][$index]
        ] : null;

        $nama_file_sppc_baru = processUploadedFile($file_info_sppc, $index, 'SPPC', $processed_files_tracker);
        if ($nama_file_sppc_baru && !empty($item['file_sppc_lama'])) {
            $files_to_delete_on_success[] = '../public/uploads/' . $item['file_sppc_lama'];
        }

        // Prepare data for database
        $new_contoh_data_for_db[] = [
            'nama_contoh' => $item['nama_contoh'],
            'jenis_contoh' => $item['jenis_contoh'] ?? 'N/A',
            'merek' => $item['merek'],
            'kode' => $item['kode'],
            'prosedur' => $item['prosedur'],
            'parameter' => implode(', ', $parameters), // Implode akan bekerja dengan benar
            'baku_mutu' => ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? ''),
            'catatan' => $item['catatan'] ?? '',
            'file_berita_acara' => $nama_file_ba_baru ?? $item['file_berita_acara_lama'] ?? null,
            'file_sppc' => $nama_file_sppc_baru ?? $item['file_sppc_lama'] ?? null
        ];
    }

    // Update formulir
    $sql_form = "UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ? WHERE id = ?";
    $stmt_form = $conn->prepare($sql_form);
    $stmt_form->bind_param("ssssssi", $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $form_id);
    if (!$stmt_form->execute()) {
        throw new Exception("Gagal mengupdate formulir: " . $stmt_form->error);
    }

    // Delete old samples
    $sql_delete_contoh = "DELETE FROM contoh WHERE formulir_id = ?";
    $stmt_delete = $conn->prepare($sql_delete_contoh);
    $stmt_delete->bind_param("i", $form_id);
    if (!$stmt_delete->execute()) {
        throw new Exception("Gagal menghapus contoh lama: " . $stmt_delete->error);
    }

    // Insert new samples
    $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, file_berita_acara, file_sppc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt_contoh = $conn->prepare($sql_contoh);
    
    foreach ($new_contoh_data_for_db as $item) {
        $stmt_contoh->bind_param("issssssssss", 
            $form_id, 
            $item['nama_contoh'],
            $item['jenis_contoh'],
            $item['merek'],
            $item['kode'],
            $item['prosedur'],
            $item['parameter'],
            $item['baku_mutu'],
            $item['catatan'],
            $item['file_berita_acara'],
            $item['file_sppc']
        );
        if (!$stmt_contoh->execute()) {
            throw new Exception("Gagal menyimpan contoh baru: " . $stmt_contoh->error);
        }
    }

    // Update report status
    $sql_laporan = "UPDATE laporan SET status = ?, catatan_revisi = NULL WHERE id = ?";
    $stmt_laporan = $conn->prepare($sql_laporan);
    $stmt_laporan->bind_param("si", $status_laporan, $laporan_id);
    if (!$stmt_laporan->execute()) {
        throw new Exception("Gagal mengupdate status laporan: " . $stmt_laporan->error);
    }

    // Commit transaction
    $conn->commit();

    // Delete old files
    foreach ($files_to_delete_on_success as $filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    $_SESSION['flash_success'] = "Laporan berhasil " . ($status_laporan === 'Menunggu Verifikasi' ? 'diajukan' : 'disimpan sebagai draft');
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();

} catch (Exception $e) {
    // Rollback transaction
    if (isset($conn)) {
        $conn->rollback();
    }

    // Delete uploaded files if error occurs
    foreach ($processed_files_tracker as $filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }

    // Log error
    error_log("Error in update_laporan.php: " . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
    exit();
}