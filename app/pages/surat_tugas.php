<?php
/**
 * ==============================================================================
 * FILE: app/pages/surat_tugas.php (Manajemen Unduh Surat Tugas & SK Tim)
 * ROLE AKSES: Admin, Ketua Tim, Anggota Tim
 * FUNGSI UTAMA:
 *   1. Menampilkan daftar dokumen Surat Tugas dan SK resmi Tim Ad Hoc.
 *   2. Menyediakan fitur pengunduhan berkas SK resmi dalam format Word (.doc).
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

// Jika ada request print_id (dari link lama), arahkan langsung ke download Word (.doc)
if (isset($_GET['print_id']) && intval($_GET['print_id']) > 0) {
    header("Location: download_sk.php?id=" . intval($_GET['print_id']));
    exit();
}

$page_title = "Surat Tugas & SK Tim Ad Hoc";

// Fetch seluruh Tim Ad Hoc beserta jumlah anggotanya
$stmt = $pdo->query("
    SELECT t.*, 
           (SELECT COUNT(*) FROM anggota_tim WHERE tim_id = t.id) as total_anggota,
           u.nama as pembuat
    FROM tim_adhoc t 
    JOIN users u ON t.created_by = u.id 
    ORDER BY t.created_at DESC
");
$teams = $stmt->fetchAll();

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1"><i class="bi bi-file-earmark-word text-primary me-2"></i>Surat Tugas &amp; SK Tim Ad Hoc</h4>
                <p class="text-muted small mb-0">Unduh dokumen resmi Surat Keputusan (SK) Tim Ad Hoc dalam format Microsoft Word (.doc) sesuai format baku UKDW.</p>
            </div>
        </div>

        <div class="row g-4">
            <?php if (empty($teams)): ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm p-5 text-center text-muted">
                        <i class="bi bi-folder-x fs-1 mb-2"></i>
                        <h5>Belum Ada Data Tim Ad Hoc</h5>
                        <p class="small">Silakan buat Tim Ad Hoc terlebih dahulu pada menu Tim Ad Hoc.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach ($teams as $t): ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="card h-100 shadow-sm border-0 rounded-3">
                            <div class="card-body p-4 d-flex flex-column justify-content-between">
                                <div>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2.5 py-1 rounded-pill small">
                                            <i class="bi bi-file-earmark-text me-1"></i> SK: <?= htmlspecialchars($t['nomor_sk']) ?>
                                        </span>
                                        <span class="badge bg-<?= $t['status'] == 'Aktif' ? 'success' : 'secondary' ?>-subtle text-<?= $t['status'] == 'Aktif' ? 'success' : 'secondary' ?> rounded-pill px-2.5 py-1 small">
                                            <?= htmlspecialchars($t['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <h5 class="fw-bold text-dark mb-2"><?= htmlspecialchars($t['nama_tim']) ?></h5>
                                    <p class="text-secondary small mb-3 lh-sm"><?= htmlspecialchars(substr($t['deskripsi'] ?? '', 0, 110)) ?><?= strlen($t['deskripsi'] ?? '') > 110 ? '...' : '' ?></p>

                                    <div class="small text-muted mb-3 bg-light p-2.5 rounded-2">
                                        <div class="mb-1"><i class="bi bi-calendar-range me-1.5 text-primary"></i> <?= tgl_indo($t['tanggal_mulai']) ?> s.d. <?= tgl_indo($t['tanggal_selesai']) ?></div>
                                        <div><i class="bi bi-people me-1.5 text-primary"></i> <?= $t['total_anggota'] ?> Personil Terdaftar</div>
                                    </div>
                                </div>

                                <div class="pt-2 border-top">
                                    <a href="download_sk.php?id=<?= $t['id'] ?>" class="btn btn-primary w-100 fw-semibold rounded-pill shadow-sm">
                                        <i class="bi bi-file-earmark-word me-1.5"></i> Unduh Surat Tugas (.doc)
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

    </div>

    <?php include '../includes/footer.php'; ?>
</div>
