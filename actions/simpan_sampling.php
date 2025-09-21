<?php
// actions/simpan_sampling.php

require_once '../app/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contoh'])) {
    $ppc_id = $_SESSION['user_id'];
    
    // Ambil data baru dari form
    $jenis_kegiatan = $_POST['jenis_kegiatan'];
    $perusahaan = $_POST['perusahaan'];
    $alamat = $_POST['alamat'];
    $tanggal = $_POST['tanggal'];
    $pengambil_sampel = $_POST['pengambil_sampel'];
    // Jika sub kontrak dipilih, ambil namanya, jika tidak, simpan sebagai NULL
    $sub_kontrak_nama = ($pengambil_sampel === 'Sub Kontrak') ? $_POST['sub_kontrak_nama'] : NULL;

    // ... (Logika pemisahan item air & udara tetap sama)
    $laporan_air_items = [];
    $laporan_udara_items = [];
    foreach ($_POST['contoh'] as $item) {
        if (isset($item['tipe_laporan'])) {
            if ($item['tipe_laporan'] == 'air') { $laporan_air_items[] = $item; } 
            elseif ($item['tipe_laporan'] == 'udara') { $laporan_udara_items[] = $item; }
        }
    }

// ... (menggabungkan logika penyimpanan ke dalam satu transaksi)

$conn->begin_transaction();

try {
    // Fungsi untuk memproses dan menyimpan satu jenis laporan
    function prosesLaporan($jenis, $items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama) {
        if (empty($items)) {
            return;
        }

        // 1. Simpan ke tabel `formulir` yang baru
        $sql_form = "INSERT INTO formulir (jenis_laporan, perusahaan, alamat, tanggal, jenis_kegiatan, pengambil_sampel, sub_kontrak_nama, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt_form = $conn->prepare($sql_form);
        $stmt_form->bind_param("sssssssi", $jenis, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama, $ppc_id);
        $stmt_form->execute();
        $form_id = $conn->insert_id;
        $stmt_form->close();

        // 2. Simpan setiap item contoh ke tabel `contoh` yang baru
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

        // 3. Simpan ke tabel `laporan` (struktur ini tidak berubah)
        $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES (?, ?, ?, 'Menunggu Verifikasi')";
        $stmt_laporan = $conn->prepare($sql_laporan);
        $stmt_laporan->bind_param("sii", $jenis, $form_id, $ppc_id);
        $stmt_laporan->execute();
        $stmt_laporan->close();
    }

    // Jalankan fungsi untuk masing-masing jenis laporan
    prosesLaporan('air', $laporan_air_items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama);
    prosesLaporan('udara', $laporan_udara_items, $conn, $ppc_id, $perusahaan, $alamat, $tanggal, $jenis_kegiatan, $pengambil_sampel, $sub_kontrak_nama);

    $conn->commit();
    header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
    exit();

// ... (semua catch dan rollback tetap sama)

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi error saat menyimpan data: " . $e->getMessage());
    }
} else {
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>