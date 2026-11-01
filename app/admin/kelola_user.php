<?php
/**
 * ==============================================================================
 * FILE: app/admin/kelola_user.php (Manajemen Pengguna & Approval Akun)
 * ROLE AKSES: Khusus ADMIN LPAIP
 * FUNGSI UTAMA:
 *   1. Menampilkan daftar pengguna yang terdaftar dan yang mengajukan akun baru.
 *   2. APPROVAL AKUN (Setujui / Tolak): Mengubah status = 0 (pending) menjadi 1 (aktif).
 *   3. PENGIRIMAN EMAIL OTOMATIS: Mengirimkan email sambutan & kredensial password
 *      ke email user baru saat disetujui atau ditambahkan oleh Admin.
 *   4. FITUR CRUD USER: Tambah, edit data/role, reset password, dan hapus pengguna.
 * ==============================================================================
 */

require_once '../config/database.php';
check_role(['admin']);

$page_title = "Kelola Pengguna Sistem";
$message = '';
$error = '';

// Handle POST: Add or Edit User
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_user'])) {
    $id = intval($_POST['id'] ?? 0);
    $nip_nim = trim($_POST['nip_nim']);
    $nama = trim($_POST['nama']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $jabatan_struktural = trim($_POST['jabatan_struktural']);
    $password = trim($_POST['password']);

    if (empty($nip_nim) || empty($nama) || empty($email) || empty($role)) {
        $error = "NIP/NIK, Nama, Email, dan Role wajib diisi!";
    } else {
        try {
            if ($id > 0) {
                // Update User
                if (!empty($password)) {
                    $hash = password_hash($password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET nip_nim = ?, nama = ?, email = ?, role = ?, jabatan_struktural = ?, password = ? WHERE id = ?");
                    $stmt->execute([$nip_nim, $nama, $email, $role, $jabatan_struktural, $hash, $id]);
                } else {
                    $stmt = $pdo->prepare("UPDATE users SET nip_nim = ?, nama = ?, email = ?, role = ?, jabatan_struktural = ? WHERE id = ?");
                    $stmt->execute([$nip_nim, $nama, $email, $role, $jabatan_struktural, $id]);
                }
                $message = "Data pengguna berhasil diperbarui!";
            } else {
                // Insert User Baru
                $pass_plain = !empty($password) ? $password : 'user123';
                $pass_insert = password_hash($pass_plain, PASSWORD_BCRYPT);
                $stmt = $pdo->prepare("INSERT INTO users (nip_nim, nama, email, role, jabatan_struktural, password) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nip_nim, $nama, $email, $role, $jabatan_struktural, $pass_insert]);
                
                // Kirim Email Notifikasi ke User Baru
                require_once '../config/email.php';
                $res_email = emailUserBaru($email, $nama, $nip_nim, $role, $pass_plain);
                if ($res_email['status']) {
                    $message = "Pengguna baru berhasil ditambahkan & email notifikasi telah dikirim ke " . htmlspecialchars($email) . "!";
                } else {
                    $message = "Pengguna berhasil ditambahkan, namun email gagal terkirim: " . htmlspecialchars($res_email['pesan']);
                }
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) {
                $error = "Email '" . htmlspecialchars($email) . "' sudah terdaftar dalam sistem! Harap gunakan email lain.";
            } else {
                $error = "Gagal menyimpan data pengguna: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}

// Handle Approve User
if (isset($_GET['approve'])) {
    $app_id = intval($_GET['approve']);
    $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_u->execute([$app_id]);
    $u_target = $stmt_u->fetch();

    if ($u_target) {
        $stmt_app = $pdo->prepare("UPDATE users SET status = 1 WHERE id = ?");
        $stmt_app->execute([$app_id]);

        // Send Email Notification to User
        require_once '../config/email.php';
        emailApprovalUser($u_target['email'], $u_target['nama']);
        $_SESSION['flash_message'] = "Akun " . htmlspecialchars($u_target['nama']) . " telah disetujui & email notifikasi terkirim!";
    }
    header("Location: kelola_user.php");
    exit();
}

// Handle Decline User
if (isset($_GET['decline'])) {
    $dec_id = intval($_GET['decline']);
    $stmt_u = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt_u->execute([$dec_id]);
    $u_target = $stmt_u->fetch();

    if ($u_target) {
        $stmt_del = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$dec_id]);
        $_SESSION['flash_message'] = "Pendaftaran akun " . htmlspecialchars($u_target['nama']) . " telah ditolak dan data terhapus.";
    }
    header("Location: kelola_user.php");
    exit();
}

// Handle Delete User
if (isset($_GET['delete'])) {
    $del_id = intval($_GET['delete']);
    if ($del_id === $_SESSION['user_id']) {
        $_SESSION['flash_error'] = "Anda tidak dapat menghapus akun Anda sendiri!";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$del_id]);
        $_SESSION['flash_message'] = "Pengguna berhasil dihapus!";
    }
    header("Location: kelola_user.php");
    exit();
}

// Flash messages check
if (isset($_SESSION['flash_message'])) {
    $message = $_SESSION['flash_message'];
    unset($_SESSION['flash_message']);
}
if (isset($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

// Daftar seluruh Tim Ad Hoc untuk Filter Dropdown
$teams_list = $pdo->query("SELECT id, nama_tim, nomor_sk FROM tim_adhoc ORDER BY nama_tim ASC")->fetchAll();

// Cek parameter tim_id:
// Jika ?tim_id=all -> mode global (semua pengguna)
// Jika ?tim_id=angka -> mode tim spesifik
// Jika tanpa parameter di URL, cek apakah ada active_tim_id di session (misal saat navigasi dari dalam tim)
$selected_tim_id = 0;
if (isset($_GET['tim_id'])) {
    if ($_GET['tim_id'] !== 'all') {
        $selected_tim_id = intval($_GET['tim_id']);
    }
} elseif (!empty($_SESSION['active_tim_id'])) {
    $selected_tim_id = intval($_SESSION['active_tim_id']);
}

$current_tim_info = null;
if ($selected_tim_id > 0) {
    $stmt_ct = $pdo->prepare("SELECT * FROM tim_adhoc WHERE id = ?");
    $stmt_ct->execute([$selected_tim_id]);
    $current_tim_info = $stmt_ct->fetch();
    if (!$current_tim_info) {
        $selected_tim_id = 0; // fallback jika tim tidak ditemukan
    }
}

if ($selected_tim_id > 0) {
    // Mode Spesifik Tim: Hanya tampilkan personil tim ini
    $stmt_users = $pdo->prepare("
        SELECT u.*, a.peran_tim, t.nama_tim, a.id as anggota_tim_id
        FROM users u
        JOIN anggota_tim a ON u.id = a.user_id
        JOIN tim_adhoc t ON a.tim_id = t.id
        WHERE a.tim_id = ?
        ORDER BY FIELD(a.peran_tim, 'Ketua', 'Anggota'), u.nama ASC
    ");
    $stmt_users->execute([$selected_tim_id]);
    $users = $stmt_users->fetchAll();

    $stat_total_users = count($users);
    $stat_pending = 0;
    $stat_ketua = count(array_filter($users, fn($u) => ($u['peran_tim'] ?? '') === 'Ketua'));
        $stat_anggota = count(array_filter($users, fn($u) => ($u['peran_tim'] ?? '') === 'Anggota'));
    $pending_users = [];
} else {
    // Mode Global: Tampilkan semua pengguna di sistem
    $sql_all_users = "
        SELECT u.*,
               (SELECT GROUP_CONCAT(CONCAT(t2.nama_tim, ' (', a2.peran_tim, ')') SEPARATOR '||')
                FROM anggota_tim a2 JOIN tim_adhoc t2 ON a2.tim_id = t2.id WHERE a2.user_id = u.id) as tim_list_raw
        FROM users u
        ORDER BY u.created_at DESC
    ";
    $users = $pdo->query($sql_all_users)->fetchAll();

    $pending_users = array_filter($users, fn($u) => isset($u['status']) && intval($u['status']) === 0);
    $stat_pending = count($pending_users);
    $stat_total_users = count($users);
    $stat_admin = 0;
    $stat_ketua = 0;
    $stat_anggota = 0;

    foreach ($users as $u) {
        if (intval($u['status'] ?? 1) === 1) {
            if ($u['role'] === 'admin') $stat_admin++;
            elseif ($u['role'] === 'ketua') $stat_ketua++;
            else $stat_anggota++;
        }
    }
}

include '../includes/header.php';

// KONDISIONAL SIDEBAR:
// 1. Saat masuk ke tim tertentu ($selected_tim_id > 0): TETAP GUNAKAN SIDEBAR SAMPING
// 2. Saat dibuka dari Dashboard Admin ($selected_tim_id === 0): TAMPIL PENUH TANPA SIDEBAR
if ($selected_tim_id > 0) {
    include '../includes/sidebar.php';
    $main_content_class = '';
} else {
    $main_content_class = 'no-sidebar';
}
?>
<?php if ($selected_tim_id === 0): ?>
<style>
.main-content.no-sidebar {
    margin-left: 0 !important;
    width: 100% !important;
    background-color: #f8fafc;
    min-height: 100vh;
}
</style>
<?php endif; ?>

<div class="main-content <?= $main_content_class ?>">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    Kelola Pengguna <?= $selected_tim_id > 0 && $current_tim_info ? '- ' . htmlspecialchars($current_tim_info['nama_tim']) : 'Sistem' ?>
                </h4>
                <p class="text-muted small mb-0">
                    Home / Pengguna Sistem <?= $selected_tim_id > 0 && $current_tim_info ? '/ ' . htmlspecialchars($current_tim_info['nama_tim']) : '(Global)' ?>
                </p>
            </div>
            <div class="d-flex flex-wrap gap-2 align-items-center">
                <?php if ($selected_tim_id === 0): ?>
                    <a href="../pages/dashboard.php" class="btn btn-outline-secondary rounded-pill px-3.5 py-2 shadow-sm fw-semibold">
                        <i class="bi bi-arrow-left me-1.5"></i> Dashboard
                    </a>
                <?php endif; ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 py-2 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalUser" onclick="resetUserForm()">
                    <i class="bi bi-plus-lg me-1.5"></i> Tambah Pengguna Baru
                </button>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (!empty($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($selected_tim_id === 0): ?>
        <!-- Filter Lingkup Tim Card (Hanya muncul di mode global) -->
        <div class="card border-0 shadow-sm p-3 mb-4 bg-white rounded-3">
            <div class="row g-3 align-items-center">
                <div class="col-md-6 col-lg-5">
                    <label class="form-label small fw-bold text-secondary mb-1">
                        <i class="bi bi-funnel-fill text-primary me-1"></i> Lingkup Data Tim Ad Hoc:
                    </label>
                    <select class="form-select" onchange="window.location.href='kelola_user.php?tim_id=' + this.value">
                        <option value="all" <?= $selected_tim_id === 0 ? 'selected' : '' ?>> Semua Pengguna Sistem </option>
                        <?php foreach ($teams_list as $tm): ?>
                            <option value="<?= $tm['id'] ?>" <?= $selected_tim_id == $tm['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tm['nama_tim']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-md-4">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold"><?= $selected_tim_id > 0 ? 'Total Personil Tim' : 'Total Pengguna Sistem' ?></div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_total_users ?></div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-people-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold"><?= $selected_tim_id > 0 ? 'Ketua Tim' : 'Dosen / Ketua Tim' ?></div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_ketua ?></div>
                        </div>
                        <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-person-badge-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-md-4">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold"><?= $selected_tim_id > 0 ? 'Anggota Personil' : 'Staf / Anggota Personil' ?></div>
                            <div class="fs-2 fw-bold text-success mt-1 mb-0"><?= $stat_anggota ?></div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-person-check-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4 py-3">NAMA LENGKAP</th>
                                <th class="py-3">NIP / NIK</th>
                                <?php if ($selected_tim_id > 0): ?>
                                    <th class="py-3">JABATAN DALAM TIM</th>
                                <?php else: ?>
                                    <th class="py-3">ROLE SISTEM</th>
                                    <th class="py-3">TIM YANG DIIKUTI</th>
                                <?php endif; ?>
                                <th class="py-3">JABATAN STRUKTURAL</th>
                                <th class="py-3">EMAIL</th>
                                <th class="py-3">STATUS</th>
                                <th class="pe-4 py-3 text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" class="text-center py-5 text-muted">
                                        <i class="bi bi-people fs-1 d-block mb-2 text-secondary opacity-50"></i>
                                        Tidak ada pengguna yang terdaftar pada lingkup ini.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($users as $u): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($u['nama']) ?></div>
                                        </td>
                                        <td class="py-3"><span class="badge bg-light text-dark border"><?= htmlspecialchars($u['nip_nim']) ?></span></td>
                                        
                                        <?php if ($selected_tim_id > 0): ?>
                                            <td class="py-3">
                                                <?php 
                                                    $peran = $u['peran_tim'] ?? 'Anggota';
                                                    $badge_color = ($peran === 'Ketua') ? 'primary' : 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $badge_color ?>-subtle text-<?= $badge_color ?> px-3 py-1 rounded-pill fw-semibold border border-<?= $badge_color ?>-subtle">
                                                    <?= htmlspecialchars($peran) ?>
                                                </span>
                                            </td>
                                        <?php else: ?>
                                            <td class="py-3">
                                                <?php if ($u['role'] === 'admin'): ?>
                                                    <span class="badge bg-danger-subtle text-danger px-3 py-1 rounded-pill border border-danger-subtle">ADMIN</span>
                                                <?php elseif ($u['role'] === 'ketua'): ?>
                                                    <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill border border-primary-subtle">KETUA</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill border border-secondary-subtle">ANGGOTA</span>
                                                <?php endif; ?>
                                            </td>
                                            <td class="py-3" style="max-width: 250px;">
                                                <?php if (!empty($u['tim_list_raw'])): 
                                                    $t_items = explode('||', $u['tim_list_raw']);
                                                ?>
                                                    <div class="d-flex flex-wrap gap-1">
                                                        <?php foreach ($t_items as $ti): ?>
                                                            <span class="badge bg-light text-dark border px-2 py-0.5 text-truncate" style="max-width: 220px;" title="<?= htmlspecialchars($ti) ?>">
                                                                <i class="bi bi-people me-1 text-primary"></i><?= htmlspecialchars($ti) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted small">Belum masuk tim</span>
                                                <?php endif; ?>
                                            </td>
                                        <?php endif; ?>

                                        <td class="py-3 text-secondary small"><?= htmlspecialchars($u['jabatan_struktural'] ?: '-') ?></td>
                                        <td class="py-3 text-secondary small"><?= htmlspecialchars($u['email']) ?></td>
                                        <td class="py-3">
                                            <?php if (intval($u['status'] ?? 1) === 1): ?>
                                                <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill border border-success-subtle"><i class="bi bi-check-circle me-1"></i> Aktif</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning px-2.5 py-1 rounded-pill border border-warning-subtle"><i class="bi bi-clock me-1"></i> Pending</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <div class="d-inline-flex gap-2 align-items-center">
                                                <?php if (intval($u['status'] ?? 1) === 0): ?>
                                                    <a href="kelola_user.php?approve=<?= $u['id'] ?>" class="btn btn-sm btn-success rounded-pill px-2.5 py-1 shadow-sm" title="Setujui Akun">
                                                        <i class="bi bi-check-lg me-1"></i> Setujui
                                                    </a>
                                                <?php endif; ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-2.5 py-1" onclick='editUser(<?= json_encode($u) ?>)' title="Edit Akun">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <?php if ($u['id'] !== $_SESSION['user_id']): 
                                                    $user_disp_label = $u['nama'] . (!empty($u['nip_nim']) ? ' (' . $u['nip_nim'] . ')' : '');
                                                ?>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1" onclick="showDeleteModal({ url: 'kelola_user.php?delete=<?= $u['id'] ?>', title: 'Hapus Pengguna', message: 'Apakah Anda yakin ingin menghapus akun pengguna ini dari sistem?', itemTitle: <?= htmlspecialchars(json_encode($user_disp_label), ENT_QUOTES, 'UTF-8') ?> });" title="Hapus Pengguna">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination Bar -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light-subtle rounded-bottom">
                    <span class="text-muted small">Menampilkan 1-<?= count($users) ?> dari <?= count($users) ?> data</span>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3 me-2" disabled>&lt; Sebelumnya</button>
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3" disabled>Selanjutnya &gt;</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Form User -->
    <div class="modal fade" id="modalUser" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="kelola_user.php">
                    <input type="hidden" name="id" id="user_id" value="0">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold" id="userModalTitle"><i class="bi bi-person-plus-fill me-2"></i> Tambah Pengguna</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">NIP / NIK (Dosen / Staf) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nip_nim" id="nip_nim" placeholder="Contoh: 198004142005011002" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama" id="nama" placeholder="Nama lengkap personil..." required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="nama@lpaip.ukdw.ac.id" required>
                        </div>
                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Role Akses <span class="text-danger">*</span></label>
                                <select class="form-select" name="role" id="role" required>
                                    <option value="anggota">Anggota</option>
                                    <option value="ketua">Ketua Tim</option>
                                    <option value="admin">Admin System</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold">Jabatan Struktural</label>
                                <input type="text" class="form-control" name="jabatan_struktural" id="jabatan_struktural" placeholder="Contoh: Dosen / Staf">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Kata Sandi (Password)</label>
                            <div class="input-group">
                                <input type="password" class="form-control" name="password" id="password" placeholder="Kosongkan jika tidak ingin mengubah">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePasswordVisibility('password', 'eye_user_modal')">
                                    <i class="bi bi-eye-slash" id="eye_user_modal"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan_user" class="btn btn-primary fw-semibold rounded-pill px-4 shadow-sm"><i class="bi bi-save me-1"></i> Simpan User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

<script>
    function resetUserForm() {
        document.getElementById('user_id').value = '';
        document.getElementById('nip_nim').value = '';
        document.getElementById('nama').value = '';
        document.getElementById('email').value = '';
        document.getElementById('role').value = 'anggota';
        document.getElementById('jabatan_struktural').value = '';
        document.getElementById('password').value = '';
        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-person-plus me-2"></i> Tambah Pengguna Baru';
    }

    function editUser(u) {
        document.getElementById('user_id').value = u.id;
        document.getElementById('nip_nim').value = u.nip_nim;
        document.getElementById('nama').value = u.nama;
        document.getElementById('email').value = u.email;
        document.getElementById('role').value = u.role;
        document.getElementById('jabatan_struktural').value = u.jabatan_struktural;
        document.getElementById('password').value = '';
        document.getElementById('userModalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Data Pengguna';
        var modal = new bootstrap.Modal(document.getElementById('modalUser'));
        modal.show();
    }

    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);
        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        } else {
            input.type = 'password';
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    }
</script>
