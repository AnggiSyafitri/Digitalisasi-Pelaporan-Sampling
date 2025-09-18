<?php
// actions/simpan_sampling.php

// Panggil file config terpusat.
// Path '../app/config.php' karena kita keluar dari folder 'actions' lalu masuk ke 'app'.
require_once '../app/config.php';

// Keamanan: Cek sesi dan peran
if (!isset($_SESSION['user_id']) || $_SESSION['role_id'] != 1) {
    // Redirect ke login jika akses tidak sah
    header("Location: " . BASE_URL . "/login.php");
    exit();
}

// Pastikan ini adalah request POST dan ada data contoh
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['contoh'])) {
    $ppc_id = $_SESSION['user_id'];
    $perusahaan = $_POST['perusahaan'];
    $alamat = $_POST['alamat'];
    $tanggal = $_POST['tanggal'];

    // Logika untuk memisahkan item air dan udara (sudah benar, tidak perlu diubah)
    $laporan_air_items = [];
    $laporan_udara_items = [];

    foreach ($_POST['contoh'] as $item) {
        if (isset($item['tipe_laporan'])) {
            if ($item['tipe_laporan'] == 'air') {
                $laporan_air_items[] = $item;
            } elseif ($item['tipe_laporan'] == 'udara') {
                $laporan_udara_items[] = $item;
            }
        }
    }

    $conn->begin_transaction(); 

    try {
        // --- PROSES LAPORAN AIR ---
        if (!empty($laporan_air_items)) {
            // ... (Kode untuk insert ke formulir_air dan contoh_air tetap sama) ...
            $sql_form = "INSERT INTO formulir_air (perusahaan, alamat, tanggal, created_by) VALUES (?, ?, ?, ?)";
            $stmt_form = $conn->prepare($sql_form);
            $stmt_form->bind_param("sssi", $perusahaan, $alamat, $tanggal, $ppc_id);
            $stmt_form->execute();
            $form_id = $conn->insert_id;
            $stmt_form->close();

            $sql_contoh = "INSERT INTO contoh_air (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            foreach ($laporan_air_items as $item) {
                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');
                
                $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $item['catatan']);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();

            $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES ('air', ?, ?, 'Menunggu Verifikasi')";
            $stmt_laporan = $conn->prepare($sql_laporan);
            $stmt_laporan->bind_param("ii", $form_id, $ppc_id);
            $stmt_laporan->execute();
            $stmt_laporan->close();
        }

        // --- PROSES LAPORAN UDARA ---
        if (!empty($laporan_udara_items)) {
            // ... (Kode untuk insert ke formulir_udara dan contoh_udara tetap sama) ...
             $sql_form = "INSERT INTO formulir_udara (perusahaan, alamat, tanggal, created_by) VALUES (?, ?, ?, ?)";
            $stmt_form = $conn->prepare($sql_form);
            $stmt_form->bind_param("sssi", $perusahaan, $alamat, $tanggal, $ppc_id);
            $stmt_form->execute();
            $form_id = $conn->insert_id;
            $stmt_form->close();

            $sql_contoh = "INSERT INTO contoh_udara (formulir_id, nama_contoh, jenis_contoh, merek, kode, prosedur, parameter, baku_mutu, catatan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt_contoh = $conn->prepare($sql_contoh);
            foreach ($laporan_udara_items as $item) {
                $jenis_contoh = $item['jenis_contoh'] ?? 'N/A';
                $parameter = isset($item['parameter']) ? implode(', ', $item['parameter']) : '';
                $baku_mutu = ($item['baku_mutu'] === 'Lainnya') ? ($item['baku_mutu_lainnya'] ?? '') : ($item['baku_mutu'] ?? '');

                $stmt_contoh->bind_param("issssssss", $form_id, $item['nama_contoh'], $jenis_contoh, $item['merek'], $item['kode'], $item['prosedur'], $parameter, $baku_mutu, $item['catatan']);
                $stmt_contoh->execute();
            }
            $stmt_contoh->close();

            $sql_laporan = "INSERT INTO laporan (jenis_laporan, form_id, ppc_id, status) VALUES ('udara', ?, ?, 'Menunggu Verifikasi')";
            $stmt_laporan = $conn->prepare($sql_laporan);
            $stmt_laporan->bind_param("ii", $form_id, $ppc_id);
            $stmt_laporan->execute();
            $stmt_laporan->close();
        }

        $conn->commit();
        // PERBAIKAN PENTING: Redirect ke dashboard menggunakan BASE_URL
        header("Location: " . BASE_URL . "/dashboard.php?status=sukses");
        exit();

    } catch (Exception $e) {
        $conn->rollback();
        die("Terjadi error saat menyimpan data: " . $e->getMessage());
    }
} else {
    // PERBAIKAN PENTING: Redirect ke form menggunakan BASE_URL
    header("Location: " . BASE_URL . "/formulir_sampling.php");
    exit();
}
?>
