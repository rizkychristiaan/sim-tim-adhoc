<?php
/**
 * ==============================================================================
 * FILE: tests/test_email.php (Pengujian Pengiriman Email SMTP & Desain Template)
 * FUNGSI UTAMA:
 *   1. Menguji koneksi SMTP Gmail (timadhoc4@gmail.com).
 *   2. Memilih dan mengirimkan desain template email resmi UKDW tanpa emoji.
 * ==============================================================================
 */

require_once '../app/config/database.php';
require_once '../app/config/email.php';

$terkirim = false;
$hasil = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_tujuan  = trim($_POST['email_tujuan']);
    $nama_tujuan   = trim($_POST['nama_tujuan'] ?? 'Bpk/Ibu Dosen / Staf UKDW');
    $template_type = $_POST['template_type'] ?? 'tugas_baru';

    if (!empty($email_tujuan)) {
        if ($template_type === 'tugas_baru') {
            $subjek = "Penugasan Baru: Penyusunan Borang Akreditasi 2026 - SIM Tim Ad Hoc LPAIP UKDW";
            $konten = "
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
              <tr>
                <td style='padding:11px 16px; width:34%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Tugas</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>Penyusunan Borang Akreditasi Standard 3 &amp; 4</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tim Ad Hoc</td>
                <td style='padding:11px 16px; font-size:14px; color:#1e293b; border-bottom:1px solid #edf2f7;'>Tim Akreditasi Program Studi 2026</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tingkat Prioritas</td>
                <td style='padding:11px 16px; border-bottom:1px solid #edf2f7;'>
                  <span style='display:inline-block; background-color:#fee2e2; color:#b91c1c; border:1px solid #fecaca; padding:3px 12px; border-radius:50px; font-size:12px; font-weight:700;'>Tinggi</span>
                </td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Batas Waktu (Deadline)</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#dc2626; border-bottom:1px solid #edf2f7;'>" . date('d F Y', strtotime('+3 days')) . "</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; vertical-align:top;'>Instruksi / Catatan</td>
                <td style='padding:11px 16px; font-size:13.5px; color:#334151; line-height:1.5;'>Mohon lengkapi data borang dan unggah berkas pendukung bukti fisik pada sistem sebelum batas waktu.</td>
              </tr>
            </table>
            
            <div style='margin-top:16px; padding:12px 16px; background-color:#eff6ff; border-radius:8px; border:1px solid #dbeafe;'>
              <p style='margin:0; font-size:13px; color:#1e40af; line-height:1.5;'>
                <strong>Petunjuk:</strong> Harap segera menindaklanjuti penugasan ini dan laporkan hasil pengerjaan atau berkas bukti melalui menu <strong>Penugasan Kerja</strong> di sistem.
              </p>
            </div>";

            $pesanHTML = templateEmailResmi(
                $nama_tujuan,
                'PENUGASAN KERJA TIM',
                'blue',
                'Penugasan Baru Telah Didelegasikan Kepada Anda',
                $konten,
                'Lihat Penugasan di Sistem'
            );
            $hasil = kirimEmailNotifikasi($email_tujuan, $nama_tujuan, $subjek, $pesanHTML);

        } elseif ($template_type === 'deadline') {
            $subjek = "Pengingat Batas Waktu Tugas: Penyusunan Laporan Evaluasi Diri - SIM Tim Ad Hoc LPAIP UKDW";
            $konten = "
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
              <tr>
                <td style='padding:11px 16px; width:34%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Tugas</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>Penyusunan Laporan Evaluasi Diri (LED)</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tim Ad Hoc</td>
                <td style='padding:11px 16px; font-size:14px; color:#1e293b; border-bottom:1px solid #edf2f7;'>Tim Kurikulum &amp; Evaluasi LPAIP</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Batas Waktu (Deadline)</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#dc2626; border-bottom:1px solid #edf2f7;'>" . date('d F Y', strtotime('+2 days')) . "</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b;'>Status Tenggat</td>
                <td style='padding:11px 16px;'>
                  <span style='display:inline-block; background-color:#fef3c7; color:#92400e; border:1px solid #fde68a; padding:3px 12px; border-radius:50px; font-size:12px; font-weight:700;'>SISA 2 HARI LAGI</span>
                </td>
              </tr>
            </table>
            
            <div style='margin-top:16px; padding:12px 16px; background-color:#eff6ff; border:1px solid #dbeafe; border-radius:8px;'>
              <p style='margin:0; font-size:13px; color:#1e40af; line-height:1.5;'>
                Tenggat waktu pengerjaan tugas ini tersisa <strong>2 hari lagi</strong>. Pastikan dokumen dan progres kerja telah dilaporkan ke dalam sistem tepat waktu.
              </p>
            </div>";

            $pesanHTML = templateEmailResmi(
                $nama_tujuan,
                'PENGINGAT TENGGAT WAKTU',
                'amber',
                'Pengingat Batas Waktu Tugas: Penyusunan Laporan Evaluasi Diri (LED)',
                $konten,
                'Buka &amp; Selesaikan Tugas'
            );
            $hasil = kirimEmailNotifikasi($email_tujuan, $nama_tujuan, $subjek, $pesanHTML);

        } elseif ($template_type === 'deadline_urgent') {
            $subjek = "Peringatan Mendesak: Batas Waktu Tugas Berakhir Hari Ini - SIM Tim Ad Hoc LPAIP UKDW";
            $konten = "
            <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
              <tr>
                <td style='padding:11px 16px; width:34%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Tugas</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>Finalisasi Dokumen Rencana Kerja Tahunan</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tim Ad Hoc</td>
                <td style='padding:11px 16px; font-size:14px; color:#1e293b; border-bottom:1px solid #edf2f7;'>Tim Perencanaan Strategis LPAIP</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Batas Waktu (Deadline)</td>
                <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#b91c1c; border-bottom:1px solid #edf2f7;'>Hari Ini (" . date('d F Y') . ")</td>
              </tr>
              <tr>
                <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b;'>Status Tenggat</td>
                <td style='padding:11px 16px;'>
                  <span style='display:inline-block; background-color:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; padding:3px 12px; border-radius:50px; font-size:12px; font-weight:700;'>DEADLINE HARI INI</span>
                </td>
              </tr>
            </table>
            
            <div style='margin-top:16px; padding:12px 16px; background-color:#fef2f2; border:1px solid #fecaca; border-radius:8px;'>
              <p style='margin:0; font-size:13px; color:#991b1b; line-height:1.5;'>
                <strong>Perhatian:</strong> Batas waktu tugas ini adalah hari ini. Harap segera menyelesaikan dan mengunggah berkas laporan sebelum hari berakhir.
              </p>
            </div>";

            $pesanHTML = templateEmailResmi(
                $nama_tujuan,
                'PERINGATAN DEADLINE MENDESAK',
                'red',
                'Batas Waktu Tugas Berakhir Hari Ini',
                $konten,
                'Segera Selesaikan Tugas'
            );
            $hasil = kirimEmailNotifikasi($email_tujuan, $nama_tujuan, $subjek, $pesanHTML);

        } elseif ($template_type === 'user_baru') {
            $hasil = emailUserBaru($email_tujuan, $nama_tujuan, '71220901', 'Anggota', 'user123');

        } elseif ($template_type === 'approval') {
            $hasil = emailApprovalUser($email_tujuan, $nama_tujuan);

        } else {
            $subjek = !empty($_POST['subjek']) ? trim($_POST['subjek']) : 'Tes Pengiriman Email SIM Tim Ad Hoc LPAIP UKDW';
            $pesan  = !empty($_POST['pesan']) ? trim($_POST['pesan']) : 'Fitur pengiriman email SMTP Gmail berhasil diuji coba.';
            $konten = "
            <div style='padding:16px 20px; background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px;'>
              <p style='margin:0; font-size:14px; color:#334151; line-height:1.6;'>
                " . nl2br(htmlspecialchars($pesan)) . "
              </p>
            </div>";
            $pesanHTML = templateEmailResmi(
                $nama_tujuan,
                'UJI COBA SISTEM',
                'blue',
                $subjek,
                $konten
            );
            $hasil = kirimEmailNotifikasi($email_tujuan, $nama_tujuan, $subjek, $pesanHTML);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Uji Coba Pengiriman & Desain Email - SIM Tim Ad Hoc UKDW</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; background-color: #f1f5f9; }
        .bg-ukdw { background-color: #6b0c22 !important; }
        .text-ukdw { color: #6b0c22 !important; }
        .btn-ukdw { background-color: #6b0c22; color: #fff; border-color: #6b0c22; }
        .btn-ukdw:hover { background-color: #83102b; color: #fff; }
    </style>
</head>
<body class="d-flex align-items-center justify-content-center min-vh-100 p-3">
    <div class="card shadow-lg border-0 rounded-4" style="max-width: 640px; width: 100%;">
        <div class="card-header bg-ukdw text-white p-4 rounded-top-4">
            <div style="color: #fcd34d; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 1.5px;">LPAIP UKDW</div>
            <h4 class="fw-bold mb-1"><i class="bi bi-envelope-paper me-2"></i> Uji Coba & Desain Template Email Resmi</h4>
            <p class="small mb-0 opacity-75">Server SMTP: <code>timadhoc4@gmail.com</code> &bull; UKDW Yogyakarta</p>
        </div>
        <div class="card-body p-4">
            <?php if ($hasil): ?>
                <?php if ($hasil['status']): ?>
                    <div class="alert alert-success alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-check-circle-fill me-2 fs-5 align-middle"></i>
                        <strong>Email Berhasil Terkirim!</strong>
                        <div class="small mt-1 text-success-emphasis">Silakan periksa kotak masuk (inbox) atau folder spam pada email tujuan Anda untuk melihat desain baru.</div>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php else: ?>
                    <div class="alert alert-danger alert-dismissible fade show rounded-3 shadow-sm" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2 fs-5 align-middle"></i>
                        <strong>Gagal Mengirim:</strong> <?= $hasil['pesan'] ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <!-- PANDUAN KODE WARNA PER FUNGSI -->
            <div class="p-3 mb-3 bg-light rounded-3 border">
                <div class="small fw-bold text-secondary text-uppercase mb-2" style="letter-spacing: 0.5px;">
                    <i class="bi bi-palette-fill text-primary me-1"></i> Standar Identitas Warna per Fungsi:
                </div>
                <div class="d-flex flex-wrap gap-2 small">
                    <span class="badge" style="background-color: #1e3a8a; color: #fff; padding: 6px 10px;">Biru Navy &bull; Penugasan Baru</span>
                    <span class="badge" style="background-color: #b45309; color: #fff; padding: 6px 10px;">Amber &bull; Pengingat Deadline</span>
                    <span class="badge" style="background-color: #991b1b; color: #fff; padding: 6px 10px;">Merah &bull; Deadline Mendesak</span>
                    <span class="badge" style="background-color: #6b0c22; color: #fff; padding: 6px 10px;">Maroon UKDW &bull; Kredensial Akun</span>
                    <span class="badge" style="background-color: #065f46; color: #fff; padding: 6px 10px;">Hijau &bull; Approval Akun</span>
                </div>
            </div>

            <form method="POST" action="">
                <div class="mb-3">
                    <label class="form-label fw-semibold">Pilih Desain Template yang Ingin Dilihat: <span class="text-danger">*</span></label>
                    <select name="template_type" id="template_type" class="form-select form-select-lg border-primary-subtle" onchange="toggleTemplateForm()">
                        <option value="tugas_baru" <?= (!isset($_POST['template_type']) || $_POST['template_type'] === 'tugas_baru') ? 'selected' : '' ?>>
                            [Tema Biru Navy] Penugasan Tugas Baru (Delegasi Kerja Tim)
                        </option>
                        <option value="deadline" <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'deadline') ? 'selected' : '' ?>>
                            [Tema Amber Oranye] Pengingat Batas Waktu Tugas (Sisa 2 Hari)
                        </option>
                        <option value="deadline_urgent" <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'deadline_urgent') ? 'selected' : '' ?>>
                            [Tema Merah Crimson] Peringatan Deadline Mendesak (Hari Ini / Terlewat)
                        </option>
                        <option value="user_baru" <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'user_baru') ? 'selected' : '' ?>>
                            [Tema Maroon UKDW] Kredensial Akun Pengguna Baru (Registrasi)
                        </option>
                        <option value="approval" <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'approval') ? 'selected' : '' ?>>
                            [Tema Hijau Emerald] Persetujuan (Approval) Pendaftaran Akun
                        </option>
                        <option value="custom" <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'custom') ? 'selected' : '' ?>>
                            [Tema Bebas] Email Pesan Kustom / Teks Bebas
                        </option>
                    </select>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Alamat Email Tujuan Anda: <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="bi bi-at"></i></span>
                        <input type="email" name="email_tujuan" class="form-control" placeholder="Contoh: namaanda@gmail.com" required value="<?= htmlspecialchars($_POST['email_tujuan'] ?? '') ?>">
                    </div>
                    <small class="text-muted">Masukkan email aktif Anda (misal Gmail) untuk menerima dan melihat hasil desainnya.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold">Nama Lengkap Penerima:</label>
                    <input type="text" name="nama_tujuan" class="form-control" placeholder="Contoh: Dr. Antonius Rachmat C." value="<?= htmlspecialchars($_POST['nama_tujuan'] ?? 'Bpk/Ibu Dosen / Staf') ?>">
                </div>

                <div id="custom_message_box" style="display: <?= (isset($_POST['template_type']) && $_POST['template_type'] === 'custom') ? 'block' : 'none' ?>;">
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Subjek Email:</label>
                        <input type="text" name="subjek" class="form-control" value="Tes Pengiriman Email SIM Tim Ad Hoc LPAIP UKDW">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-semibold">Isi Pesan:</label>
                        <textarea name="pesan" class="form-control" rows="3">Fitur kirim email berhasil diintegrasikan dengan akun timadhoc4@gmail.com!</textarea>
                    </div>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-4 pt-2 border-top">
                    <a href="../app/pages/dashboard.php" class="btn btn-outline-secondary rounded-pill px-4">
                        <i class="bi bi-arrow-left me-1"></i> Dashboard
                    </a>
                    <button type="submit" class="btn btn-ukdw rounded-pill px-4 py-2.5 fw-semibold shadow-sm">
                        <i class="bi bi-send-fill me-1.5"></i> Kirim & Lihat Email Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    function toggleTemplateForm() {
        var val = document.getElementById('template_type').value;
        var customBox = document.getElementById('custom_message_box');
        if (val === 'custom') {
            customBox.style.display = 'block';
        } else {
            customBox.style.display = 'none';
        }
    }
    </script>
</body>
</html>
