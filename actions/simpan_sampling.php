<?php
// actions/simpan_sampling.php

require_once '../app/config.php';

// Keamanan: Pastikan hanya PPC yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

/**
 * Fungsi helper untuk memvalidasi dan memindahkan satu file upload.
 * @param array|null $file_info - Detail file dari array $_FILES.
 * @param int $index - Indeks dari contoh uji untuk pesan error.
 * @param string $file_type_name - Nama jenis file untuk pesan error (e.g., "Berita Acara").
 * @param array &$processed_files - Array untuk melacak file yang sudah berhasil dipindahkan untuk cleanup.
 * @return string|null - Mengembalikan nama file unik jika berhasil, null jika tidak ada file.
 * @throws Exception - Melempar exception jika validasi atau pemindahan gagal.
 */
function processUploadedFile($file_info, $index, $file_type_name, &$processed_files) {
    // Cek hanya jika ada file yang di-upload dan tidak ada error
    if ($file_info && $file_info['error'] == 0) {
        $nama_asli = $file_info['name'];
        $ukuran_file = $file_info['size'];
        $tmp_name = $file_info['tmp_name'];

        // 1. Validasi Ekstensi
        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            // Lemparkan error yang akan ditangkap oleh blok catch
            throw new Exception("Format file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " tidak diizinkan (hanya PDF, JPG, PNG).");
        }

        // 2. Validasi Ukuran
        if ($ukuran_file > 5 * 1024 * 1024) { // 5 MB
            throw new Exception("Ukuran file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " melebihi batas 5MB.");
        }

        // 3. Buat nama unik dan pindahkan file
        $nama_file_unik = uniqid(strtolower(str_replace(' ', '_', $file_type_name)) . '_', true) . '.' . $file_ext;
        $upload_path = '../public/uploads/' . $nama_file_unik;
        
        if (move_uploaded_file($tmp_name, $upload_path)) {
            $processed_files[] = $upload_path; // Lacak file yang berhasil dipindah untuk kemungkinan cleanup
            return $nama_file_unik;
        } else {
            throw new Exception("Gagal memindahkan file ${file_type_name} untuk Contoh Uji #" . ($index + 1) . ".");
        }
    }
    return null; // Tidak ada file yang di-upload atau ada error dari sisi client
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contoh'])) {
    
    // Tentukan status laporan berdasarkan tombol yang diklik
    $status_laporan = 'Draft'; // Default
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'ajukan') {
        $status_laporan = 'Menunggu Verifikasi';
    }

    $ppc_id = $_SESSION['user_id'];
    $processed_files_tracker = []; // Array untuk melacak semua file yang berhasil di-upload dalam request ini

    $conn->begin_transaction();

    try {
        // Ambil data utama dari form
        $jenis_kegiatan = $_POST['jenis_kegiatan'];
        $perusahaan = $_POST['perusahaan'];
        $alamat = $_POST['alamat'];
        $tanggal = $_POST['tanggal'];
        $pengambil_sampel = $_POST['pengambil_sampel'];
        $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

        // Kelompokkan item contoh berdasarkan tipe laporannya
        $laporan_items_by_type = ['air' => [],'udara' => [],'kebisingan' => [],'getaran' => []];
        foreach ($_POST['contoh'] as $key => $item) {
            if (isset($item['tipe_laporan']) && array_key_exists($item['tipe_laporan'], $laporan_items_by_type)) {
                $item['original_key'] = $key;
                $laporan_items_by_type[$item['tipe_laporan']][] = $item;
            }
        }

        $data_utama = compact('perusahaan', 'alamat', 'tanggal', 'jenis_kegiatan', 'pengambil_sampel', 'sub_kontrak_nama');
        
        // Loop untuk setiap jenis laporan (misal: 'air', 'udara', dll.)
        foreach ($laporan_items_by_type as $jenis => $items) {
            if (!empty($items)) {
                
                // 1. Buat entri di tabel `formulir`
                $sql_form = "INSERT INTO formulir (jenis_laporan, perusahaan, alamat, tanggal, jenis_kegiatan, pengambil_sampel, sub_kontrak_nama, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_form = $conn->prepare($sql_form);
                $stmt_form->bind_param("sssssssi", $jenis, $data_utama['perusahaan'], $data_utama['alamat'], $data_utama['tanggal'], $data_utama['jenis_kegiatan'], $data_utama['pengambil_sampel'], $data_utama['sub_kontrak_nama'], $ppc_id);
                $stmt_form->execute();
                $form_id = $conn->insert_id;
                $stmt_form->close();

                // 2. Loop melalui setiap contoh uji, validasi & simpan filenya, lalu simpan datanya
                $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, file_berita_acara, file_sppc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_contoh = $conn->prepare($sql_contoh);
                $files_data = $_FILES['contoh'] ?? [];

                foreach ($items as $item) {
                    $index = $item['original_key']; // Perbaikan bug
                    
                    $file_info_ba = isset($files_data['name'][$index]['file_berita_acara']) ? ['name'=>$files_data['name'][$index]['file_berita_acara'], 'error'=>$files_data['error'][$index]['file_berita_acara'], 'tmp_name'=>$files_data['tmp_name'][$index]['file_berita_acara'], 'size'=>$files_data['size'][$index]['file_berita_acara']] : null;
                    $nama_file_ba = processUploadedFile($file_info_ba, $index, 'Berita Acara', $processed_files_tracker);

                    $file_info_sppc = isset($files_data['name'][$index]['file_sppc']) ? ['name'=>$files_data['name'][$index]['file_sppc'], 'error'=>$files_data['error'][$index]['file_sppc'], 'tmp_name'=>$files_data['tmp_name'][$index]['file_sppc'], 'size'=>$files_data['size'][$index]['file_sppc']] : null;
                    $nama_file_sppc = processUploadedFile($file_info_sppc, $index, 'SPPC', $processed_files_tracker);

                    // Data teks lainnya
                    $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                    $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                    $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                    $catatan = $item['catatan'] ?? '';

                    $stmt_contoh->bind_param("issssssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan, $nama_file_ba, $nama_file_sppc);
                    $stmt_contoh->execute();
                }
                $stmt_contoh->close();

                // 3. Buat entri di tabel `laporan` dengan status yang sesuai
                $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES (?, ?, ?, ?)";
                $stmt_laporan = $conn->prepare($sql_laporan);
                $stmt_laporan->bind_param("siis", $jenis, $form_id, $ppc_id, $status_laporan);
                $stmt_laporan->execute();
                $stmt_laporan->close();
            }
        }
        
        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Jika terjadi error, hapus semua file yang sudah terlanjur di-upload
        foreach ($processed_files_tracker as $filepath) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        // Kirim pesan error kembali ke formulir
        $_SESSION['flash_error'] = "Terjadi error: " . $e->getMessage();
        header("Location: " . BASE_URL . "/formulir_sampling.php");
        exit();
    }
} else {
    // Jika akses tidak sah
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>
