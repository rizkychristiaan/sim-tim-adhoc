<?php
/**
 * ==============================================================================
 * FILE: app/config/database.php (Konfigurasi Database & Helper Global)
 * SISTEM: Sistem Informasi Manajemen Tim Ad Hoc LPAIP UKDW
 * FUNGSI: 
 *   1. Membuka koneksi database MySQL menggunakan PDO (PHP Data Objects).
 *   2. Memeriksa & membuat struktur tabel otomatis jika belum ada.
 *   3. Menghitung BASE_URL dinamis untuk navigasi antar subfolder.
 *   4. Menyediakan fungsi helper keamanan: check_login() & check_role().
 *   5. Menyediakan fungsi pengingat otomatis tenggat waktu (deadline reminder).
 * ==============================================================================
 */

// Set zona waktu default ke Waktu Indonesia Barat (WIB)
date_default_timezone_set('Asia/Jakarta');

// ------------------------------------------------------------------------------
// 1. INSISIALISASI SESI PENGGUNA
// ------------------------------------------------------------------------------
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ------------------------------------------------------------------------------
// 2. KREDENSIAL DAN KONEKSI DATABASE MYSQL
// ------------------------------------------------------------------------------
// Deteksi otomatis: Jika di InfinityFree / Hosting Publik vs XAMPP Lokal
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_hosting = !empty($http_host) && !in_array($http_host, ['localhost', '127.0.0.1']);

if ($is_hosting) {
    // Kredensial Database InfinityFree
    $host = 'sql111.infinityfree.com';
    $user = 'if0_43328345';
    $pass = 'nWhjWIwVlz';
    $dbname = 'if0_43328345_simadhoc';
    $port = 3306;
} else {
    // Kredensial Database XAMPP Lokal
    $host = getenv('DB_HOST') ?: '127.0.0.1';
    $user = getenv('DB_USER') ?: 'root';
    $pass = getenv('DB_PASS') !== false ? getenv('DB_PASS') : '';
    $dbname = getenv('DB_NAME') ?: 'db_sim_adhoc';
    $port = getenv('DB_PORT') ?: 3306;
}

try {
    // Membuat instance PDO baru dengan charset UTF-8
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,        // Lempar exception jika terjadi error SQL
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,  // Hasil query otomatis berbentuk Array Asosiatif
        PDO::ATTR_EMULATE_PREPARES => false,                // Gunakan prepared statement asli MySQL demi keamanan SQL Injection
    ]);

    // OTO-CREATE TABEL: tugas_files (Menyimpan berkas tugas multi-file)
    $pdo->exec("CREATE TABLE IF NOT EXISTS `tugas_files` (
      `id` INT(11) NOT NULL AUTO_INCREMENT,
      `tugas_id` INT(11) NOT NULL,
      `uploader_id` INT(11) NOT NULL,
      `nama_file_asli` VARCHAR(255) NOT NULL,
      `file_path` VARCHAR(255) NOT NULL,
      `file_size` INT(11) DEFAULT 0,
      `uploaded_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`),
      FOREIGN KEY (`tugas_id`) REFERENCES `tugas` (`id`) ON DELETE CASCADE,
      FOREIGN KEY (`uploader_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // OTO-MIGRASI KOLOM: Tambah kolom 'status' pada tabel users (1 = Aktif, 0 = Pending Approval Admin)
    $stmt_col = $pdo->query("SHOW COLUMNS FROM `users` LIKE 'status'");
    if (!$stmt_col->fetch()) {
        $pdo->exec("ALTER TABLE `users` ADD COLUMN `status` TINYINT(1) NOT NULL DEFAULT 1 AFTER `password`");
    }

    // OTO-MIGRASI KOLOM: Tambah kolom 'email_sent' pada tabel notifikasi
    // Kolom ini digunakan untuk menandai apakah notifikasi sudah dikirimkan lewat email (1 = sudah, 0 = belum)
    // Mencegah pengiriman email berulang setiap kali halaman di-refresh
    $stmt_col2 = $pdo->query("SHOW COLUMNS FROM `notifikasi` LIKE 'email_sent'");
    if (!$stmt_col2->fetch()) {
        $pdo->exec("ALTER TABLE `notifikasi` ADD COLUMN `email_sent` TINYINT(1) NOT NULL DEFAULT 0 AFTER `is_read`");
    }

    // OTO-MIGRASI HASH PASSWORD DEMO (Ubah dummy hash ke hash bcrypt asli 'admin123' / 'user123')
    $dummy_hash = '$2y$10$4hGkZ8Yd/O6p6lC1.jZq9.K5Q0lT6gN9f9b5c3x4y5z6a7b8c9d0e';
    $real_admin_hash = '$2y$10$1ovwQ.JNKeI.OWSuaKmsbeM9b9bjyu.Rhua2B/sWrNS46RntmNZvm';
    $real_user_hash = '$2y$10$.Uxm7HBseM1QNkuDEHB2/e6hSWg3E2A.cYeiQUchkvlPD05A0JLFW';

    $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `email` = 'admin@lpaip.ukdw.ac.id' AND `password` = ?")->execute([$real_admin_hash, $dummy_hash]);
    $pdo->prepare("UPDATE `users` SET `password` = ? WHERE `email` != 'admin@lpaip.ukdw.ac.id' AND `password` = ?")->execute([$real_user_hash, $dummy_hash]);

} catch (PDOException $e) {
    // Tampilan pesan ramah pengguna jika MySQL di XAMPP belum dinyalakan
    die("
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; border: 1px solid #dc3545; border-radius: 8px; background-color: #f8d7da; color: #721c24;'>
        <h3 style='margin-top:0;'>Gagal Terhubung ke Database MySQL!</h3>
        <p>Aplikasi gagal terhubung ke database <strong>db_sim_adhoc</strong>.</p>
        <hr>
        <p><strong>Langkah Penyelesaian di XAMPP:</strong></p>
        <ol>
            <li>Buka <strong>XAMPP Control Panel</strong> dan pastikan Service <strong>MySQL</strong> dalam keadaan <code>Running</code>.</li>
            <li>Buka browser dan masuk ke <a href='http://localhost/phpmyadmin' target='_blank'>http://localhost/phpmyadmin</a>.</li>
            <li>Buat database baru bernama <code>db_sim_adhoc</code>.</li>
            <li>Import file <code>database.sql</code> yang terletak di folder <code>database/database.sql</code>.</li>
        </ol>
        <p><em>Pesan Error Teknis: " . htmlspecialchars($e->getMessage()) . "</em></p>
    </div>
    ");
}

// ------------------------------------------------------------------------------
// 3. DETEKSI DINAMIS BASE_URL UNTUK NAVIGASI SUBFOLDER /app/
// ------------------------------------------------------------------------------
if (!defined('BASE_URL')) {
    $script_dir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $base_path = preg_replace('#/app(/.*)?$#i', '/app/', $script_dir);
    if (substr($base_path, -1) !== '/') {
        $base_path .= '/';
    }
    define('BASE_URL', $base_path);
}

// ------------------------------------------------------------------------------
// 4. FUNGSI HELPER KEAMANAN & AUTENTIKASI
// ------------------------------------------------------------------------------

/**
 * Memastikan pengguna sudah dalam posisi login.
 * Jika belum login, otomatis mengarahkan ke halaman login.
 */
function check_login() {
    if (!isset($_SESSION['user_id'])) {
        header("Location: " . BASE_URL . "auth/login.php");
        exit();
    }
}

/**
 * Memastikan pengguna memiliki hak akses (role) yang diizinkan.
 * Example: check_role(['admin']) atau check_role(['admin', 'ketua'])
 */
function check_role($allowed_roles = []) {
    check_login();
    if (!in_array($_SESSION['role'], $allowed_roles)) {
        $_SESSION['flash_error'] = "Anda tidak memiliki hak akses ke halaman tersebut!";
        header("Location: " . BASE_URL . "pages/dashboard.php");
        exit();
    }
}

/**
 * Memformat tanggal YYYY-MM-DD menjadi format bahasa Indonesia (e.g. 15 Oktober 2026).
 */
function tgl_indo($tanggal) {
    if (!$tanggal || $tanggal == '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $split = explode('-', $tanggal);
    if (count($split) < 3) return $tanggal;
    return $split[2] . ' ' . $bulan[(int)$split[1]] . ' ' . $split[0];
}

/**
 * Menyimpan pesan notifikasi baru ke dalam tabel notifikasi user.
 */
function create_notifikasi($pdo, $user_id, $judul, $pesan) {
    $stmt = $pdo->prepare("INSERT INTO notifikasi (user_id, judul, pesan) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $judul, $pesan]);
}

/**
 * Mengecek dan membuatkan pengingat notifikasi otomatis jika ada tugas yang mendekati/melewati tenggat waktu.
 * Setiap notifikasi juga akan dikirimkan ke email personil (1x per hari, anti-duplikasi via email_sent).
 */
function check_deadline_reminders($pdo, $user_id) {
    if (!$user_id || !$pdo) return;

    $today = date('Y-m-d');

    $stmt = $pdo->prepare("SELECT t.*, ta.nama_tim FROM tugas t JOIN tim_adhoc ta ON t.tim_id = ta.id WHERE t.assigned_to = ? AND t.status != 'Selesai'");
    $stmt->execute([$user_id]);
    $tasks = $stmt->fetchAll();

    foreach ($tasks as $task) {
        $deadline  = $task['deadline'];
        $diff_days = (int) floor((strtotime($deadline) - strtotime($today)) / 86400);

        $judul_notif = '';
        $pesan_notif = '';

        if ($diff_days < 0) {
            $judul_notif = "Peringatan: Tugas Melewati Deadline!";
            $pesan_notif = "Tugas '" . $task['judul_tugas'] . "' pada " . $task['nama_tim'] . " telah melewati tenggat waktu (" . tgl_indo($deadline) . "). Harap segera selesaikan!";
        } elseif ($diff_days <= 3 && $diff_days >= 0) {
            $judul_notif = "Pengingat: Deadline Tugas " . ($diff_days == 0 ? 'Hari Ini!' : $diff_days . ' Hari Lagi');
            $pesan_notif = "Tugas '" . $task['judul_tugas'] . "' pada " . $task['nama_tim'] . " mendekati tenggat waktu (" . tgl_indo($deadline) . ").";
        }

        if (!empty($judul_notif)) {
            // Cek duplikasi notifikasi spesifik tugas pada hari yang sama
            $stmt_chk = $pdo->prepare("SELECT id, email_sent FROM notifikasi WHERE user_id = ? AND judul = ? AND pesan = ? AND DATE(created_at) = ?");
            $stmt_chk->execute([$user_id, $judul_notif, $pesan_notif, $today]);
            $existing_notif = $stmt_chk->fetch();

            if (!$existing_notif) {
                // Belum ada notifikasi → buat notifikasi baru DAN kirim email
                create_notifikasi($pdo, $user_id, $judul_notif, $pesan_notif);

                // Ambil ID notifikasi yang baru saja dibuat
                $notif_id = (int) $pdo->lastInsertId();

                // Kirim email pengingat (email_sent otomatis ditandai 1 di dalam fungsi)
                require_once __DIR__ . '/../config/email.php';
                emailPengingatDeadline($pdo, $notif_id, $user_id, $task['judul_tugas'], $task['nama_tim'], $deadline, $diff_days);

            } elseif ($existing_notif['email_sent'] == 0) {
                // Notifikasi sudah ada TAPI email belum terkirim → kirim email sekarang
                require_once __DIR__ . '/../config/email.php';
                emailPengingatDeadline($pdo, $existing_notif['id'], $user_id, $task['judul_tugas'], $task['nama_tim'], $deadline, $diff_days);
            }
            // Jika email_sent = 1 → email sudah pernah dikirim hari ini → lewati
        }
    }
}
?>
