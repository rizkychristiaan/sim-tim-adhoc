<?php
/**
 * ==============================================================================
 * FILE: app/auth/login.php (Halaman Autentikasi Login Pengguna)
 * FUNGSI: 
 *   1. Menampilkan form login pengguna (Email & Password).
 *   2. Memverifikasi kredensial user terhadap database MySQL.
 *   3. Memeriksa status aktifasi akun (status = 1 aktif, status = 0 pending approval).
 *   4. Menyimpan data pengguna ke dalam variabel SESSION jika berhasil login.
 * ==============================================================================
 */
require_once '../config/database.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($email) || empty($password)) {
        $error = "Silakan isi email dan password!";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if ($user) {
            $password_valid = password_verify($password, $user['password']);
            if ($password_valid) {
                $status = isset($user['status']) ? intval($user['status']) : 1;
                if ($status === 0) {
                    $error = "⏳ Akun Anda masih dalam proses peninjauan / persetujuan (approval) oleh Admin LPAIP. Silakan tunggu konfirmasi Admin.";
                } else {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['nama'] = $user['nama'];
                    $_SESSION['email'] = $user['email'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['nip_nim'] = $user['nip_nim'];
                    $_SESSION['jabatan'] = $user['jabatan_struktural'];
                    
                    header("Location: ../pages/dashboard.php");
                    exit();
                }
            } else {
                $error = "Password yang Anda masukkan salah!";
            }
        } else {
            $error = "Email tidak terdaftar dalam sistem!";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SIM Tim Ad Hoc LPAIP UKDW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>

<div class="login-card">
    <div class="login-header">
        <img src="../assets/img/logo_ukdw_hq.png" alt="Logo UKDW" style="height: 62px; width: auto;" class="mb-2.5 drop-shadow">
        <h4 class="fw-bold mb-0" style="letter-spacing: 0.5px;">SIM TIM AD HOC</h4>
        <small class="text-warning fw-semibold" style="letter-spacing: 0.5px; color: #facc15 !important;">LPAIP UKDW</small>
        <p class="text-white-50 mb-0 small mt-1" style="font-size: 0.76rem;">Universitas Kristen Duta Wacana Yogyakarta</p>
    </div>
    
    <div class="p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <form method="POST" action="">
            <div class="mb-3">
                <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email Civitas UKDW</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                    <input type="email" class="form-control bg-light border-start-0" id="email" name="email" placeholder="nama@staff.ukdw.ac.id" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <small class="text-muted" style="font-size: 0.72rem;">*Gunakan email resmi institusi (@ukdw.ac.id / @lpaip.ukdw.ac.id)</small>
            </div>
            
            <div class="mb-4">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label for="password" class="form-label small fw-semibold text-secondary mb-0">Kata Sandi (Password)</label>
                    <a href="#modalLupaPassword" data-bs-toggle="modal" class="small text-decoration-none fw-semibold" style="color: #6b0c22; font-size: 0.75rem;">Lupa Password?</a>
                </div>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                    <input type="password" class="form-control bg-light border-start-0 border-end-0" id="password" name="password" placeholder="••••••••" required>
                    <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePasswordVisibility('password', 'eye_login')">
                        <i class="bi bi-eye-slash" id="eye_login"></i>
                    </button>
                </div>
            </div>

            <button type="submit" class="btn btn-primary btn-login w-100 mb-3 shadow-sm">
                <i class="bi bi-box-arrow-in-right me-2"></i> Masuk ke Sistem
            </button>
        </form>

        <!-- MODAL BANTUAN LUPA PASSWORD -->
        <div class="modal fade" id="modalLupaPassword" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header bg-primary text-white" style="background-color: #6b0c22 !important;">
                        <h6 class="modal-title fw-bold"><i class="bi bi-question-circle me-2"></i> Bantuan Lupa Password</h6>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4 small text-secondary">
                        <p class="mb-2">Bagi staf atau dosen UKDW yang lupa kata sandi akun SIM Tim Ad Hoc:</p>
                        <ul class="mb-3 ps-3">
                            <li>Silakan hubungi <strong>Administrator LPAIP UKDW</strong> melalui email resmi: <code>admin@lpaip.ukdw.ac.id</code>.</li>
                            <li>Atau kunjungi langsung kantor LPAIP di <strong>Gedung Agape Lantai 2, Kampus UKDW</strong>.</li>
                            <li>Admin akan melakukan verifikasi identitas NIP/NIM Anda dan mereset kata sandi default untuk akun Anda.</li>
                        </ul>
                        <div class="text-muted text-center pt-2 border-top" style="font-size: 0.75rem;">Lembaga Pengembangan Akademik dan Inovasi Pembelajaran (LPAIP) UKDW</div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/login.js"></script>
</body>
</html>