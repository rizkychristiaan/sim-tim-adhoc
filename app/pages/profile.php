<?php
/**
 * ==============================================================================
 * FILE: app/pages/profile.php (Pengaturan Profil Saya & Ubah Password)
 * ROLE AKSES: Seluruh Pengguna Login
 * FUNGSI UTAMA:
 *   1. Menampilkan rincian identitas akun pengguna (Nama, NIP/NIM, Role, Email).
 *   2. Memperbarui Nama Lengkap pengguna.
 *   3. Mengubah Kata Sandi (Password) lama ke Password baru secara aman.
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$page_title = "Profil Saya";
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $gelar_depan = trim($_POST['gelar_depan'] ?? '');
    $nama = trim($_POST['nama'] ?? '');
    $gelar_belakang = trim($_POST['gelar_belakang'] ?? '');
    $prodi_fakultas = trim($_POST['prodi_fakultas'] ?? '');
    $pass_lama = trim($_POST['pass_lama'] ?? '');
    $pass_baru = trim($_POST['pass_baru'] ?? '');
    $pass_konfirmasi = trim($_POST['pass_konfirmasi'] ?? '');

    if (empty($nama)) {
        $error = "Nama lengkap tanpa gelar wajib diisi!";
    } else {
        $stmt_cur = $pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt_cur->execute([$user_id]);
        $cur_u = $stmt_cur->fetch();

        // Bangun format display nama lengkap
        $display_nama = trim(($gelar_depan ? $gelar_depan . ' ' : '') . $nama . ($gelar_belakang ? ', ' . $gelar_belakang : ''));

        if (!empty($pass_baru)) {
            if ($pass_baru !== $pass_konfirmasi) {
                $error = "Konfirmasi password baru tidak cocok!";
            } else if (!password_verify($pass_lama, $cur_u['password'])) {
                $error = "Password lama Anda tidak cocok!";
            } else {
                $hash = password_hash($pass_baru, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("UPDATE users SET gelar_depan = ?, nama = ?, gelar_belakang = ?, prodi_fakultas = ?, password = ? WHERE id = ?");
                $stmt->execute([$gelar_depan, $display_nama, $gelar_belakang, $prodi_fakultas, $hash, $user_id]);
                $_SESSION['nama'] = $display_nama;
                $message = "Profil dan kata sandi berhasil diperbarui!";
            }
        } else {
            $stmt = $pdo->prepare("UPDATE users SET gelar_depan = ?, nama = ?, gelar_belakang = ?, prodi_fakultas = ? WHERE id = ?");
            $stmt->execute([$gelar_depan, $display_nama, $gelar_belakang, $prodi_fakultas, $user_id]);
            $_SESSION['nama'] = $display_nama;
            $message = "Profil Anda berhasil diperbarui!";
        }
    }
}

// Fetch User Info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$u = $stmt->fetch();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        <h4 class="fw-bold mb-4"><i class="bi bi-person-circle text-primary me-2"></i>Pengaturan Profil Saya</h4>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm border-0 bg-success-subtle text-success-emphasis" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm border-0 bg-danger-subtle text-danger-emphasis" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card border-0 shadow-sm text-center p-4 rounded-3">
                    <div class="bg-primary text-white rounded-circle d-inline-flex align-items-center justify-content-center fw-bold mx-auto mb-3 shadow-sm" style="width: 80px; height: 80px; font-size: 2rem;">
                        <?= strtoupper(substr($u['nama'], 0, 1)) ?>
                    </div>
                    <h5 class="fw-bold text-dark mb-1"><?= htmlspecialchars($u['nama']) ?></h5>
                    <div class="text-muted small mb-2"><?= htmlspecialchars($u['email']) ?></div>
                    <div>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill text-uppercase px-3 py-1.5 fw-semibold"><?= strtoupper($u['role']) ?></span>
                    </div>
                    <hr class="my-3">
                    <div class="text-start small">
                        <div class="mb-2 text-secondary">NIP / NIK: <strong class="text-dark font-monospace"><?= htmlspecialchars($u['nip_nim']) ?></strong></div>
                        <div class="text-secondary">Jabatan Struktural: <strong class="text-dark"><?= htmlspecialchars($u['jabatan_struktural']) ?></strong></div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-pencil-square text-primary me-2"></i>Form Ubah Profil & Password</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                            <!-- 3 Kolom Nama Sesuai Arahan Pak Anton -->
                            <div class="row g-3 mb-3">
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Gelar Depan</label>
                                    <input type="text" class="form-control" name="gelar_depan" value="<?= htmlspecialchars($u['gelar_depan'] ?? '') ?>" placeholder="Contoh: Dr. / Prof.">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama Lengkap (Tanpa Gelar) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="nama" value="<?= htmlspecialchars($u['nama']) ?>" placeholder="Nama lengkap asli" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label small fw-semibold">Gelar Belakang</label>
                                    <input type="text" class="form-control" name="gelar_belakang" value="<?= htmlspecialchars($u['gelar_belakang'] ?? '') ?>" placeholder="Contoh: S.Kom., M.Cs.">
                                </div>
                            </div>

                            <div class="row g-3 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Program Studi / Fakultas / Unit</label>
                                    <input type="text" class="form-control" name="prodi_fakultas" value="<?= htmlspecialchars($u['prodi_fakultas'] ?? '') ?>" placeholder="Contoh: Informatika / FTI">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Alamat Email Resmi UKDW (Readonly)</label>
                                    <input type="email" class="form-control bg-light text-secondary" value="<?= htmlspecialchars($u['email']) ?>" readonly>
                                </div>
                            </div>
                            <hr class="my-4">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-key text-primary me-2"></i>Ubah Kata Sandi (Password)</h6>
                            
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password Lama</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="pass_lama" name="pass_lama" placeholder="Masukkan password lama Anda">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('pass_lama', 'eye_lama')">
                                        <i class="bi bi-eye-slash" id="eye_lama"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="pass_baru" name="pass_baru" placeholder="Masukkan password baru Anda">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('pass_baru', 'eye_baru')">
                                        <i class="bi bi-eye-slash" id="eye_baru"></i>
                                    </button>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label small fw-semibold">Konfirmasi Password Baru</label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="pass_konfirmasi" name="pass_konfirmasi" placeholder="Ulangi password baru Anda">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('pass_konfirmasi', 'eye_konfirmasi')">
                                        <i class="bi bi-eye-slash" id="eye_konfirmasi"></i>
                                    </button>
                                </div>
                            </div>

                            <button type="submit" name="update_profile" class="btn btn-primary fw-semibold rounded-pill px-4 shadow-sm">
                                <i class="bi bi-save me-1"></i> Simpan Perubahan Profil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

    </div>

<?php include '../includes/footer.php'; ?>
