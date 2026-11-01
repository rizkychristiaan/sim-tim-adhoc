<?php
/**
 * ==============================================================================
 * FILE: app/index.php (Router Internal Aplikasi)
 * FUNGSI: Mengecek status sesi login pengguna saat pertama kali membuka aplikasi.
 * ALUR KERJA:
 *   1. Jika variabel $_SESSION['user_id'] ADA (Sudah Login):
 *      -> Diarahkan ke Halaman Dashboard Utama (pages/dashboard.php)
 *   2. Jika variabel $_SESSION['user_id'] KOSONG (Belum Login):
 *      -> Diarahkan ke Halaman Login (auth/login.php)
 * ==============================================================================
 */
session_start();

if (isset($_SESSION['user_id'])) {
    // Pengguna sudah terautentikasi -> Masuk ke Dashboard
    header("Location: pages/dashboard.php");
} else {
    // Pengguna belum login -> Masuk ke Form Login
    header("Location: auth/login.php");
}
exit();
?>
