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

    $conn->begin_transaction();

    try {
        // Fungsi generik untuk memproses dan menyimpan satu jenis laporan
        function prosesLaporan($jenis, $items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama) {
            // 1. Simpan ke tabel `formulir`
            $sql_form = "INSERT INTO formulir (jenis_laporan, perusahaan, alamat, tanggal, jenis_kegiatan, pengambil_sampel, sub_kontrak_nama, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_form = $conn->prepare($sql_form);
            $stmt_form->bind_param("sssssssi", $jenis, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $ppc_id);
            $stmt_form->execute();
            $form_id = $conn->insert_id;
            $stmt_form->close();

            // 2. Simpan setiap item contoh ke tabel `contoh`
            $sql_contoh = "INSERT INTO contoh (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            foreach ($items as $item) {
                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                $catatan = $item['catatan'] ?? '';

                $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $catatan);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();

            // 3. Simpan entri ke tabel `laporan`
            $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES (?, ?, ?, 'Menunggu Verifikasi')";
            $stmt_laporan = $conn->prepare($sql_laporan);
            $stmt_laporan->bind_param("sii", $jenis, $form_id, $ppc_id);
            $stmt_laporan->execute();
            $stmt_laporan->close();
        }

        // --- MULAI PERUBAHAN ---
        // Jalankan fungsi untuk setiap jenis laporan yang memiliki item
        foreach ($laporan_items_by_type as $jenis => $items) {
            // Hanya proses jika ada item untuk jenis ini
            if (!empty($items)) {
                prosesLaporan($jenis, $items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama);
            }
        }
        // --- AKHIR PERUBAHAN ---
        
        $conn->commit();
        header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi error saat menyimpan data: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>
