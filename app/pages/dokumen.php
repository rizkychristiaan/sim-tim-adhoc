<?php
/**
 * ==============================================================================
 * FILE: app/pages/dokumen.php (Pengarsipan Berkas & LPJ Tim Ad Hoc)
 * ROLE AKSES: Admin, Ketua Tim, Anggota Tim
 * FUNGSI UTAMA:
 *   1. Pengarsipan berkas dokumen akhir dan Laporan Pertanggungjawaban (LPJ) tim.
 *   2. Mengelompokkan berkas pendukung berdasarkan nama Tim Ad Hoc.
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$page_title = "Repositori Dokumen & LPJ";
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle File Upload (Hanya Admin dan Ketua Tim yang dapat mengunggah)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dokumen'])) {
    if ($role === 'anggota') {
        $error = "Anggota tim hanya memiliki hak akses untuk melihat dan mengunduh dokumen!";
    } else {
        $tim_id = intval($_POST['tim_id']);
        $nama_dokumen = trim($_POST['nama_dokumen']);
        $jenis_dokumen = $_POST['jenis_dokumen'];

        if ($tim_id <= 0 || empty($nama_dokumen) || !isset($_FILES['file_dokumen'])) {
            $error = "Pilih tim ad hoc, isi nama dokumen, dan lampirkan berkas!";
        } else {
            $file = $_FILES['file_dokumen'];
            if ($file['error'] === UPLOAD_ERR_OK) {
                $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'zip', 'rar'];

                if (!in_array($ext, $allowed)) {
                    $error = "Format berkas tidak diizinkan! (Hanya PDF, DOC, XLS, JPG, PNG, ZIP, RAR)";
                } else {
                    $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
                    $upload_dir = '../uploads/dokumen/';
                    if (!is_dir($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }
                    $target = $upload_dir . $new_filename;

                    if (move_uploaded_file($file['tmp_name'], $target)) {
                        $stmt = $pdo->prepare("INSERT INTO dokumen (tim_id, uploader_id, nama_dokumen, jenis_dokumen, file_path) VALUES (?, ?, ?, ?, ?)");
                        $stmt->execute([$tim_id, $user_id, $nama_dokumen, $jenis_dokumen, $new_filename]);
                        $message = "Berkas dokumen berhasil diunggah!";
                    } else {
                        $error = "Gagal mengunggah berkas ke server.";
                    }
                }
            } else {
                $error = "Terjadi kesalahan saat mengunggah berkas.";
            }
        }
    }
}

// Handle File Edit / Update (Hanya Admin, Ketua Tim, atau Uploader)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_dokumen'])) {
    if ($role === 'anggota') {
        $error = "Anggota tim hanya memiliki hak akses untuk melihat dan mengunduh dokumen!";
    } else {
        $id = intval($_POST['id']);
        $tim_id = intval($_POST['tim_id']);
        $nama_dokumen = trim($_POST['nama_dokumen']);
        $jenis_dokumen = $_POST['jenis_dokumen'];

        if ($id <= 0 || $tim_id <= 0 || empty($nama_dokumen)) {
            $error = "Pilih tim ad hoc dan isi nama dokumen!";
        } else {
            $stmt_chk = $pdo->prepare("SELECT * FROM dokumen WHERE id = ?");
            $stmt_chk->execute([$id]);
            $doc_data = $stmt_chk->fetch();

            if ($doc_data) {
                if ($role === 'admin' || $role === 'ketua' || $doc_data['uploader_id'] == $user_id) {
                    if (isset($_FILES['file_dokumen']) && $_FILES['file_dokumen']['error'] === UPLOAD_ERR_OK) {
                        $file = $_FILES['file_dokumen'];
                        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                        $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png', 'zip', 'rar'];

                        if (!in_array($ext, $allowed)) {
                            $error = "Format berkas tidak diizinkan! (Hanya PDF, DOC, XLS, JPG, PNG, ZIP, RAR)";
                        } else {
                            $new_filename = time() . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $file['name']);
                            $upload_dir = '../uploads/dokumen/';
                            if (!is_dir($upload_dir)) {
                                mkdir($upload_dir, 0777, true);
                            }
                            $target = $upload_dir . $new_filename;

                            if (move_uploaded_file($file['tmp_name'], $target)) {
                                $old_file = $upload_dir . $doc_data['file_path'];
                                if (!empty($doc_data['file_path']) && file_exists($old_file)) {
                                    @unlink($old_file);
                                }

                                $stmt_upd = $pdo->prepare("UPDATE dokumen SET tim_id = ?, nama_dokumen = ?, jenis_dokumen = ?, file_path = ? WHERE id = ?");
                                $stmt_upd->execute([$tim_id, $nama_dokumen, $jenis_dokumen, $new_filename, $id]);
                                $message = "Berkas dokumen dan file berhasil diperbarui!";
                            } else {
                                $error = "Gagal mengunggah berkas baru ke server.";
                            }
                        }
                    } else {
                        $stmt_upd = $pdo->prepare("UPDATE dokumen SET tim_id = ?, nama_dokumen = ?, jenis_dokumen = ? WHERE id = ?");
                        $stmt_upd->execute([$tim_id, $nama_dokumen, $jenis_dokumen, $id]);
                        $message = "Data dokumen berhasil diperbarui!";
                    }
                } else {
                    $error = "Anda tidak memiliki akses untuk mengedit dokumen ini!";
                }
            } else {
                $error = "Dokumen tidak ditemukan.";
            }
        }
    }
}

// Handle File Delete (Hanya Admin, Ketua Tim, atau Uploader)
if (isset($_GET['delete'])) {
    if ($role === 'anggota') {
        $error = "Anggota tim hanya memiliki hak akses untuk melihat dan mengunduh dokumen!";
    } else {
        $del_id = intval($_GET['delete']);
        $stmt_chk = $pdo->prepare("SELECT * FROM dokumen WHERE id = ?");
        $stmt_chk->execute([$del_id]);
        $doc_data = $stmt_chk->fetch();

        if ($doc_data) {
            if ($role === 'admin' || $role === 'ketua' || $doc_data['uploader_id'] == $user_id) {
                $file_path = '../uploads/dokumen/' . $doc_data['file_path'];
                if (!empty($doc_data['file_path']) && file_exists($file_path)) {
                    @unlink($file_path);
                }
                $stmt_del = $pdo->prepare("DELETE FROM dokumen WHERE id = ?");
                $stmt_del->execute([$del_id]);
                $message = "Berkas dokumen berhasil dihapus!";
            } else {
                $error = "Anda tidak memiliki akses untuk menghapus dokumen ini!";
            }
        } else {
            $error = "Dokumen tidak ditemukan.";
        }
    }
}

// Fetch Teams for Dropdown & Filter
$teams_dropdown = $pdo->query("SELECT id, nama_tim, nomor_sk FROM tim_adhoc ORDER BY nama_tim ASC")->fetchAll();

// Fetch Documents
// Cek filter tim:
// 1. Jika URL parameter tim_id=all -> tampilkan semua tim ($filter_tim = 0)
// 2. Jika URL parameter tim_id ada angka -> gunakan tim_id tersebut
// 3. Jika tanpa parameter di URL tapi sedang aktif di tim tertentu (ada di session) -> gunakan $_SESSION['active_tim_id']
$filter_tim = 0;
if (isset($_GET['tim_id'])) {
    if ($_GET['tim_id'] !== 'all' && $_GET['tim_id'] !== '') {
        $filter_tim = intval($_GET['tim_id']);
    }
} elseif (!empty($_SESSION['active_tim_id'])) {
    $filter_tim = intval($_SESSION['active_tim_id']);
}

$current_tim_doc = null;
if ($filter_tim > 0) {
    $stmt_ct = $pdo->prepare("SELECT * FROM tim_adhoc WHERE id = ?");
    $stmt_ct->execute([$filter_tim]);
    $current_tim_doc = $stmt_ct->fetch();
    if (!$current_tim_doc) {
        $filter_tim = 0;
    }
}

$filter_jenis = $_GET['jenis_dokumen'] ?? '';
$search_query = trim($_GET['search'] ?? '');

$query = "SELECT d.*, t.nama_tim, t.nomor_sk, u.nama as uploader FROM dokumen d JOIN tim_adhoc t ON d.tim_id = t.id JOIN users u ON d.uploader_id = u.id WHERE 1=1";
$params = [];

if ($filter_tim > 0) {
    $query .= " AND d.tim_id = ?";
    $params[] = $filter_tim;
}

if (!empty($filter_jenis)) {
    $query .= " AND d.jenis_dokumen = ?";
    $params[] = $filter_jenis;
}

if (!empty($search_query)) {
    $query .= " AND (d.nama_dokumen LIKE ? OR t.nama_tim LIKE ? OR u.nama LIKE ?)";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
    $params[] = "%$search_query%";
}

$query .= " ORDER BY d.uploaded_at DESC";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$docs = $stmt->fetchAll();

// Calculate Document Stats (sesuai tim yang sedang aktif / difilter)
if ($filter_tim > 0) {
    $stat_stmt = $pdo->prepare("SELECT jenis_dokumen, COUNT(*) as cnt FROM dokumen WHERE tim_id = ? GROUP BY jenis_dokumen");
    $stat_stmt->execute([$filter_tim]);
} else {
    $stat_stmt = $pdo->query("SELECT jenis_dokumen, COUNT(*) as cnt FROM dokumen GROUP BY jenis_dokumen");
}

$stat_total_docs = 0;
$stat_lpj = 0;
$stat_proposal = 0;
$stat_lainnya = 0;

while ($r = $stat_stmt->fetch()) {
    $stat_total_docs += $r['cnt'];
    if ($r['jenis_dokumen'] === 'LPJ') $stat_lpj += $r['cnt'];
    elseif ($r['jenis_dokumen'] === 'Proposal') $stat_proposal += $r['cnt'];
    else $stat_lainnya += $r['cnt'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="fw-bold mb-1">
                    Repositori Dokumen & LPJ <?= $filter_tim > 0 && $current_tim_doc ? '- ' . htmlspecialchars($current_tim_doc['nama_tim']) : '' ?>
                </h4>
                <p class="text-muted small mb-0">
                    Home / Berkas Dokumen <?= $filter_tim > 0 && $current_tim_doc ? '/ ' . htmlspecialchars($current_tim_doc['nama_tim']) : '(Semua Tim)' ?>
                </p>
            </div>
            <?php if ($role === 'admin' || $role === 'ketua'): ?>
                <button type="button" class="btn btn-primary rounded-pill px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalUpload">
                    <i class="bi bi-plus-lg me-1"></i> Upload Berkas Baru
                </button>
            <?php endif; ?>
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

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Total Dokumen</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_total_docs ?></div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-folder-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">LPJ Terunggah</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_lpj ?></div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-file-earmark-check-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Proposal</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_proposal ?></div>
                        </div>
                        <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-file-earmark-text-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Dokumen Lainnya</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_lainnya ?></div>
                        </div>
                        <div class="bg-info-subtle text-info rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-file-earmark-zip-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="search" class="form-control" placeholder="Cari nama berkas, tim, uploader..." value="<?= htmlspecialchars($search_query) ?>">
                    </div>
                    <div class="col-md-3">
                        <select name="tim_id" class="form-select" onchange="this.form.submit()">
                            <option value="all" <?= $filter_tim === 0 ? 'selected' : '' ?>>Tim: Semua Tim</option>
                            <?php foreach ($teams_dropdown as $t): ?>
                                <option value="<?= $t['id'] ?>" <?= $filter_tim == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nama_tim']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="jenis_dokumen" class="form-select" onchange="this.form.submit()">
                            <option value="">Kategori: Semua</option>
                            <option value="LPJ" <?= $filter_jenis == 'LPJ' ? 'selected' : '' ?>>LPJ</option>
                            <option value="Proposal" <?= $filter_jenis == 'Proposal' ? 'selected' : '' ?>>Proposal Kegiatan</option>
                            <option value="SK Penugasan" <?= $filter_jenis == 'SK Penugasan' ? 'selected' : '' ?>>SK / Surat Penugasan</option>
                            <option value="Berita Acara" <?= $filter_jenis == 'Berita Acara' ? 'selected' : '' ?>>Berita Acara</option>
                            <option value="Lainnya" <?= $filter_jenis == 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                        </select>
                    </div>
                    <div class="col-md-2 text-end">
                        <a href="dokumen.php?tim_id=all" class="btn btn-outline-secondary w-100">Reset Filter</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Documents Table -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4 py-3">NAMA DOKUMEN</th>
                                <th class="py-3">TIM TERKAIT</th>
                                <th class="py-3">KATEGORI</th>
                                <th class="py-3">PENGUNGGAH</th>
                                <th class="py-3">TANGGAL</th>
                                <th class="pe-4 py-3 text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($docs)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada berkas terdaftar dalam repositori.</td></tr>
                            <?php else: ?>
                                <?php foreach ($docs as $d): ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($d['nama_dokumen']) ?></div>
                                            <small class="text-muted"><i class="bi bi-paperclip me-1"></i><?= htmlspecialchars($d['file_path']) ?></small>
                                        </td>
                                        <td class="py-3">
                                            <a href="tim_detail.php?id=<?= $d['tim_id'] ?>" class="text-decoration-none text-dark fw-semibold small d-inline-block hover-primary" title="Buka Ruang Kerja Tim"><?= htmlspecialchars($d['nama_tim']) ?> <i class="bi bi-box-arrow-up-right ms-1 text-muted" style="font-size: 0.75rem;"></i></a>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($d['jenis_dokumen'] === 'LPJ'): ?>
                                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill border border-success-subtle">LPJ</span>
                                            <?php elseif ($d['jenis_dokumen'] === 'Proposal'): ?>
                                                <span class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill border border-primary-subtle">Proposal</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill border border-secondary-subtle"><?= htmlspecialchars($d['jenis_dokumen']) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3"><span class="small text-secondary"><i class="bi bi-person-circle me-1 text-primary"></i><?= htmlspecialchars($d['uploader']) ?></span></td>
                                        <td class="py-3 text-secondary small"><?= date('d M Y H:i', strtotime($d['uploaded_at'])) ?></td>
                                        <td class="pe-4 py-3 text-end">
                                            <div class="d-inline-flex gap-2 align-items-center">
                                                <a href="../uploads/dokumen/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 fw-medium" title="Unduh Berkas">
                                                    <i class="bi bi-download me-1"></i> Unduh
                                                </a>
                                                <?php if ($role === 'admin' || $role === 'ketua' || $d['uploader_id'] == $user_id): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 fw-medium" onclick='editDokumen(<?= json_encode($d) ?>)' title="Edit Dokumen">
                                                        <i class="bi bi-pencil me-1"></i> Edit
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill px-3 py-1 fw-medium" onclick="showDeleteModal({ url: 'dokumen.php?delete=<?= $d['id'] ?>', title: 'Hapus Berkas Dokumen', message: 'Apakah Anda yakin ingin menghapus berkas dokumen ini?', itemTitle: <?= htmlspecialchars(json_encode($d['nama_dokumen'])) ?> });" title="Hapus Dokumen">
                                                        <i class="bi bi-trash me-1"></i> Hapus
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
                    <span class="text-muted small">Menampilkan 1-<?= count($docs) ?> dari <?= count($docs) ?> data</span>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3 me-2" disabled>&lt; Sebelumnya</button>
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3" disabled>Selanjutnya &gt;</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Upload -->
    <div class="modal fade" id="modalUpload" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form method="POST" action="" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up-fill me-2"></i> Upload Berkas Dokumen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Tim Ad Hoc Terkait <span class="text-danger">*</span></label>
                            <select class="form-select" name="tim_id" required>
                                <option value="">-- Pilih Tim Ad Hoc --</option>
                                <?php foreach ($teams_dropdown as $t): ?>
                                    <option value="<?= $t['id'] ?>" <?= $filter_tim == $t['id'] ? 'selected' : '' ?>><?= htmlspecialchars($t['nama_tim']) ?> (<?= htmlspecialchars($t['nomor_sk']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul / Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_dokumen" placeholder="Contoh: Laporan LPJ Akhir Tim 2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis / Kategori Berkas <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_dokumen" required>
                                <option value="LPJ">LPJ (Laporan Pertanggungjawaban)</option>
                                <option value="Proposal">Proposal Kegiatan</option>
                                <option value="SK Penugasan">SK / Surat Penugasan</option>
                                <option value="Berita Acara">Berita Acara Rapat</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih File Berkas (PDF, DOCX, XLSX, ZIP, RAR) <span class="text-danger">*</span></label>
                            <input type="file" class="form-control" name="file_dokumen" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="upload_dokumen" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm"><i class="bi bi-upload me-1"></i> Upload Berkas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal Edit Dokumen -->
    <div class="modal fade" id="modalEditDokumen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-3">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="id" id="edit_dokumen_id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-pencil-square me-2"></i> Edit Data Dokumen</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Tim Ad Hoc Terkait <span class="text-danger">*</span></label>
                            <select class="form-select" name="tim_id" id="edit_tim_id" required>
                                <option value="">-- Pilih Tim Ad Hoc --</option>
                                <?php foreach ($teams_dropdown as $t): ?>
                                    <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['nama_tim']) ?> (<?= htmlspecialchars($t['nomor_sk']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul / Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_dokumen" id="edit_nama_dokumen" placeholder="Contoh: Laporan LPJ Akhir Tim 2026" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis / Kategori Berkas <span class="text-danger">*</span></label>
                            <select class="form-select" name="jenis_dokumen" id="edit_jenis_dokumen" required>
                                <option value="LPJ">LPJ (Laporan Pertanggungjawaban)</option>
                                <option value="Proposal">Proposal Kegiatan</option>
                                <option value="SK Penugasan">SK / Surat Penugasan</option>
                                <option value="Berita Acara">Berita Acara Rapat</option>
                                <option value="Lainnya">Lainnya</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Ganti File Berkas (Opsional)</label>
                            <input type="file" class="form-control" name="file_dokumen">
                            <div class="form-text text-muted small mt-1">
                                <i class="bi bi-info-circle me-1 text-primary"></i> Berkas saat ini: <span id="edit_current_file" class="fw-semibold text-dark"></span>. Biarkan kosong jika tidak ingin mengganti file.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="edit_dokumen" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    document.querySelectorAll('#modalUpload, #modalEditDokumen').forEach(function(modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            var form = this.querySelector('form');
            if (form) form.reset();
        });
    });
    </script>

    <?php include '../includes/footer.php'; ?>



