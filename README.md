# LITERAKSI: Baca, Pahami, Beraksi

<p align="center">
  <img src="logo.png" width="300" alt="Logo LITERAKSI">
</p>

<p align="center">
  Aplikasi manajemen peminjaman buku perpustakaan sekolah dengan antarmuka yang estetik dan mudah digunakan.
</p>

<p align="center">
<a href="https://github.com/manggalaputraaji/LITERAKSI"><img src="https://img.shields.io/badge/status-dalam%20pengembangan-informational?style=flat-square" alt="Status: Dalam pengembangan"></a>
<img src="https://img.shields.io/badge/license-GPL_3.0-yellowgreen?style=flat-square" alt="Lisensi: GNU GPL 3.0">
<a href="https://github.com/manggalaputraaji/LITERAKSI/Issues"><img src="https://img.shields.io/github/issues/manggalaputraaji/LITERAKSI?color=important&style=flat-square" alt="GitHub issues"></a>
</p>

## Tentang Proyek

LITERAKSI adalah aplikasi manajemen peminjaman buku yang dirancang untuk membantu pengelolaan perpustakaan sekolah menjadi lebih teratur, cepat, dan nyaman. Aplikasi ini berfokus pada pencatatan buku, data peminjam, serta aktivitas peminjaman dalam satu pengalaman yang sederhana dan modern.

Proyek ini dikembangkan sebagai solusi digital untuk mengurangi pencatatan manual dan memberikan pengalaman pengelolaan perpustakaan yang lebih baik bagi petugas maupun siswa.

## Nilai Utama

- Pencatatan peminjaman yang praktis dan rapi.
- Alur kerja yang sesuai dengan kebutuhan perpustakaan sekolah.
- Antarmuka yang bersih, estetis, dan mudah dipahami.
- Fondasi yang dapat dikembangkan untuk katalog, anggota, dan laporan.

## Fitur

### Saat ini dan dalam pengembangan

- [x] Pengelolaan katalog buku dan ketersediaan.
- [x] Pencarian dan penyaringan katalog.
- [x] Data siswa atau anggota perpustakaan.
- [x] Pencatatan peminjaman dan pengembalian.
- [x] Informasi batas waktu dan keterlambatan.
- [x] Diskusi pesan bersama dengan balasan untuk anggota dan petugas.
- [x] Like pada pesan dan balasan Diskusi.
- [x] Profil anggota publik dengan foto, bio, dan daftar buku yang sedang dipinjam.
- [x] Pelaporan pesan diskusi dan moderasi pesan oleh petugas.
- [x] Tampilan responsif untuk berbagai ukuran layar.
- [x] Dasbor aktivitas peminjaman.
- [ ] Ekspor laporan peminjaman.

> Daftar fitur dapat berubah seiring perkembangan proyek.

## Teknologi

LITERAKSI menggunakan PHP, MySQL atau MariaDB, HTML, CSS, dan JavaScript.

<p>
  <img src="https://img.shields.io/badge/HTML5-Documentation-E34F26?logo=html5&style=for-the-badge&labelColor=e3e3e3" alt="HTML5">
  <img src="https://img.shields.io/badge/CSS-Documentation-663399?logo=css&style=for-the-badge&logoColor=663399&labelColor=e3e3e3" alt="CSS">
  <img src="https://img.shields.io/badge/JavaScript-Documentation-bdaa17?logo=javascript&style=for-the-badge&labelColor=e3e3e3&logoColor=bdaa17" alt="JavaScript">
  <img src="https://img.shields.io/badge/PHP-Documentation-777BB4?logo=php&style=for-the-badge&labelColor=e3e3e3" alt="PHP">
  <img src="https://img.shields.io/badge/MySQL-Documentation-00758F?logo=mysql&style=for-the-badge&logoColor=00758F&labelColor=e3e3e3" alt="MySQL">
  <img src="https://img.shields.io/badge/phpMyAdmin-Documentation-F89C0E?logo=phpmyadmin&style=for-the-badge&logoColor=F89C0E&labelColor=e3e3e3" alt="phpMyAdmin">
</p>

## Memulai Pengembangan

Gunakan PHP 8 atau lebih baru dengan ekstensi PDO MySQL, mbstring, dan fileinfo. XAMPP menyediakan kebutuhan tersebut.

### Menyiapkan database MySQL

1. Buka database yang ingin dipakai di phpMyAdmin, pilih tab **Import**, lalu pilih [`database/literaksi.sql`](database/literaksi.sql). File ini menghapus dan membuat ulang tabel aplikasi, lalu mengimpor kembali data dari dump yang disediakan.
2. Secara default, koneksi memakai host `localhost`, database `literaksi`, pengguna `root`, password `root`, dan port `3306`. Ubah nilai di [`config.php`](config.php) atau atur environment variable `LITERAKSI_DB_HOST`, `LITERAKSI_DB_NAME`, `LITERAKSI_DB_USER`, `LITERAKSI_DB_PASS`, dan `LITERAKSI_DB_PORT`.
3. Jalankan XAMPP Apache dan MySQL, lalu buka `http://localhost/LITERAKSI/`.

Impor schema dapat dijalankan dari phpMyAdmin maupun command line MySQL/MariaDB. File ini menghapus tabel lama, termasuk `postingan` dan `diskusi_suka`, lalu membuat schema baru. Buat backup terpisah sebelum mengimpornya.

Sampul buku disajikan dari file lokal. Untuk memasang sampul lewat pengelola berkas, letakkan gambar di `uploads/book-covers` dan beri nama sesuai ID buku, misalnya `BKB0000001.jpg`. Format yang didukung: JPG/JPEG, PNG, GIF, WebP, AVIF, BMP, dan ICO. ID buku dapat dilihat di Manajemen Perpustakaan > Buku. Nama file ID langsung didahulukan; file lama yang menggunakan nama SHA-256 tetap didukung. Buku tanpa file sampul lokal akan menampilkan sampul placeholder.

Schema mengimpor akun dan data yang ada di dump database yang disediakan. Data tabel `postingan` sengaja tidak dipulihkan karena tabel tersebut tidak digunakan aplikasi.

Jika database tidak memiliki tabel pengaturan profil, aplikasi mencoba membuatnya saat terhubung. Akun database perlu izin `CREATE TABLE` dan `ALTER TABLE` untuk penyesuaian schema otomatis. Foto profil dan sampul disimpan di folder `uploads`; pastikan PHP dapat menulis ke folder tersebut.

Anggota dapat mendaftar dari halaman autentikasi. Penulis, penerbit, kategori, dan buku dapat ditambahkan melalui **Manajemen Perpustakaan**. Anggota dapat memilih tema otomatis, terang, gelap, OLED, atau Flashbang (tema putih terang tanpa animasi berkedip), serta memilih warna banner profil atau mengunggah foto JPG, PNG, atau WebP (maksimal 3 MB).

Untuk database yang sudah ada, pilih database `literaksi` di phpMyAdmin dan jalankan hanya migrasi yang belum diterapkan:

1. [`20261112_discussion.sql`](database/migrations/20261112_discussion.sql)
2. [`20261114_discussion_reports.sql`](database/migrations/20261114_discussion_reports.sql)
3. [`20261114_discussion_report_reasons.sql`](database/migrations/20261114_discussion_report_reasons.sql)
4. [`20261115_discussion_likes.sql`](database/migrations/20261115_discussion_likes.sql)
5. [`20261112_profile_banner_photo.sql`](database/migrations/20261112_profile_banner_photo.sql)
6. [`20261112_profile_banner_custom_color.sql`](database/migrations/20261112_profile_banner_custom_color.sql)
7. [`20261114_member_bio.sql`](database/migrations/20261114_member_bio.sql)
8. [`20261112_optional_member_email.sql`](database/migrations/20261112_optional_member_email.sql)
9. [`20261112_member_theme_modes.sql`](database/migrations/20261112_member_theme_modes.sql)

Instalasi baru mendapatkan seluruh tabel dan kolom fitur tersebut dari [`database/literaksi.sql`](database/literaksi.sql).

## Halaman Aplikasi

- Tamu: `guest.php?page=katalog` dan `guest.php?page=detail_buku&id=...`.
- Anggota: `user.php?page=dashboard`, `katalog`, `detail_buku`, `peminjaman`, `notifikasi`, dan `profile`; diskusi bersama tersedia di `diskusi.php`.
- Petugas: `admin.php?page=dashboard`, `peminjaman`, `manajemen`, dan `manajemen_user`. Pengelolaan laporan dan penghapusan pesan tersedia langsung di halaman `diskusi.php`.
- Autentikasi: `autentikasi.php?action=login`, `register`, `loginadmin`, dan `logout`.

## Struktur Proyek

```text
LITERAKSI/
|-- admin.php
|-- adminlogic.php
|-- adminkelola.php
|-- autentikasi.php
|-- config.php
|-- diskusi.php
|-- fungsi.php
|-- guest.php
|-- index.html
|-- layout.php
|-- preferensi.php
|-- user.php
|-- database/
|   |-- literaksi.sql
|   `-- migrations/
|-- assets/
|   |-- css/
|   `-- img/
|-- uploads/
`-- README.md
```
## Status Proyek

LITERAKSI merupakan proyek aktif yang masih dalam tahap awal. Arsitektur, fitur, dan dokumentasi dapat berkembang sesuai kebutuhan pengembangan.

## Kontribusi

Saran dan kontribusi sangat terbuka. Jika ingin berkontribusi:

1. Buka issue untuk menjelaskan masalah atau usulan.
2. Buat branch khusus untuk perubahan yang dikerjakan.
3. Gunakan commit yang jelas dan terfokus.
4. Buat pull request dengan deskripsi perubahan yang lengkap.
5. Sertakan screenshot jika perubahan memengaruhi tampilan.

Sebelum membuat issue baru, silakan periksa issue yang sudah tersedia untuk menghindari duplikasi.

## Lisensi

README proyek menyatakan GNU General Public License 3.0, tetapi file teks `LICENSE` belum tersedia di checkout ini.

## Pengembang

Dibuat dan dikelola oleh [manggalaputraaji](https://github.com/manggalaputraaji).

---

<p align="center">
  Dibuat untuk menjadikan pengelolaan peminjaman buku sekolah lebih sederhana, rapi, dan nyaman.
</p>
