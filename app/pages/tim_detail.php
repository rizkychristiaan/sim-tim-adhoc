<?php
/**
 * ==============================================================================
 * FILE: app/pages/tim_detail.php (Workspace Terpadu Tim Ad Hoc LPAIP UKDW)
 * ARSITEKTUR: Sesuai Arahan Pembimbing (Dr. Antonius Rachmat C.) - Model e-Class
 * 
 * 4 TAB UTAMA:
 *   1. Tab 1: Surat Permohonan & SK Resmi (Draf Permohonan .doc & Upload SK Rektorat)
 *   2. Tab 2: Struktur Anggota Personil (Plotting, NIP/NIM, Jabatan Tim)
 *   3. Tab 3: Penugasan Kerja (Task Management, Bukti Pengerjaan, Progress % Otomatis)
 *   4. Tab 4: Berkas & Laporan Pertanggungjawaban (LPJ Akhir & Dokumen Pendukung)
 * ==============================================================================
 */

require_once '../config/database.php';
check_login();

$tim_id = intval($_GET['id'] ?? 0);
if ($tim_id <= 0) {
    header("Location: dashboard.php");
    exit();
}

$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];
$active_tab = $_GET['tab'] ?? 'sk'; // Default tab (Tab 1: Surat SK & Permohonan)
$message = '';
$error = '';

// 1. Ambil data Tim Ad Hoc
$stmt_tim = $pdo->prepare("SELECT t.*, u.nama as pembuat FROM tim_adhoc t JOIN users u ON t.created_by = u.id WHERE t.id = ?");
$stmt_tim->execute([$tim_id]);
$tim = $stmt_tim->fetch();

if (!$tim) {
    die("Tim Ad Hoc tidak ditemukan!");
}

// Simpan tim aktif saat ini ke session agar navigasi sidebar 'Ruang Kerja Tim Ini' tetap ada saat membuka halaman lain
$_SESSION['active_tim_id'] = $tim['id'];
$_SESSION['active_tim_nama'] = $tim['nama_tim'];

$page_title = "Ruang Kerja Tim: " . $tim['nama_tim'];

// 2. Cek keanggotaan dan hak akses
$is_admin = ($role === 'admin');
$stmt_my_role = $pdo->prepare("SELECT peran_tim FROM anggota_tim WHERE tim_id = ? AND user_id = ?");
$stmt_my_role->execute([$tim_id, $user_id]);
$my_team_record = $stmt_my_role->fetch();

$is_member = $is_admin || ($my_team_record !== false);
$is_ketua_tim = $is_admin || ($my_team_record && $my_team_record['peran_tim'] === 'Ketua');
$can_manage_team = $is_admin || $is_ketua_tim;

if (!$is_member) {
    $_SESSION['flash_error'] = "Anda tidak terdaftar sebagai anggota atau pengelola pada tim ini!";
    header("Location: dashboard.php");
    exit();
}

// --------------------------------------------------------------------------
// HANDLER 1: TAMBAH ANGGOTA
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_anggota'])) {
    $active_tab = 'members';
    if (!$can_manage_team) {
        $error = "Hanya Ketua Tim atau Admin LPAIP yang berwenang menetapkan atau menambah personil tim!";
    } else {
        $mode_user = $_POST['mode_user'] ?? 'existing';
        $peran_tim = $_POST['peran_tim'] ?? 'Anggota';

        if ($mode_user === 'new') {
            $new_nip_nim = trim($_POST['new_nip_nim'] ?? '');
            $new_nama = trim($_POST['new_nama'] ?? '');
            $new_email = trim($_POST['new_email'] ?? '');
            $new_role = $_POST['new_role'] ?? 'anggota';
            if ($new_role === 'admin' && !$is_admin) {
                $new_role = 'anggota';
            }
            $new_jabatan = trim($_POST['new_jabatan_struktural'] ?? 'Staf/Dosen UKDW');

            if (empty($new_nip_nim) || empty($new_nama) || empty($new_email)) {
                $error = "NIP/NIK, Nama, dan Email wajib diisi!";
            } else {
                $stmt_chk_u = $pdo->prepare("SELECT id FROM users WHERE email = ? OR nip_nim = ?");
                $stmt_chk_u->execute([$new_email, $new_nip_nim]);
                $existing_u = $stmt_chk_u->fetch();

                if ($existing_u) {
                    $add_user_id = $existing_u['id'];
                } else {
                    $pass_hash = password_hash('user123', PASSWORD_BCRYPT);
                    $stmt_ins_u = $pdo->prepare("INSERT INTO users (nip_nim, nama, email, password, role, jabatan_struktural) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt_ins_u->execute([$new_nip_nim, $new_nama, $new_email, $pass_hash, $new_role, $new_jabatan]);
                    $add_user_id = $pdo->lastInsertId();
                }

                $stmt_chk = $pdo->prepare("SELECT id FROM anggota_tim WHERE tim_id = ? AND user_id = ?");
                $stmt_chk->execute([$tim_id, $add_user_id]);
                if ($stmt_chk->fetch()) {
                    $error = "Personil ini sudah terdaftar sebagai anggota tim!";
                } else {
                    $stmt_add = $pdo->prepare("INSERT INTO anggota_tim (tim_id, user_id, peran_tim) VALUES (?, ?, ?)");
                    $stmt_add->execute([$tim_id, $add_user_id, $peran_tim]);
                    create_notifikasi($pdo, $add_user_id, "Penetapan Anggota Tim", "Anda telah ditambahkan ke dalam " . $tim['nama_tim'] . " sebagai " . $peran_tim . ".");
                    $message = "User baru berhasil dibuat dan ditambahkan ke dalam tim!";
                }
            }
        } else {
            $add_user_id = intval($_POST['user_id'] ?? 0);
            if ($add_user_id <= 0) {
                $error = "Pilih user personil terlebih dahulu!";
            } else {
                $stmt_chk = $pdo->prepare("SELECT id FROM anggota_tim WHERE tim_id = ? AND user_id = ?");
                $stmt_chk->execute([$tim_id, $add_user_id]);
                if ($stmt_chk->fetch()) {
                    $error = "Personil ini sudah terdaftar sebagai anggota tim!";
                } else {
                    $stmt_add = $pdo->prepare("INSERT INTO anggota_tim (tim_id, user_id, peran_tim) VALUES (?, ?, ?)");
                    $stmt_add->execute([$tim_id, $add_user_id, $peran_tim]);
                    create_notifikasi($pdo, $add_user_id, "Penetapan Anggota Tim", "Anda telah ditambahkan ke dalam " . $tim['nama_tim'] . " sebagai " . $peran_tim . ".");
                    $message = "Personil anggota berhasil ditambahkan ke tim!";
                }
            }
        }
    }
}

// --------------------------------------------------------------------------
// HANDLER 2: HAPUS ANGGOTA
// --------------------------------------------------------------------------
if (isset($_GET['hapus_anggota'])) {
    $active_tab = 'members';
    if (!$can_manage_team) {
        $error = "Hanya Ketua Tim atau Admin LPAIP yang berwenang menghapus atau mengubah susunan personil tim!";
    } else {
        $del_anggota_id = intval($_GET['hapus_anggota']);
        $stmt_del = $pdo->prepare("DELETE FROM anggota_tim WHERE id = ? AND tim_id = ?");
        $stmt_del->execute([$del_anggota_id, $tim_id]);
        $message = "Anggota berhasil dikeluarkan dari tim!";
    }
}

// --------------------------------------------------------------------------
// HANDLER 3: BUAT PENUGASAN BARU (BISA BANYAK TUGAS SEKALIGUS)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tambah_tugas'])) {
    $active_tab = 'tasks';
    $juduls = is_array($_POST['judul_tugas'] ?? null) ? $_POST['judul_tugas'] : [$_POST['judul_tugas'] ?? ''];
    $assignees = is_array($_POST['assigned_to'] ?? null) ? $_POST['assigned_to'] : [$_POST['assigned_to'] ?? 0];
    $desks = is_array($_POST['deskripsi_tugas'] ?? null) ? $_POST['deskripsi_tugas'] : [$_POST['deskripsi_tugas'] ?? ''];
    $priors = is_array($_POST['prioritas'] ?? null) ? $_POST['prioritas'] : [$_POST['prioritas'] ?? 'Sedang'];
    $deadlines = is_array($_POST['deadline'] ?? null) ? $_POST['deadline'] : [$_POST['deadline'] ?? ''];

    $inserted_count = 0;
    require_once '../config/email.php';

    for ($i = 0; $i < count($juduls); $i++) {
        $j_judul = trim($juduls[$i] ?? '');
        $j_assigned = intval($assignees[$i] ?? 0);
        $j_desk = trim($desks[$i] ?? '');
        $j_prior = $priors[$i] ?? 'Sedang';
        $j_dl = trim($deadlines[$i] ?? '');

        if (!empty($j_judul) && $j_assigned > 0 && !empty($j_dl)) {
            $stmt_tg = $pdo->prepare("INSERT INTO tugas (tim_id, assigned_to, judul_tugas, deskripsi_tugas, prioritas, status, progres_persen, deadline) VALUES (?, ?, ?, ?, ?, 'Belum Dikerjakan', 0, ?)");
            $stmt_tg->execute([$tim_id, $j_assigned, $j_judul, $j_desk, $j_prior, $j_dl]);

            $today_now = date('Y-m-d');
            if ($j_dl < $today_now) {
                create_notifikasi($pdo, $j_assigned, "Peringatan: Tugas Melewati Deadline!", "Tugas '$j_judul' pada " . $tim['nama_tim'] . " telah melewati tenggat waktu (" . tgl_indo($j_dl) . "). Harap segera selesaikan!");
            } else {
                create_notifikasi($pdo, $j_assigned, "Penugasan Baru", "Anda mendapatkan penugasan baru: '$j_judul' pada " . $tim['nama_tim'] . ". Deadline: " . tgl_indo($j_dl));
            }
            try {
                emailTugasBaru($pdo, $j_assigned, $j_judul, $j_desk, $tim['nama_tim'], $j_prior, $j_dl);
            } catch (Exception $e) {
                // Email failure non-blocking
            }
            $inserted_count++;
        }
    }

    if ($inserted_count > 0) {
        $message = "Berhasil membuat $inserted_count penugasan baru untuk personil tim!";
    } else {
        $error = "Mohon lengkapi judul tugas, personil penerima, dan deadline untuk setiap penugasan!";
    }
}

// --------------------------------------------------------------------------
// HANDLER 4: KERJAKAN / KIRIM BUKTI HASIL TUGAS (PIC/ANGGOTA) - MULTI-FILE, LINK & PROGRES %
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kirim_bukti_tugas'])) {
    $active_tab = 'tasks';
    $tugas_id = intval($_POST['tugas_id'] ?? 0);
    $catatan_tugas = trim($_POST['catatan_tugas'] ?? '');
    $progres_persen = isset($_POST['progres_persen']) ? max(0, min(100, intval($_POST['progres_persen']))) : 100;
    $link_bukti = trim($_POST['link_bukti'] ?? '');
    $nama_link = trim($_POST['nama_link'] ?? '');

    $stmt_chk_tg = $pdo->prepare("SELECT * FROM tugas WHERE id = ? AND tim_id = ?");
    $stmt_chk_tg->execute([$tugas_id, $tim_id]);
    $curr_tg = $stmt_chk_tg->fetch();

    if (!$curr_tg) {
        $error = "Data tugas tidak ditemukan!";
    } elseif ($curr_tg['assigned_to'] != $user_id && !$can_manage_team) {
        $error = "Hanya PIC penanggung jawab atau Ketua Tim yang dapat mengirimkan hasil tugas!";
    } else {
        $files_uploaded = 0;
        $dest_dir = '../uploads/tugas/';
        if (!is_dir($dest_dir)) {
            mkdir($dest_dir, 0777, true);
        }

        // 1. Multiple Files Upload (Format apa saja diizinkan)
        if (!empty($_FILES['bukti_files']['name']) && is_array($_FILES['bukti_files']['name'])) {
            foreach ($_FILES['bukti_files']['name'] as $idx => $fname) {
                if (!empty($fname) && $_FILES['bukti_files']['error'][$idx] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['bukti_files']['tmp_name'][$idx];
                    $file_size = $_FILES['bukti_files']['size'][$idx];
                    $file_asli = $fname;
                    $ext = strtolower(pathinfo($file_asli, PATHINFO_EXTENSION));
                    $safe_ext = !empty($ext) ? '.' . $ext : '';
                    $new_name = 'TUGAS_' . $tugas_id . '_' . time() . '_' . $idx . $safe_ext;
                    $dest = $dest_dir . $new_name;

                    if (move_uploaded_file($tmp_name, $dest)) {
                        $stmt_tf = $pdo->prepare("INSERT INTO tugas_files (tugas_id, uploader_id, nama_file_asli, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                        $stmt_tf->execute([$tugas_id, $user_id, $file_asli, $new_name, $file_size]);
                        $files_uploaded++;
                    }
                }
            }
        } elseif (!empty($_FILES['bukti_file']['name'])) {
            $file = $_FILES['bukti_file'];
            $file_asli = $file['name'];
            $file_size = $file['size'];
            $ext = strtolower(pathinfo($file_asli, PATHINFO_EXTENSION));
            $safe_ext = !empty($ext) ? '.' . $ext : '';
            $new_name = 'TUGAS_' . $tugas_id . '_' . time() . $safe_ext;
            if (move_uploaded_file($file['tmp_name'], $dest_dir . $new_name)) {
                $stmt_tf = $pdo->prepare("INSERT INTO tugas_files (tugas_id, uploader_id, nama_file_asli, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
                $stmt_tf->execute([$tugas_id, $user_id, $file_asli, $new_name, $file_size]);
                $files_uploaded++;
            }
        }

        // 2. Link Eksternal (Google Drive / Docs / Figma / Canva / Sheet dll)
        if (!empty($link_bukti)) {
            if (!preg_match("~^(?:f|ht)tps?://~i", $link_bukti)) {
                $link_bukti = "https://" . $link_bukti;
            }
            $label_link = !empty($nama_link) ? $nama_link : 'Tautan Hasil Kerja Online';
            $stmt_tf = $pdo->prepare("INSERT INTO tugas_files (tugas_id, uploader_id, nama_file_asli, file_path, file_size, uploaded_at) VALUES (?, ?, ?, ?, 0, NOW())");
            $stmt_tf->execute([$tugas_id, $user_id, $label_link, $link_bukti]);
            $files_uploaded++;
        }

        // 3. Tentukan Status Berdasarkan Persentase Progres (100% = Selesai)
        $new_status = 'Belum Dikerjakan';
        if ($progres_persen >= 100) {
            $new_status = 'Selesai';
            $progres_persen = 100;
        } elseif ($progres_persen > 0) {
            $new_status = 'Sedang Dikerjakan';
        }

        $deskripsi_update = $curr_tg['deskripsi_tugas'];
        if (!empty($catatan_tugas)) {
            $deskripsi_update .= "

[" . date('d/m/Y H:i') . " - Progres " . $progres_persen . "%: " . $catatan_tugas . "]";
        }

        $stmt_upd = $pdo->prepare("UPDATE tugas SET status = ?, progres_persen = ?, deskripsi_tugas = ? WHERE id = ?");
        $stmt_upd->execute([$new_status, $progres_persen, $deskripsi_update, $tugas_id]);

        if ($new_status === 'Selesai') {
            create_notifikasi($pdo, $tim['created_by'], "Tugas Selesai (100%)", "Tugas '" . $curr_tg['judul_tugas'] . "' pada " . $tim['nama_tim'] . " telah diselesaikan 100% oleh " . $_SESSION['nama'] . ".");
            $message = "Hasil pengerjaan berhasil dikirim! Status tugas kini berubah menjadi Selesai (100%).";
        } else {
            create_notifikasi($pdo, $tim['created_by'], "⏳ Pembaruan Progres Tugas", "Tugas '" . $curr_tg['judul_tugas'] . "' pada " . $tim['nama_tim'] . " diperbarui menjadi " . $progres_persen . "% oleh " . $_SESSION['nama'] . ".");
            $message = "Progres pengerjaan berhasil diperbarui menjadi " . $progres_persen . "% (Status: Sedang Dikerjakan).";
        }
    }
}

// --------------------------------------------------------------------------
// HANDLER 4b: HAPUS BUKTI FILE TUGAS
// --------------------------------------------------------------------------
if (isset($_GET['hapus_bukti_file']) && isset($_GET['tugas_id'])) {
    $active_tab = isset($_GET['tab']) && in_array($_GET['tab'], ['info', 'members', 'tasks', 'docs']) ? $_GET['tab'] : 'tasks';
    $file_id    = intval($_GET['hapus_bukti_file']);
    $tugas_id_f = intval($_GET['tugas_id']);

    $stmt_chk_f = $pdo->prepare("SELECT tf.*, tg.assigned_to, tg.tim_id FROM tugas_files tf JOIN tugas tg ON tf.tugas_id = tg.id WHERE tf.id = ? AND tg.tim_id = ?");
    $stmt_chk_f->execute([$file_id, $tim_id]);
    $del_file = $stmt_chk_f->fetch();

    if (!$del_file) {
        $error = "File bukti tidak ditemukan!";
    } elseif ($del_file['uploader_id'] != $user_id && !$can_manage_team) {
        $error = "Anda tidak berhak menghapus bukti file ini!";
    } else {
        $is_url = preg_match("~^(?:f|ht)tps?://~i", $del_file['file_path']);
        if (!$is_url) {
            $file_path_del = '../uploads/tugas/' . $del_file['file_path'];
            if (file_exists($file_path_del)) {
                @unlink($file_path_del);
            }
        }
        $pdo->prepare("DELETE FROM tugas_files WHERE id = ?")->execute([$file_id]);
        $message = "Bukti file berhasil dihapus.";
    }
}

// --------------------------------------------------------------------------
// HANDLER 5: UPLOAD BERKAS SK RESMI REKTORAT (PDF)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_sk_resmi'])) {
    $active_tab = 'sk';
    if (!$can_manage_team) {
        $error = "Hanya Ketua Tim atau Admin LPAIP yang berhak mengunggah berkas SK resmi!";
    } elseif (empty($_FILES['file_sk']['name'])) {
        $error = "Pilih berkas SK resmi terlebih dahulu!";
    } else {
        $file = $_FILES['file_sk'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowed = ['pdf', 'doc', 'docx'];
        if (!in_array($ext, $allowed)) {
            $error = "Format berkas harus berupa dokumen PDF atau Word (.doc/.docx)!";
        } else {
            $new_name = 'SK_RESMI_' . $tim_id . '_' . time() . '.' . $ext;
            $dest = '../uploads/sk/' . $new_name;

            if (move_uploaded_file($file['tmp_name'], $dest)) {
                $stmt_upd_sk = $pdo->prepare("UPDATE tim_adhoc SET file_sk = ? WHERE id = ?");
                $stmt_upd_sk->execute([$new_name, $tim_id]);

                // Simpan juga ke repositori dokumen
                $stmt_doc = $pdo->prepare("INSERT INTO dokumen (tim_id, uploader_id, nama_dokumen, jenis_dokumen, file_path, uploaded_at) VALUES (?, ?, ?, 'SK Penugasan', ?, NOW())");
                $stmt_doc->execute([$tim_id, $user_id, "SK Resmi Rektorat - " . $tim['nama_tim'], $new_name]);

                $message = "Berkas SK Resmi bertandatangan berhasil diunggah!";
                $stmt_tim->execute([$tim_id]);
                $tim = $stmt_tim->fetch();
            } else {
                $error = "Gagal memproses unggahan berkas SK!";
            }
        }
    }
}

// --------------------------------------------------------------------------
// HANDLER 6: UPLOAD DOKUMEN / LPJ AKHIR
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_dokumen'])) {
    $active_tab = 'docs';
    $nama_dok = trim($_POST['nama_dokumen'] ?? '');
    $jenis_dok = $_POST['jenis_dokumen'] ?? 'Lainnya';

    if (empty($nama_dok) || empty($_FILES['file_dok']['name'])) {
        $error = "Nama berkas dan file dokumen wajib diisi!";
    } else {
        $file = $_FILES['file_dok'];
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $new_name = 'DOC_' . $tim_id . '_' . time() . '.' . $ext;
        $dest = '../uploads/dokumen/' . $new_name;

        if (move_uploaded_file($file['tmp_name'], $dest)) {
            $stmt_in_doc = $pdo->prepare("INSERT INTO dokumen (tim_id, uploader_id, nama_dokumen, jenis_dokumen, file_path, uploaded_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt_in_doc->execute([$tim_id, $user_id, $nama_dok, $jenis_dok, $new_name]);

            if ($jenis_dok === 'LPJ') {
                if (!empty($_POST['set_tim_selesai'])) {
                    $pdo->prepare("UPDATE tim_adhoc SET status = 'Selesai' WHERE id = ?")->execute([$tim_id]);
                    $stmt_tim->execute([$tim_id]);
                    $tim = $stmt_tim->fetch();
                    $message = "Laporan Pertanggungjawaban (LPJ) Akhir Tim berhasil diunggah dan status kegiatan tim telah ditandai Selesai!";
                } else {
                    $message = "Laporan Pertanggungjawaban (LPJ) Akhir Tim berhasil diunggah!";
                }
            } else {
                $message = "Dokumen pendukung ($jenis_dok) berhasil diunggah!";
            }
        } else {
            $error = "Gagal mengunggah berkas!";
        }
    }
}

// --------------------------------------------------------------------------
// HANDLER 7: HAPUS DOKUMEN
// --------------------------------------------------------------------------
if (isset($_GET['hapus_dokumen'])) {
    $active_tab = 'docs';
    $del_doc_id = intval($_GET['hapus_dokumen']);
    $stmt_doc_chk = $pdo->prepare("SELECT * FROM dokumen WHERE id = ? AND tim_id = ?");
    $stmt_doc_chk->execute([$del_doc_id, $tim_id]);
    $doc_to_del = $stmt_doc_chk->fetch();

    if ($doc_to_del && ($can_manage_team || $doc_to_del['uploader_id'] == $user_id)) {
        $file_on_disk = '../uploads/dokumen/' . $doc_to_del['file_path'];
        if (file_exists($file_on_disk)) {
            @unlink($file_on_disk);
        }
        $pdo->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$del_doc_id]);
        $message = "Berkas dokumen berhasil dihapus!";
    }
}

// --------------------------------------------------------------------------
// HANDLER 8: UBAH STATUS KEGIATAN TIM (Aktif / Selesai)
// --------------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status_tim'])) {
    $active_tab = $_POST['redirect_tab'] ?? 'docs';
    if (!$can_manage_team) {
        $error = "Hanya Ketua Tim atau Admin LPAIP yang berhak mengubah status kegiatan tim!";
    } else {
        $new_status = $_POST['status_tim'] ?? 'Aktif';
        if (in_array($new_status, ['Perencanaan', 'Aktif', 'Selesai', 'Diperpanjang'])) {
            $pdo->prepare("UPDATE tim_adhoc SET status = ? WHERE id = ?")->execute([$new_status, $tim_id]);
            $stmt_tim->execute([$tim_id]);
            $tim = $stmt_tim->fetch();
            $message = "Status kegiatan tim berhasil diubah menjadi: " . $new_status . "!";
        }
    }
}

// --------------------------------------------------------------------------
// QUERY DATA PENUNJANG
// --------------------------------------------------------------------------
// Anggota
$stmt_members = $pdo->prepare("SELECT a.*, u.nama, u.nip_nim, u.email, u.jabatan_struktural FROM anggota_tim a JOIN users u ON a.user_id = u.id WHERE a.tim_id = ? ORDER BY FIELD(a.peran_tim, 'Ketua', 'Anggota'), u.nama ASC");
$stmt_members->execute([$tim_id]);
$members = $stmt_members->fetchAll();

// Users dropdown
$users_dropdown = $pdo->query("SELECT id, nama, nip_nim, role FROM users ORDER BY nama ASC")->fetchAll();

// Tugas
$stmt_tasks = $pdo->prepare("SELECT tg.*, u.nama as pic FROM tugas tg JOIN users u ON tg.assigned_to = u.id WHERE tg.tim_id = ? ORDER BY tg.deadline ASC");
$stmt_tasks->execute([$tim_id]);
$tasks = $stmt_tasks->fetchAll();

// File Bukti Tugas
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

// Hitung Progres Tim (%) Berdasarkan Rata-rata Persentase Setiap Tugas
$total_tugas = count($tasks);
$tugas_selesai = count(array_filter($tasks, fn($t) => $t['status'] === 'Selesai' || (isset($t['progres_persen']) && intval($t['progres_persen']) >= 100)));
$total_persen_tugas = array_reduce($tasks, function($carry, $t) {
    $persen = isset($t['progres_persen']) ? intval($t['progres_persen']) : ($t['status'] === 'Selesai' ? 100 : 0);
    return $carry + $persen;
}, 0);
$progress_tim = $total_tugas > 0 ? round($total_persen_tugas / $total_tugas) : 0;

// Dokumen & LPJ
$stmt_docs = $pdo->prepare("SELECT d.*, u.nama as uploader FROM dokumen d JOIN users u ON d.uploader_id = u.id WHERE d.tim_id = ? ORDER BY d.uploaded_at DESC");
$stmt_docs->execute([$tim_id]);
$all_docs = $stmt_docs->fetchAll();

$docs_lpj = array_filter($all_docs, fn($d) => $d['jenis_dokumen'] === 'LPJ');
$docs_pendukung_raw = array_filter($all_docs, fn($d) => $d['jenis_dokumen'] !== 'LPJ');

$docs_pendukung = [];
foreach ($docs_pendukung_raw as $d) {
    $d['sumber'] = 'dokumen';
    $d['judul_tugas'] = '';
    $d['tugas_id'] = 0;
    $docs_pendukung[] = $d;
}

// Berkas & tautan bukti pengerjaan tugas dari Tab 3 otomatis masuk ke repositori dokumen pendukung tim
$stmt_tf_all = $pdo->prepare("
    SELECT tf.id, tf.nama_file_asli as nama_dokumen, tf.file_path, tf.uploaded_at, 
           u.nama as uploader, tg.judul_tugas, 'Hasil Penugasan' as jenis_dokumen, 'tugas' as sumber,
           tf.uploader_id, tf.tugas_id
    FROM tugas_files tf 
    JOIN tugas tg ON tf.tugas_id = tg.id 
    JOIN users u ON tf.uploader_id = u.id 
    WHERE tg.tim_id = ? 
    ORDER BY tf.uploaded_at DESC
");
$stmt_tf_all->execute([$tim_id]);
$task_deliverables = $stmt_tf_all->fetchAll();

foreach ($task_deliverables as $td) {
    $docs_pendukung[] = $td;
}

// Urutkan semua berkas pendukung dan hasil tugas berdasarkan tanggal upload terbaru
usort($docs_pendukung, function($a, $b) {
    return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
});

$total_semua_dokumen = count($docs_lpj) + count($docs_pendukung);

include '../includes/header.php';
include '../includes/sidebar.php';
?>

<div class="main-content">
    <?php include '../includes/navbar.php'; ?>

    <div class="p-4 container-fluid">
        
        <!-- Breadcrumb & Header Ringkas -->
        <div class="mb-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                        <h3 class="fw-bold text-dark mb-0 fs-2"><?= htmlspecialchars($tim['nama_tim']) ?></h3>
                        <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-3 py-1.5 rounded-pill fs-7 fw-semibold">
                            <i class="bi bi-file-earmark-text me-1"></i> SK: <?= htmlspecialchars($tim['nomor_sk']) ?>
                        </span>
                        <span class="badge bg-<?= $tim['status'] == 'Aktif' ? 'success' : ($tim['status'] == 'Selesai' ? 'primary' : 'warning') ?>-subtle text-<?= $tim['status'] == 'Aktif' ? 'success' : ($tim['status'] == 'Selesai' ? 'primary' : 'warning') ?> px-3 py-1.5 rounded-pill fs-7 fw-semibold">
                            <i class="bi <?= $tim['status'] == 'Selesai' ? 'bi-check2-circle' : 'bi-circle-fill' ?> me-1" style="<?= $tim['status'] == 'Selesai' ? '' : 'font-size: 0.5rem;' ?>"></i> <?= $tim['status'] ?>
                        </span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="tim_detail.php?id=<?= $tim['id'] ?>" class="btn btn-light rounded-pill px-3.5 shadow-sm border fw-semibold text-secondary" title="Segarkan Halaman">
                        <i class="bi bi-arrow-clockwise me-1"></i> Refresh
                    </a>
                </div>
            </div>
        </div>

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

        <!-- Kartu Deskripsi & Ruang Lingkup -->
        <div class="card mb-4 border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="row g-4 align-items-center">
                    <div class="col-md-7">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-text-paragraph text-primary fs-5"></i>
                            <h6 class="fw-bold text-dark text-uppercase fs-7 mb-0" style="letter-spacing: 0.5px;">Deskripsi & Ruang Lingkup Kerja:</h6>
                        </div>
                        <p class="text-secondary mb-0 ps-4 lh-base" style="font-size: 0.95rem;"><?= nl2br(htmlspecialchars($tim['deskripsi'] ?? 'Belum ada rincian ruang lingkup kerja.')) ?></p>
                    </div>
                    <div class="col-md-5 border-start ps-md-4">
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <i class="bi bi-calendar-range text-primary fs-5"></i>
                            <h6 class="fw-bold text-dark text-uppercase fs-7 mb-0" style="letter-spacing: 0.5px;">Masa Berlaku Tim:</h6>
                        </div>
                        <div class="ps-4">
                            <div class="d-flex align-items-center justify-content-between mb-1 py-1 border-bottom border-light">
                                <span class="small text-secondary"><i class="bi bi-calendar-check text-success me-1.5"></i> Tanggal Mulai:</span>
                                <span class="fw-bold text-dark small"><?= tgl_indo($tim['tanggal_mulai']) ?></span>
                            </div>
                            <div class="d-flex align-items-center justify-content-between mb-2 py-1 border-bottom border-light">
                                <span class="small text-secondary"><i class="bi bi-calendar-x text-danger me-1.5"></i> Tanggal Selesai:</span>
                                <span class="fw-bold text-dark small"><?= tgl_indo($tim['tanggal_selesai']) ?></span>
                            </div>
                            <div class="small text-muted mt-2">
                                <i class="bi bi-person-circle me-1 text-secondary"></i> Penanggung Jawab / Ketua: <strong class="text-dark"><?= htmlspecialchars($tim['pembuat']) ?></strong>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 4 TAB NAVIGASI RUANG KERJA TIM -->
        <ul class="nav nav-tabs nav-fill mb-4 border-bottom-0 rounded-3 shadow-sm p-2" id="timTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'sk' ? 'active' : '' ?> fw-semibold rounded-pill py-2.5" id="sk-tab" data-bs-toggle="tab" data-bs-target="#sk" type="button" role="tab">
                    <i class="bi bi-file-earmark-check me-2"></i> 1. Surat SK & Permohonan
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'members' ? 'active' : '' ?> fw-semibold rounded-pill py-2.5" id="members-tab" data-bs-toggle="tab" data-bs-target="#members" type="button" role="tab">
                    <i class="bi bi-people me-2"></i> 2. Personil Anggota (<?= count($members) ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'tasks' ? 'active' : '' ?> fw-semibold rounded-pill py-2.5" id="tasks-tab" data-bs-toggle="tab" data-bs-target="#tasks" type="button" role="tab">
                    <i class="bi bi-check2-square me-2"></i> 3. Penugasan Kerja (<?= $total_tugas ?>)
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link <?= $active_tab === 'docs' ? 'active' : '' ?> fw-semibold rounded-pill py-2.5" id="docs-tab" data-bs-toggle="tab" data-bs-target="#docs" type="button" role="tab">
                    <i class="bi bi-folder2-open me-2"></i> 4. Repositori & LPJ (<?= $total_semua_dokumen ?>)
                </button>
            </li>
        </ul>

        <div class="tab-content" id="timTabContent">
            
            <!-- ================================================================= -->
            <!-- TAB 1: SURAT PERMOHONAN & SK RESMI REKTORAT                      -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $active_tab === 'sk' ? 'show active' : '' ?>" id="sk" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 border-bottom-0">
                        <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-award text-primary me-2"></i>Dokumen Pengesahan & Surat Keputusan (SK) Tim Ad Hoc</h6>
                    </div>
                    <div class="card-body p-4 pt-0">
                        
                        <!-- Penjelasan Alur Dokumen -->
                        <div class="alert alert-info py-2.5 px-3 small border-0 bg-info-subtle text-info-emphasis rounded-3 mb-4">
                            <i class="bi bi-info-circle-fill me-1"></i> <strong>Alur Legalitas:</strong> Draf Surat Permohonan digenerate dari sistem ini untuk diajukan ke Rektorat UKDW. Setelah Surat Keputusan (SK) resmi bertandatangan Rektor diterbitkan, berkas pindaian (PDF) diunggah ke dalam sistem sebagai dasar legalitas tim.
                        </div>

                        <div class="row g-4">
                            <!-- BAGIAN A: DRAF SURAT PERMOHONAN PENGAJUAN SK -->
                            <div class="col-md-6">
                                <div class="border rounded-3 p-4 h-100 bg-light">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-3 p-3 bg-white shadow-sm text-primary fs-3">
                                            <i class="bi bi-file-earmark-word-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Draf Surat Permohonan SK</h6>
                                            <small class="text-muted">Format Microsoft Word (.doc) resmi UKDW</small>
                                        </div>
                                    </div>
                                    <p class="text-secondary small mb-3">
                                        Memuat kop resmi UKDW, konsiderans penugasan, daftar lengkap personil anggota beserta NIP, dan lampiran butir tugas kerja tim.
                                    </p>
                                    <a href="download_sk.php?id=<?= $tim_id ?>" class="btn btn-primary rounded-pill px-4 fw-semibold shadow-sm w-100">
                                        <i class="bi bi-download me-1"></i> Unduh Draf Permohonan SK (.doc)
                                    </a>
                                </div>
                            </div>

                            <!-- BAGIAN B: BERKAS SK RESMI REKTORAT (PDF) -->
                            <div class="col-md-6">
                                <div class="border rounded-3 p-4 h-100 bg-light">
                                    <div class="d-flex align-items-center gap-3 mb-3">
                                        <div class="rounded-3 p-3 bg-white shadow-sm text-success fs-3">
                                            <i class="bi bi-file-earmark-pdf-fill"></i>
                                        </div>
                                        <div>
                                            <h6 class="fw-bold text-dark mb-1">Berkas SK Resmi Bertandatangan</h6>
                                            <small class="text-muted">Surat Keputusan Rektorat UKDW</small>
                                        </div>
                                    </div>

                                    <?php if (!empty($tim['file_sk'])): ?>
                                        <div class="alert alert-success py-2 px-3 small border-0 bg-success-subtle text-success-emphasis mb-3">
                                            <i class="bi bi-check-circle-fill me-1"></i> Berkas SK Resmi telah diunggah dan tersimpan di sistem.
                                        </div>
                                        <a href="../uploads/sk/<?= htmlspecialchars($tim['file_sk']) ?>" target="_blank" download class="btn btn-success rounded-pill px-4 fw-semibold shadow-sm w-100 mb-3">
                                            <i class="bi bi-file-earmark-pdf me-1"></i> Unduh / Buka SK Resmi Rektorat (PDF)
                                        </a>
                                    <?php else: ?>
                                        <div class="alert alert-warning py-2 px-3 small border-0 bg-warning-subtle text-warning-emphasis mb-3">
                                            <i class="bi bi-hourglass-split me-1"></i> Berkas SK resmi bertandatangan dari Rektorat belum diunggah.
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($can_manage_team): ?>
                                        <form method="POST" enctype="multipart/form-data" class="pt-2 border-top">
                                            <label class="form-label small fw-semibold text-dark">
                                                <?= !empty($tim['file_sk']) ? 'Perbarui Berkas SK Resmi:' : 'Unggah Berkas SK Resmi (PDF):' ?>
                                            </label>
                                            <div class="input-group">
                                                <input type="file" name="file_sk" class="form-control" accept=".pdf,.doc,.docx" required>
                                                <button type="submit" name="upload_sk_resmi" class="btn btn-primary fw-semibold">
                                                    <i class="bi bi-upload me-1"></i> Unggah
                                                </button>
                                            </div>
                                            <small class="text-muted d-block mt-1" style="font-size: 0.72rem;">*Hanya dapat dilakukan oleh Ketua Tim atau Admin LPAIP.</small>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- TAB 2: ANGGOTA PERSONIL TIM                                      -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $active_tab === 'members' ? 'show active' : '' ?>" id="members" role="tabpanel">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom-0">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-person-badge text-primary me-2"></i>Struktur & Plotting Personil Tim Ad Hoc</h6>
                            <small class="text-muted">Susunan personil ditetapkan secara resmi berdasarkan Surat Keputusan (SK) Rektorat</small>
                        </div>
                        <?php if ($can_manage_team): ?>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalAnggota">
                                <i class="bi bi-person-plus me-1"></i> Tambah Personil Anggota
                            </button>
                        <?php else: ?>
                            <span class="badge bg-light text-secondary border px-3 py-1.5 rounded-pill fs-7">
                                <i class="bi bi-shield-lock me-1 text-primary"></i> Ditetapkan via SK Rektorat
                            </span>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">No</th>
                                        <th>Nama Personil</th>
                                        <th>NIP / NIK</th>
                                        <th>Jabatan dalam Tim</th>
                                        <th>Jabatan Struktural UKDW</th>
                                        <th>Tanggal Bergabung</th>
                                        <?php if ($can_manage_team): ?><th class="text-center">Aksi</th><?php endif; ?>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($members)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada anggota yang ditambahkan.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($members as $idx => $m): ?>
                                            <tr>
                                                <td class="ps-3 fw-semibold text-secondary"><?= $idx + 1 ?></td>
                                                <td>
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($m['nama']) ?></div>
                                                    <small class="text-muted"><?= htmlspecialchars($m['email']) ?></small>
                                                </td>
                                                <td><span class="badge bg-light text-secondary border px-2.5 py-1 font-monospace"><?= htmlspecialchars($m['nip_nim']) ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $m['peran_tim'] === 'Ketua' ? 'primary' : 'secondary' ?>-subtle text-<?= $m['peran_tim'] === 'Ketua' ? 'primary' : 'secondary' ?> rounded-pill px-3 py-1 fw-semibold">
                                                        <?= $m['peran_tim'] ?>
                                                    </span>
                                                </td>
                                                <td class="small text-secondary"><?= htmlspecialchars($m['jabatan_struktural']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y', strtotime($m['tgl_bergabung'])) ?></td>
                                                <?php if ($can_manage_team): ?>
                                                    <td class="text-center">
                                                        <?php if ($m['user_id'] != $user_id || $is_admin): ?>
                                                            <button type="button" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-semibold border-0" onclick="showDeleteModal({ url: 'tim_detail.php?id=<?= $tim_id ?>&hapus_anggota=<?= $m['id'] ?>&tab=members', title: 'Keluarkan Personil', message: 'Apakah Anda yakin ingin mengeluarkan personil ini dari tim?', itemTitle: <?= htmlspecialchars(json_encode($m['nama'])) ?> });">
                                                                <i class="bi bi-person-x me-1"></i> Hapus
                                                            </button>
                                                        <?php else: ?>
                                                            <span class="badge bg-light text-muted border px-2 py-1 fs-7">Ketua Tim</span>
                                                        <?php endif; ?>
                                                    </td>
                                                <?php endif; ?>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- TAB 3: PENUGASAN KERJA (TASK MANAGEMENT)                         -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $active_tab === 'tasks' ? 'show active' : '' ?>" id="tasks" role="tabpanel">
                
                <!-- KARTU PROGRES KERJA TIM (%) -->
                <div class="card border-0 shadow-sm rounded-3 mb-4 bg-white">
                    <div class="card-body p-4">
                        <div class="row align-items-center g-3">
                            <div class="col-md-4">
                                <div class="d-flex align-items-center gap-3">
                                    <div class="rounded-circle d-flex align-items-center justify-content-center text-white fw-bold shadow-sm" style="width: 52px; height: 52px; background-color: #6b0c22;">
                                        <?= $progress_tim ?>%
                                    </div>
                                    <div>
                                        <h6 class="fw-bold text-dark mb-0">Tingkat Capaian Kerja Tim</h6>
                                        <small class="text-muted"><?= $tugas_selesai ?> dari <?= $total_tugas ?> tugas selesai dikerjakan</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="progress" style="height: 12px; background-color: #f1f5f9; border-radius: 999px;">
                                    <div class="progress-bar <?= $progress_tim >= 100 ? 'bg-success' : 'bg-primary' ?>" role="progressbar" style="width: <?= $progress_tim ?>%;" aria-valuenow="<?= $progress_tim ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-card-checklist text-success me-2"></i>Daftar Penugasan & Bukti Pelaksanaan</h6>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill small" id="countFilteredTasks"><?= count($tasks) ?> Tugas</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- Dropdown Filter PIC / Peserta Tim -->
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted fw-semibold mb-0 d-none d-sm-inline" for="filterPicTugas">
                                    <i class="bi bi-person-filter me-1 text-primary"></i>Filter Peserta:
                                </label>
                                <div class="input-group input-group-sm" style="min-width: 250px;">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person-check"></i></span>
                                    <select id="filterPicTugas" class="form-select form-select-sm border-start-0 shadow-none fw-medium" onchange="filterTugasByPic(this.value)">
                                        <option value="all">Semua Peserta Tim (<?= count($tasks) ?>)</option>
                                        <?php foreach ($members as $m): 
                                            $pic_tasks_count = count(array_filter($tasks, fn($tk) => $tk['assigned_to'] == $m['user_id']));
                                        ?>
                                            <option value="<?= $m['user_id'] ?>">
                                                <?= htmlspecialchars($m['nama']) ?> (<?= $pic_tasks_count ?> Tugas)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <?php if ($can_manage_team): ?>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalTugas">
                                    <i class="bi bi-plus-lg me-1"></i> Buat Penugasan Baru
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Rincian Tugas</th>
                                        <th>Penanggung Jawab (PIC)</th>
                                        <th>Prioritas</th>
                                        <th>Deadline</th>
                                        <th>Bukti Hasil Kerja</th>
                                        <th>Status</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($tasks)): ?>
                                        <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada tugas yang dibuat untuk tim ini.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($tasks as $t): 
                                            $today = date('Y-m-d');
                                            $is_overdue = $t['deadline'] < $today && $t['status'] != 'Selesai';
                                            $t_files = $task_files_map[$t['id']] ?? [];
                                        ?>
                                            <tr class="task-row" data-pic-id="<?= $t['assigned_to'] ?>">
                                                <td class="ps-3 py-3" style="max-width: 280px;">
                                                    <div class="fw-bold text-dark"><?= htmlspecialchars($t['judul_tugas']) ?></div>
                                                    <small class="text-muted d-block mb-1"><?= nl2br(htmlspecialchars($t['deskripsi_tugas'] ?? '')) ?></small>
                                                </td>
                                                <td><span class="fw-semibold text-primary"><i class="bi bi-person-circle me-1"></i> <?= htmlspecialchars($t['pic']) ?></span></td>
                                                <td>
                                                    <span class="badge bg-<?= $t['prioritas'] == 'Mendesak' ? 'danger' : ($t['prioritas'] == 'Tinggi' ? 'warning' : 'info') ?>-subtle text-<?= $t['prioritas'] == 'Mendesak' ? 'danger' : ($t['prioritas'] == 'Tinggi' ? 'warning' : 'info') ?> px-2.5 py-1 rounded-pill fw-semibold">
                                                        <?= $t['prioritas'] ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?= $is_overdue ? 'danger-subtle text-danger' : 'light text-dark border' ?> px-2.5 py-1">
                                                        <i class="bi bi-calendar-event me-1"></i> <?= tgl_indo($t['deadline']) ?>
                                                        <?= $is_overdue ? ' (TERLAMBAT)' : '' ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($t_files)): ?>
                                                        <div class="d-flex flex-column gap-1">
                                                            <?php foreach ($t_files as $tf): 
                                                                $is_url = preg_match("~^(?:f|ht)tps?://~i", $tf['file_path']);
                                                            ?>
                                                                <div class="d-inline-flex align-items-center gap-1" style="max-width: 210px;">
                                                                    <?php if ($is_url): ?>
                                                                        <a href="<?= htmlspecialchars($tf['file_path']) ?>" target="_blank" rel="noopener noreferrer" class="badge bg-info-subtle text-info-emphasis border border-info-subtle text-decoration-none px-2 py-1 text-truncate flex-grow-1" style="max-width: 175px;" title="<?= htmlspecialchars($tf['nama_file_asli']) ?>">
                                                                            <i class="bi bi-link-45deg me-1 text-primary fs-6"></i><?= htmlspecialchars($tf['nama_file_asli']) ?>
                                                                        </a>
                                                                    <?php else: ?>
                                                                        <a href="../uploads/tugas/<?= htmlspecialchars($tf['file_path']) ?>" download="<?= htmlspecialchars($tf['nama_file_asli']) ?>" target="_blank" class="badge bg-light text-dark border text-decoration-none px-2 py-1 text-truncate flex-grow-1" style="max-width: 175px;" title="<?= htmlspecialchars($tf['nama_file_asli']) ?>">
                                                                            <i class="bi bi-paperclip me-1 text-primary"></i><?= htmlspecialchars($tf['nama_file_asli']) ?>
                                                                        </a>
                                                                    <?php endif; ?>
                                                                    <?php if ($user_id == $tf['uploader_id'] || $can_manage_team): ?>
                                                                        <button type="button"
                                                                           class="btn btn-link p-0 text-danger flex-shrink-0"
                                                                           style="font-size: 13px; line-height: 1;"
                                                                           title="Hapus file ini"
                                                                           onclick="showDeleteModal({ url: 'tim_detail.php?id=<?= $tim_id ?>&hapus_bukti_file=<?= $tf['id'] ?>&tugas_id=<?= $t['id'] ?>&tab=tasks', title: 'Hapus Berkas Bukti', message: 'Apakah Anda yakin ingin menghapus berkas bukti tugas ini?', itemTitle: <?= htmlspecialchars(json_encode($tf['nama_file_asli'])) ?> });">
                                                                            <i class="bi bi-x-circle-fill"></i>
                                                                        </button>
                                                                    <?php endif; ?>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="text-muted small">Belum ada bukti</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <?php 
                                                        $persen = isset($t['progres_persen']) ? intval($t['progres_persen']) : ($t['status'] == 'Selesai' ? 100 : 0);
                                                    ?>
                                                    <?php if ($t['status'] == 'Selesai'): ?>
                                                        <span class="badge bg-success-subtle text-success rounded-pill px-3 py-1 fw-semibold">
                                                            <i class="bi bi-check-circle-fill me-1"></i> Selesai (100%)
                                                        </span>
                                                    <?php elseif ($t['status'] == 'Sedang Dikerjakan'): ?>
                                                        <div>
                                                            <span class="badge bg-warning-subtle text-warning-emphasis rounded-pill px-2.5 py-1 fw-semibold mb-1">
                                                                <i class="bi bi-hourglass-split me-1"></i> Dikerjakan (<?= $persen ?>%)
                                                            </span>
                                                            <div class="progress" style="height: 6px; width: 110px;">
                                                                <div class="progress-bar bg-warning" role="progressbar" style="width: <?= $persen ?>%;"></div>
                                                            </div>
                                                        </div>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary rounded-pill px-3 py-1 fw-semibold">
                                                            <i class="bi bi-circle me-1"></i> Belum (0%)
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="text-center">
                                                    <?php if ($user_id == $t['assigned_to'] || $can_manage_team): ?>
                                                        <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalKirimBukti<?= $t['id'] ?>">
                                                            <i class="bi bi-cloud-arrow-up me-1"></i> <?= $t['status'] == 'Selesai' ? 'Update Bukti / Progres' : 'Kirim Bukti & Progres' ?>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted small">-</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>

                                            <!-- MODAL KIRIM BUKTI HASIL TUGAS -->
                                            <div class="modal fade" id="modalKirimBukti<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-dialog-centered">
                                                    <div class="modal-content">
                                                        <form method="POST" enctype="multipart/form-data">
                                                            <input type="hidden" name="tugas_id" value="<?= $t['id'] ?>">
                                                            <div class="modal-header bg-primary text-white">
                                                                <h5 class="modal-title fw-bold"><i class="bi bi-cloud-arrow-up me-2"></i> Pengerjaan & Bukti Hasil Tugas</h5>
                                                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                                            </div>
                                                            <div class="modal-body p-4">
                                                                <div class="mb-3 p-3 bg-light rounded-3 border">
                                                                    <div class="small fw-semibold text-muted mb-1">Judul Penugasan:</div>
                                                                    <div class="fw-bold text-dark fs-6"><?= htmlspecialchars($t['judul_tugas']) ?></div>
                                                                    <?php if (!empty($t['deskripsi_tugas'])): ?>
                                                                        <small class="text-muted d-block mt-1"><?= nl2br(htmlspecialchars($t['deskripsi_tugas'])) ?></small>
                                                                    <?php endif; ?>
                                                                </div>

                                                                <!-- SLIDER PROGRES PENGERJAAN -->
                                                                <?php 
                                                                    $curr_p = isset($t['progres_persen']) ? intval($t['progres_persen']) : ($t['status'] == 'Selesai' ? 100 : 50);
                                                                ?>
                                                                <div class="mb-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <label class="form-label small fw-semibold mb-0">Tingkat Capaian Pengerjaan Tugas (%):</label>
                                                                        <span class="badge bg-primary fs-6 px-3 py-1" id="badgePersen<?= $t['id'] ?>"><?= $curr_p ?>%</span>
                                                                    </div>
                                                                    <input type="range" class="form-range" name="progres_persen" id="sliderPersen<?= $t['id'] ?>" min="0" max="100" step="5" value="<?= $curr_p ?>" oninput="document.getElementById('badgePersen<?= $t['id'] ?>').innerText = this.value + '%'; var hint = document.getElementById('hintSelesai<?= $t['id'] ?>'); if(this.value >= 100){ hint.classList.remove('d-none'); } else { hint.classList.add('d-none'); }">
                                                                    <div class="d-flex justify-content-between text-muted" style="font-size: 0.75rem;">
                                                                        <span>0% (Belum)</span>
                                                                        <span>50% (Sebagian)</span>
                                                                        <span>100% (Selesai Penuh)</span>
                                                                    </div>
                                                                    <div id="hintSelesai<?= $t['id'] ?>" class="<?= $curr_p >= 100 ? '' : 'd-none' ?> mt-2 alert alert-success py-1.5 px-3 small border-0 bg-success-subtle text-success-emphasis mb-0">
                                                                        <i class="bi bi-check-circle-fill me-1"></i> Mencapai 100% akan menandai tugas ini sebagai <strong>Selesai (Centang Hijau)</strong>.
                                                                    </div>
                                                                </div>

                                                                <!-- MULTIPLE FILES UPLOAD -->
                                                                <div class="mb-3">
                                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                                        <label class="form-label small fw-semibold mb-0">Unggah Berkas Bukti (Bisa Banyak File & Format Bebas):</label>
                                                                        <button type="button" class="btn btn-link text-danger text-decoration-none p-0 small d-none btn-clear-file" style="font-size: 0.75rem;" onclick="resetFileInput(this)">
                                                                            <i class="bi bi-x-circle me-1"></i>Hapus Pilihan File
                                                                        </button>
                                                                    </div>
                                                                    <input type="file" name="bukti_files[]" class="form-control" multiple onchange="toggleClearFileBtn(this)">
                                                                    <small class="text-muted">Mendukung multi-file: PDF, Word, Excel, PowerPoint, ZIP, RAR, gambar, dsb.</small>
                                                                </div>

                                                                <!-- TAUTAN / LINK ONLINE -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Atau Lampirkan Tautan / Link Online (Opsional):</label>
                                                                    <div class="input-group mb-1">
                                                                        <span class="input-group-text"><i class="bi bi-link-45deg"></i></span>
                                                                        <input type="url" name="link_bukti" class="form-control" placeholder="https://docs.google.com/... atau Canva/Figma/Drive">
                                                                    </div>
                                                                    <input type="text" name="nama_link" class="form-control form-control-sm" placeholder="Keterangan Link (Misal: Spreadsheet Data / Drive Laporan)">
                                                                </div>

                                                                <!-- CATATAN PENGERJAAN -->
                                                                <div class="mb-3">
                                                                    <label class="form-label small fw-semibold">Catatan / Ringkasan Pengerjaan:</label>
                                                                    <textarea name="catatan_tugas" class="form-control" rows="2" placeholder="Tuliskan keterangan output apa saja yang sudah selesai dikerjakan..."></textarea>
                                                                </div>
                                                            </div>
                                                            <div class="modal-footer bg-light">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <button type="submit" name="kirim_bukti_tugas" class="btn btn-primary fw-semibold"><i class="bi bi-cloud-arrow-up me-1"></i> Simpan Hasil & Progres</button>
                                                            </div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                        <?php endforeach; ?>
                                        <tr id="noTaskFilterRow" class="d-none">
                                            <td colspan="7" class="text-center py-5 text-muted">
                                                <i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary"></i>
                                                <div class="fw-semibold text-dark">Tidak ada penugasan untuk peserta tim yang dipilih.</div>
                                                <small class="text-muted">Silakan pilih peserta lain atau kembalikan ke "Semua Peserta Tim".</small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ================================================================= -->
            <!-- TAB 4: REPOSITORI & LPJ AKHIR                                    -->
            <!-- ================================================================= -->
            <div class="tab-pane fade <?= $active_tab === 'docs' ? 'show active' : '' ?>" id="docs" role="tabpanel">
                
                <!-- STATUS KEGIATAN TIM & KELENGKAPAN LPJ -->
                <?php if ($tim['status'] === 'Selesai'): ?>
                    <div class="alert alert-success d-flex flex-wrap align-items-center justify-content-between rounded-3 border-0 shadow-sm p-3 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="bi bi-check2-all fs-4"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-success mb-1">Status Kegiatan: Selesai & Berkas Terarsip</h6>
                                <div class="small text-secondary">Seluruh rangkaian penugasan kerja telah diselesaikan dan Laporan Pertanggungjawaban (LPJ) akhir telah tersimpan di repositori LPAIP.</div>
                            </div>
                        </div>
                        <?php if ($can_manage_team): ?>
                            <form method="POST" action="" class="m-0 mt-2 mt-md-0">
                                <input type="hidden" name="update_status_tim" value="1">
                                <input type="hidden" name="status_tim" value="Aktif">
                                <input type="hidden" name="redirect_tab" value="docs">
                                <button type="submit" class="btn btn-sm btn-outline-success rounded-pill px-3 fw-semibold shadow-sm" onclick="return confirm('Buka kembali status kegiatan tim menjadi Aktif?');">
                                    <i class="bi bi-arrow-repeat me-1"></i> Buka Kembali Kegiatan Tim
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php else: ?>
                    <div class="alert alert-light border border-info-subtle d-flex flex-wrap align-items-center justify-content-between rounded-3 shadow-sm p-3 mb-4">
                        <div class="d-flex align-items-center">
                            <div class="bg-primary-subtle text-primary rounded-circle p-2 me-3 d-flex align-items-center justify-content-center" style="width: 44px; height: 44px;">
                                <i class="bi bi-flag-fill fs-5"></i>
                            </div>
                            <div>
                                <h6 class="fw-bold text-dark mb-1">Kelengkapan Laporan Akhir Kegiatan</h6>
                                <div class="small text-muted">Progres penugasan saat ini: <strong><?= $progress_tim ?>%</strong> (<?= $tugas_selesai ?> dari <?= $total_tugas ?> tugas selesai). Unggah berkas LPJ yang telah disahkan untuk menyelesaikan kegiatan tim ini.</div>
                            </div>
                        </div>
                        <?php if ($can_manage_team && !empty($docs_lpj)): ?>
                            <form method="POST" action="" class="m-0 mt-2 mt-md-0">
                                <input type="hidden" name="update_status_tim" value="1">
                                <input type="hidden" name="status_tim" value="Selesai">
                                <input type="hidden" name="redirect_tab" value="docs">
                                <button type="submit" class="btn btn-sm btn-success rounded-pill px-4 py-2 fw-semibold shadow-sm" onclick="return confirm('Tetapkan status tim ini sebagai SELESAI?');">
                                    <i class="bi bi-check2-circle me-1"></i> Tandai Tim Selesai
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>

                <!-- BAGIAN 1: LAPORAN PERTANGGUNGJAWABAN (LPJ) AKHIR -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center border-bottom-0">
                        <div>
                            <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-journal-check text-primary me-2"></i>Laporan Pertanggungjawaban (LPJ) Akhir Tim</h6>
                            <small class="text-muted">Muara akhir pertanggungjawaban kegiatan tim setelah tugas-tugas terselesaikan</small>
                        </div>
                        <?php if ($can_manage_team): ?>
                            <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 shadow-sm fw-semibold" data-bs-toggle="modal" data-bs-target="#modalLPJ">
                                <i class="bi bi-upload me-1"></i> Unggah LPJ Akhir
                            </button>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Nama Berkas LPJ</th>
                                        <th>Pengunggah</th>
                                        <th>Tanggal Pengunggahan</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($docs_lpj)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada Laporan Pertanggungjawaban (LPJ) yang diunggah untuk tim ini.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($docs_lpj as $lpj): ?>
                                            <tr>
                                                <td class="ps-3 fw-bold text-dark">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger me-2 fs-5"></i> <?= htmlspecialchars($lpj['nama_dokumen']) ?>
                                                </td>
                                                <td class="small text-muted"><?= htmlspecialchars($lpj['uploader']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y H:i', strtotime($lpj['uploaded_at'])) ?></td>
                                                <td class="text-center">
                                                    <a href="../uploads/dokumen/<?= htmlspecialchars($lpj['file_path']) ?>" target="_blank" download class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-semibold border-0">
                                                        <i class="bi bi-download me-1"></i> Unduh LPJ
                                                    </a>
                                                    <?php if ($can_manage_team): ?>
                                                        <button type="button" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-semibold border-0" onclick="showDeleteModal({ url: 'tim_detail.php?id=<?= $tim_id ?>&hapus_dokumen=<?= $lpj['id'] ?>&tab=docs', title: 'Hapus Berkas LPJ', message: 'Apakah Anda yakin ingin menghapus berkas LPJ ini?', itemTitle: <?= htmlspecialchars(json_encode($lpj['nama_dokumen'])) ?> });">
                                                            <i class="bi bi-trash me-1"></i> Hapus
                                                        </button>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- BAGIAN 2: BERKAS & DOKUMEN PENDUKUNG -->
                <?php
                    // Siapkan daftar pengunggah unik untuk filter dokumen
                    $doc_uploaders_map = [];
                    foreach ($members as $m) {
                        $doc_uploaders_map[$m['user_id']] = $m['nama'];
                    }
                    foreach ($docs_pendukung as $d) {
                        if (!empty($d['uploader_id']) && !isset($doc_uploaders_map[$d['uploader_id']])) {
                            $doc_uploaders_map[$d['uploader_id']] = $d['uploader'] ?? ('Pengguna #' . $d['uploader_id']);
                        }
                    }
                ?>
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3 d-flex flex-wrap justify-content-between align-items-center gap-2 border-bottom">
                        <div class="d-flex align-items-center gap-2">
                            <h6 class="fw-bold mb-0 text-dark fs-6"><i class="bi bi-folder-symlink text-primary me-2"></i>Dokumen Pendukung Kegiatan (Proposal, Notula, Berita Acara)</h6>
                            <span class="badge bg-secondary-subtle text-secondary rounded-pill small" id="countFilteredDocs"><?= count($docs_pendukung) ?> Dokumen</span>
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <!-- Dropdown Filter Pengunggah / Peserta Tim -->
                            <div class="d-flex align-items-center gap-2">
                                <label class="small text-muted fw-semibold mb-0 d-none d-sm-inline" for="filterUploaderDocs">
                                    <i class="bi bi-person-filter me-1 text-primary"></i>Filter Pengunggah:
                                </label>
                                <div class="input-group input-group-sm" style="min-width: 250px;">
                                    <span class="input-group-text bg-light text-muted border-end-0"><i class="bi bi-person-check"></i></span>
                                    <select id="filterUploaderDocs" class="form-select form-select-sm border-start-0 shadow-none fw-medium" onchange="filterDocsByUploader(this.value)">
                                        <option value="all">Semua Pengunggah (<?= count($docs_pendukung) ?>)</option>
                                        <?php foreach ($doc_uploaders_map as $uid => $unama): 
                                            $m_docs_count = count(array_filter($docs_pendukung, fn($dk) => $dk['uploader_id'] == $uid));
                                        ?>
                                            <option value="<?= $uid ?>">
                                                <?= htmlspecialchars($unama) ?> (<?= $m_docs_count ?> Berkas)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 fw-semibold" data-bs-toggle="modal" data-bs-target="#modalDokumen">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen Pendukung
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Nama Berkas Dokumen</th>
                                        <th>Jenis Berkas</th>
                                        <th>Pengunggah</th>
                                        <th>Tanggal Upload</th>
                                        <th class="text-center">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($docs_pendukung)): ?>
                                        <tr><td colspan="5" class="text-center py-4 text-muted">Belum ada dokumen pendukung atau berkas penugasan yang diunggah.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($docs_pendukung as $d): ?>
                                            <?php 
                                                $is_task = isset($d['sumber']) && $d['sumber'] === 'tugas';
                                                $is_url = $is_task && preg_match("~^(?:f|ht)tps?://~i", $d['file_path']);
                                            ?>
                                            <tr class="doc-row" data-uploader-id="<?= $d['uploader_id'] ?>">
                                                <td class="ps-3">
                                                    <div class="d-flex align-items-center">
                                                        <?php if ($is_url): ?>
                                                            <i class="bi bi-link-45deg text-info me-2 fs-4"></i>
                                                        <?php elseif ($is_task): ?>
                                                            <i class="bi bi-file-earmark-check text-success me-2 fs-5"></i>
                                                        <?php else: ?>
                                                            <i class="bi bi-file-earmark-text text-primary me-2 fs-5"></i>
                                                        <?php endif; ?>
                                                        <div>
                                                            <span class="fw-bold text-dark d-block"><?= htmlspecialchars($d['nama_dokumen']) ?></span>
                                                            <?php if ($is_task && !empty($d['judul_tugas'])): ?>
                                                                <small class="text-muted d-inline-flex align-items-center mt-0.5">
                                                                    <i class="bi bi-card-checklist text-primary me-1"></i>
                                                                    <span>Dari Tugas: <strong class="text-dark"><?= htmlspecialchars($d['judul_tugas']) ?></strong></span>
                                                                </small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?php if ($is_url): ?>
                                                        <span class="badge bg-info-subtle text-info-emphasis px-2.5 py-1 rounded-pill fw-semibold">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i>Tautan Tugas
                                                        </span>
                                                    <?php elseif ($is_task): ?>
                                                        <span class="badge bg-success-subtle text-success px-2.5 py-1 rounded-pill fw-semibold">
                                                            <i class="bi bi-check2-circle me-1"></i>Bukti Tugas
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-secondary-subtle text-secondary px-2.5 py-1 rounded-pill fw-semibold"><?= htmlspecialchars($d['jenis_dokumen']) ?></span>
                                                    <?php endif; ?>
                                                </td>
                                                <td class="small text-muted"><?= htmlspecialchars($d['uploader']) ?></td>
                                                <td class="small text-muted"><?= date('d M Y H:i', strtotime($d['uploaded_at'])) ?></td>
                                                <td class="text-center">
                                                    <?php if ($is_url): ?>
                                                        <a href="<?= htmlspecialchars($d['file_path']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-semibold border-0">
                                                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Tautan
                                                        </a>
                                                    <?php elseif ($is_task): ?>
                                                        <a href="../uploads/tugas/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" download class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-semibold border-0">
                                                            <i class="bi bi-download me-1"></i> Unduh
                                                        </a>
                                                    <?php else: ?>
                                                        <a href="../uploads/dokumen/<?= htmlspecialchars($d['file_path']) ?>" target="_blank" download class="btn btn-sm btn-light text-primary rounded-pill px-3 fw-semibold border-0">
                                                            <i class="bi bi-download me-1"></i> Unduh
                                                        </a>
                                                    <?php endif; ?>

                                                    <?php if ($can_manage_team || $d['uploader_id'] == $user_id): ?>
                                                        <?php if ($is_task): ?>
                                                            <button type="button" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-semibold border-0" onclick="showDeleteModal({ url: 'tim_detail.php?id=<?= $tim_id ?>&hapus_bukti_file=<?= $d['id'] ?>&tugas_id=<?= $d['tugas_id'] ?>&tab=docs', title: 'Hapus Bukti Tugas', message: 'Apakah Anda yakin ingin menghapus berkas hasil penugasan ini?', itemTitle: <?= htmlspecialchars(json_encode($d['nama_dokumen'])) ?> });">
                                                                <i class="bi bi-trash me-1"></i> Hapus
                                                            </button>
                                                        <?php else: ?>
                                                            <button type="button" class="btn btn-sm btn-light text-danger rounded-pill px-3 fw-semibold border-0" onclick="showDeleteModal({ url: 'tim_detail.php?id=<?= $tim_id ?>&hapus_dokumen=<?= $d['id'] ?>&tab=docs', title: 'Hapus Dokumen Tim', message: 'Apakah Anda yakin ingin menghapus dokumen ini?', itemTitle: <?= htmlspecialchars(json_encode($d['nama_dokumen'])) ?> });">
                                                                <i class="bi bi-trash me-1"></i> Hapus
                                                            </button>
                                                        <?php endif; ?>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <tr id="noDocFilterRow" class="d-none">
                                            <td colspan="5" class="text-center py-5 text-muted">
                                                <i class="bi bi-folder-x fs-2 d-block mb-2 text-secondary"></i>
                                                <div class="fw-semibold text-dark">Tidak ada berkas/dokumen dari pengunggah yang dipilih.</div>
                                                <small class="text-muted">Silakan pilih peserta lain atau kembalikan ke "Semua Pengunggah".</small>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>

        </div>

    </div>

    <?php if ($can_manage_team): ?>
    <!-- MODAL FORM ANGGOTA (Admin LPAIP & Ketua Tim) -->
    <div class="modal fade" id="modalAnggota" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-person-plus me-2"></i> Tambah Anggota Tim</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold text-muted d-block">Pilih Sumber Personil:</label>
                            <div class="btn-group w-100" role="group">
                                <input type="radio" class="btn-check" name="mode_user" id="modeExisting" value="existing" checked onclick="toggleUserMode('existing')">
                                <label class="btn btn-outline-primary btn-sm py-2 fw-semibold" for="modeExisting">
                                    <i class="bi bi-person-check me-1"></i> User Terdaftar
                                </label>
                                <input type="radio" class="btn-check" name="mode_user" id="modeNew" value="new" onclick="toggleUserMode('new')">
                                <label class="btn btn-outline-primary btn-sm py-2 fw-semibold" for="modeNew">
                                    <i class="bi bi-person-plus-fill me-1"></i> Buat User Baru
                                </label>
                            </div>
                        </div>

                        <div id="sectionExistingUser">
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Pilih Personil / Staf LPAIP <span class="text-danger">*</span></label>
                                <select class="form-select" name="user_id" id="select_user_id" style="width: 100%;">
                                    <option value="">-- Ketik untuk Cari Nama / NIP Pegawai --</option>
                                    <?php foreach ($users_dropdown as $u): ?>
                                        <option value="<?= $u['id'] ?>"><?= htmlspecialchars($u['nama']) ?> (<?= htmlspecialchars($u['nip_nim']) ?>) - <?= strtoupper($u['role']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="text-muted d-block mt-1" style="font-size: 0.8rem;"><i class="bi bi-search me-1"></i>Ketik nama, NIP/NIK, atau role untuk memfilter pilihan secara instan.</small>
                            </div>
                        </div>

                        <div id="sectionNewUser" style="display: none;">
                            <div class="alert alert-info py-2 px-3 small mb-3 border-0 bg-info-subtle text-info-emphasis">
                                <i class="bi bi-info-circle me-1"></i> User baru akan langsung dibuatkan akun (Password default: <code>user123</code>).
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">NIP / NIK (Dosen / Staf) <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="new_nip_nim" id="new_nip_nim" placeholder="Contoh: P002 / 71220903">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Nama Lengkap & Gelar <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" name="new_nama" id="new_nama" placeholder="Nama lengkap personil">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label small fw-semibold">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" name="new_email" id="new_email" placeholder="nama@lpaip.ukdw.ac.id">
                            </div>
                            <div class="row g-2 mb-3">
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Role Sistem</label>
                                    <select class="form-select" name="new_role">
                                        <option value="anggota" selected>Anggota</option>
                                        <option value="ketua">Ketua Tim</option>
                                        <?php if ($is_admin): ?><option value="admin">Admin</option><?php endif; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label small fw-semibold">Jabatan Struktural</label>
                                    <input type="text" class="form-control" name="new_jabatan_struktural" placeholder="Staf / Dosen UKDW">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jabatan dalam Tim Ad Hoc <span class="text-danger">*</span></label>
                            <select class="form-select" name="peran_tim" required>
                                <option value="Anggota" selected>Anggota</option>
                                <option value="Ketua">Ketua Tim</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_anggota" class="btn btn-primary fw-semibold"><i class="bi bi-check-circle me-1"></i> Tambahkan Personil</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <style>
    /* ===== SCROLL MODAL BUAT PENUGASAN ===== */
    #modalTugas .modal-content {
        max-height: 90vh;
    }
    #modalTugas .modal-body {
        overflow-y: auto;
        max-height: calc(90vh - 140px);
        scroll-behavior: smooth;
    }
    #modalTugas .modal-body::-webkit-scrollbar {
        width: 6px;
    }
    #modalTugas .modal-body::-webkit-scrollbar-track {
        background: #f1f5f9;
        border-radius: 4px;
    }
    #modalTugas .modal-body::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    #modalTugas .modal-body::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
    /* fallback scrollbar for container */
    #task_cards_container::-webkit-scrollbar {
        width: 6px;
    }
    #task_cards_container::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 4px;
    }
    #task_cards_container::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    #task_cards_container::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }
</style>
<!-- MODAL FORM TUGAS (MENDUKUNG BANYAK TUGAS SEKALIGUS) -->
    <div class="modal fade" id="modalTugas" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <form method="POST" action="">
                    <div class="modal-header bg-primary text-white">
                        <div>
                            <h5 class="modal-title fw-bold mb-0"><i class="bi bi-check2-square me-2"></i> Buat Penugasan Tim</h5>
                            <small class="text-white-50">Ketua tim dapat menambahkan satu atau beberapa tugas sekaligus</small>
                        </div>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <!-- CONTAINER KARTU PENUGASAN (DENGAN SCROLL VERTICAL) -->
                        <div id="task_cards_container" style="max-height: 52vh; overflow-y: auto; overflow-x: hidden; padding-right: 8px;">
                            <!-- BARIS TUGAS 1 -->
                            <div class="task-row-item card border shadow-none mb-3 bg-light rounded-3" id="task_row_1">
                                <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center border-bottom">
                                    <span class="fw-bold text-primary small task-row-title"><i class="bi bi-card-checklist me-1"></i> Penugasan #1</span>
                                    <span class="badge bg-secondary-subtle text-secondary small">Utama</span>
                                </div>
                                <div class="card-body p-3">
                                    <div class="row g-2 mb-2">
                                        <div class="col-md-7">
                                            <label class="form-label small fw-semibold">Judul Penugasan <span class="text-danger">*</span></label>
                                            <input type="text" class="form-control" name="judul_tugas[]" placeholder="Contoh: Menyusun Draft Laporan / Riset Modul" required>
                                        </div>
                                        <div class="col-md-5">
                                            <label class="form-label small fw-semibold">Penerima Tugas (PIC) <span class="text-danger">*</span></label>
                                            <select class="form-select" name="assigned_to[]" required>
                                                <option value="">-- Pilih PIC Anggota --</option>
                                                <?php foreach ($members as $m): ?>
                                                    <option value="<?= $m['user_id'] ?>"><?= htmlspecialchars($m['nama']) ?> (<?= $m['peran_tim'] ?>)</option>
                                                <?php endforeach; ?>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label small fw-semibold">Deskripsi Instruksi Tugas</label>
                                        <textarea class="form-control" name="deskripsi_tugas[]" rows="2" placeholder="Rincian instruksi atau deliverables yang harus dikumpulkan..."></textarea>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Tingkat Prioritas</label>
                                            <select class="form-select" name="prioritas[]">
                                                <option value="Rendah">Rendah</option>
                                                <option value="Sedang" selected>Sedang</option>
                                                <option value="Tinggi">Tinggi</option>
                                                <option value="Mendesak">Mendesak</option>
                                            </select>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label small fw-semibold">Tenggat Waktu (Deadline) <span class="text-danger">*</span></label>
                                            <input type="date" class="form-control" name="deadline[]" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TOMBOL TAMBAH PENUGASAN LAIN -->
                        <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                            <button type="button" class="btn btn-outline-primary btn-sm rounded-pill fw-semibold" onclick="tambahBarisTugas()">
                                <i class="bi bi-plus-circle me-1"></i> Tambah Penugasan Lain
                            </button>
                            <span class="text-muted small">Bisa menambahkan beberapa tugas sekaligus sebelum disimpan</span>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="tambah_tugas" class="btn btn-primary fw-semibold"><i class="bi bi-save me-1"></i> Simpan Semua Penugasan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- TEMPLATE ROW TUGAS (DI-CLONE DENGAN JS) -->
    <div id="task_row_template" class="task-row-item card border shadow-none mb-3 bg-light rounded-3" style="display: none;">
        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center border-bottom">
            <span class="fw-bold text-primary small task-row-title"><i class="bi bi-card-checklist me-1"></i> Penugasan #</span>
            <button type="button" class="btn btn-sm btn-outline-danger py-0 px-2 rounded-pill" onclick="hapusBarisTugas(this)" title="Hapus baris penugasan ini">
                <i class="bi bi-trash me-1"></i> Hapus
            </button>
        </div>
        <div class="card-body p-3">
            <div class="row g-2 mb-2">
                <div class="col-md-7">
                    <label class="form-label small fw-semibold">Judul Penugasan <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" name="judul_tugas[]" placeholder="Contoh: Menyusun Analisis / Desain" data-required="true">
                </div>
                <div class="col-md-5">
                    <label class="form-label small fw-semibold">Penerima Tugas (PIC) <span class="text-danger">*</span></label>
                    <select class="form-select" name="assigned_to[]" data-required="true">
                        <option value="">-- Pilih PIC Anggota --</option>
                        <?php foreach ($members as $m): ?>
                            <option value="<?= $m['user_id'] ?>"><?= htmlspecialchars($m['nama']) ?> (<?= $m['peran_tim'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="mb-2">
                <label class="form-label small fw-semibold">Deskripsi Instruksi Tugas</label>
                <textarea class="form-control" name="deskripsi_tugas[]" rows="2" placeholder="Rincian instruksi atau deliverables yang harus dikumpulkan..."></textarea>
            </div>
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tingkat Prioritas</label>
                    <select class="form-select" name="prioritas[]">
                        <option value="Rendah">Rendah</option>
                        <option value="Sedang" selected>Sedang</option>
                        <option value="Tinggi">Tinggi</option>
                        <option value="Mendesak">Mendesak</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Tenggat Waktu (Deadline) <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" name="deadline[]" data-required="true">
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FORM UNGGAH LPJ -->
    <div class="modal fade" id="modalLPJ" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="jenis_dokumen" value="LPJ">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-journal-check me-2"></i> Unggah LPJ Akhir Kegiatan Tim</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul / Keterangan LPJ <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_dokumen" value="Laporan Pertanggungjawaban (LPJ) Akhir - <?= htmlspecialchars($tim['nama_tim']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Berkas Dokumen LPJ (PDF/Word) <span class="text-danger">*</span></label>
                            <input type="file" name="file_dok" class="form-control" accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">Pastikan berkas telah memuat tanda tangan Ketua Tim.</small>
                        </div>
                        <div class="form-check p-2.5 bg-light rounded-3 border">
                            <input class="form-check-input ms-0 me-2" type="checkbox" name="set_tim_selesai" value="1" id="checkSelesai" checked>
                            <label class="form-check-label small fw-semibold text-dark" for="checkSelesai">
                                Otomatis tandai status tim sebagai <span class="badge bg-success-subtle text-success">Selesai</span> setelah berkas LPJ berhasil diunggah
                            </label>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="upload_dokumen" class="btn btn-primary fw-semibold"><i class="bi bi-upload me-1"></i> Unggah LPJ</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- MODAL FORM DOKUMEN PENDUKUNG -->
    <div class="modal fade" id="modalDokumen" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="POST" enctype="multipart/form-data">
                    <div class="modal-header bg-primary text-white">
                        <h5 class="modal-title fw-bold"><i class="bi bi-folder-plus me-2"></i> Tambah Dokumen Pendukung</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body p-4">
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Nama Dokumen <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="nama_dokumen" placeholder="Contoh: Proposal Kegiatan / Notula Rapat 1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Jenis Dokumen</label>
                            <select class="form-select" name="jenis_dokumen">
                                <option value="Proposal">Proposal</option>
                                <option value="Berita Acara">Berita Acara</option>
                                <option value="Lainnya" selected>Lainnya (Notula/Materi)</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Pilih Berkas Dokumen <span class="text-danger">*</span></label>
                            <input type="file" name="file_dok" class="form-control" required>
                        </div>
                    </div>
                    <div class="modal-footer bg-light">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" name="upload_dokumen" class="btn btn-primary fw-semibold"><i class="bi bi-upload me-1"></i> Unggah Berkas</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

        <script>
    // ==============================================================================
    // SCRIPT INTERAKSI TIM DETAIL (INLINE UNTUK MENCEGAH MASALAH BROWSER CACHE)
    // ==============================================================================
    // Inisialisasi Dropdown Pencarian (Select2)
    function initUserSelect2() {
        if (window.jQuery && $.fn.select2 && $('#select_user_id').length) {
            $('#select_user_id').select2({
                theme: 'bootstrap-5',
                dropdownParent: $('#modalAnggota'),
                placeholder: '-- Ketik untuk Cari Nama / NIP Pegawai --',
                allowClear: true,
                width: '100%'
            });
        }
    }

    function toggleUserMode(mode) {
        var existingSec = document.getElementById('sectionExistingUser');
        var newSec = document.getElementById('sectionNewUser');
        var selectUser = document.getElementById('select_user_id');
        var newNip = document.getElementById('new_nip_nim');
        var newNama = document.getElementById('new_nama');
        var newEmail = document.getElementById('new_email');

        if (!existingSec || !newSec) return;

        if (mode === 'new') {
            existingSec.style.display = 'none';
            newSec.style.display = 'block';
            if (selectUser) selectUser.removeAttribute('required');
            if (newNip) newNip.setAttribute('required', 'required');
            if (newNama) newNama.setAttribute('required', 'required');
            if (newEmail) newEmail.setAttribute('required', 'required');
        } else {
            existingSec.style.display = 'block';
            newSec.style.display = 'none';
            if (newNip) newNip.removeAttribute('required');
            if (newNama) newNama.removeAttribute('required');
            if (newEmail) newEmail.removeAttribute('required');
            initUserSelect2();
        }
    }

    // Pasang Select2 saat modal dibuka & validasi form
    document.addEventListener('DOMContentLoaded', function() {
        var modalAnggotaEl = document.getElementById('modalAnggota');
        if (modalAnggotaEl) {
            modalAnggotaEl.addEventListener('shown.bs.modal', function () {
                initUserSelect2();
            });

            var formAnggota = modalAnggotaEl.querySelector('form');
            if (formAnggota) {
                formAnggota.addEventListener('submit', function(e) {
                    var modeExisting = document.getElementById('modeExisting');
                    if (modeExisting && modeExisting.checked) {
                        var selectVal = $('#select_user_id').val();
                        if (!selectVal || selectVal === '') {
                            e.preventDefault();
                            alert('Silakan cari dan pilih salah satu personil / staf terlebih dahulu!');
                            if (window.jQuery && $('#select_user_id').data('select2')) {
                                $('#select_user_id').select2('open');
                            }
                            return false;
                        }
                    }
                });
            }
        }
    });

    var taskRowCounter = 1;

    function tambahBarisTugas() {
        taskRowCounter++;
        var container = document.getElementById('task_cards_container');
        var template = document.getElementById('task_row_template');
        if (!container || !template) {
            console.error('Container atau template tidak ditemukan:', container, template);
            return;
        }

        var newRow = template.cloneNode(true);
        newRow.id = 'task_row_' + taskRowCounter;
        newRow.style.display = 'block';

        // Update label nomor penugasan
        var titleEl = newRow.querySelector('.task-row-title');
        if (titleEl) {
            titleEl.innerHTML = '<i class="bi bi-card-checklist me-1"></i> Penugasan #' + taskRowCounter;
        }

        // Aktifkan required pada input di baris baru
        var inputs = newRow.querySelectorAll('input[data-required], select[data-required]');
        inputs.forEach(function(el) {
            el.required = true;
            el.setAttribute('required', 'required');
        });

        container.appendChild(newRow);

        // Scroll container otomatis ke kartu tugas yang baru
        setTimeout(function() {
            container.scrollTop = container.scrollHeight;
            var firstInput = newRow.querySelector('input[name="judul_tugas[]"]');
            if (firstInput) firstInput.focus();
        }, 50);
    }

    function hapusBarisTugas(buttonEl) {
        var card = buttonEl.closest('.task-row-item');
        if (card) {
            card.remove();
        }
    }

    // Fungsi reset file input langsung dari tombol Hapus Pilihan File
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

    // Auto-reset form dan input file saat modal pengerjaan bukti ditutup (baik lewat tombol X atau tombol Batal)
    document.querySelectorAll('[id^="modalKirimBukti"]').forEach(function(modalEl) {
        modalEl.addEventListener('hidden.bs.modal', function () {
            var form = this.querySelector('form');
            if (form) {
                form.reset();
                var clearBtn = this.querySelector('.btn-clear-file');
                if (clearBtn) clearBtn.classList.add('d-none');
                
                // Reset slider progres & badge ke nilai awal tugas
                var slider = this.querySelector('input[type="range"]');
                var badge = this.querySelector('[id^="badgePersen"]');
                var hint = this.querySelector('[id^="hintSelesai"]');
                if (slider && badge) {
                    badge.innerText = slider.defaultValue + '%';
                    slider.value = slider.defaultValue;
                    if (hint) {
                        if (parseInt(slider.defaultValue) >= 100) {
                            hint.classList.remove('d-none');
                        } else {
                            hint.classList.add('d-none');
                        }
                    }
                }
            }
        });
    });
    // Filter Penugasan Kerja berdasarkan PIC / Peserta Tim
    function filterTugasByPic(userId) {
        var rows = document.querySelectorAll('.task-row');
        var visibleCount = 0;
        rows.forEach(function(row) {
            if (userId === 'all' || row.getAttribute('data-pic-id') === userId.toString()) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        var noRow = document.getElementById('noTaskFilterRow');
        if (noRow) {
            if (visibleCount === 0 && rows.length > 0) {
                noRow.classList.remove('d-none');
            } else {
                noRow.classList.add('d-none');
            }
        }
        var badgeCount = document.getElementById('countFilteredTasks');
        if (badgeCount) {
            badgeCount.innerText = visibleCount + ' Tugas';
        }
    }
    // Filter Dokumen Pendukung berdasarkan Pengunggah / Peserta Tim
    function filterDocsByUploader(userId) {
        var rows = document.querySelectorAll('.doc-row');
        var visibleCount = 0;
        rows.forEach(function(row) {
            if (userId === 'all' || row.getAttribute('data-uploader-id') === userId.toString()) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        var noRow = document.getElementById('noDocFilterRow');
        if (noRow) {
            if (visibleCount === 0 && rows.length > 0) {
                noRow.classList.remove('d-none');
            } else {
                noRow.classList.add('d-none');
            }
        }
        var badgeCount = document.getElementById('countFilteredDocs');
        if (badgeCount) {
            badgeCount.innerText = visibleCount + ' Dokumen';
        }
    }
    </script>

    <?php include '../includes/footer.php'; ?>
