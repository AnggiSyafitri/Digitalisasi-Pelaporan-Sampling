<?php

require_once '../app/config.php';

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

// Fungsi helper pindah ke sini agar lebih rapi
function processUploadedFile($file_info, &$processed_files) {
    if ($file_info && $file_info['error'] == 0) {
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) throw new Exception("Format file tidak diizinkan.");
        if ($file_info['size'] > 5 * 1024 * 1024) throw new Exception("Ukuran file melebihi 5MB.");

        $nama_file_unik = uniqid('file_', true) . '.' . $file_ext;
        $upload_path = '../public/uploads/' . $nama_file_unik;

        if (move_uploaded_file($file_info['tmp_name'], $upload_path)) {
            $processed_files[] = $upload_path;
            return $nama_file_unik;
        } else {
            throw new Exception("Gagal memindahkan file yang diunggah.");
        }
    }
    return null;
}


$laporan_id = (int)$_POST['laporan_id'];
$form_id = (int)$_POST['form_id'];
$aksi = $_POST['aksi'];
$status_laporan = ($aksi === 'ajukan') ? 'Menunggu Verifikasi' : 'Draft';

$processed_files_tracker = [];
$ppc_id = $_SESSION['user_id'];
$ttd_ppc_file = null;

$conn->begin_transaction();

try {
    // 1. Ambil TTD jika mengajukan
    if ($aksi === 'ajukan') {
        $stmt_ttd = $conn->prepare("SELECT tanda_tangan FROM users WHERE id = ?");
        $stmt_ttd->bind_param("i", $ppc_id);
        $stmt_ttd->execute();
        $result_ttd = $stmt_ttd->get_result()->fetch_assoc();
        $stmt_ttd->close();

        if (empty($result_ttd['tanda_tangan'])) {
            throw new Exception("Aksi ditolak. Anda harus mengunggah tanda tangan di profil.");
        }
        $ttd_ppc_file = $result_ttd['tanda_tangan'];
    }

    // 2. Proses file upload utama
    $nama_file_ba_baru = processUploadedFile($_FILES['file_berita_acara'] ?? null, $processed_files_tracker);
    $nama_file_sppc_baru = processUploadedFile($_FILES['file_sppc'] ?? null, $processed_files_tracker);

    $file_ba_final = $nama_file_ba_baru ?? $_POST['file_berita_acara_lama'];
    $file_sppc_final = $nama_file_sppc_baru ?? $_POST['file_sppc_lama'];

    // 3. Update data utama di tabel 'formulir'
    $stmt_form = $conn->prepare("UPDATE formulir SET perusahaan = ?, alamat = ?, tanggal_mulai = ?, tanggal_selesai = ?, jenis_kegiatan = ?, pengambil_sampel = ?, sub_kontrak_nama = ?, tujuan_pemeriksaan = ?, tujuan_pemeriksaan_lainnya = ?, file_berita_acara = ?, file_sppc = ? WHERE id = ?");
    $sub_kontrak_nama = ($_POST['pengambil_sampel'] === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : null;
    $tujuan_pemeriksaan = $_POST['tujuan_pemeriksaan'];
    $tujuan_pemeriksaan_lainnya = ($tujuan_pemeriksaan === 'Lainnya') ? $_POST['tujuan_pemeriksaan_lainnya'] : null;
    $stmt_form->bind_param("sssssssssssi", $_POST['perusahaan'], $_POST['alamat'], $_POST['tanggal_mulai'], $_POST['tanggal_selesai'], $_POST['jenis_kegiatan'], $_POST['pengambil_sampel'], $sub_kontrak_nama, $tujuan_pemeriksaan, $tujuan_pemeriksaan_lainnya, $file_ba_final, $file_sppc_final, $form_id);
    $stmt_form->execute();
    $stmt_form->close();

    // 4. Hapus semua contoh uji yang lama, lalu sisipkan yang baru
    $stmt_delete = $conn->prepare("DELETE FROM contoh WHERE formulir_id = ?");
    $stmt_delete->bind_param("i", $form_id);
    $stmt_delete->execute();
    $stmt_delete->close();

    if (!empty($_POST['contoh'])) {
        $stmt_contoh = $conn->prepare("INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($_POST['contoh'] as $item) {
            $prosedur_str = isset($item['prosedur']) ? implode(', ', $item['prosedur']) : '';
            $parameter_str = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
            $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');

            $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $item['jenis_contoh'], $item['merek'], $item['kode'], $prosedur_str, $parameter_str, $baku_mutu, $item['catatan']);
            $stmt_contoh->execute();
        }
        $stmt_contoh->close();
    }

    // 5. Update status laporan di tabel 'laporan'
    $sql_laporan = "UPDATE laporan SET status = ?, ttd_ppc = ?, catatan_revisi = NULL WHERE id = ?";
    $stmt_laporan = $conn->prepare($sql_laporan);
    $stmt_laporan->bind_param("ssi", $status_laporan, $ttd_ppc_file, $laporan_id);
    $stmt_laporan->execute();
    $stmt_laporan->close();

    // Jika semua berhasil, commit
    $conn->commit();

    // Hapus file lama JIKA file baru diupload untuk menggantikannya
    if ($nama_file_ba_baru && !empty($_POST['file_berita_acara_lama']) && file_exists('../public/uploads/' . $_POST['file_berita_acara_lama'])) {
        unlink('../public/uploads/' . $_POST['file_berita_acara_lama']);
    }
    if ($nama_file_sppc_baru && !empty($_POST['file_sppc_lama']) && file_exists('../public/uploads/' . $_POST['file_sppc_lama'])) {
        unlink('../public/uploads/' . $_POST['file_sppc_lama']);
    }

    $_SESSION['flash_success'] = "Laporan berhasil diperbarui!";
    header("Location: " . BASE_URL . "/dashboard.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    // Jika gagal, hapus file baru yang mungkin terupload
    foreach ($processed_files_tracker as $filepath) {
        if (file_exists($filepath)) unlink($filepath);
    }
    $_SESSION['flash_error'] = $e->getMessage();
    header("Location: " . BASE_URL . "/edit_laporan.php?laporan_id=" . $laporan_id);
    exit();
}