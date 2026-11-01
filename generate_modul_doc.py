import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from build_helpers import set_cell_background, set_cell_margins, add_callout, add_code_block, format_table

doc = docx.Document()

# Set standard margins (1 inch)
for section in doc.sections:
    section.top_margin = Inches(1)
    section.bottom_margin = Inches(1)
    section.left_margin = Inches(1)
    section.right_margin = Inches(1)

# Helper styling
MAROON = RGBColor(107, 12, 34)   # #6B0C22
GOLD = RGBColor(217, 119, 6)     # #D97706
DARK_GRAY = RGBColor(51, 65, 85)

def add_title(text):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(18)
    run.font.bold = True
    run.font.color.rgb = MAROON
    return p

def add_subtitle(text):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(20)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(11)
    run.font.bold = True
    run.font.color.rgb = GOLD
    return p

def add_h1(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(18)
    p.paragraph_format.space_after = Pt(6)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(14)
    run.font.bold = True
    run.font.color.rgb = MAROON
    return p

def add_h2(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(12)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(11.5)
    run.font.bold = True
    run.font.color.rgb = GOLD
    return p

def add_h3(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(8)
    p.paragraph_format.space_after = Pt(2)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(10.5)
    run.font.bold = True
    run.font.color.rgb = RGBColor(30, 41, 59)
    return p

def add_p(text, bold_prefix="", italic=False):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = 'Arial'
        r_pre.font.size = Pt(10)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(30, 41, 59)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(10)
    run.font.italic = italic
    run.font.color.rgb = DARK_GRAY
    return p

def add_bullet(text, bold_prefix=""):
    p = doc.add_paragraph(style='List Bullet')
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = 'Arial'
        r_pre.font.size = Pt(10)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(30, 41, 59)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(10)
    run.font.color.rgb = DARK_GRAY
    return p

# ==============================================================================
# COVER & HEADER
# ==============================================================================
add_title("MODUL PANDUAN LENGKAP ARSITEKTUR KODE, KEAMANAN PASSWORD HASH, DAN NOTIFIKASI EMAIL")
add_subtitle("Sistem Informasi Manajemen Tim Ad Hoc Berbasis Web - LPAIP UKDW\nMetode Pengembangan: Prototyping | Arsitektur: Modular Separation of Concerns")

add_callout(doc, 
    "Dokumen modul ini disusun secara rinci dan komprehensif sebagai buku panduan teknis bagi pengembang sistem "
    "sekaligus bahan penguasaan materi untuk ujian skripsi/sidang sarjana. Di dalamnya mencakup fungsi setiap file, "
    "alur logika, cara kerja session, keamanan password hash bcrypt, arsitektur email SMTP, serta panduan modifikasi UI.",
    "PENGANTAR MODUL SKRIPSI")

# ==============================================================================
# BAB 1: ARSITEKTUR SISTEM & PEMISAHAN KODE
# ==============================================================================
add_h1("BAB 1: ARSITEKTUR SISTEM & STRUKTUR FILE")
add_p(
    "Sistem Informasi Manajemen Tim Ad Hoc LPAIP UKDW dibangun menggunakan pendekatan Modular Separation of Concerns "
    "(Pemisahan Tanggung Jawab). Dengan pendekatan ini, kode program tidak ditumpuk menjadi satu berkas raksasa yang "
    "membingungkan, melainkan dikelompokkan ke dalam direktori khusus sesuai peruntukannya."
)

t_arch = doc.add_table(rows=1, cols=3)
format_table(t_arch, [Inches(1.5), Inches(1.8), Inches(3.2)],
    ["Direktori", "Tipe Berkas", "Peran & Tanggung Jawab Utama"],
    [
        ["app/config/", "PHP Configuration", "Pusat koneksi basis data PDO (database.php) dan pengaturan server SMTP Email (email.php)."],
        ["app/auth/", "PHP Authentication", "Pintu gerbang otentikasi pengguna: login.php, register.php, dan logout.php."],
        ["app/includes/", "Reusable Components", "Komponen bersama yang dipanggil berulang: header.php, navbar.php, sidebar.php, footer.php, dan pustaka PHPMailer."],
        ["app/pages/", "Application Core (HTML/PHP)", "Halaman data dan proses bisnis utama: dashboard.php, tim_detail.php, tim_adhoc.php, tugas.php, dokumen.php, profile.php."],
        ["app/admin/", "Administrator Module", "Modul khusus hak akses admin untuk menyetujui akun baru dan mengelola pengguna (kelola_user.php)."],
        ["app/assets/css/", "Cascading Style Sheets", "Murni aturan desain visual per halaman (style.css, dashboard.css, tim_detail.css, dokumen.css, login.css)."],
        ["app/assets/js/", "JavaScript Client Logic", "Murni logika interaktif per halaman (tim_detail.js, tim_adhoc.js, dokumen.js, tugas.js, profile.js, login.js)."],
        ["app/assets/img/", "Image Assets", "Berkas grafis resmi universitas (Logo UKDW HQ, header kop, footer kop surat)."],
        ["app/uploads/", "Physical Storage", "Folder penyimpanan berkas fisik yang diunggah (sub-folder: dokumen, sk, dan tugas)."],
        ["app/index.php", "Entry Point Redirector", "Pengarah awal yang memeriksa status login lalu meneruskan pengguna ke auth/login.php atau pages/dashboard.php."]
    ]
)

# ==============================================================================
# BAB 2: KONSEP FUNDAMENTAL SISTEM
# ==============================================================================
add_h1("BAB 2: KONSEP FUNDAMENTAL SISTEM (SESSION, PDO, RBAC, AUTO-LOADER)")

add_h2("2.1 Konsep dan Alur Kerja $_SESSION (PHP Session)")
add_p(
    "Protokol HTTP yang mendasari jaringan web bersifat stateless, artinya server web tidak memiliki ingatan bawaan "
    "tentang siapa yang mengirimkan permintaan sebelumnya. Setiap kali browser berpindah halaman, server menganggapnya "
    "sebagai pengunjung baru yang asing. Untuk mengatasi hal ini, digunakanlah mekanisme PHP Session ($_SESSION)."
)
add_bullet("Fungsi: Menyimpan identitas pengguna (user_id, nama, nip_nim, dan role) di memori server selama pengguna aktif menjelajah sistem.", "1. ");
add_bullet("Alasan Digunakan: Mencegah pengguna harus memasukkan email dan password berulang kali di setiap halaman yang diklik.", "2. ");
add_bullet("Cara Kerja Teknis: Saat login berhasil, server menjalankan session_start(), membuat tiket unik acak bernama Session ID, lalu menyimpannya di browser sebagai cookie (PHPSESSID). Saat halaman lain dibuka, browser mengirim cookie tersebut sehingga server mengenali identitas pengguna.", "3. ");
add_bullet("Penghancuran Sesi: Saat pengguna menekan tombol Logout, file logout.php mengeksekusi session_unset() dan session_destroy(), sehingga tiket sesi dihapus dan pengguna tidak bisa lagi menekan tombol Back untuk masuk.", "4. ");

add_code_block(doc, """// Contoh implementasi Sesi saat Login berhasil (app/auth/login.php):
session_start();
$_SESSION['user_id'] = $user['id'];
$_SESSION['nama']    = $user['nama'];
$_SESSION['role']    = $user['role']; // 'admin', 'ketua', atau 'anggota'
$_SESSION['nip_nim'] = $user['nip_nim'];
header("Location: ../pages/dashboard.php");
exit;""")

add_h2("2.2 Konsep Database PDO & Pencegahan Serangan SQL Injection")
add_p(
    "Sistem menggunakan PHP Data Objects (PDO) dengan fitur Prepared Statements untuk berinteraksi dengan database MariaDB/MySQL. "
    "Dibandingkan dengan fungsi mysqli lama yang menggabungkan teks masukan pengguna secara langsung ke dalam string query, "
    "Prepared Statements memisahkan struktur logika SQL dari nilai parameternya. Nilai masukan pengguna dikirimkan melalui proses "
    "parameter binding sehingga karakter berbahaya (seperti tanda petik peretas atau injeksi ' OR '1'='1) otomatis dinetralisir."
)

add_h2("2.3 Konsep Hak Akses Pengguna (Role-Based Access Control / RBAC)")
add_p("Sistem membagi pengguna ke dalam 3 hierarki peran (*Role*) yang tegas:")
add_bullet("Admin (Sekretariat/Pimpinan LPAIP): Memiliki kewenangan absolut mengelola master data tim ad hoc, menerbitkan SK tugas, menyetujui akun baru, dan memantau seluruh tim.", "A. ");
add_bullet("Ketua Tim Ad Hoc: Memimpin tim tertentu, mendistribusikan pembagian tugas kepada anggota, mengunggah Laporan Pertanggungjawaban (LPJ), dan memperbarui persentase progres capaian tim.", "B. ");
add_bullet("Anggota Tim: Menerima tugas spesifik, memperbarui status pengerjaan tugas (Belum Dimulai, Sedang Berjalan, Selesai), mengunggah berkas bukti kerja, dan mengunduh berkas SK.", "C. ");

add_h2("2.4 Mekanisme Auto-Loader CSS & JS Dinamis")
add_p(
    "Sistem dirancang cerdas dengan mekanisme Auto-Loader 1:1 pada header.php dan footer.php. "
    "Setiap kali suatu file PHP dibuka (misal: pages/dashboard.php), sistem secara otomatis memeriksa "
    "apakah file assets/css/dashboard.css dan assets/js/dashboard.js ada di server. Jika ada, berkas tersebut "
    "otomatis dimuat tanpa perlu menuliskan tag <link> atau <script> secara manual di dalam file PHP."
)

# ==============================================================================
# BAB 3: KEAMANAN OTENTIKASI & CARA MENGECEK PASSWORD HASH
# ==============================================================================
add_h1("BAB 3: KEAMANAN OTENTIKASI & CARA MENGECEK PASSWORD HASH")

add_h2("3.1 Apa itu Password Hashing dan Mengapa Bukan Enkripsi?")
add_p(
    "Dalam keamanan informasi modern, kata sandi (password) tidak boleh disimpan dalam bentuk teks biasa (plain text) "
    "maupun bentuk enkripsi dua arah. Enkripsi memiliki kunci dekripsi (bisa dibalikkan kembali menjadi teks asli). "
    "Sebaliknya, Hashing adalah algoritma matematis satu arah (one-way mathematical function): sebuah kata sandi diubah "
    "menjadi deretan karakter acak yang mustahil untuk dihitung balik ke bentuk aslinya."
)

add_h2("3.2 Algoritma BCRYPT (PASSWORD_BCRYPT) di Sistem Ini")
add_p(
    "Sistem informasi ini menggunakan algoritma hashing standar internasional yaitu BCRYPT melalui fungsi bawaan "
    "PHP password_hash($password, PASSWORD_BCRYPT). Karakteristik utama BCRYPT adalah:"
)
add_bullet("Panjang Karakter Selalu 60 Karakter: Apapun panjang password aslinya (baik 4 huruf maupun 50 huruf), hasil hash BCRYPT selalu menghasilkan string sepanjang tepat 60 karakter.", "1. ");
add_bullet("Awalan Tanda Pengenal $2y$10$: Menandakan varian algoritma BCRYPT ($2y$) dan faktor biaya komputasi ($10$ / cost factor).", "2. ");
add_bullet("Fitur Salt Otomatis: BCRYPT secara otomatis menghasilkan garam acak (salt) setiap kali hashing dilakukan. Dua pengguna dengan kata sandi yang persis sama ('admin123') akan menghasilkan string hash yang sama sekali berbeda di database, sehingga kebal terhadap serangan kamus (Dictionary Attack) dan Rainbow Table.", "3. ");

add_h2("3.3 Cara Mengecek Apakah Password Sudah Ter-Hash di Web & Database")
add_p("Berikut adalah panduan langkah demi langkah untuk menguji dan membuktikan bahwa sistem telah menerapkan hashing:")

add_h3("Langkah 1: Pemeriksaan Langsung di Basis Data (phpMyAdmin)")
add_bullet("Buka browser dan akses phpMyAdmin di alamat: http://localhost/phpmyadmin.", "a. ");
add_bullet("Pilih database proyek Anda: tim_adhoc_db.", "b. ");
add_bullet("Klik pada tabel users, lalu lihat data di kolom 'password'.", "c. ");
add_bullet("Bukti Keberhasilan: Jika password sudah ter-hash, Anda TIDAK AKAN melihat tulisan asli seperti 'dosen123' atau 'password'. Anda akan melihat deretan 60 karakter acak yang diawali dengan '$2y$10$...', contohnya: $2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi.", "d. ");

add_h3("Langkah 2: Pemeriksaan Alur Verifikasi di Kode (app/auth/login.php)")
add_p("Saat pengguna mengetikkan password di formulir login, proses pencocokan TIDAK menggunakan tanda sama dengan (==). Sistem menggunakan fungsi khusus password_verify():")
add_code_block(doc, """// Potongan kode verifikasi di app/auth/login.php:
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
$user = $stmt->fetch();

if ($user) {
    // Fungsi password_verify mengambil password input dan mencocokkannya dengan hash di DB
    $password_valid = password_verify($password_input, $user['password']);
    
    if ($password_valid) {
        // Password Benar! Login Diterima.
    } else {
        $error = "Password yang Anda masukkan salah!";
    }
}""")

add_h3("Langkah 3: Skrip Pengujian Mandiri (Self-Test Script)")
add_p(
    "Anda dapat membuat file pengujian kecil bernama test_hash.php untuk membuktikan di depan dosen penguji "
    "bagaimana BCRYPT bekerja secara nyata:"
)
add_code_block(doc, """<?php
$password_asli = "ukdw2026";

// 1. Proses Hashing saat Registrasi
$hash_1 = password_hash($password_asli, PASSWORD_BCRYPT);
$hash_2 = password_hash($password_asli, PASSWORD_BCRYPT);

echo "Password Asli: " . $password_asli . "<br>";
echo "Hash 1: " . $hash_1 . "<br>";
echo "Hash 2: " . $hash_2 . "<br><br>";

// Perhatikan: $hash_1 dan $hash_2 BERBEDA meskipun password sama! (Bukti Salt BCRYPT)

// 2. Proses Verifikasi saat Login
$uji_benar = password_verify("ukdw2026", $hash_1);
$uji_salah = password_verify("salah123", $hash_1);

echo "Hasil Uji Password Benar: " . ($uji_benar ? "VALID (Cocok)" : "GAGAL") . "<br>";
echo "Hasil Uji Password Salah: " . ($uji_salah ? "VALID" : "DITOLAK (Aman)") . "<br>";
?>""")

# ==============================================================================
# BAB 4: MEKANISME NOTIFIKASI EMAIL OTOMATIS
# ==============================================================================
add_h1("BAB 4: MEKANISME & IMPLEMENTASI NOTIFIKASI EMAIL OTOMATIS")

add_h2("4.1 Arsitektur Pengiriman Email PHPMailer & SMTP")
add_p(
    "Pengiriman email di web sering kali gagal jika hanya mengandalkan fungsi bawaan PHP mail() karena server lokal "
    "(XAMPP) tidak memiliki mail transfer agent (MTA) bawaan dan surat sering masuk ke folder spam. "
    "Oleh karena itu, sistem ini menggunakan pustaka industri PHPMailer yang terhubung langsung ke Server SMTP Google "
    "(smtp.gmail.com) melalui port aman 587 dengan enkripsi STARTTLS."
)

add_h2("4.2 Konfigurasi Server SMTP (app/config/email.php)")
add_p("Pusat kendali pengiriman email berada di file app/config/email.php dengan parameter utama sebagai berikut:")
add_bullet("Host Server: smtp.gmail.com", "• ");
add_bullet("Port Koneksi: 587 (Protokol STARTTLS)", "• ");
add_bullet("Otentikasi: SMTPAuth = true", "• ");
add_bullet("Akun Pengirim: timadhoc4@gmail.com", "• ");
add_bullet("Kata Sandi Aplikasi: xemn ubbq cjko zxnu (16 karakter App Password khusus dari Google Account Security)", "• ");
add_bullet("Nama Pengirim Resmi: SIM Tim Ad Hoc LPAIP UKDW", "• ");

add_h2("4.3 Pemicu (*Event Triggers*) Pengiriman Email di Sistem")
add_p("Sistem secara otomatis mengirimkan email resmi berformat HTML pada 3 skenario penting:")

add_h3("Skenario 1: Persetujuan / Penambahan Akun Pengguna Baru (app/admin/kelola_user.php)")
add_p(
    "Saat Admin menekan tombol 'Setujui Akun' atau mendaftarkan dosen baru, sistem secara otomatis merangkai surat sambutan "
    "elektronik yang berisi ucapan selamat bergabung, informasi hak akses (Role), NIP/NIM, serta tombol tautan untuk langsung "
    "masuk ke sistem."
)

add_h3("Skenario 2: Pembagian Tugas Baru & Pengingat Tenggat Waktu (app/pages/tugas.php & database.php)")
add_p(
    "Saat Ketua Tim membagikan tugas baru kepada seorang anggota, atau saat sistem mendeteksi bahwa tenggat waktu (deadline) "
    "tugas tersisa kurang dari 3 hari, sistem memicu pengiriman email pengingat berisi judul tugas, tenggat waktu, dan deskripsi tugas."
)

add_h3("Skenario 3: Notifikasi Pengunggahan Laporan Akhir LPJ (app/pages/tim_detail.php)")
add_p(
    "Ketika Ketua Tim selesai mengunggah dokumen Laporan Pertanggungjawaban (LPJ) akhir pada tab LPJ, sistem mengirimkan "
    "email konfirmasi kepada pihak Admin LPAIP bahwa tim bersangkutan telah merampungkan tugasnya."
)

add_h2("4.4 Desain Template Email HTML yang Dihasilkan")
add_p(
    "Fungsi templateEmailNotifikasi() di app/config/email.php merangkai email dengan format responsif dan profesional yang "
    "terdiri dari: Header berlatar belakang warna UKDW, Ikon Notifikasi, Kotak Konten Berbayang Lembut, Informasi Detail Tugas/Akun, "
    "Tombol CTA (Call to Action) 'Buka Sistem SIM Tim Ad Hoc', serta Footer Hak Cipta resmi LPAIP UKDW."
)

add_h2("4.5 Panduan Troubleshooting Jika Email Gagal Terkirim")
add_bullet("Penyebab 1: Komputer tidak terkoneksi ke internet (SMTP Gmail mewajibkan koneksi internet aktif).", "1. ");
add_bullet("Penyebab 2: Antivirus/Firewall Windows memblokir Port 587 (Pastikan port 587 diizinkan).", "2. ");
add_bullet("Penyebab 3: Ekstensi OpenSSL di PHP belum aktif (Buka php.ini di XAMPP, pastikan baris 'extension=openssl' tidak diawali tanda titik koma ';').", "3. ");
add_bullet("Penyebab 4: Google App Password kedaluwarsa atau di-revoke (Jika akun Google berganti password, buat App Password baru 16 karakter di menu Keamanan Akun Google).", "4. ");

# ==============================================================================
# BAB 5: BEDAH RINCI FILE PER FILE
# ==============================================================================
add_h1("BAB 5: BEDAH RINCI FILE PER FILE (FUNGSI, ALASAN, & CARA KERJA)")

t_files = doc.add_table(rows=1, cols=4)
format_table(t_files, [Inches(1.3), Inches(0.9), Inches(2.1), Inches(2.2)],
    ["Nama Berkas", "Kategori", "Fungsi & Alasan Digunakan", "Cara Kerja & Alur Logika"],
    [
        ["config/database.php", "Config", "Pusat koneksi PDO MariaDB & fungsi global check_deadline_reminders().", "Membuka koneksi PDO terpusat; jika ada tugas yang mendekati deadline, otomatis mencatat notifikasi baru di database."],
        ["config/email.php", "Config", "Konfigurasi server SMTP PHPMailer & template HTML email seragam.", "Memuat kelas PHPMailer, mengotentikasi akun timadhoc4@gmail.com, dan mengirim email HTML via port 587 TLS."],
        ["auth/login.php", "Auth", "Form login, validasi email/password, dan inisialisasi sesi login.", "Mengecek email di DB, menjalankan password_verify(), memeriksa status persetujuan akun, dan membuat $_SESSION."],
        ["auth/register.php", "Auth", "Form pendaftaran akun baru bagi dosen/staf UKDW.", "Memvalidasi NIP/NIM unik, mengenkripsi password dengan password_hash(..., PASSWORD_BCRYPT), lalu menyimpan user dengan status pending (0)."],
        ["auth/logout.php", "Auth", "Menutup sesi pengguna secara bersih dan aman.", "Menjalankan session_unset() dan session_destroy(), menghapus cookie PHPSESSID, lalu me-redirect ke login.php."],
        ["includes/header.php", "Includes", "Struktur pembuka HTML, CSS Bootstrap, dan Auto-Loader CSS.", "Menyediakan tag <head>, font Inter, Bootstrap 5.3 CSS, style.css global, dan otomatis mendeteksi file CSS halaman."],
        ["includes/footer.php", "Includes", "Struktur penutup HTML, JS Bootstrap, dan Auto-Loader JS.", "Menutup kontainer utama, memuat Bootstrap Bundle JS, skrip navigasi sidebar mobile, dan otomatis mendeteksi JS halaman."],
        ["includes/navbar.php", "Includes", "Bilah menu atas, lonceng notifikasi real-time, dan dropdown user.", "Menampilkan logo UKDW, menghitung notifikasi belum dibaca (is_read=0), dan menampilkan foto/nama akun pengguna."],
        ["includes/sidebar.php", "Includes", "Menu samping bernuansa maroon gelap saat berada di dalam tim.", "Menampilkan identitas tim ad hoc yang aktif dan menu pintas menuju Tab Beranda, Tugas, Dokumen, dan Anggota."],
        ["pages/dashboard.php", "Core Page", "Halaman beranda utama dengan layout 2 kolom ala e-Class UKDW.", "Menampilkan banner semester, panel pengumuman di kiri, dan kartu daftar tim ad hoc di kanan tanpa inline style."],
        ["pages/tim_detail.php", "Core Page", "Pusat ruang kerja digital tim ad hoc dengan 5 tab interaktif.", "Menyajikan 5 tab (Beranda, Tugas, Dokumen, Anggota, LPJ), grafik capaian progres tim, dan tombol unduh SK (.doc)."],
        ["pages/tim_adhoc.php", "Core Page", "Manajemen master data tim ad hoc (khusus hak akses Admin).", "Operasi CRUD tim: input nomor SK, nama tim, tanggal mulai/selesai, status keaktifan, dan form modal Bootstrap."],
        ["pages/tugas.php", "Core Page", "Manajemen dan monitoring pembagian tugas kerja tim ad hoc.", "Daftar tugas dengan filter status, modal update progres, dan lampiran berkas kerja (upload bukti penyelesaian)."],
        ["pages/dokumen.php", "Core Page", "Repositori arsip dokumen tim ad hoc (SK, Panduan, Berita Acara).", "Unggah berkas digital, filter kategori dokumen, unduh berkas, dan modal pembaruan metadata dokumen."],
        ["pages/profile.php", "Core Page", "Pembaruan identitas profil pengguna dan penggantian kata sandi.", "Memperbarui biodata user dan validasi ganti password baru (wajib memasukkan password lama yang benar)."],
        ["pages/download_sk.php", "Core Page", "Generator cetak dokumen Surat Keputusan (SK) resmi UKDW.", "Menghasilkan format surat resmi lengkap dengan Kop Surat UKDW, konsiderans, diktum keputusan, dan tabel susunan personalia."],
        ["pages/surat_tugas.php", "Core Page", "Generator cetak Surat Tugas resmi bagi anggota tim.", "Membuat surat penugasan resmi dari pimpinan lembaga untuk keperluan administratif dan BKD dosen."],
        ["pages/mark_notif.php", "Core Page", "Endpoint background untuk menandai notifikasi telah dibaca.", "Menerima ID notifikasi via GET/POST, mengubah kolom is_read = 1 di tabel notifikasi, lalu kembali ke halaman asal."],
        ["admin/kelola_user.php", "Admin", "Manajemen akun pengguna dan persetujuan (approval) akun baru.", "Menyetujui pendaftar baru (status 0 -> 1), mengubah hak akses peran (Admin/Ketua/Anggota), dan mereset password."],
        ["assets/css/style.css", "Styling", "Variabel global warna maroon UKDW, override komponen Bootstrap.", "Mendefinisikan CSS Variables (--ukdw-maroon, --ukdw-gold), tombol kustom, kartu, navigasi, dan tipografi Inter."],
        ["assets/css/dashboard.css", "Styling", "Styling khusus layout e-Class UKDW pada halaman dashboard.", "Menata .eclass-topbar maroon, .eclass-team-card, garis batas kiri maroon 6px, dan efek hover bayangan lembut."],
        ["assets/css/tim_detail.css", "Styling", "Styling visual tab aktivitas tim, progress bar, dan kartu anggota.", "Mengatur tampilan tab navigasi aktif, kartu statistik persentase tugas tim, dan badge status keanggotaan."],
        ["assets/js/tim_detail.js", "Script", "Logika interaktif tab switching dan modal persentase progres.", "Mengaktifkan tab Bootstrap via JS, validasi form unggah LPJ, dan konfirmasi penyelesaian tugas tim."],
        ["assets/js/tim_adhoc.js", "Script", "Fungsi pembersihan form modal dan pengisian form edit tim.", "Fungsi resetForm() saat klik tombol tambah baru dan editTim(t) untuk mengisi form modal edit secara instan."],
        ["assets/js/dokumen.js", "Script", "Fungsi pengisian data modal edit dokumen.", "Fungsi editDokumen(doc) yang menyalin metadata dokumen ke input modal sebelum ditampilkan ke layar."],
        ["assets/js/tugas.js", "Script", "Fungsi modal pembaruan status tugas dan rendering lampiran.", "Fungsi openUpdateModal(t) yang merender daftar file lampiran yang diunggah anggota secara dinamis."],
        ["assets/js/profile.js", "Script", "Fungsi interaktif toggle penglihatan kata sandi.", "Fungsi togglePasswordVisibility() yang mengubah atribut input password menjadi teks saat ikon mata diklik."]
    ]
)

# ==============================================================================
# BAB 6: PANDUAN KUSTOMISASI TEKS & ANTARMUKA
# ==============================================================================
add_h1("BAB 6: PANDUAN KUSTOMISASI TEKS & KATA-KATA ANTARMUKA (UI TEXT GUIDE)")
add_p(
    "Jika sewaktu-waktu Anda ingin mengubah nama universitas, nama lembaga, semester akademik, label tombol, "
    "atau teks petunjuk pada antarmuka sistem, gunakan tabel panduan praktis berikut:"
)

t_ui = doc.add_table(rows=1, cols=4)
format_table(t_ui, [Inches(1.8), Inches(1.6), Inches(1.5), Inches(1.6)],
    ["Bagian Teks yang Ingin Diubah", "Lokasi Berkas", "Nomor Baris", "Cara & Contoh Perubahan"],
    [
        ["Nama Lembaga & Judul Sistem di Bilah Atas", "app/includes/navbar.php", "Baris 31 - 32", "Ubah teks 'Sistem Informasi Manajemen Tim Ad Hoc' atau 'LPAIP'."],
        ["Teks Header Maroon e-Class Dashboard", "app/pages/dashboard.php", "Baris 102", "Ubah teks 'UNIVERSITAS KRISTEN DUTA WACANA • SIM TIM AD HOC LPAIP'."],
        ["Teks Semester Akademik (Gasal/Genap)", "app/pages/dashboard.php", "Baris 134", "Ganti kata 'Semester Gasal' menjadi 'Semester Genap'."],
        ["Teks Kotak Petunjuk Kuning Dashboard", "app/pages/dashboard.php", "Baris 206 - 208", "Edit kalimat di dalam tag <div class='p-3 rounded-2 shadow-sm mb-3'...>."],
        ["Nama-Nama Tab di Ruang Kerja Tim", "app/pages/tim_detail.php", "Baris 310 - 330", "Ganti teks di dalam tombol nav-link (Beranda, Tugas, Dokumen, Anggota, LPJ)."],
        ["Teks Hak Cipta (Copyright) di Footer", "app/includes/footer.php", "Baris 3", "Ubah tahun atau nama instansi di dalam tag <footer>."],
        ["Pesan Error Gagal Login", "app/auth/login.php", "Baris 50 & 53", "Ubah kalimat 'Password yang Anda masukkan salah!' atau 'Email tidak terdaftar!'."],
        ["Subjek & Isi Email Notifikasi Otomatis", "app/config/email.php", "Baris 87 - 120", "Sesuaikan template ucapan dan informasi di fungsi templateEmailNotifikasi()."]
    ]
)

# ==============================================================================
# BAB 7: PANDUAN KUSTOMISASI WARNA TEMA & GAYA TAMPILAN
# ==============================================================================
add_h1("BAB 7: PANDUAN KUSTOMISASI WARNA TEMA & DESAIN VISUAL")
add_p(
    "Seluruh warna tema sistem dikendalikan secara elegan menggunakan CSS Custom Properties (Variables) pada baris teratas "
    "file app/assets/css/style.css. Anda tidak perlu mengubah kode di ratusan tempat; cukup mengubah nilai variabel di file ini:"
)

add_code_block(doc, """:root {
    --primary-color: #6b0c22;       /* Warna Utama: Maroon Khas UKDW */
    --primary-hover: #83102b;       /* Maroon Terang Saat Kursor Menyorot */
    --ukdw-maroon: #6b0c22;         /* Palet Identitas Maroon */
    --ukdw-gold: #d97706;           /* Warna Aksen Emas / Oranye UKDW */
    --ukdw-gold-hover: #b45309;     /* Warna Emas Saat Kursor Menyorot */
    --secondary-bg: #f8fafc;        /* Warna Latar Belakang Netral Lembut */
    --sidebar-width: 260px;         /* Lebar Bilah Menu Samping */
}""")

add_p(
    "Jika Anda ingin mengubah warna tema aplikasi menjadi warna lain (misalnya Biru Tua UKDW #1E3A8A), Anda cukup mengganti "
    "kode heksadesimal #6b0c22 pada variabel --primary-color dan --ukdw-maroon di atas. Seluruh tombol, navbar, badge, "
    "dan garis aksen tim di seluruh halaman akan berubah warna secara otomatis dan seragam."
)

# ==============================================================================
# BAB 8: TIPS MENGHADAPI TANYA JAWAB PENGUJI SKRIPSI
# ==============================================================================
add_h1("BAB 8: TIPS & TRIK MENGHADAPI TANYA JAWAB PENGUJI SKRIPSI")

add_h2("Pertanyaan 1: Mengapa memilih arsitektur Modular terpisah (HTML, CSS, JS) dibandingkan menjadikannya satu file?")
add_p(
    "Jawaban Rekomendasi: 'Pemisahan ini menerapkan prinsip Separation of Concerns dan Clean Code Architecture. "
    "Dengan memisahkan CSS ke folder assets/css dan JS ke assets/js, kode PHP menjadi jauh lebih ringan, "
    "proses pemeliharaan (maintenance) sistem menjadi mudah tanpa risiko merusak logika sistem, serta file CSS/JS "
    "dapat di-cache oleh browser pengguna sehingga mempercepat kecepatan akses (loading performance).'",
    italic=True
)

add_h2("Pertanyaan 2: Bagaimana Anda menjamin keamanan kata sandi pengguna di sistem ini?")
add_p(
    "Jawaban Rekomendasi: 'Sistem menerapkan hashing satu arah menggunakan algoritma BCRYPT (PASSWORD_BCRYPT). "
    "BCRYPT secara otomatis menyertakan cryptographic salt yang unik pada setiap password, sehingga dua pengguna "
    "dengan kata sandi yang sama akan memiliki nilai hash yang sama sekali berbeda di database. Hal ini menjamin "
    "sistem kebal terhadap serangan Rainbow Table dan Dictionary Attack, serta bahkan administrator database pun "
    "tidak dapat mengetahui kata sandi asli pengguna.'",
    italic=True
)

add_h2("Pertanyaan 3: Mengapa menggunakan SMTP PHPMailer daripada fungsi bawaan PHP mail()?")
add_p(
    "Jawaban Rekomendasi: 'Fungsi bawaan PHP mail() tidak mendukung otentikasi SMTP yang ketat, sangat rentan ditolak "
    "oleh penyedia email (masuk spam), dan membutuhkan mail server lokal yang kompleks. Dengan PHPMailer yang terhubung "
    "ke Google SMTP melalui port aman 587 TLS, pengiriman email notifikasi terjamin kehandalannya (reliable), mendukung "
    "template visual HTML yang profesional, dan memiliki penanganan kesalahan (exception handling) yang jelas jika pengiriman gagal.'",
    italic=True
)

# Output Paths
output_path_workspace = r"C:\Users\Lenovo\Documents\Skripsi\Modul_Panduan_Arsitektur_dan_Kustomisasi_Sistem.docx"
output_path_artifacts = r"C:\Users\Lenovo\.gemini\antigravity\brain\1370e15a-2d95-4ab6-a646-a4cdb05bc1f3\Modul_Panduan_Arsitektur_dan_Kustomisasi_Sistem.docx"

doc.save(output_path_workspace)
doc.save(output_path_artifacts)
print(f"Document saved successfully to:\n1. {output_path_workspace}\n2. {output_path_artifacts}")
