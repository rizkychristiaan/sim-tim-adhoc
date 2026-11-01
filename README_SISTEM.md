# 🚀 SISTEM INFORMASI MANAJEMEN TIM AD HOC LPAIP UKDW
**Skripsi: Rancang Bangun Sistem Manajemen Tim Ad Hoc Berbasis Web Menggunakan Metode Prototype**

---

### 📂 Struktur Modul Sistem
- `database.sql` : Skema basis data MySQL & data sampel awal untuk pengujian.
- `config/database.php` : Modul koneksi database PDO MySQL & helper fungsi.
- `includes/` : Template header, navbar, sidebar, dan footer berbasis **Bootstrap 5**.
- `login.php` : Halaman Login modern & tombol instant akun demo.
- `dashboard.php` : Dashboard visual statistik tim, penugasan, dan pengingat *deadline*.
- `tim_adhoc.php` : Tata kelola data Tim Ad Hoc (Tambah, Edit, Hapus, Filter Status).
- `tim_detail.php` : Detail Tim, Plotting Anggota Personil, Penugasan Task, & Repositori Berkas LPJ.
- `tugas.php` : *Task Management* & Pembaruan Status Pengerjaan Tugas (*Real-time*).
- `surat_tugas.php` : Otomatisasi Cetak Surat Tugas / SK Resmi LPAIP UKDW (Format Siap Cetak/PDF).
- `dokumen.php` : Repositori Dokumen & Unggah Berkas Kegiatan / LPJ.
- `kelola_user.php` : Kelola Hak Akses Pengguna (*Role-Based Access Control*: Admin, Ketua, Anggota).
- `profile.php` : Pengaturan Profil Pengguna & Ganti Password.

---

### 🛠️ Cara Menjalankan Sistem di XAMPP:

1. **Nyalakan Service XAMPP**:
   * Buka **XAMPP Control Panel**.
   * Klik **Start** pada **Apache** dan **MySQL**.

2. **Import Database `db_sim_adhoc`**:
   * Buka browser, masuk ke `http://localhost/phpmyadmin`.
   * Buat database baru bernama **`db_sim_adhoc`**.
   * Klik tab **Import**, pilih file `database.sql` dari folder `c:\Users\Lenovo\Documents\Skripsi\database.sql`, lalu klik **Go/Kirim**.

3. **Akses Aplikasi Web**:
   * Buka browser dan ketik alamat:
     `http://localhost/Skripsi`
   * Atau dari terminal PowerShell di folder ini, Anda dapat menjalankan server lokal PHP:
     `C:\xampp\php\php.exe -S localhost:8000`
     lalu buka `http://localhost:8000` di browser.

---

### 🔑 Akun Demo Pengujian (Login Instant):

1. **Akun Admin LPAIP Utama**:
   * Email: `admin@lpaip.ukdw.ac.id`
   * Password: `admin123`

2. **Akun Ketua Tim (Dosen Pembimbing)**:
   * Email: `antonius@lpaip.ukdw.ac.id`
   * Password: `user123`

3. **Akun Anggota Tim (Mahasiswa / Staf)**:
   * Email: `rizky.chrales@ti.ukdw.ac.id`
   * Password: `user123`
