<?php

require_once '../app/config.php';

// Aktifkan logging error untuk debugging
ini_set('log_errors', 1);
error_log("--- Memulai proses update_laporan.php ---");

// Keamanan: Cek sesi dan peran pengguna
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Validasi parameter POST dasar
if (!isset($_POST['laporan_id'], $_POST['form_id'], $_POST['aksi'])) {
    $_SESSION['flash_error'] = "Permintaan tidak valid: Parameter dasar tidak lengkap.";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();
}

$laporan_id = (int)$_POST['laporan_id'];
$form_id = (int)$_POST['form_id'];
$aksi = $_POST['aksi'];
$status_laporan = ($aksi === 'ajukan') ? 'Menunggu Verifikasi' : 'Draft';

// --- VALIDASI DATA (HANYA JIKA DIAJUKAN) ---
if ($aksi === 'ajukan') {
    try {
        // Validasi field utama
        $required_fields = ['jenis_kegiatan', 'perusahaan', 'alamat', 'tanggal', 'pengambil_sampel'];
        foreach ($required_fields as $field) {
            if (empty(trim($_POST[$field]))) {
                throw new Exception("Field '" . ucfirst(str_replace('_', ' ', $field)) . "' wajib diisi untuk mengajukan laporan.");
            }
        }
        if ($_POST['pengambil_sampel'] === 'Sub Kontrak' && empty(trim($_POST['sub_kontrak_nama']))) {
            throw new Exception("Nama Perusahaan Sub Kontrak wajib diisi.");
        }

        // Validasi contoh uji
        if (empty($_POST['contoh'])) {
            throw new Exception("Minimal harus ada satu contoh uji untuk diajukan.");
        }
        foreach ($_POST['contoh'] as $index => $item) {
            if (empty($item['parameter'])) {
                throw new Exception("Parameter Uji wajib dipilih minimal satu untuk Contoh Uji #" . ($index + 1));
            }
        }
    } catch (Exception $e) {
        $_SESSION['flash_error'] = $e->getMessage();
        header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
        exit();
    }
}

// --- PROSES PENYIMPANAN DATA ---
$processed_files_tracker = [];
$files_to_delete_on_success = [];

$conn->begin_transaction();

try {
    // 1. Update data utama di tabel 'formulir'
    $stmt_form = $conn->prepare("UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ? WHERE id = ?");
    $sub_kontrak_nama = ($_POST['pengambil_sampel'] === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : null;
    $stmt_form->bind_param("ssssssi", $_POST['perusahaan'], $_POST['alamat'], $_POST['tanggal'], $_POST['jenis_kegiatan'], $_POST['pengambil_sampel'], $sub_kontrak_nama, $form_id);
    $stmt_form->execute();
    $stmt_form->close();

    // 2. Kumpulkan file lama yang mungkin perlu dihapus
    $stmt_old_files = $conn->prepare("SELECT file_berita_acara, file_sppc FROM contoh WHERE formulir_id = ?");
    $stmt_old_files->bind_param("i", $form_id);
    $stmt_old_files->execute();
    $result_old_files = $stmt_old_files->get_result();
    while($row = $result_old_files->fetch_assoc()){
        if (!empty($row['file_berita_acara'])) $files_to_delete_on_success[] = $row['file_berita_acara'];
        if (!empty($row['file_sppc'])) $files_to_delete_on_success[] = $row['file_sppc'];
    }
    $stmt_old_files->close();

    // 3. Hapus semua contoh uji yang lama dari database
    $stmt_delete = $conn->prepare("DELETE FROM contoh WHERE formulir_id = ?");
    $stmt_delete->bind_param("i", $form_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    // 4. Proses dan sisipkan contoh uji yang baru
    if (!empty($_POST['contoh'])) {
        $stmt_contoh = $conn->prepare("INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, file_berita_acara, file_sppc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $files_data = $_FILES['contoh'] ?? [];

        foreach ($_POST['contoh'] as $index => $item) {
            // Proses file Berita Acara
            $file_info_ba = (isset($files_data['name']['file_berita_acara'][$index]) && $files_data['error']['file_berita_acara'][$index] == 0) ? ['name' => $files_data['name']['file_berita_acara'][$index], 'error' => $files_data['error']['file_berita_acara'][$index], 'tmp_name' => $files_data['tmp_name']['file_berita_acara'][$index], 'size' => $files_data['size']['file_berita_acara'][$index]] : null;
            $nama_file_ba_baru = processUploadedFile($file_info_ba, $index, 'Berita Acara', $processed_files_tracker);

            // Proses file SPPC
            $file_info_sppc = (isset($files_data['name']['file_sppc'][$index]) && $files_data['error']['file_sppc'][$index] == 0) ? ['name' => $files_data['name']['file_sppc'][$index], 'error' => $files_data['error']['file_sppc'][$index], 'tmp_name' => $files_data['tmp_name']['file_sppc'][$index], 'size' => $files_data['size']['file_sppc'][$index]] : null;
            $nama_file_sppc_baru = processUploadedFile($file_info_sppc, $index, 'SPPC', $processed_files_tracker);

            $parameter_str = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
            $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');

            $file_ba_final = $nama_file_ba_baru ?? $item['file_berita_acara_lama'] ?? null;
            $file_sppc_final = $nama_file_sppc_baru ?? $item['file_sppc_lama'] ?? null;

            $stmt_contoh->bind_param("issssssssss", $form_id, $item['nama_contoh'], $item['jenis_contoh'], $item['merek'], $item['kode'], $item['prosedur'], $parameter_str, $baku_mutu, $item['catatan'], $file_ba_final, $file_sppc_final);
            $stmt_contoh->execute();
        }
        $stmt_contoh->close();
    }

    // 5. Update status laporan
    $sql_laporan = "UPDATE laporan SET status = ?, catatan_revisi = NULL WHERE id = ?";
    $stmt_laporan = $conn->prepare($sql_laporan);
    $stmt_laporan->bind_param("si", $status_laporan, $laporan_id);
    $stmt_laporan->execute();
    $stmt_laporan->close();

    // Jika semua berhasil, commit transaksi
    $conn->commit();

    // Hapus file lama HANYA jika file baru diupload untuk menggantikannya
    foreach ($_POST['contoh'] as $index => $item) {
        $file_info_ba = (isset($files_data['name']['file_berita_acara'][$index]) && $files_data['error']['file_berita_acara'][$index] == 0);
        $file_info_sppc = (isset($files_data['name']['file_sppc'][$index]) && $files_data['error']['file_sppc'][$index] == 0);

        if ($file_info_ba && !empty($item['file_berita_acara_lama']) && file_exists('../public/uploads/' . $item['file_berita_acara_lama'])) {
            unlink('../public/uploads/' . $item['file_berita_acara_lama']);
        }
        if ($file_info_sppc && !empty($item['file_sppc_lama']) && file_exists('../public/uploads/' . $item['file_sppc_lama'])) {
            unlink('../public/uploads/' . $item['file_sppc_lama']);
        }
    }

    $_SESSION['flash_success'] = "Laporan berhasil " . ($status_laporan === 'Menunggu Verifikasi' ? 'diajukan' : 'disimpan sebagai draft');
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    // Jika terjadi error saat transaksi DB, hapus file yang baru saja di-upload
    foreach ($processed_files_tracker as $filepath) {
        if (file_exists($filepath)) {
            unlink($filepath);
        }
    }
    error_log("Error in update_laporan.php: " . $e->getMessage());
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
    exit();
}

// Fungsi untuk memproses file upload (letakkan di dalam file yang sama atau include)
function processUploadedFile($file_info, $index, $file_type_name, &$processed_files) {
    if (!$file_info) return null;

    $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
    $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
    if (!in_array($file_ext, $allowed_ext)) throw new Exception("Format file ${file_type_name} pada Contoh #" . ($index + 1) . " tidak diizinkan.");
    if ($file_info['size'] > 5 * 1024 * 1024) throw new Exception("Ukuran file ${file_type_name} pada Contoh #" . ($index + 1) . " melebihi 5MB.");

    $nama_file_unik = uniqid(strtolower(str_replace(' ', '_', $file_type_name)) . '_', true) . '.' . $file_ext;
    $upload_path = '../public/uploads/' . $nama_file_unik;

    if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
        $processed_files[] = $upload_path;
        return $nama_file_unik;
    } else {
        throw new Exception("Gagal memindahkan file ${file_type_name} untuk Contoh #" . ($index + 1) . ".");
    }
}
?>