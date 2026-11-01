<?php
/**
 * ==============================================================================
 * FILE: app/auth/register.php (Halaman Registrasi Akun Baru)
 * SISTEM: SIM Tim Ad Hoc LPAIP UKDW
 * TEMA: Maroon UKDW (#6b0c22) & Gold (#d97706)
 * ==============================================================================
 */
require_once '../config/database.php';

// Pendaftaran mandiri ditutup (Akun dikelola langsung oleh Administrator LPAIP)
header("Location: login.php");
exit();

$error = '';
$success = '';

if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nip_nim = trim($_POST['nip_nim'] ?? '');
    $gelar_depan = trim($_POST['gelar_depan'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $gelar_belakang = trim($_POST['gelar_belakang'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'anggota';
    $prodi_fakultas = trim($_POST['prodi_fakultas'] ?? '');
    $jabatan_struktural = trim($_POST['jabatan_struktural'] ?? 'Staf / Dosen UKDW');
    $password = trim($_POST['password'] ?? '');
    $confirm_password = trim($_POST['confirm_password'] ?? '');

    if (empty($nip_nim) || empty($nama) || empty($email) || empty($role) || empty($password)) {
        $error = "Semua kolom berpembatas bintang (*) wajib diisi!";
    } elseif ($password !== $confirm_password) {
        $error = "Konfirmasi kata sandi tidak cocok dengan kata sandi yang dimasukkan!";
    } elseif (strlen($password) < 4) {
        $error = "Kata sandi minimal harus 4 karakter!";
    } else {
        try {
            $stmt_check = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nip_nim = ?");
            $stmt_check->execute([$email, $nip_nim]);
            if ($stmt_check->fetch()) {
                $error = "Alamat email atau NIP/NIM sudah pernah terdaftar! Silakan hubungi Admin LPAIP jika lupa kata sandi.";
            } else {
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (nip_nim, gelar_depan, nama, gelar_belakang, email, role, jabatan_struktural, prodi_fakultas, password, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0)");
                $stmt->execute([$nip_nim, $gelar_depan, $nama, $gelar_belakang, $email, $role, $jabatan_struktural, $prodi_fakultas, $hash]);

                $success = "Pendaftaran Akun Berhasil! Akun Anda saat ini sedang dalam proses peninjauan & approval oleh Admin LPAIP UKDW. Anda akan dapat masuk setelah akun diaktifkan oleh Admin.";
            }
        } catch (PDOException $e) {
            $error = "Terjadi kesalahan sistem: " . htmlspecialchars($e->getMessage());
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Akun Baru - SIM Tim Ad Hoc LPAIP UKDW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/register.css">
</head>
<body>

<div class="register-card">
    <div class="register-header">
        <img src="../assets/img/logo_ukdw_hq.png" alt="Logo UKDW" style="height: 60px; width: auto;" class="mb-2 drop-shadow">
        <h4 class="fw-bold mb-0" style="letter-spacing: 0.5px;">REGISTRASI AKUN BARU</h4>
        <small class="text-warning fw-semibold" style="letter-spacing: 0.5px; color: #facc15 !important;">SIM TIM AD HOC LPAIP UKDW</small>
        <p class="text-white-50 mb-0 small mt-1" style="font-size: 0.76rem;">Universitas Kristen Duta Wacana Yogyakarta</p>
    </div>
    
    <div class="p-4">
        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show small py-2 mb-3" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($success)): ?>
            <div class="alert alert-success fade show small p-3 mb-4 rounded-3 border-success" role="alert">
                <div class="fw-bold mb-1 fs-6"><i class="bi bi-check-circle-fill me-1 text-success"></i> Registrasi Akun Berhasil</div>
                <div class="text-secondary"><?= htmlspecialchars($success) ?></div>
                <div class="mt-3 pt-2 border-top border-success-subtle text-end">
                    <a href="login.php" class="btn btn-sm btn-success rounded-pill px-4 fw-semibold"><i class="bi bi-box-arrow-in-right me-1"></i> Kembali ke Halaman Login</a>
                </div>
            </div>
        <?php else: ?>

            <form method="POST" action="">
                <!-- Baris 1: NIP/NIM & Peran Akses -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="nip_nim" class="form-label small fw-semibold text-secondary">NIP / NIM <span class="text-danger">*</span></label>
                        <input type="text" class="form-control bg-light" id="nip_nim" name="nip_nim" placeholder="Contoh: 71220902 / D001" required value="<?= htmlspecialchars($_POST['nip_nim'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label small fw-semibold text-secondary">Peran Akses Utama <span class="text-danger">*</span></label>
                        <select class="form-select bg-light" id="role" name="role" required>
                            <option value="anggota" <?= (($_POST['role'] ?? '') === 'anggota') ? 'selected' : '' ?>>Anggota Tim Ad Hoc</option>
                            <option value="ketua" <?= (($_POST['role'] ?? '') === 'ketua') ? 'selected' : '' ?>>Ketua Tim Ad Hoc / Dosen</option>
                        </select>
                    </div>
                </div>

                <!-- Baris 2: Nama Lengkap Terstruktur -->
                <div class="mb-3">
                    <label class="form-label small fw-semibold text-secondary mb-1">Nama Lengkap & Gelar Akademik <span class="text-danger">*</span></label>
                    <div class="row g-2">
                        <div class="col-3">
                            <input type="text" class="form-control bg-light" name="gelar_depan" placeholder="Dr. / Ir." value="<?= htmlspecialchars($_POST['gelar_depan'] ?? '') ?>" title="Gelar Depan">
                        </div>
                        <div class="col-6">
                            <input type="text" class="form-control bg-light" name="nama" placeholder="Nama Lengkap (Tanpa Gelar)" required value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                        </div>
                        <div class="col-3">
                            <input type="text" class="form-control bg-light" name="gelar_belakang" placeholder="M.Cs. / S.Kom." value="<?= htmlspecialchars($_POST['gelar_belakang'] ?? '') ?>" title="Gelar Belakang">
                        </div>
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">*Format gelar depan, nama inti, dan gelar belakang dipisahkan secara otomatis.</small>
                </div>

                <!-- Baris 3: Email Civitas UKDW -->
                <div class="mb-3">
                    <label for="email" class="form-label small fw-semibold text-secondary">Alamat Email Civitas UKDW <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
                        <input type="email" class="form-control bg-light border-start-0" id="email" name="email" placeholder="nama@staff.ukdw.ac.id / nama@ti.ukdw.ac.id" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                    </div>
                    <small class="text-muted" style="font-size: 0.72rem;">*Gunakan email resmi institusi (@ukdw.ac.id atau @lpaip.ukdw.ac.id)</small>
                </div>

                <!-- Baris 4: Program Studi / Fakultas & Jabatan Struktural -->
                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label for="prodi_fakultas" class="form-label small fw-semibold text-secondary">Program Studi / Fakultas</label>
                        <input type="text" class="form-control bg-light" id="prodi_fakultas" name="prodi_fakultas" placeholder="Contoh: Informatika / FTI" value="<?= htmlspecialchars($_POST['prodi_fakultas'] ?? '') ?>">
                    </div>
                    <div class="col-md-6">
                        <label for="jabatan_struktural" class="form-label small fw-semibold text-secondary">Jabatan Struktural / Unit Kerja</label>
                        <input type="text" class="form-control bg-light" id="jabatan_struktural" name="jabatan_struktural" placeholder="Contoh: Dosen / Staf Administrasi" value="<?= htmlspecialchars($_POST['jabatan_struktural'] ?? '') ?>">
                    </div>
                </div>

                <!-- Baris 5: Kata Sandi & Konfirmasi -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label for="password" class="form-label small fw-semibold text-secondary">Kata Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control bg-light border-end-0" id="password" name="password" placeholder="••••••••" required>
                            <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePass('password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="confirm_password" class="form-label small fw-semibold text-secondary">Konfirmasi Sandi <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control bg-light border-end-0" id="confirm_password" name="confirm_password" placeholder="••••••••" required>
                            <button class="btn btn-light border border-start-0 text-muted" type="button" onclick="togglePass('confirm_password', this)">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Box Pemberitahuan Approval Admin -->
                <div class="alert alert-light border border-warning-subtle d-flex align-items-center p-3 mb-4 rounded-3" style="background-color: #fffbeb;">
                    <i class="bi bi-info-circle-fill text-warning me-2 fs-5 flex-shrink-0"></i>
                    <div class="small text-secondary">
                        Setelah pendaftaran, akun Anda memerlukan <strong>Approval Admin LPAIP</strong> sebelum dapat digunakan untuk masuk ke sistem.
                    </div>
                </div>

                <button type="submit" class="btn btn-register w-100 mb-3 shadow-sm">
                    <i class="bi bi-send-fill me-2"></i> Kirim Pendaftaran Akun
                </button>
            </form>

        <?php endif; ?>

        <div class="mt-3 text-center border-top pt-3">
            <span class="text-secondary small">Sudah memiliki akun terdaftar?</span>
            <a href="login.php" class="fw-bold small text-decoration-none ms-1" style="color: #6b0c22;">Masuk (Login) &raquo;</a>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="../assets/js/register.js"></script>
</body>
</html>
