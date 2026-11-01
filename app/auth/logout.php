<?php
/**
 * ==============================================================================
 * FILE: app/auth/logout.php (Proses Keluar dari Sistem)
 * FUNGSI: Menghapus seluruh variabel sesi pengguna dan mengakhiri login.
 * ALUR KERJA:
 *   1. Menjalankan session_start() untuk mengakses sesi aktif.
 *   2. Menghapus semua variabel dalam sesi dengan session_unset().
 *   3. Membinasakan (destroy) sesi di server dengan session_destroy().
 *   4. Mengarahkan pengguna kembali ke halaman Login (login.php).
 * ==============================================================================
 */
session_start();
session_unset();
session_destroy();
header("Location: login.php");
exit();
?>
