<?php
// actions/simpan_sampling.php

require_once '../app/config.php';

// Keamanan: Pastikan hanya PPC yang bisa mengakses
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

function processUploadedFile($file_info, $index, $file_type_name, &$processed_files) {
    if ($file_info && $file_info['error'] == 0) {
        $nama_asli = $file_info['name'];
        $ukuran_file = $file_info['size'];
        $tmp_name = $file_info['tmp_name'];

        $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
        $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
        if (!in_array($file_ext, $allowed_ext)) {
            throw new Exception("Format file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " tidak diizinkan (hanya PDF, JPG, PNG).");
        }

        if ($ukuran_file > 5 * 1024 * 1024) { // 5 MB
            throw new Exception("Ukuran file ${file_type_name} pada Contoh Uji #" . ($index + 1) . " melebihi batas 5MB.");
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

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contoh'])) {
    
    $status_laporan = 'Draft';
    if (isset($_POST['aksi']) && $_POST['aksi'] === 'ajukan') {
        $status_laporan = 'Menunggu Verifikasi';
    }

    $ppc_id = $_SESSION['user_id'];
    $ppc_nama = $_SESSION['nama_lengkap']; // Ambil nama PPC untuk pesan notifikasi
    $processed_files_tracker = [];

    $conn->begin_transaction();

    try {
        $nama_file_ba = processUploadedFile($_FILES['file_berita_acara'] ?? null, 0, 'Berita Acara', $processed_files_tracker);
        $nama_file_sppc = processUploadedFile($_FILES['file_sppc'] ?? null, 0, 'SPPC', $processed_files_tracker);

        $stmt_ttd = $conn->prepare("SELECT tanda_tangan FROM users WHERE id = ?");
        $stmt_ttd->bind_param("i", $ppc_id);
        $stmt_ttd->execute();
        $ttd_ppc_file = $stmt_ttd->get_result()->fetch_assoc()['tanda_tangan'];
        $stmt_ttd->close();

        if ($status_laporan === 'Menunggu Verifikasi' && empty($ttd_ppc_file)) {
            throw new Exception("Aksi ditolak. Anda harus mengunggah tanda tangan di halaman profil Anda terlebih dahulu sebelum bisa mengajukan laporan.");
        }

        $jenis_kegiatan = $_POST['jenis_kegiatan'];
        $perusahaan = $_POST['perusahaan'];
        $alamat = $_POST['alamat'];
        $tanggal_mulai = $_POST['tanggal_mulai'];
        $tanggal_selesai = $_POST['tanggal_selesai'];
        $pengambil_sampel = $_POST['pengambil_sampel'];
        $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;
        $tujuan_pemeriksaan = $_POST['tujuan_pemeriksaan'];
        $tujuan_pemeriksaan_lainnya = ($tujuan_pemeriksaan === 'Lainnya') ? $_POST['tujuan_pemeriksaan_lainnya'] : NULL;

        $laporan_items_by_type = ['air' => [],'udara' => [],'kebisingan' => [],'getaran' => []];
        foreach ($_POST['contoh'] as $key => $item) {
            if (isset($item['tipe_laporan']) && array_key_exists($item['tipe_laporan'], $laporan_items_by_type)) {
                $item['original_key'] = $key;
                $laporan_items_by_type[$item['tipe_laporan']][] = $item;
            }
        }

        $data_utama = compact('perusahaan', 'alamat', 'jenis_kegiatan', 'pengambil_sampel', 'sub_kontrak_nama');
        
        foreach ($laporan_items_by_type as $jenis => $items) {
            if (!empty($items)) {
                
                $sql_form = "INSERT INTO formulir (jenis_laporan, perusahaan, alamat, tanggal_mulai, tanggal_selesai, jenis_kegiatan, pengambil_sampel, sub_kontrak_nama, tujuan_pemeriksaan, tujuan_pemeriksaan_lainnya, created_by, file_berita_acara, file_sppc) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_form = $conn->prepare($sql_form);
                $stmt_form->bind_param("ssssssssssiss", $jenis, $data_utama['perusahaan'], $data_utama['alamat'], $tanggal_mulai, $tanggal_selesai, $data_utama['jenis_kegiatan'], $data_utama['pengambil_sampel'], $data_utama['sub_kontrak_nama'], $tujuan_pemeriksaan, $tujuan_pemeriksaan_lainnya, $ppc_id, $nama_file_ba, $nama_file_sppc);
                $stmt_form->execute();
                $form_id = $conn->insert_id;
                $stmt_form->close();

                $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_contoh = $conn->prepare($sql_contoh);

                foreach ($items as $item) {
                    $index = $item['original_key'];

                    $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                    $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                    $prosedur = isset($item['prosedur']) ? implode(', ', $item['prosedur']) : '';
                    $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                    $catatan = $item['catatan'] ?? '';

                    $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $prosedur, $parameter, $baku_mutu, $catatan);
                    $stmt_contoh->execute();
                }
                $stmt_contoh->close();

                $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, ttd_ppc, status) VALUES (?, ?, ?, ?, ?)";
                $stmt_laporan = $conn->prepare($sql_laporan);
                $stmt_laporan->bind_param("siiss", $jenis, $form_id, $ppc_id, $ttd_ppc_file, $status_laporan);
                $stmt_laporan->execute();
                $laporan_id_baru = $conn->insert_id; // Ambil ID laporan yang baru saja dibuat
                $stmt_laporan->close();

                // === BLOK BARU: Buat Notifikasi untuk Penyelia ===
                if ($status_laporan === 'Menunggu Verifikasi') {
                    $pesan = "Laporan baru (#{$laporan_id_baru}) dari {$ppc_nama} menunggu verifikasi Anda.";
                    // Kirim notifikasi ke semua user dengan role_id = 2 (Penyelia)
                    buatNotifikasiUntukRole($conn, 2, $pesan, $laporan_id_baru);
                }
                // === AKHIR BLOK BARU ===
            }
        }
        
        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        foreach ($processed_files_tracker as $filepath) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
        }
        $_SESSION['flash_error'] = "Terjadi error: " . $e->getMessage();
        header("Location: " . BASE_URL . "/formulir_sampling.php");
        exit();
    }
} else {
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>