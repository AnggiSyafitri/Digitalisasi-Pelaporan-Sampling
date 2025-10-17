<?php
// app/config.php

// 1. Mulai Session
// Memulai sesi di satu tempat terpusat.
session_start();

// 2. Konfigurasi Database
// Ganti isinya sesuai dengan pengaturan Laragon & HeidiSQL kamu.
define('DB_HOST', 'localhost'); // Biasanya 'localhost' atau '127.0.0.1'
define('DB_USER', 'root');      // User default Laragon biasanya 'root'
define('DB_PASS', '');          // Password default Laragon biasanya kosong
define('DB_NAME', 'samplingdb');  // Nama database kamu

// 3. Buat Koneksi Database
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Cek koneksi
if ($conn->connect_error) {
    die("Koneksi ke database gagal: " . $conn->connect_error);
}

// 4. Konfigurasi URL Utama (Base URL)
// Ini agar semua link, gambar, dan redirect berfungsi dengan benar.
// Ganti 'digitalisasi-pelaporan.test' sesuai dengan nama virtual host di lingkungan server kamu (aku Laragon).
// Atau jika kamu akses via localhost, ganti menjadi 'http://localhost/digitalisasi-pelaporan-sampling/public'
define('BASE_URL', 'http://digitalisasi-pelaporan-sampling.test/public'); 

// 5. Muat dan jalankan otorisasi waktu
require_once 'otorisasi_waktu.php';

// === BLOK BARU: Muat file fungsi bantuan ===
require_once 'functions.php';
// === AKHIR BLOK BARU ===
?>