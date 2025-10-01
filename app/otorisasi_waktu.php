<?php
// app/otorisasi_waktu.php

/**
 * Mengatur zona waktu ke Waktu Indonesia Barat (WIB).
 */
date_default_timezone_set('Asia/Jakarta');

/**
 * Konfigurasi jam akses untuk setiap peran.
 * Format: 'role_id' => ['jam_mulai', 'jam_selesai']
 * Disiapkan untuk bisa diubah per role di kemudian hari.
 */
$waktu_akses_roles = [
    // Role 1: Petugas Pengambil Contoh
    1 => ['00:01', '23:59'],
    // Role 2: Penyelia
    2 => ['00:01', '23:59'],
    // Role 3: Manajer Teknis
    3 => ['00:01', '23:59'],
    // Role 4: Penerima Contoh
    4 => ['00:01', '23:59'],
];

/**
 * Fungsi untuk memeriksa apakah pengguna diizinkan mengakses sistem pada waktu saat ini.
 *
 * @param int $role_id ID peran dari pengguna yang login.
 * @return bool True jika diizinkan, false jika di luar jam kerja.
 */
function cekWaktuAkses($role_id) {
    global $waktu_akses_roles;

    // Cek apakah role ada di konfigurasi
    if (!isset($waktu_akses_roles[$role_id])) {
        return false; // Jika role tidak terdefinisi, tolak akses
    }

    // Ambil waktu saat ini
    $waktu_sekarang = new DateTime();

    // Ambil jam mulai dan selesai dari konfigurasi
    $jam_mulai_str = $waktu_akses_roles[$role_id][0];
    $jam_selesai_str = $waktu_akses_roles[$role_id][1];

    // Buat objek DateTime untuk perbandingan
    $jam_mulai = DateTime::createFromFormat('H:i', $jam_mulai_str);
    $jam_selesai = DateTime::createFromFormat('H:i', $jam_selesai_str);

    // Periksa apakah waktu saat ini berada di dalam rentang yang diizinkan
    if ($waktu_sekarang >= $jam_mulai && $waktu_sekarang <= $jam_selesai) {
        return true; // Diizinkan
    }

    return false; // Ditolak
}

?>