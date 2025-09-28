<?php
// actions/simpan_sampling.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contoh'])) {
    $ppc_id = $_SESSION['user_id'];
    
    // Ambil data utama dari form
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    $perusahaan = $_POST['perusahaan'];
    $alamat = $_POST['alamat'];
    $tanggal = $_POST['tanggal'];
    $pengambil_sampel = $_POST['pengambil_sampel'];
    $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

    // --- MULAI PERUBAHAN ---
    // Kelompokkan item contoh berdasarkan tipe laporannya
    $laporan_items_by_type = [
        'air' => [],
        'udara' => [],
        'kebisingan' => [],
        'getaran' => [],
    ];

    foreach ($_POST['contoh'] as $item) {
        if (isset($item['tipe_laporan']) && array_key_exists($item['tipe_laporan'], $laporan_items_by_type)) {
            $laporan_items_by_type[$item['tipe_laporan']][] = $item;
        }
    }
    // --- AKHIR PERUBAHAN ---

    // --- BLOK BARU UNTUK VALIDASI FILE DI AWAL ---
    if (isset($_FILES['contoh'])) {
        $files_data = $_FILES['contoh'];
        foreach ($_POST['contoh'] as $index => $item) {
            if (isset($files_data['name'][$index]['dokumen_pendukung']) && $files_data['error'][$index]['dokumen_pendukung'] == 0) {
                $nama_asli = $files_data['name'][$index]['dokumen_pendukung'];
                $ukuran_file = $files_data['size'][$index]['dokumen_pendukung'];

                $allowed_ext = ['pdf', 'jpg', 'jpeg', 'png'];
                $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));

                if (!in_array($file_ext, $allowed_ext)) {
                    $_SESSION['flash_error'] = "Validasi Gagal: Format file pada Contoh Uji #${index + 1} tidak diizinkan (hanya PDF, JPG, PNG).";
                    header("Location: " . BASE_URL . "/formulir_sampling.php");
                    exit();
                }

                if ($ukuran_file > 5 * 1024 * 1024) { // 5 MB
                    $_SESSION['flash_error'] = "Validasi Gagal: Ukuran file pada Contoh Uji #${index + 1} melebihi batas 5MB.";
                    header("Location: " . BASE_URL . "/formulir_sampling.php");
                    exit();
                }
            }
        }
    }
    // --- AKHIR BLOK BARU ---

    $conn->begin_transaction();

    try {
        // Fungsi prosesLaporan tetap sama seperti sebelumnya (tanpa validasi file di dalamnya)
        function prosesLaporan($jenis, $items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama) {
            // 1. Simpan ke tabel `formulir` (kode ini tidak berubah)
            $sql_form = "INSERT INTO formulir (jenis_laporan, perusahaan, alamat, tanggal, jenis_kegiatan, pengambil_sampel, sub_kontrak_nama, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_form = $conn->prepare($sql_form);
            $stmt_form->bind_param("sssssssi", $jenis, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $ppc_id);
            $stmt_form->execute();
            $form_id = $conn->insert_id;
            $stmt_form->close();

            // 2. Simpan setiap item contoh (logika upload dipindahkan ke sini)
            $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan, dokumen_pendukung) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            $files_data = $_FILES['contoh'] ?? [];

            foreach ($items as $index => $item) {
                $nama_file_unik = null;
                if (isset($files_data['name'][$index]['dokumen_pendukung']) && $files_data['error'][$index]['dokumen_pendukung'] == 0) {
                    $nama_asli = $files_data['name'][$index]['dokumen_pendukung'];
                    $tmp_name = $files_data['tmp_name'][$index]['dokumen_pendukung'];
                    $file_ext = strtolower(pathinfo($nama_asli, PATHINFO_EXTENSION));
                    $nama_file_unik = uniqid() . '-' . time() . '.' . $file_ext;
                    $upload_path = '../public/uploads/' . $nama_file_unik;
                    if (!move_uploaded_file($tmp_name, $upload_path)) {
                        throw new Exception("Gagal memindahkan file untuk Contoh #${index + 1}.");
                    }
                }

                // sisa logika (parameter, baku_mutu, dll) tetap sama
                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                $catatan = $item['catatan'] ?? '';

                $stmt_contoh->bind_param("isssssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan, $nama_file_unik);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();

            // 3. Simpan entri ke tabel `laporan` (kode ini tidak berubah)
            $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES (?, ?, ?, 'Menunggu Verifikasi')";
            $stmt_laporan = $conn->prepare($sql_laporan);
            $stmt_laporan->bind_param("sii", $jenis, $form_id, $ppc_id);
            $stmt_laporan->execute();
            $stmt_laporan->close();
        }

        // Pemanggilan fungsi prosesLaporan (kode ini tidak berubah)
        foreach ($laporan_items_by_type as $jenis => $items) {
            if (!empty($items)) {
                prosesLaporan($jenis, $items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama);
            }
        }

        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        // Simpan pesan error ke session, bukan die()
        $_SESSION['flash_error'] = "Terjadi error saat menyimpan data: " . $e->getMessage();
        header("Location: " . BASE_URL . "/formulir_sampling.php");
        exit();
    }
    
} else {
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>
