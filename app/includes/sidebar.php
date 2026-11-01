<?php
$current_page = basename($_SERVER['PHP_SELF']);
$user_role = $_SESSION['role'] ?? 'anggota';

// Ambil ID tim aktif: dari parameter URL $_GET['id'] jika ada, atau dari $_SESSION['active_tim_id']
$active_tim_id = 0;
if ($current_page === 'tim_detail.php' && isset($_GET['id'])) {
    $active_tim_id = intval($_GET['id']);
} elseif (!empty($_SESSION['active_tim_id'])) {
    $active_tim_id = intval($_SESSION['active_tim_id']);
}

// Fallback cerdas: Jika belum ada di session (misal baru login langsung ke menu Rekap Dokumen), ambil tim pertama yang ada
if ($active_tim_id <= 0 && isset($pdo)) {
    if ($user_role === 'admin') {
        $stmt_fb = $pdo->query("SELECT id, nama_tim FROM tim_adhoc ORDER BY id DESC LIMIT 1");
        $fb = $stmt_fb ? $stmt_fb->fetch() : null;
        if ($fb) {
            $active_tim_id = $fb['id'];
            $_SESSION['active_tim_id'] = $fb['id'];
            $_SESSION['active_tim_nama'] = $fb['nama_tim'];
        }
    } else {
        $uid = $_SESSION['user_id'] ?? 0;
        $stmt_fb = $pdo->prepare("SELECT t.id, t.nama_tim FROM tim_adhoc t JOIN anggota_tim a ON t.id = a.tim_id WHERE a.user_id = ? ORDER BY t.id DESC LIMIT 1");
        $stmt_fb->execute([$uid]);
        $fb = $stmt_fb->fetch();
        if ($fb) {
            $active_tim_id = $fb['id'];
            $_SESSION['active_tim_id'] = $fb['id'];
            $_SESSION['active_tim_nama'] = $fb['nama_tim'];
        }
    }
}
?>
<nav class="sidebar" id="sidebar">
    <div class="brand-title d-flex align-items-center gap-3 py-3 px-3">
        <img src="<?= BASE_URL ?>assets/img/logo_ukdw_hq.png" alt="Logo UKDW" style="height: 42px; width: auto;" class="flex-shrink-0 drop-shadow">
        <div class="lh-sm">
            <div class="fw-bold text-white" style="font-size: 0.95rem; letter-spacing: 0.5px;">SIM AD HOC</div>
            <small class="text-warning fw-semibold" style="font-size: 0.75rem; letter-spacing: 0.3px; color: #facc15 !important;">LPAIP UKDW</small>
        </div>
    </div>
    
    <div class="py-3">
        <div class="nav-section-label">Navigasi Utama</div>
        
        <a href="<?= BASE_URL ?>pages/dashboard.php" class="nav-link <?= $current_page == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2"></i> Dashboard Tim
        </a>
        
        <?php if ($active_tim_id > 0): ?>
            <a href="<?= BASE_URL ?>pages/tim_detail.php?id=<?= $active_tim_id ?>" class="nav-link <?= $current_page == 'tim_detail.php' ? 'active' : '' ?>" title="<?= htmlspecialchars($_SESSION['active_tim_nama'] ?? 'Ruang Kerja Tim Ini') ?>">
                <i class="bi bi-folder-check"></i> Ruang Kerja Tim Ini
            </a>
        <?php endif; ?>

        <?php if ($user_role === 'admin'): ?>
            <div class="nav-section-label mt-4">Pengaturan Admin</div>
            <a href="<?= BASE_URL ?>pages/dokumen.php<?= $active_tim_id > 0 ? '?tim_id=' . $active_tim_id : '?tim_id=all' ?>" class="nav-link <?= $current_page == 'dokumen.php' ? 'active' : '' ?>">
                <i class="bi bi-folder2-open"></i> Rekap LPJ & Dokumen
            </a>
            <a href="<?= BASE_URL ?>admin/kelola_user.php<?= $active_tim_id > 0 ? '?tim_id=' . $active_tim_id : '?tim_id=all' ?>" class="nav-link <?= $current_page == 'kelola_user.php' ? 'active' : '' ?>">
                <i class="bi bi-person-gear"></i> Kelola Pengguna
            </a>
        <?php endif; ?>
        
        <div class="nav-section-label mt-4">Sesi Akun</div>
        <a href="<?= BASE_URL ?>auth/logout.php" class="nav-link logout-link mt-1">
            <i class="bi bi-box-arrow-right"></i> Keluar (Logout)
        </a>
    </div>
</nav>
