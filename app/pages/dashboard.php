<?php
/**
 * ==============================================================================
 * FILE: app/pages/dashboard.php (Dashboard Terpadu SIM Tim Ad Hoc LPAIP UKDW)
 * ROLE AKSES: Admin, Ketua Tim (Dosen), Anggota Tim
 * 
 * FUNGSI UTAMA:
 *   1. Menampilkan portal e-Class daftar Tim Ad Hoc (Penuh & Lega).
 *   2. Rekapitulasi Statistik Lembaga (Tim Aktif, Total Anggota, SK Terbit, Total Tim) untuk Admin.
 *   3. Operasi CRUD Tim Ad Hoc Terpadu (Tambah & Edit via Modal, Hapus Tim) tanpa halaman dobel.
 *   4. Akses langsung ke Ruang Kerja Tim (tim_detail.php).
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$page_title = "Daftar Tim Ad Hoc";
$user_id = $_SESSION['user_id'];

// Ambil profil user terbaru langsung dari database
$stmt_me = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt_me->execute([$user_id]);
$current_user = $stmt_me->fetch();

$user_nama = $current_user['nama'] ?? $_SESSION['nama'] ?? 'Pengguna';
$role = $current_user['role'] ?? $_SESSION['role'] ?? 'anggota';
$nip_nim = $current_user['nip_nim'] ?? '-';
$jabatan = $current_user['jabatan_struktural'] ?? 'Sivitas LPAIP UKDW';

// Sinkronkan data session
$_SESSION['nama'] = $user_nama;
$_SESSION['role'] = $role;

// ==============================================================================
// HANDLER ADMIN: SIMPAN (TAMBAH / EDIT) TIM AD HOC
// ==============================================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_tim'])) {
    if ($role !== 'admin') {
        $_SESSION['flash_error'] = "Hanya Admin (LPAIP) yang berwenang membuat dan mengelola data Tim Ad Hoc!";
    } else {
        $id = intval($_POST['id'] ?? 0);
        $nomor_sk = trim($_POST['nomor_sk'] ?? '');
        $nama_tim = trim($_POST['nama_tim'] ?? '');
        $deskripsi = trim($_POST['deskripsi'] ?? '');
        $tanggal_mulai = $_POST['tanggal_mulai'] ?? '';
        $tanggal_selesai = $_POST['tanggal_selesai'] ?? '';
        $status = $_POST['status'] ?? 'Aktif';

        if (empty($nomor_sk) || empty($nama_tim) || empty($tanggal_mulai) || empty($tanggal_selesai)) {
            $_SESSION['flash_error'] = "Semua kolom bertanda bintang (*) wajib diisi!";
        } else {
            if ($id > 0) {
                // Update
                $stmt = $pdo->prepare("UPDATE tim_adhoc SET nomor_sk = ?, nama_tim = ?, deskripsi = ?, tanggal_mulai = ?, tanggal_selesai = ?, status = ? WHERE id = ?");
                $stmt->execute([$nomor_sk, $nama_tim, $deskripsi, $tanggal_mulai, $tanggal_selesai, $status, $id]);
                $_SESSION['flash_success'] = "Data Tim Ad Hoc berhasil diperbarui!";
                header("Location: dashboard.php");
                exit();
            } else {
                // Insert
                $stmt = $pdo->prepare("INSERT INTO tim_adhoc (nomor_sk, nama_tim, deskripsi, tanggal_mulai, tanggal_selesai, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$nomor_sk, $nama_tim, $deskripsi, $tanggal_mulai, $tanggal_selesai, $status, $user_id]);
                $new_tim_id = $pdo->lastInsertId();

                // Otomatis tambahkan pembuat sebagai Ketua
                $stmt_member = $pdo->prepare("INSERT INTO anggota_tim (tim_id, user_id, peran_tim) VALUES (?, ?, 'Ketua')");
                $stmt_member->execute([$new_tim_id, $user_id]);

                $_SESSION['flash_success'] = "Tim Ad Hoc baru berhasil dibuat!";
                header("Location: tim_detail.php?id=" . $new_tim_id);
                exit();
            }
        }
    }
}

// ==============================================================================
// HANDLER ADMIN: HAPUS TIM AD HOC
// ==============================================================================
if (isset($_GET['delete_tim'])) {
    if ($role !== 'admin') {
        $_SESSION['flash_error'] = "Hanya Admin yang berhak menghapus Tim Ad Hoc!";
    } else {
        $del_id = intval($_GET['delete_tim']);
        $pdo->prepare("DELETE FROM anggota_tim WHERE tim_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM tugas WHERE tim_id = ?")->execute([$del_id]);
        $pdo->prepare("DELETE FROM dokumen WHERE tim_id = ?")->execute([$del_id]);
        if (isset($_SESSION['active_tim_id']) && $_SESSION['active_tim_id'] == $del_id) {
            unset($_SESSION['active_tim_id'], $_SESSION['active_tim_nama']);
        }
        $stmt = $pdo->prepare("DELETE FROM tim_adhoc WHERE id = ?");
        $stmt->execute([$del_id]);
        $_SESSION['flash_success'] = "Data Tim Ad Hoc beserta seluruh relasinya berhasil dihapus!";
        header("Location: dashboard.php");
        exit();
    }
}

// Filter pencarian
$search = trim($_GET['search'] ?? '');

if ($role === 'admin') {
    // Admin: Menampilkan seluruh Tim Ad Hoc LPAIP
    $sql_admin = "
        SELECT t.*, u.nama as pembuat, 
               (SELECT COUNT(*) FROM anggota_tim WHERE tim_id = t.id) as total_anggota,
               (SELECT GROUP_CONCAT(u2.nama SEPARATOR ', ') FROM anggota_tim a2 JOIN users u2 ON a2.user_id = u2.id WHERE a2.tim_id = t.id) as daftar_anggota,
               (SELECT COUNT(*) FROM tugas WHERE tim_id = t.id) as total_tugas_tim,
               (SELECT COUNT(*) FROM tugas WHERE tim_id = t.id AND status = 'Selesai') as total_tugas_selesai
        FROM tim_adhoc t 
        LEFT JOIN users u ON t.created_by = u.id 
    ";
    if (!empty($search)) {
        $stmt_tim = $pdo->prepare($sql_admin . " WHERE t.nama_tim LIKE ? OR t.nomor_sk LIKE ? ORDER BY t.created_at DESC");
        $stmt_tim->execute(["%$search%", "%$search%"]);
    } else {
        $stmt_tim = $pdo->query($sql_admin . " ORDER BY t.created_at DESC");
    }
    $tim_list = $stmt_tim->fetchAll();
    $total_tim_semua = count($tim_list);


} else {
    // Dosen (Ketua Tim) & Anggota Tim: Hanya menampilkan Tim Ad Hoc yang Diikuti
    $sql_user = "
        SELECT DISTINCT t.*, u.nama as pembuat, a.peran_tim,
               (SELECT COUNT(*) FROM anggota_tim WHERE tim_id = t.id) as total_anggota,
               (SELECT GROUP_CONCAT(u2.nama SEPARATOR ', ') FROM anggota_tim a2 JOIN users u2 ON a2.user_id = u2.id WHERE a2.tim_id = t.id) as daftar_anggota,
               (SELECT COUNT(*) FROM tugas WHERE tim_id = t.id) as total_tugas_tim,
               (SELECT COUNT(*) FROM tugas WHERE tim_id = t.id AND status = 'Selesai') as total_tugas_selesai
        FROM tim_adhoc t 
        JOIN users u ON t.created_by = u.id 
        JOIN anggota_tim a ON t.id = a.tim_id 
        WHERE a.user_id = ?
    ";
    if (!empty($search)) {
        $stmt_tim = $pdo->prepare($sql_user . " AND (t.nama_tim LIKE ? OR t.nomor_sk LIKE ?) ORDER BY t.created_at DESC");
        $stmt_tim->execute([$user_id, "%$search%", "%$search%"]);
    } else {
        $stmt_tim = $pdo->prepare($sql_user . " ORDER BY t.created_at DESC");
        $stmt_tim->execute([$user_id]);
    }
    $tim_list = $stmt_tim->fetchAll();
    $total_tim_semua = count($tim_list);
}

// Ambil notifikasi penting / pengumuman sistem
$stmt_my_notif = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 4");
$stmt_my_notif->execute([$user_id]);
$pengumuman_list = $stmt_my_notif->fetchAll();

include '../includes/header.php';
// Di halaman dashboard, sidebar kiri hitam sengaja tidak dimuat
// agar tampilan bersih dan penuh persis seperti e-Class UKDW.
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-xl py-4">
        
        <?php if (isset($_SESSION['flash_error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-exclamation-octagon-fill me-2"></i> <?= htmlspecialchars($_SESSION['flash_error']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_error']); ?>
        <?php endif; ?>

        <?php if (isset($_SESSION['flash_success'])): ?>
            <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($_SESSION['flash_success']) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['flash_success']); ?>
        <?php endif; ?>

        <!-- HEADER BANNER PROFIL DENGAN LOGO RESMI UKDW (DIPERBESAR) -->
        <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white border-top border-5" style="border-top-color: #6b0c22 !important;">
            <div class="card-body p-4 p-lg-4" style="padding: 1.6rem 2rem !important;">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                    <div class="d-flex align-items-center gap-3.5">
                        <img src="<?= BASE_URL ?>assets/img/logo_ukdw_hq.png" alt="Logo UKDW" style="height: 72px; width: auto;" class="flex-shrink-0 d-none d-sm-block me-1">
                        <div>
                            <h3 class="fw-bold text-dark mb-1" style="font-size: 1.65rem; letter-spacing: 0.3px;">
                                <?= ($role === 'admin') ? 'DAFTAR SELURUH TIM AD HOC LPAIP' : 'DAFTAR TIM AD HOC SAYA' ?>
                            </h3>
                            <div class="text-secondary" style="font-size: 0.95rem;">
                                Lembaga Pengembangan Akademik dan Inovasi Pembelajaran (LPAIP) UKDW
                            </div>
                        </div>
                    </div>

                    <?php if ($role === 'admin'): ?>
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary rounded-pill px-4 py-2.5 shadow-sm fw-semibold fs-6" data-bs-toggle="modal" data-bs-target="#modalTim" onclick="resetForm()">
                                <i class="bi bi-plus-circle me-1.5 fs-5 align-middle"></i> Buat Tim Baru
                            </button>
                            <a href="../admin/kelola_user.php?tim_id=all" class="btn btn-outline-secondary rounded-pill px-4 py-2.5 fw-semibold fs-6">
                                <i class="bi bi-people me-1.5 fs-5 align-middle"></i> Kelola Pengguna
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- DAFTAR TIM AD HOC: FULL LEBAR -->
        <div class="row g-4">

            <!-- PANEL DAFTAR TIM: PENUH LEBAR -->
            <div class="col-12">
                
                <!-- BAR PENCARIAN (DIPERBESAR) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 p-3.5 bg-white" style="padding: 1.25rem 1.5rem !important;">
                    <form method="GET" action="" class="row g-2 align-items-center">
                        <div class="col-12 col-sm-9">
                            <div class="input-group input-group-lg">
                                <span class="input-group-text bg-light border-end-0 text-muted px-3 fs-5"><i class="bi bi-search"></i></span>
                                <input type="text" name="search" class="form-control bg-light border-start-0 py-2.5 fs-6" placeholder="Ketik nama tim atau nomor SK..." value="<?= htmlspecialchars($search) ?>" style="font-size: 1.05rem !important;">
                            </div>
                        </div>
                        <div class="col-6 col-sm-3 d-flex gap-2">
                            <button type="submit" class="btn btn-primary btn-lg w-100 fw-bold fs-6 py-2.5">
                                <i class="bi bi-search me-1"></i> Cari
                            </button>
                            <?php if (!empty($search)): ?>
                                <a href="dashboard.php" class="btn btn-outline-secondary btn-lg fs-6 py-2.5 px-3.5">Reset</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>

                <!-- LIST KARTU TIM ALA E-CLASS -->
                <?php if (empty($tim_list)): ?>
                    <div class="card border-0 shadow-sm rounded-3 p-5 text-center bg-white">
                        <i class="bi bi-folder-x fs-1 text-muted mb-2"></i>
                        <h6 class="fw-bold text-dark">Tidak Ada Tim Ad Hoc yang Ditampilkan</h6>
                        <p class="text-muted small mb-0">
                            <?= ($role === 'admin') 
                                ? 'Belum ada data tim di sistem. Silakan klik tombol "Buat Tim Baru" untuk mulai.' 
                                : 'Anda belum terdaftar dalam Tim Ad Hoc mana pun saat ini.' ?>
                        </p>
                    </div>
                <?php else: ?>
                    <div class="d-flex flex-column gap-4">
                        <?php foreach ($tim_list as $t): ?>
                            <!-- KARTU KELAS TIM -->
                            <a href="tim_detail.php?id=<?= $t['id'] ?>" class="eclass-team-card">
                                <div class="team-item">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1 pe-4">
                                            
                                            <!-- JUDUL BESAR MAROON ALA E-CLASS -->
                                            <div class="team-title">
                                                <?= htmlspecialchars($t['nama_tim']) ?>
                                            </div>

                                            <!-- BARIS SUB-INFORMASI ABU-ABU -->
                                            <div class="text-secondary mb-2" style="font-size: 1.0rem;">
                                                <strong>Masa Berlaku:</strong> <?= tgl_indo($t['tanggal_mulai']) ?> s.d. <?= tgl_indo($t['tanggal_selesai']) ?>
                                                &nbsp;|&nbsp;
                                                <strong>Status:</strong> 
                                                <span class="badge <?= $t['status'] === 'Aktif' ? 'bg-success' : 'bg-secondary' ?> py-1 px-3 rounded-1" style="font-size: 0.9rem;">
                                                    <?= htmlspecialchars($t['status']) ?>
                                                </span>
                                                <?php if (isset($t['peran_tim'])): ?>
                                                    &nbsp;|&nbsp;
                                                    <strong>Peran Anda:</strong> <span class="fw-bold text-dark"><?= htmlspecialchars($t['peran_tim']) ?></span>
                                                <?php endif; ?>
                                            </div>

                                            <?php 
                                                $tot_tugas = intval($t['total_tugas_tim'] ?? 0);
                                                $tot_selesai = intval($t['total_tugas_selesai'] ?? 0);
                                                $progress_pct = ($tot_tugas > 0) ? round(($tot_selesai / $tot_tugas) * 100) : 0;
                                            ?>
                                            <div class="text-muted mb-2" style="font-size: 0.98rem;">
                                                <i class="bi bi-person-fill text-secondary"></i> Penanggung Jawab / Ketua: <strong><?= htmlspecialchars($t['pembuat'] ?? '-') ?></strong>
                                                &nbsp;&bull;&nbsp;
                                                <i class="bi bi-people-fill text-secondary"></i> <?= intval($t['total_anggota'] ?? 0) ?> Anggota Terdaftar
                                                &nbsp;&bull;&nbsp;
                                                <i class="bi bi-check2-circle text-success"></i> <?= $tot_selesai ?>/<?= $tot_tugas ?> Tugas Selesai
                                            </div>

                                            <!-- Progres Kerja Tim: Persentase Angka Saja (tanpa progress bar) -->
                                            <div class="mt-2.5 pt-2 border-top border-light d-flex align-items-center gap-2">
                                                <span class="fw-semibold text-secondary" style="font-size: 1.0rem;">
                                                    <i class="bi bi-activity me-1" style="color: <?= $progress_pct >= 100 ? '#16a34a' : ($progress_pct > 0 ? '#6b0c22' : '#94a3b8') ?>;"></i> Progres Kerja Tim:
                                                </span>
                                                <span class="fw-bold" style="font-size: 1.15rem; color: <?= $progress_pct >= 100 ? '#16a34a' : ($progress_pct > 0 ? '#6b0c22' : '#94a3b8') ?>;">
                                                    <?= $progress_pct ?>% Selesai
                                                </span>
                                            </div>


                                            <?php if (!empty($t['deskripsi'])): ?>
                                                <div class="text-secondary mt-2.5 pt-2 border-top border-light text-truncate-2" style="font-size: 0.96rem; line-height: 1.5;">
                                                    <?= htmlspecialchars($t['deskripsi']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <!-- AKSI KHUSUS ADMIN & SIMBOL PANAH GANDA -->
                                        <div class="d-flex align-items-center gap-2 align-self-center ps-2">
                                            <?php if ($role === 'admin'): ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1.5 shadow-sm" title="Edit Data Tim" onclick="event.preventDefault(); event.stopPropagation(); editTim(<?= htmlspecialchars(json_encode($t)) ?>);">
                                                    <i class="bi bi-pencil-square text-dark me-1"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-2.5 py-1.5 shadow-sm" title="Hapus Tim" onclick="event.preventDefault(); event.stopPropagation(); konfirmasiHapusTim(<?= $t['id'] ?>, <?= htmlspecialchars(json_encode($t['nama_tim'])) ?>);">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            <?php endif; ?>
                                            <div class="text-muted fs-1 fw-light ms-1">
                                                <i class="bi bi-chevron-double-right text-secondary"></i>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>

        </div>

    </div>

    <!-- MODAL FORM TIM AD HOC (TERPADU DI DASHBOARD) -->
    <div class="modal fade" id="modalTim" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form method="POST" action="dashboard.php">
                    <input type="hidden" name="id" id="tim_id" value="0">
                    <div class="modal-header bg-primary text-white" style="background-color: #6b0c22 !important;">
                        <h5 class="modal-title fw-bold" id="modalTitle"><i class="bi bi-people-fill me-2"></i> Tambah Tim Ad Hoc Baru</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Nomor SK Penugasan <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nomor_sk" id="nomor_sk" placeholder="Contoh: SK/LPAIP/2026/003" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-semibold text-secondary">Nama Tim Ad Hoc <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="nama_tim" id="nama_tim" placeholder="Contoh: Panitia Lokakarya Pembelajaran Digital" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-semibold text-secondary">Deskripsi / Ruang Lingkup Tugas</label>
                                <textarea class="form-control" name="deskripsi" id="deskripsi" rows="3" placeholder="Jelaskan tujuan dan cakupan tim ad hoc ini..."></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Mulai SK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_mulai" id="tanggal_mulai" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Tanggal Selesai SK <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="tanggal_selesai" id="tanggal_selesai" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-semibold text-secondary">Status Operasional <span class="text-danger">*</span></label>
                                <select class="form-select" name="status" id="status" required>
                                    <option value="Perencanaan">Perencanaan</option>
                                    <option value="Aktif" selected>Aktif</option>
                                    <option value="Diperpanjang">Diperpanjang</option>
                                    <option value="Selesai">Selesai</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="simpan_tim" class="btn btn-primary rounded-pill px-4 fw-semibold">
                            <i class="bi bi-save me-1"></i> Simpan Tim Ad Hoc
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <?php include '../includes/footer.php'; ?>
</div>
