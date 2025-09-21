# Sistem Digitalisasi Pelaporan Sampling & Pengujian Onsite

Sistem ini merupakan aplikasi web yang dibangun untuk Latihan Dasar (Latsar) CPNS, dengan studi kasus di Laboratorium Pengujian BSPJI Medan. Tujuannya adalah untuk mendigitalisasi alur kerja pelaporan hasil sampling dan pengujian mutu lingkungan, mulai dari input data di lapangan hingga laporan akhir yang siap cetak.

## Fitur Utama

- **Alur Kerja Berjenjang**: Sistem menerapkan proses persetujuan multi-level untuk menjamin kualitas dan validitas data.
- **Manajemen Peran Pengguna**: Terdapat empat hak akses yang berbeda (PPC, Penyelia, Manajer Teknis, Penerima Contoh), masing-masing dengan tugas dan wewenang yang spesifik.
- **Input Data Dinamis**: Petugas di lapangan (PPC) dapat dengan mudah menambahkan beberapa contoh uji dalam satu laporan, dengan pilihan parameter dan prosedur yang menyesuaikan secara otomatis.
- **Pelacakan Real-time**: Semua pengguna dapat melihat status progres sebuah laporan, mulai dari "Menunggu Verifikasi" hingga "Selesai".
- **Notifikasi (Konseptual)**: Sistem dirancang untuk memberikan notifikasi tugas kepada setiap pengguna yang relevan.
- **Arsip Digital**: Semua laporan yang telah dibuat akan tersimpan secara terpusat dan dapat diakses kembali.

## Alur Kerja Sistem

1.  **Input Laporan**: **Petugas Pengambil Contoh (PPC)** membuat laporan baru, mengisi informasi umum, dan menambahkan satu atau lebih data contoh uji beserta parameternya.
2.  **Verifikasi**: **Penyelia** menerima laporan, memeriksa kelengkapan data, dan dapat menyetujuinya untuk diteruskan atau mengembalikannya ke PPC untuk revisi.
3.  **Persetujuan**: **Manajer Teknis** meninjau laporan yang telah diverifikasi dan memberikan persetujuan akhir (ACC). Laporan juga dapat dikembalikan ke Penyelia jika perlu.
4.  **Penyelesaian**: **Penerima Contoh** melihat daftar laporan yang sudah final (ACC), membukanya untuk dicetak, lalu mengubah statusnya menjadi "Selesai".

## Teknologi yang Digunakan

- **Bahasa**: PHP (Native)
- **Database**: MySQL / MariaDB
- **Front-End**: HTML, CSS, JavaScript (Vanilla)
- **Lingkungan Pengembangan**: Laragon, HeidiSQL, Visual Studio Code

## Struktur Proyek

/digitalisasi-pelaporan-sampling
|
|-- /app                 -> Konfigurasi inti (database) dan fungsi bantuan.
|-- /actions             -> Skrip untuk memproses logika (simpan, verifikasi, setuju).
|-- /public              -> Folder utama yang diakses browser (halaman & aset).
|   |-- /assets          -> File CSS, JS, dan gambar.
|   |-- index.php        -> (Rencana) Titik masuk utama.
|   |-- (semua file .php yang menampilkan halaman)
|
|-- /templates           -> Potongan UI yang bisa dipakai ulang (header, footer).
|-- .gitignore           -> Mengabaikan file yang tidak perlu di-commit.
|-- README.md            -> Dokumentasi ini.
|-- samplingdb.sql       -> Struktur database.

## Instalasi & Setup (Lokal)

1.  Pastikan kamu memiliki lingkungan server lokal seperti Laragon atau XAMPP.
2.  *Clone* repositori ini ke direktori `www` server lokalmu.
3.  Impor file `samplingdb.sql` ke database manager (HeidiSQL, phpMyAdmin) untuk membuat tabel yang dibutuhkan.
4.  Salin atau buat file `app/config.php` dan sesuaikan kredensial database (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) dan `BASE_URL` sesuai dengan pengaturan lokalmu.
5.  Gunakan file `public/registrasi.php` untuk membuat beberapa *user* awal dengan peran yang berbeda.
6.  Akses proyek melalui URL yang telah kamu atur (misal: `http://nama-proyek.test/public/login.php`).

**Penting**: Untuk penggunaan di lingkungan produksi, pastikan untuk menghapus file `registrasi.php` dan `debug_session.php`.