<?php
/**
 * ==============================================================================
 * FILE: app/pages/tugas.php (Manajemen Penugasan Kerja Personil)
 * ROLE AKSES: Admin, Ketua Tim, Anggota Tim
 * FUNGSI UTAMA:
 *   1. Menampilkan daftar tugas yang diberikan kepada pengguna.
 *   2. PEMBARUAN STATUS: Mengubah status tugas (Belum Dimulai, Proses, Selesai).
 *   3. MULTI-FILE UPLOAD: Mengunggah berkas hasil penyelesaian tugas (lampiran).
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$page_title = "Tata Kelola Penugasan";
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$message = '';
$error = '';

// Handle POST: Update Task Status & Multi-File Upload
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $tugas_id = intval($_POST['tugas_id']);
    $new_status = $_POST['status'];

    if ($tugas_id > 0) {
        $stmt_upd = $pdo->prepare("UPDATE tugas SET status = ? WHERE id = ?");
        $stmt_upd->execute([$new_status, $tugas_id]);
        $message = "Status penugasan berhasil diperbarui!";

        // Handle Multi-file Uploads
        if (isset($_FILES['lampiran_files']) && !empty($_FILES['lampiran_files']['name'][0])) {
            $files = $_FILES['lampiran_files'];
            $total = count($files['name']);
            $uploaded_count = 0;
            $upload_dir = 'uploads/tugas/';
            if (!is_dir($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }

            $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'jpeg', 'png', 'zip', 'rar', 'txt', 'csv', 'ppt', 'pptx'];

            for ($i = 0; $i < $total; $i++) {
                if ($files['error'][$i] === UPLOAD_ERR_OK) {
                    $orig_name = $files['name'][$i];
                    $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

                    if (in_array($ext, $allowed)) {
                        $new_filename = time() . '_' . $i . '_' . preg_replace('/[^a-zA-Z0-9_\.-]/', '_', $orig_name);
                        $target = $upload_dir . $new_filename;
                        $size = $files['size'][$i];

                        if (move_uploaded_file($files['tmp_name'][$i], $target)) {
                            $stmt_f = $pdo->prepare("INSERT INTO tugas_files (tugas_id, uploader_id, nama_file_asli, file_path, file_size) VALUES (?, ?, ?, ?, ?)");
                            $stmt_f->execute([$tugas_id, $user_id, $orig_name, $new_filename, $size]);
                            $uploaded_count++;
                        }
                    }
                }
            }

            if ($uploaded_count > 0) {
                $message .= " ($uploaded_count berkas lampiran berhasil diunggah).";
            }
        }
    }
}

// Handle GET: Hapus Single Lampiran File
if (isset($_GET['hapus_file'])) {
    $file_id = intval($_GET['hapus_file']);
    $stmt_chk = $pdo->prepare("SELECT tf.*, t.assigned_to FROM tugas_files tf JOIN tugas t ON tf.tugas_id = t.id WHERE tf.id = ?");
    $stmt_chk->execute([$file_id]);
    $file_data = $stmt_chk->fetch();

    if ($file_data && ($role === 'admin' || $role === 'ketua' || $file_data['uploader_id'] == $user_id)) {
        $file_path = 'uploads/tugas/' . $file_data['file_path'];
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
        $pdo->prepare("DELETE FROM tugas_files WHERE id = ?")->execute([$file_id]);
        $message = "Berkas lampiran berhasil dihapus!";
    }
}

// Filter Status
$filter_status = $_GET['status'] ?? '';
$filter_tim = intval($_GET['tim_id'] ?? 0);

$query = "SELECT tg.*, t.nama_tim, t.nomor_sk, u.nama as pic FROM tugas tg JOIN tim_adhoc t ON tg.tim_id = t.id JOIN users u ON tg.assigned_to = u.id WHERE 1=1";
$params = [];

if ($role !== 'admin' && $role !== 'ketua') {
    $query .= " AND tg.assigned_to = ?";
    $params[] = $user_id;
}

if (!empty($filter_status)) {
    $query .= " AND tg.status = ?";
    $params[] = $filter_status;
}

if ($filter_tim > 0) {
    $query .= " AND tg.tim_id = ?";
    $params[] = $filter_tim;
}

$query .= " ORDER BY tg.deadline ASC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$tasks = $stmt->fetchAll();

// Fetch attached files for tasks
$task_files_map = [];
if (!empty($tasks)) {
    $task_ids = array_column($tasks, 'id');
    $in_clause = implode(',', array_fill(0, count($task_ids), '?'));
    $stmt_f = $pdo->prepare("SELECT tf.*, u.nama as uploader_nama FROM tugas_files tf JOIN users u ON tf.uploader_id = u.id WHERE tf.tugas_id IN ($in_clause) ORDER BY tf.uploaded_at DESC");
    $stmt_f->execute($task_ids);
    $all_files = $stmt_f->fetchAll();
    foreach ($all_files as $f) {
        $task_files_map[$f['tugas_id']][] = $f;
    }
}

// Calculate Task Stats
$stat_belum = 0;
$stat_proses = 0;
$stat_review = 0;
$stat_selesai = 0;

$all_tasks_stmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM tugas GROUP BY status");
while ($r = $all_tasks_stmt->fetch()) {
    if ($r['status'] === 'Belum Dikerjakan') $stat_belum = $r['cnt'];
    elseif ($r['status'] === 'Sedang Dikerjakan') $stat_proses = $r['cnt'];
    elseif ($r['status'] === 'Menunggu Review') $stat_review = $r['cnt'];
    elseif ($r['status'] === 'Selesai') $stat_selesai = $r['cnt'];
}

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        
        <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
            <div>
                <h4 class="fw-bold mb-1">Tata Kelola Penugasan Task</h4>
                <p class="text-muted small mb-0">Home / Penugasan Tim</p>
            </div>
        </div>

        <?php if (!empty($message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($message) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Stat Cards Row -->
        <div class="row g-3 mb-4">
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Belum Dikerjakan</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_belum ?></div>
                        </div>
                        <div class="bg-secondary-subtle text-secondary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-clock-history fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Sedang Dikerjakan</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_proses ?></div>
                        </div>
                        <div class="bg-primary-subtle text-primary rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-gear-wide-connected fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Menunggu Review</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_review ?></div>
                        </div>
                        <div class="bg-warning-subtle text-warning rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-eye-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card p-3 border-0 shadow-sm bg-white rounded-3">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-secondary small fw-semibold">Tugas Selesai</div>
                            <div class="fs-2 fw-bold text-dark mt-1 mb-0"><?= $stat_selesai ?></div>
                        </div>
                        <div class="bg-success-subtle text-success rounded-circle d-flex align-items-center justify-content-center" style="width: 48px; height: 48px;">
                            <i class="bi bi-check-circle-fill fs-4"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Bar Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-3">
                <form method="GET" action="" class="row g-2 align-items-center">
                    <div class="col-md-9">
                        <select name="status" class="form-select" onchange="this.form.submit()">
                            <option value="">Status: Semua</option>
                            <option value="Belum Dikerjakan" <?= $filter_status == 'Belum Dikerjakan' ? 'selected' : '' ?>>Belum Dikerjakan</option>
                            <option value="Sedang Dikerjakan" <?= $filter_status == 'Sedang Dikerjakan' ? 'selected' : '' ?>>Sedang Dikerjakan</option>
                            <option value="Menunggu Review" <?= $filter_status == 'Menunggu Review' ? 'selected' : '' ?>>Menunggu Review</option>
                            <option value="Selesai" <?= $filter_status == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <a href="tugas.php" class="btn btn-outline-secondary w-100">Reset Filter</a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Task Table Card -->
        <div class="card border-0 shadow-sm">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light text-secondary small text-uppercase fw-bold">
                            <tr>
                                <th class="ps-4 py-3">NAMA TUGAS</th>
                                <th class="py-3">TIM TERKAIT</th>
                                <th class="py-3">PIC</th>
                                <th class="py-3">DEADLINE</th>
                                <th class="py-3">STATUS</th>
                                <th class="pe-4 py-3 text-end">AKSI</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($tasks)): ?>
                                <tr><td colspan="6" class="text-center py-5 text-muted">Belum ada penugasan sesuai kriteria ini.</td></tr>
                            <?php else: ?>
                                <?php foreach ($tasks as $t): 
                                    $today = date('Y-m-d');
                                    $is_overdue = $t['deadline'] < $today && $t['status'] != 'Selesai';
                                    $t_files = $task_files_map[$t['id']] ?? [];
                                ?>
                                    <tr>
                                        <td class="ps-4 py-3">
                                            <div class="fw-bold text-dark"><?= htmlspecialchars($t['judul_tugas']) ?></div>
                                            <small class="text-muted d-block mb-1"><?= htmlspecialchars(substr($t['deskripsi_tugas'] ?? '', 0, 80)) ?></small>
                                            
                                            <!-- List Attached Files -->
                                            <?php if (!empty($t_files)): ?>
                                                <div class="mt-2 d-flex flex-wrap gap-1 align-items-center">
                                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-2 py-1 small">
                                                        <i class="bi bi-paperclip me-1"></i> <?= count($t_files) ?> Berkas Lampiran
                                                    </span>
                                                    <?php foreach ($t_files as $tf): ?>
                                                        <a href="uploads/tugas/<?= htmlspecialchars($tf['file_path']) ?>" download="<?= htmlspecialchars($tf['nama_file_asli']) ?>" target="_blank" class="badge bg-light text-dark border text-decoration-none px-2 py-1 small me-1" title="Pengunggah: <?= htmlspecialchars($tf['uploader_nama']) ?> (<?= date('d M Y H:i', strtotime($tf['uploaded_at'])) ?>)">
                                                            <i class="bi bi-download me-1 text-primary"></i><?= htmlspecialchars($tf['nama_file_asli']) ?>
                                                        </a>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php else: ?>
                                                <small class="text-muted fst-italic" style="font-size: 0.8rem;"><i class="bi bi-file-earmark me-1"></i> Belum ada berkas terlampir</small>
                                            <?php endif; ?>
                                        </td>
                                        <td class="py-3">
                                            <span class="text-secondary small"><?= htmlspecialchars($t['nama_tim']) ?></span>
                                        </td>
                                        <td class="py-3">
                                            <span class="text-secondary small fw-semibold"><i class="bi bi-person-circle me-1 text-primary"></i><?= htmlspecialchars($t['pic']) ?></span>
                                        </td>
                                        <td class="py-3 text-secondary small">
                                            <?= tgl_indo($t['deadline']) ?>
                                            <?= $is_overdue ? '<span class="badge bg-danger-subtle text-danger ms-1">Terlambat</span>' : '' ?>
                                        </td>
                                        <td class="py-3">
                                            <?php if ($t['status'] == 'Selesai'): ?>
                                                <span class="badge bg-success-subtle text-success px-3 py-1 rounded-pill border border-success-subtle">Selesai</span>
                                            <?php elseif ($t['status'] == 'Sedang Dikerjakan'): ?>
                                                <span class="badge bg-warning-subtle text-warning px-3 py-1 rounded-pill border border-warning-subtle">Proses</span>
                                            <?php elseif ($t['status'] == 'Menunggu Review'): ?>
                                                <span class="badge bg-info-subtle text-info px-3 py-1 rounded-pill border border-info-subtle">Review</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary-subtle text-secondary px-3 py-1 rounded-pill border border-secondary-subtle">Draft</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-4 py-3 text-end">
                                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-medium" onclick='openUpdateModal(<?= json_encode($t) ?>)'>
                                                <i class="bi bi-pencil-square me-1"></i> Update & Berkas
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Footer Pagination Bar -->
                <div class="d-flex justify-content-between align-items-center p-3 border-top bg-light-subtle rounded-bottom">
                    <span class="text-muted small">Menampilkan 1-<?= count($tasks) ?> dari <?= count($tasks) ?> data</span>
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3 me-2" disabled>&lt; Sebelumnya</button>
                        <button class="btn btn-outline-secondary btn-sm rounded-1 px-3" disabled>Selanjutnya &gt;</button>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <!-- Modal Update Status & Upload Lampiran Multi-File -->
    <div class="modal fade" id="modalStatus" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content border-0 shadow-lg">
                <form method="POST" action="" enctype="multipart/form-data">
                    <input type="hidden" name="tugas_id" id="status_tugas_id">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-file-earmark-arrow-up me-2"></i> Update Status & Lampiran Tugas</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3 p-3 bg-light rounded-3 border">
                            <label class="form-label small text-muted text-uppercase fw-bold mb-1">Judul Penugasan</label>
                            <h6 class="fw-bold text-dark mb-0" id="modal_judul_tugas">-</h6>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Status Baru Penugasan <span class="text-danger">*</span></label>
                            <select class="form-select" name="status" id="modal_select_status" required>
                                <option value="Belum Dikerjakan">Belum Dikerjakan</option>
                                <option value="Sedang Dikerjakan">Sedang Dikerjakan</option>
                                <option value="Menunggu Review">Menunggu Review / Verifikasi</option>
                                <option value="Selesai">Selesai</option>
                            </select>
                        </div>

                        <hr class="my-4">

                        <!-- Section Upload Multiple Files -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label small fw-semibold text-dark mb-0">
                                    <i class="bi bi-paperclip text-primary me-1"></i> Unggah Lampiran Berkas Tugas (Multi-File)
                                </label>
                                <button type="button" class="btn btn-link text-danger text-decoration-none p-0 small d-none btn-clear-file" style="font-size: 0.75rem;" onclick="resetFileInput(this)">
                                    <i class="bi bi-x-circle me-1"></i>Hapus Pilihan File
                                </button>
                            </div>
                            <input type="file" class="form-control" name="lampiran_files[]" multiple onchange="toggleClearFileBtn(this)">
                            <div class="form-text text-muted small mt-1">
                                <i class="bi bi-info-circle me-1 text-primary"></i> Anda dapat memilih <strong>beberapa berkas sekaligus</strong> dengan menahan tombol <code>Ctrl</code> (atau <code>Shift</code>) saat memilih file. 
                                <br><span class="text-secondary">(Format diizinkan: PDF, DOCX, XLSX, ZIP, JPG, PNG, dll.)</span>
                            </div>
                        </div>

                        <!-- Display Currently Uploaded Files -->
                        <div class="mt-4">
                            <label class="form-label small fw-semibold text-secondary">Daftar Berkas Terunggah Saat Ini:</label>
                            <div id="modal_uploaded_files" class="list-group list-group-flush border rounded-3 overflow-hidden">
                                <!-- Populated dynamically by JS -->
                            </div>
                        </div>

                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="update_status" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm"><i class="bi bi-save me-1"></i> Simpan Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
    window.taskFilesMap = <?= json_encode($task_files_map ?? []) ?>;
    window.currentUserId = <?= json_encode($user_id ?? 0) ?>;
    window.currentUserRole = <?= json_encode($role ?? '') ?>;

    function openUpdateModal(task) {
        document.getElementById('status_tugas_id').value = task.id;
        document.getElementById('modal_judul_tugas').innerText = task.judul_tugas;
        document.getElementById('modal_select_status').value = task.status;
        
        var filesContainer = document.getElementById('modal_uploaded_files');
        filesContainer.innerHTML = '';
        
        var files = (window.taskFilesMap && window.taskFilesMap[task.id]) ? window.taskFilesMap[task.id] : [];
        if (files.length === 0) {
            filesContainer.innerHTML = '<div class="p-3 text-muted text-center small fst-italic">Belum ada berkas lampiran yang diunggah untuk tugas ini.</div>';
        } else {
            files.forEach(function(f) {
                var item = document.createElement('div');
                item.className = 'list-group-item d-flex justify-content-between align-items-center py-2 px-3';
                var canDelete = (window.currentUserRole === 'admin' || window.currentUserRole === 'ketua' || f.uploader_id == window.currentUserId);
                var delBtn = canDelete ? '<a href="tugas.php?hapus_file=' + f.id + '" class="btn btn-sm btn-outline-danger border-0 p-1" onclick="return confirm(\'Hapus berkas ini?\')" title="Hapus Berkas"><i class="bi bi-trash"></i></a>' : '';
                item.innerHTML = '<div><a href="uploads/tugas/' + f.file_path + '" target="_blank" download="' + f.nama_file_asli + '" class="fw-semibold text-decoration-none text-primary small"><i class="bi bi-paperclip me-1"></i>' + f.nama_file_asli + '</a><div class="text-muted" style="font-size:0.75rem;">Diunggah oleh: ' + f.uploader_nama + '</div></div><div>' + delBtn + '</div>';
                filesContainer.appendChild(item);
            });
        }
        
        var modal = new bootstrap.Modal(document.getElementById('modalStatus'));
        modal.show();
    }

    function resetFileInput(buttonEl) {
        var container = buttonEl.closest('.mb-3');
        if (!container) return;
        var fileInput = container.querySelector('input[type="file"]');
        if (fileInput) {
            fileInput.value = '';
        }
        buttonEl.classList.add('d-none');
    }

    function toggleClearFileBtn(inputEl) {
        var container = inputEl.closest('.mb-3');
        if (!container) return;
        var btn = container.querySelector('.btn-clear-file');
        if (btn) {
            if (inputEl.files && inputEl.files.length > 0) {
                btn.classList.remove('d-none');
            } else {
                btn.classList.add('d-none');
            }
        }
    }

    // Auto-reset modal status saat ditutup (X atau Batal)
    var modalStatusEl = document.getElementById('modalStatus');
    if (modalStatusEl) {
        modalStatusEl.addEventListener('hidden.bs.modal', function () {
            var form = this.querySelector('form');
            if (form) {
                form.reset();
                var clearBtn = this.querySelector('.btn-clear-file');
                if (clearBtn) clearBtn.classList.add('d-none');
            }
        });
    }
</script>
<?php include '../includes/footer.php'; ?>
