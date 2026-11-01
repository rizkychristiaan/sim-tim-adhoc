# MODUL PANDUAN LENGKAP: ARSITEKTUR KODE, FUNGSI SETIAP FILE, DAN KUSTOMISASI UI
**Sistem Informasi Manajemen Tim Ad Hoc Berbasis Web (LPAIP UKDW)**

---

## DAFTAR ISI
1. [Arsitektur Keseluruhan & Pemisahan Kode (*Separation of Concerns*)](#1-arsitektur-keseluruhan--pemisahan-kode)
2. [Konsep Fundamental Sistem (Session, Database PDO, RBAC, Auto-Loader)](#2-konsep-fundamental-sistem)
3. [Bedah Rinci File per File (Fungsi, Alasan, & Cara Kerja)](#3-bedah-rinci-file-per-file)
   - [A. Konfigurasi Sistem (`app/config/`)](#a-konfigurasi-sistem-appconfig)
   - [B. Modul Autentikasi (`app/auth/`)](#b-modul-autentikasi-appauth)
   - [C. Modul Komponen Bersama (`app/includes/`)](#c-modul-komponen-bersama-appincludes)
   - [D. Modul Halaman Utama (`app/pages/`)](#d-modul-halaman-utama-apppages)
   - [E. Modul Administrator (`app/admin/`)](#e-modul-administrator-appadmin)
   - [F. Modul Desain Tampilan (`app/assets/css/`)](#f-modul-desain-tampilan-appassetscss)
   - [G. Modul Interaktivitas Pengguna (`app/assets/js/`)](#g-modul-interaktivitas-pengguna-appassetsjs)
4. [Panduan Kustomisasi Teks & Kata-Kata Antarmuka (UI Text Guide)](#4-panduan-kustomisasi-teks--kata-kata-antarmuka)
5. [Panduan Kustomisasi Desain & Warna Tema](#5-panduan-kustomisasi-desain--warna-tema)

---

## 1. Arsitektur Keseluruhan & Pemisahan Kode

Berdasarkan struktur proyek Anda:

```text
Skripsi/
└── app/
    ├── admin/          -> Pengelolaan akun pengguna khusus role Admin
    ├── assets/
    │   ├── css/        -> Murni aturan desain visual (CSS) per halaman
    │   ├── img/        -> Berkas citra (Logo resmi UKDW, header, footer)
    │   └── js/         -> Murni instruksi logika interaktif (JavaScript)
    ├── auth/           -> Pintu masuk sistem (Login, Logout, Registrasi)
    ├── config/         -> Koneksi database PDO dan pengaturan SMTP Email
    ├── includes/       -> Bagian berulang (Header, Footer, Navbar, Sidebar, PHPMailer)
    ├── pages/          -> Struktur data dan proses bisnis utama (HTML + PHP)
    ├── uploads/        -> Direktori penyimpanan fisik dokumen yang diunggah
    └── index.php       -> Pengarah awal (Redirector) ke halaman login/dashboard
```

### Mengapa Kode Harus Dipisahkan (*Separation of Concerns*)?
1. **Prinsip *Single Responsibility*:** File PHP tidak lagi dipenuhi ribuan baris CSS atau JavaScript. Setiap file hanya bertanggung jawab atas satu peran:
   * **PHP (`.php`)**: Memproses data dari database dan menyusun kerangka HTML.
   * **CSS (`.css`)**: Mengatur estetika warna maroon UKDW, tata letak, dan responsivitas.
   * **JavaScript (`.js`)**: Menangani interaksi tombol, jendela *modal pop-up*, dan validasi form.
2. **Kemudahan Perawatan (*Maintainability*):** Jika ingin mengubah kata-kata atau tombol, Anda cukup membuka file PHP. Jika ingin mengganti warna tema, cukup membuka file CSS. Jika ingin mengubah animasi popup modal, cukup membuka file JS.
3. **Nilai Akademik Tinggi untuk Skripsi:** Struktur modular ini menunjukkan penerapan standar industri rekayasa perangkat lunak modern yang sangat disukai penguji saat sidang skripsi.

---

## 2. Konsep Fundamental Sistem

### A. Konsep `$_SESSION` (PHP Session)
* **Fungsi:** Menyimpan data identitas pengguna yang sedang aktif (seperti `user_id`, `nama`, `role`, `nip_nim`) selama pengguna tersebut menjelajahi berbagai halaman web.
* **Mengapa Digunakan?** Protokol web (HTTP) bersifat ***stateless*** (tidak memiliki daya ingat). Tanpa session, server akan langsung "lupa" siapa Anda setiap kali Anda mengklik halaman baru, sehingga Anda akan dipaksa login berulang kali di setiap klik.
* **Cara Kerjanya:**
  1. Saat login berhasil di [login.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/auth/login.php), server menjalankan perintah `session_start()`.
  2. Server membuat sebuah kode acak unik bernama **Session ID** dan menyimpannya di browser pengguna sebagai *cookie* (`PHPSESSID`).
  3. Di memori server, data pengguna disimpan:
     ```php
     $_SESSION['user_id'] = $user['id'];
     $_SESSION['nama']    = $user['nama'];
     $_SESSION['role']    = $user['role']; // 'admin', 'ketua', atau 'anggota'
     ```
  4. Ketika pengguna membuka halaman [dashboard.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/dashboard.php), server memeriksa `PHPSESSID`. Jika valid, server langsung mengenali pengguna tersebut tanpa meminta login ulang.
  5. Saat pengguna mengklik *Logout*, file [logout.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/auth/logout.php) memanggil `session_destroy()`, sehingga tiket sesi dihapus permanen.

---

### B. Konsep Basis Data: PDO (*PHP Data Objects*) & Prepared Statements
* **Fungsi:** Sebagai jembatan penghubung aman antara program PHP dan basis data MariaDB/MySQL.
* **Mengapa Digunakan?** Jauh lebih aman dan modern dibandingkan fungsi lama `mysqli_query` biasa.
* **Cara Kerjanya (Pencegahan SQL Injection):**
  Alih-alih langsung menyatukan teks input pengguna ke dalam perintah SQL (yang rentan dibobol peretas), sistem menggunakan tanda tanya `?` atau *named placeholder* (`:email`), lalu mengirimkan nilainya secara terpisah melalui mekanisme *binding*:
  ```php
  // Nilai input dipisahkan dari struktur logika SQL
  $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
  $stmt->execute([$email]); // Aman 100% dari injeksi SQL berbahaya
  ```

---

### C. Konsep Hak Akses Pengguna (*Role-Based Access Control / RBAC*)
Sistem memiliki 3 tingkat hak akses (*Role*):
1. **Admin (Sekretariat/Pimpinan LPAIP):** Memiliki hak penuh membuat Tim Ad Hoc baru, menerbitkan SK, mengelola akun user, dan melihat seluruh tim.
2. **Ketua Tim Ad Hoc:** Dapat membagi tugas kepada anggota, mengunggah Laporan Pertanggungjawaban (LPJ), dan mengubah persentase progres tim yang dipimpinnya.
3. **Anggota Tim:** Menerima tugas, memperbarui status tugas yang ditugaskan kepadanya, dan mengunduh dokumen tim.

---

### D. Konsep Auto-Loader CSS & JS Dinamis
* **Fungsi:** Memuat file CSS dan JS secara otomatis berdasarkan nama halaman yang sedang dibuka.
* **Cara Kerjanya:**
  * Di [header.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/includes/header.php): Sistem memeriksa nama file yang sedang aktif. Jika Anda membuka `dashboard.php`, sistem secara otomatis memeriksa apakah file `app/assets/css/dashboard.css` ada di disk. Jika ada, otomatis disisipkan tag `<link rel="stylesheet">`.
  * Di [footer.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/includes/footer.php): Sistem otomatis memeriksa apakah file `app/assets/js/<nama_halaman>.js` ada. Jika ada, otomatis disisipkan tag `<script src="...">` sebelum `</body>`.

---

## 3. Bedah Rinci File per File

### A. Konfigurasi Sistem (`app/config/`)

#### 1. `database.php`
* **Fungsi:** Pusat pengaturan koneksi ke basis data MySQL/MariaDB dan fungsi global otomatis seperti pengingat tenggat waktu (*deadline reminders*).
* **Alasan Digunakan:** Agar kredensial database (host, user, password, dbname) hanya diatur di 1 tempat terpusat.
* **Cara Kerja:** Menginstansiasi objek `new PDO(...)` dengan opsi `ATTR_ERRMODE => ERRMODE_EXCEPTION` agar setiap kendala query langsung dilaporkan dengan aman.

#### 2. `email.php`
* **Fungsi:** Konfigurasi akun SMTP (misal: Gmail atau server mail UKDW) untuk keperluan notifikasi otomatis melalui email.
* **Alasan Digunakan:** Memisahkan data rahasia server email (Host, Port, Username, Password aplikasi) agar mudah diganti ketika berpindah dari server lokal (localhost) ke hosting publik.

---

### B. Modul Autentikasi (`app/auth/`)

#### 1. `login.php`
* **Fungsi:** Gerbang masuk pengguna ke sistem.
* **Cara Kerja:** Menerima NIP/NIM/Email dan kata sandi. Mengambil data akun dari database, lalu memeriksa kecocokan kata sandi menggunakan fungsi keamanan bawaan PHP: `password_verify($password_input, $password_hash_db)`. Jika cocok, sesi diinisialisasi dan diarahkan ke `dashboard.php`.

#### 2. `register.php`
* **Fungsi:** Pendaftaran akun baru bagi dosen atau tenaga kependidikan UKDW.
* **Cara Kerja:** Memvalidasi input (memastikan NIP/NIM belum terdaftar). Mengamankan password menggunakan algoritma hashing standar industri: `password_hash($password, PASSWORD_BCRYPT)`.

#### 3. `logout.php`
* **Fungsi:** Menutup sesi pengguna secara aman.
* **Cara Kerja:** Menghapus seluruh array `$_SESSION`, menghancurkan cookie sesi, dan mengalihkan browser kembali ke `login.php`.

---

### C. Modul Komponen Bersama (`app/includes/`)

#### 1. `header.php`
* **Fungsi:** Menampung struktur pembuka HTML (`<!DOCTYPE html>`, `<head>`, meta tag, font Google *Inter*, Bootstrap 5 CSS, stylesheet global [style.css](file:///c:/Users/Lenovo/Documents/Skripsi/app/assets/css/style.css), dan Auto-Loader CSS).

#### 2. `navbar.php`
* **Fungsi:** Bilah menu navigasi bagian atas yang memuat Logo UKDW, nama sistem LPAIP, lonceng notifikasi dinamis (menghitung pesan yang belum dibaca), serta menu dropdown profil/logout.

#### 3. `sidebar.php`
* **Fungsi:** Bilah navigasi vertikal di sisi kiri dengan latar belakang gradasi maroon gelap UKDW (`#3d0712` ke `#260309`).
* **Catatan Desain:** Sidebar ini aktif saat pengguna masuk ke dalam halaman kerja tim ([tim_detail.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/tim_detail.php)) untuk memudahkan navigasi antar-modul.

#### 4. `footer.php`
* **Fungsi:** Menutup tag pembungkus, menampilkan teks hak cipta (*copyright*), memanggil pustaka Bootstrap 5 JS Bundle, skrip navigasi toggle sidebar mobile, dan Auto-Loader JS halaman.

#### 5. `PHPMailer/` (`Exception.php`, `PHPMailer.php`, `SMTP.php`)
* **Fungsi:** Pustaka (*library*) standar dunia untuk pengiriman email berbasis protokol SMTP secara andal.

---

### D. Modul Halaman Utama (`app/pages/`)

#### 1. `dashboard.php`
* **Fungsi:** Halaman beranda utama yang mengadopsi tampilan khas **e-Class UKDW**.
* **Cara Kerja:** Mengambil daftar seluruh tim ad hoc di mana user tersebut tergabung (atau semua tim jika rolenya Admin), menampilkan kotak pengumuman terbaru di kolom kiri, dan menyusun kartu tim di kolom kanan.

#### 2. `tim_detail.php`
* **Fungsi:** Ruang kerja digital (*digital workspace*) tim ad hoc yang dipilih.
* **Fitur Utama (5 Tab Navigasi):**
  1. **Tab Beranda:** Informasi umum tim, nomor SK, masa tugas, dan bilah persentase capaian progres tim.
  2. **Tab Tugas:** Pembagian tugas kepada anggota dan status eksekusinya.
  3. **Tab Dokumen:** Berkas pendukung dan notula rapat tim.
  4. **Tab Anggota:** Struktur tim (Ketua, Sekretaris, Anggota).
  5. **Tab LPJ:** Tempat pengunggahan berkas Laporan Pertanggungjawaban akhir oleh Ketua Tim.

#### 3. `tim_adhoc.php`
* **Fungsi:** Halaman manajemen data master tim (menambah tim baru, menetapkan nomor SK, mengedit masa tugas, menonaktifkan tim). Khusus diakses oleh Admin.

#### 4. `tugas.php`
* **Fungsi:** Pemantauan dan manajemen tugas lintas tim. Anggota tim dapat mengubah status tugas (*Belum Dimulai, Sedang Dikerjakan, Selesai*) serta melampirkan berkas bukti kerja.

#### 5. `dokumen.php`
* **Fungsi:** Repositori arsip digital tim ad hoc (SK Tugas, Panduan, Berita Acara, Laporan Antara).

#### 6. `profile.php`
* **Fungsi:** Pengelolaan profil pribadi pengguna, pembaruan nomor kontak/email, serta pergantian kata sandi akun.

#### 7. `download_sk.php` & `surat_tugas.php`
* **Fungsi:** Generator dokumen Surat Keputusan (SK) dan Surat Tugas resmi berformat cetak/unduh lengkap dengan kop surat resmi UKDW.

#### 8. `mark_notif.php`
* **Fungsi:** Skrip pemroses yang berjalan cepat di latar belakang untuk mengubah status notifikasi menjadi "sudah dibaca" (*is_read = 1*) saat pengguna mengklik lonceng notifikasi.

---

### E. Modul Administrator (`app/admin/`)

#### `kelola_user.php`
* **Fungsi:** Dasbor khusus Admin untuk menambah akun dosen/staf baru, mengatur peran (*role*), dan melakukan *reset password* jika ada pengguna yang lupa sandi.

---

### F. Modul Desain Tampilan (`app/assets/css/`)

| File CSS | Fungsi & Peran Styling |
| :--- | :--- |
| **`style.css`** | Fondasi global seluruh sistem: variabel warna maroon UKDW (`--ukdw-maroon: #6b0c22`), override tombol Bootstrap, gaya navbar, sidebar, badge status, dan tipografi Inter. |
| **`dashboard.css`** | Penataan bilah atas e-Class UKDW (`.eclass-topbar`), kartu kelas tim (`.eclass-team-card`), garis batas kiri maroon (`border-left: 6px solid #6b0c22`), dan efek melayang saat kursor diarahkan (`hover box-shadow`). |
| **`tim_detail.css`** | Desain tab navigasi aktif, kartu ringkasan progres persentase, dan tata letak daftar anggota. |
| **`dokumen.css`** | Tampilan ikon jenis file (PDF/Word/Excel), ukuran kartu pratinjau dokumen, dan tabel arsip. |
| **`login.css`** & **`register.css`** | Penataan kartu form login/registrasi agar berada tepat di tengah layar (*centered card*), gradasi latar belakang, dan logo UKDW. |

---

### G. Modul Interaktivitas Pengguna (`app/assets/js/`)

| File JS | Fungsi & Logika Interaktif |
| :--- | :--- |
| **`tim_detail.js`** | Mengatur perpindahan tab tanpa memuat ulang halaman, kalkulasi progres visual, dan pembukaan modal LPJ. |
| **`tim_adhoc.js`** | Fungsi `resetForm()` saat klik tombol tambah tim baru dan fungsi `editTim()` untuk mengisi formulir edit secara otomatis. |
| **`dokumen.js`** | Fungsi `editDokumen()` untuk membuka modal edit metadata berkas. |
| **`tugas.js`** | Fungsi `openUpdateModal()` untuk menampilkan daftar berkas lampiran yang diunggah serta form pembaruan status tugas. |
| **`profile.js`** | Fungsi `togglePasswordVisibility()` untuk mengubah tipe input password menjadi teks saat ikon mata diklik. |
| **`login.js`** & **`register.js`** | Validasi masukan pengguna sebelum dikirim ke server (memastikan email valid dan konfirmasi password cocok). |

---

## 4. Panduan Kustomisasi Teks & Kata-Kata Antarmuka

Jika Anda ingin mengubah kata-kata, judul, atau tulisan label pada antarmuka web, berikut adalah tabel panduan lokasi file dan barisnya:

### 1. Mengubah Nama Sistem & Lembaga di Bilah Atas
* **File:** [app/includes/navbar.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/includes/navbar.php#L31-L32)
* **Baris:** Baris 31 - 32
* **Kode Asal:**
  ```html
  <h6 class="mb-0 fw-bold text-dark">Sistem Informasi Manajemen Tim Ad Hoc</h6>
  <small class="text-muted"><strong style="color: #6b0c22;">LPAIP</strong> &bull; Universitas Kristen Duta Wacana</small>
  ```
* **Cara Mengubah:** Ganti teks `"Sistem Informasi Manajemen Tim Ad Hoc"` atau `"LPAIP"` sesuai kebutuhan Anda.

### 2. Mengubah Teks Topbar Maroon e-Class
* **File:** [app/pages/dashboard.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/dashboard.php#L102)
* **Baris:** Baris 102
* **Kode Asal:**
  ```html
  <span>UNIVERSITAS KRISTEN DUTA WACANA &bull; SIM TIM AD HOC LPAIP</span>
  ```

### 3. Mengubah Semester Akademik & Teks Banner Dashboard
* **File:** [app/pages/dashboard.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/dashboard.php#L134-L141)
* **Baris:** Baris 134 - 141
* **Kode Asal:**
  ```html
  Semester Gasal <?= date('Y') ?>/<?= date('Y')+1 ?>
  ```
* **Cara Mengubah:** Jika ingin mengubah menjadi Semester Genap, ganti kata `"Semester Gasal"` menjadi `"Semester Genap"`.

### 4. Mengubah Teks Petunjuk Kotak Kuning di Dashboard
* **File:** [app/pages/dashboard.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/dashboard.php#L206-L208)
* **Baris:** Baris 206 - 208
* **Kode Asal:**
  ```html
  <strong class="d-block mb-1 text-uppercase"><i class="bi bi-info-circle-fill me-1"></i> Petunjuk Penggunaan:</strong>
  Klik pada salah satu nama tim di sebelah kanan...
  ```
* **Cara Mengubah:** Edit kalimat di dalam tag tersebut dengan bahasa petunjuk yang Anda inginkan.

### 5. Mengubah Nama Tab di Halaman Detail Tim
* **File:** [app/pages/tim_detail.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/pages/tim_detail.php#L310-L330)
* **Cara Mengubah:** Cari teks `<button class="nav-link ...">` yang memuat tulisan *Beranda*, *Tugas*, *Dokumen*, *Anggota*, atau *Laporan LPJ*, lalu ubah teksnya sesuai keinginan.

### 6. Mengubah Teks Hak Cipta di Footer Bawah
* **File:** [app/includes/footer.php](file:///c:/Users/Lenovo/Documents/Skripsi/app/includes/footer.php#L3)
* **Baris:** Baris 3
* **Kode Asal:**
  ```html
  &copy; 2026 <strong>LPAIP UKDW</strong> - Sistem Informasi Manajemen Tim Ad Hoc Berbasis Web (Metode Prototype)
  ```

---

## 5. Panduan Kustomisasi Desain & Warna Tema

Semua warna utama sistem dikendalikan secara terpusat menggunakan **CSS Variables** di baris teratas file [app/assets/css/style.css](file:///c:/Users/Lenovo/Documents/Skripsi/app/assets/css/style.css#L8-L17):

```css
:root {
    --primary-color: #6b0c22;       /* Warna Utama: Maroon Khas UKDW */
    --primary-hover: #83102b;       /* Maroon Sedikit Terang Saat Disorot */
    --ukdw-gold: #d97706;           /* Warna Aksen Emas UKDW */
    --ukdw-gold-hover: #b45309;     /* Warna Emas Saat Disorot */
    --secondary-bg: #f8fafc;        /* Warna Latar Belakang Lembut */
    --sidebar-width: 260px;         /* Lebar Menu Samping */
}
```

* **Jika ingin mengganti warna dominan seluruh aplikasi:** Anda cukup mengganti kode heksadesimal `#6b0c22` di baris tersebut. Seluruh tombol, header, navbar, badge, dan garis aksen tim akan otomatis berubah secara serentak di semua halaman.
* **Jika ingin mengatur ketebalan bayangan kartu:** Buka [app/assets/css/dashboard.css](file:///c:/Users/Lenovo/Documents/Skripsi/app/assets/css/dashboard.css#L63) dan sesuaikan angka pada properti `box-shadow: 0 4px 12px rgba(0,0,0,0.06);`.

---
*Modul ini dapat dijadikan acuan utama dalam penulisan Bab 3 (Metodologi/Perancangan Sistem) dan Bab 4 (Implementasi & Pembahasan) pada laporan Skripsi Anda.*
