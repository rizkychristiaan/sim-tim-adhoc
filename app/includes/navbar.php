<?php
$user_name = $_SESSION['nama'] ?? 'Pengguna';
$user_role = $_SESSION['role'] ?? 'anggota';
$user_id = $_SESSION['user_id'] ?? 0;

// Automatically check deadline reminders for logged-in user (kecuali admin)
$unread_count = 0;
$notif_list = [];
if (isset($pdo) && $user_id) {
    check_deadline_reminders($pdo, $user_id);
    
    $stmt_notif = $pdo->prepare("SELECT COUNT(*) FROM notifikasi WHERE user_id = ? AND is_read = 0");
    $stmt_notif->execute([$user_id]);
    $unread_count = $stmt_notif->fetchColumn();
    
    // Fetch latest 5 notifications
    $stmt_notif_list = $pdo->prepare("SELECT * FROM notifikasi WHERE user_id = ? ORDER BY created_at DESC LIMIT 5");
    $stmt_notif_list->execute([$user_id]);
    $notif_list = $stmt_notif_list->fetchAll();
}
?>
<header class="top-navbar d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-light d-lg-none" id="sidebarToggle">
            <i class="bi bi-list fs-4"></i>
        </button>
        <img src="<?= BASE_URL ?>assets/img/logo_ukdw_hq.png" alt="Logo UKDW" style="height: 38px; width: auto;" class="d-none d-sm-block">
        <div>
            <h6 class="mb-0 fw-bold text-dark" style="letter-spacing: 0.2px;">Sistem Informasi Manajemen Tim Ad Hoc</h6>
            <small class="text-muted"><strong style="color: #6b0c22;">LPAIP</strong> &bull; Universitas Kristen Duta Wacana</small>
        </div>
    </div>
    
    <div class="d-flex align-items-center gap-3">
        <?php if (true): ?>
        <!-- Notifikasi Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light position-relative rounded-circle p-2" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="notifBellBtn">
                <i class="bi bi-bell fs-5 text-secondary"></i>
                <?php if ($unread_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="notifBadge">
                        <?= $unread_count ?>
                    </span>
                <?php endif; ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0" style="width: 330px; max-height: 400px; overflow-y: auto;">
                <li class="dropdown-header fw-bold text-dark d-flex justify-content-between align-items-center py-2 px-3">
                    <span>Notifikasi Saya</span>
                    <?php if ($unread_count > 0): ?>
                        <a href="<?= BASE_URL ?>pages/mark_notif.php?action=read_all" class="badge bg-primary text-decoration-none shadow-sm" id="notifBadgeHeader" onclick="markAllNotifRead(event)">
                            <i class="bi bi-check2-all me-1"></i> Tandai Sudah Dibaca
                        </a>
                    <?php endif; ?>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <?php if (empty($notif_list)): ?>
                    <li class="px-3 py-3 text-muted text-center"><small><i class="bi bi-bell-slash me-1"></i> Belum ada notifikasi</small></li>
                <?php else: ?>
                    <?php foreach ($notif_list as $n): ?>
                        <li>
                            <a class="dropdown-item py-2 text-wrap <?= $n['is_read'] == 0 ? 'bg-light border-start border-primary border-3 fw-bold' : 'opacity-75' ?>" href="<?= BASE_URL ?>pages/mark_notif.php?action=read_one&id=<?= $n['id'] ?>">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <span class="small <?= $n['is_read'] == 0 ? 'text-primary' : 'text-dark' ?>"><?= htmlspecialchars($n['judul']) ?></span>
                                    <?php if ($n['is_read'] == 0): ?>
                                        <span class="badge bg-danger rounded-pill notif-item-badge" style="font-size: 0.65rem;">Baru</span>
                                    <?php endif; ?>
                                </div>
                                <div class="text-secondary small fw-normal" style="font-size: 0.82rem;"><?= htmlspecialchars($n['pesan']) ?></div>
                                <div class="text-muted fs-7 mt-1 fw-normal" style="font-size: 0.72rem;"><i class="bi bi-clock me-1"></i><?= date('d M Y H:i', strtotime($n['created_at'])) ?></div>
                            </a>
                        </li>
                    <?php endforeach; ?>
                <?php endif; ?>
            </ul>
        </div>

        <script>
        function markAllNotifRead(e) {
            if (e) e.preventDefault();
            fetch('<?= BASE_URL ?>pages/mark_notif.php?action=read_all&ajax=1')
                .then(res => res.json())
                .then(data => {
                    var badge = document.getElementById('notifBadge');
                    var badgeHeader = document.getElementById('notifBadgeHeader');
                    if (badge) badge.remove();
                    if (badgeHeader) badgeHeader.remove();
                    document.querySelectorAll('.dropdown-item.border-primary').forEach(el => {
                        el.classList.remove('bg-light', 'border-start', 'border-primary', 'border-3', 'fw-bold');
                        el.classList.add('opacity-75');
                    });
                    document.querySelectorAll('.notif-item-badge').forEach(el => el.remove());
                })
                .catch(err => {
                    window.location.href = '<?= BASE_URL ?>pages/mark_notif.php?action=read_all';
                });
        }
        </script>
        <?php endif; ?>

        <!-- Profil Dropdown -->
        <div class="dropdown">
            <button class="btn btn-light d-flex align-items-center gap-2 rounded-pill px-3 py-1.5 border-0 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" id="userProfileDropdownBtn">
                <div class="text-white rounded-circle d-flex align-items-center justify-content-center fw-bold shadow-sm" style="width: 34px; height: 34px; font-size: 0.85rem; background: linear-gradient(135deg, #6b0c22 0%, #83102b 100%); border: 1.5px solid #d97706;">
                    <?= strtoupper(substr($user_name, 0, 1)) ?>
                </div>
                <div class="text-start d-none d-md-block">
                    <div class="fw-semibold text-dark <?= $user_role === 'admin' ? 'lh-1' : '' ?>" style="font-size: 0.88rem;"><?= htmlspecialchars($user_name) ?></div>
                    <?php if ($user_role === 'admin'): ?>
                        <span class="badge bg-danger-subtle text-danger badge-role mt-1 fw-semibold">
                            ADMIN
                        </span>
                    <?php endif; ?>
                </div>
                <i class="bi bi-chevron-down text-secondary small ms-1"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow-sm border-0">
                <li><a class="dropdown-item" href="<?= BASE_URL ?>pages/profile.php"><i class="bi bi-person me-2"></i> Profil Saya</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= BASE_URL ?>auth/logout.php"><i class="bi bi-box-arrow-right me-2"></i> Logout</a></li>
            </ul>
        </div>
    </div>
</header>
