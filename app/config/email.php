<?php
/**
 * ==============================================================================
 * FILE: app/config/email.php (Konfigurasi & Engine Desain Email Resmi UKDW)
 * SISTEM: Sistem Informasi Manajemen Tim Ad Hoc LPAIP UKDW
 * WARNA RESMI: UKDW Maroon (#6b0c22) & UKDW Gold (#d97706)
 * STANDAR: Desain Formal Institusi Tanpa Emotikon/Emoji
 * ==============================================================================
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../includes/PHPMailer/Exception.php';
require_once __DIR__ . '/../includes/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../includes/PHPMailer/SMTP.php';

// ==============================================================================
// 1. FUNGSI PENGIRIMAN EMAIL SMTP GMAIL
// ==============================================================================
function kirimEmailNotifikasi($tujuan_email, $tujuan_nama, $subjek, $pesanHTML) {
    if (empty($tujuan_email) || !filter_var($tujuan_email, FILTER_VALIDATE_EMAIL)) {
        return ['status' => false, 'pesan' => 'Alamat email penerima tidak valid atau kosong.'];
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'timadhoc4@gmail.com';
        $mail->Password   = 'xemn ubbq cjko zxnu';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('timadhoc4@gmail.com', 'SIM Tim Ad Hoc LPAIP UKDW');
        $mail->addAddress($tujuan_email, $tujuan_nama);

        $mail->isHTML(true);
        $mail->Subject = $subjek;
        $mail->Body    = $pesanHTML;
        $mail->AltBody = strip_tags(str_replace(['<br>', '</p>', '</div>', '</td>'], ["\n", "\n\n", "\n", " - "], $pesanHTML));

        $mail->send();
        return ['status' => true, 'pesan' => 'Email berhasil dikirimkan ke ' . htmlspecialchars($tujuan_email)];

    } catch (Exception $e) {
        return ['status' => false, 'pesan' => 'Gagal mengirimkan email. Error: ' . $mail->ErrorInfo];
    }
}

// ==============================================================================
// 2. TEMPLATE INDUK RESMI DENGAN TEMA WARNA SESUAI FUNGSI
// ==============================================================================
function templateEmailResmi($nama_penerima, $badge_text, $theme = 'maroon', $judul_pesan, $isi_html, $cta_text = 'Buka Sistem Sekarang', $cta_url = null) {
    if (!$cta_url) {
        $cta_url = 'http://localhost/Skripsi/app/pages/dashboard.php';
    }

    // Definisi palet warna formal institusional per fungsi email
    $palettes = [
        // FUNGSI 1: PENUGASAN TUGAS BARU (Biru Sapphire / Navy Profesional)
        'blue' => [
            'header_bg'    => '#1e3a8a',
            'top_bar'      => '#0284c7',
            'sub_title'    => '#93c5fd',
            'badge_style'  => 'background-color: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe;',
            'btn_bg'       => '#1e40af',
            'btn_shadow'   => 'rgba(30, 64, 175, 0.28)',
        ],
        // FUNGSI 2: PENGINGAT DEADLINE REGULER (Amber / Oranye Hangat)
        'amber' => [
            'header_bg'    => '#92400e',
            'top_bar'      => '#f59e0b',
            'sub_title'    => '#fef08a',
            'badge_style'  => 'background-color: #fffbeb; color: #92400e; border: 1px solid #fde68a;',
            'btn_bg'       => '#b45309',
            'btn_shadow'   => 'rgba(180, 83, 9, 0.28)',
        ],
        // FUNGSI 3: PERINGATAN DEADLINE MENDESAK / HARI H (Merah Crimson)
        'red' => [
            'header_bg'    => '#991b1b',
            'top_bar'      => '#ef4444',
            'sub_title'    => '#fecaca',
            'badge_style'  => 'background-color: #fef2f2; color: #991b1b; border: 1px solid #fca5a5;',
            'btn_bg'       => '#b91c1c',
            'btn_shadow'   => 'rgba(185, 28, 28, 0.28)',
        ],
        // FUNGSI 4: PERSETUJUAN & AKTIVASI AKUN (Hijau Emerald)
        'green' => [
            'header_bg'    => '#065f46',
            'top_bar'      => '#10b981',
            'sub_title'    => '#a7f3d0',
            'badge_style'  => 'background-color: #f0fdf4; color: #065f46; border: 1px solid #bbf7d0;',
            'btn_bg'       => '#065f46',
            'btn_shadow'   => 'rgba(6, 95, 70, 0.28)',
        ],
        // FUNGSI 5: AKUN PENGGUNA BARU / KREDENSIAL RESMI (Maroon Khas UKDW)
        'maroon' => [
            'header_bg'    => '#6b0c22',
            'top_bar'      => '#d97706',
            'sub_title'    => '#fcd34d',
            'badge_style'  => 'background-color: #fdf2f4; color: #6b0c22; border: 1px solid #fecdd3;',
            'btn_bg'       => '#6b0c22',
            'btn_shadow'   => 'rgba(107, 12, 34, 0.28)',
        ],
    ];

    $p = $palettes[$theme] ?? $palettes['maroon'];

    return "
<!DOCTYPE html>
<html lang='id'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <meta name='color-scheme' content='light'>
  <meta name='supported-color-schemes' content='light'>
  <title>" . htmlspecialchars($judul_pesan) . "</title>
</head>
<body style='margin:0; padding:0; background-color:#f1f5f9; font-family: -apple-system, BlinkMacSystemFont, \"Segoe UI\", Roboto, Helvetica, Arial, sans-serif; -webkit-font-smoothing: antialiased;'>

  <!-- WRAPPER UTAMA -->
  <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f1f5f9; padding: 35px 15px;'>
    <tr>
      <td align='center'>
        
        <!-- CARD CONTAINER -->
        <table width='100%' cellpadding='0' cellspacing='0' border='0' style='max-width:580px; background-color:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e2e8f0; box-shadow: 0 4px 18px rgba(0,0,0,0.06);'>
          
          <!-- TOP ACCENT BAR -->
          <tr>
            <td style='background-color:{$p['top_bar']}; height:4px; line-height:4px; font-size:1px;'>&nbsp;</td>
          </tr>

          <!-- HEADER RESMI BERDASARKAN TEMA FUNGSI -->
          <tr>
            <td style='background-color:{$p['header_bg']}; padding: 26px 30px; text-align:center;'>
              <table width='100%' cellpadding='0' cellspacing='0' border='0'>
                <tr>
                  <td align='center'>
                    <div style='color:{$p['sub_title']} !important; font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:1.5px; margin-bottom:5px;'>
                      LPAIP UKDW
                    </div>
                    <h1 style='color:#ffffff !important; margin:0; font-size:19px; font-weight:700; letter-spacing:0.3px; line-height:1.3;'>
                      Sistem Informasi Manajemen Tim Ad Hoc
                    </h1>
                    <div style='color:#e2e8f0 !important; font-size:12px; margin-top:5px; opacity:0.9;'>
                      Universitas Kristen Duta Wacana Yogyakarta
                    </div>
                  </td>
                </tr>
              </table>
            </td>
          </tr>

          <!-- BODY KONTEN -->
          <tr>
            <td style='padding: 32px 30px 24px 30px; background-color:#ffffff;'>
              
              <!-- KATEGORI BADGE -->
              <div style='margin-bottom: 16px;'>
                <span style='display:inline-block; {$p['badge_style']} font-size:11px; font-weight:700; letter-spacing:0.8px; text-transform:uppercase; padding:4px 12px; border-radius:50px;'>
                  {$badge_text}
                </span>
              </div>

              <!-- JUDUL PESAN -->
              <h2 style='color:#0f172a; margin:0 0 14px 0; font-size:18px; font-weight:700; line-height:1.4;'>
                {$judul_pesan}
              </h2>

              <!-- SALAM PEMBUKA -->
              <p style='font-size:14px; color:#475569; margin:0 0 8px 0; line-height:1.6;'>
                Yth. <strong style='color:#0f172a;'>" . htmlspecialchars($nama_penerima) . "</strong>,
              </p>
              <p style='font-size:14px; color:#64748b; margin:0 0 20px 0; line-height:1.6;'>
                Anda menerima pemberitahuan resmi dari Sistem Informasi Manajemen Tim Ad Hoc LPAIP UKDW sebagai berikut:
              </p>

              <!-- KONTEN SPESIFIK -->
              {$isi_html}

              <!-- TOMBOL AKSI (CTA) -->
              <div style='text-align:center; margin-top:30px; margin-bottom:10px;'>
                <a href='{$cta_url}' target='_blank'
                   style='display:inline-block; background-color:{$p['btn_bg']} !important; color:#ffffff !important; text-decoration:none;
                          padding:13px 36px; border-radius:50px; font-size:14px; font-weight:700; letter-spacing:0.3px;
                          box-shadow: 0 4px 12px {$p['btn_shadow']};'>
                  {$cta_text}
                </a>
              </div>

            </td>
          </tr>

          <!-- FOOTER RESMI INSTITUSI -->
          <tr>
            <td style='background-color:#f8fafc; padding: 22px 30px; text-align:center; border-top:1px solid #e2e8f0;'>
              <p style='margin:0 0 4px 0; font-size:12px; font-weight:600; color:#475569;'>
                Lembaga Pengembangan Akademik &amp; Inovasi Pembelajaran (LPAIP)
              </p>
              <p style='margin:0 0 10px 0; font-size:11.5px; color:#64748b;'>
                Universitas Kristen Duta Wacana &bull; Jl. Dr. Wahidin Sudirohusodo No. 5-25, Yogyakarta 55224
              </p>
              <p style='margin:0; font-size:11px; color:#94a3b8; line-height:1.5;'>
                Email ini dikirimkan otomatis oleh sistem. Mohon untuk tidak membalas langsung ke alamat email ini.
              </p>
            </td>
          </tr>

        </table>

      </td>
    </tr>
  </table>

</body>
</html>";
}

// Backward compatibility helper
function templateEmailNotifikasi($nama_penerima, $judul_pesan, $isi_konten, $warna_header = '#6b0c22', $emoji_header = '') {
    return templateEmailResmi($nama_penerima, 'PEMBERITAHUAN TUGAS', 'maroon', $judul_pesan, $isi_konten);
}

// ==============================================================================
// 3. FUNGSI EMAIL NOTIFIKASI TUGAS BARU
// ==============================================================================
function emailTugasBaru($pdo, $assigned_to, $judul_tugas, $deskripsi_tugas, $nama_tim, $prioritas, $deadline) {
    $stmt = $pdo->prepare("SELECT nama, email FROM users WHERE id = ?");
    $stmt->execute([$assigned_to]);
    $penerima = $stmt->fetch();

    if (!$penerima || empty($penerima['email'])) return;

    $prio_styles = [
        'Rendah'   => 'background-color:#dcfce7; color:#15803d; border:1px solid #bbf7d0;',
        'Sedang'   => 'background-color:#fef3c7; color:#b45309; border:1px solid #fde68a;',
        'Tinggi'   => 'background-color:#fee2e2; color:#b91c1c; border:1px solid #fecaca;',
        'Mendesak' => 'background-color:#f3e8ff; color:#7e22ce; border:1px solid #e9d5ff;',
    ];
    $prio_style = $prio_styles[$prioritas] ?? $prio_styles['Sedang'];

    // Format tanggal deadline ke format Indonesia
    $tgl_parts = explode('-', $deadline);
    $bulan_id  = ['', 'Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl_dl    = (count($tgl_parts) === 3) ? ltrim($tgl_parts[2],'0') . ' ' . $bulan_id[(int)$tgl_parts[1]] . ' ' . $tgl_parts[0] : $deadline;

    $has_desk = !empty($deskripsi_tugas);
    $konten = "
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
      <tr>
        <td style='padding:11px 16px; width:34%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Tugas</td>
        <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($judul_tugas) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tim Ad Hoc</td>
        <td style='padding:11px 16px; font-size:14px; color:#1e293b; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($nama_tim) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tingkat Prioritas</td>
        <td style='padding:11px 16px; border-bottom:1px solid #edf2f7;'>
          <span style='display:inline-block; {$prio_style} padding:3px 12px; border-radius:50px; font-size:12px; font-weight:700;'>" . htmlspecialchars($prioritas) . "</span>
        </td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:" . ($has_desk ? "1px solid #edf2f7" : "none") . ";'>Batas Waktu (Deadline)</td>
        <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#dc2626; border-bottom:" . ($has_desk ? "1px solid #edf2f7" : "none") . ";'>" . htmlspecialchars($tgl_dl) . "</td>
      </tr>
      " . ($has_desk ? "
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; vertical-align:top;'>Instruksi / Catatan</td>
        <td style='padding:11px 16px; font-size:13.5px; color:#334151; line-height:1.5;'>" . nl2br(htmlspecialchars($deskripsi_tugas)) . "</td>
      </tr>" : "") . "
    </table>
    
    <div style='margin-top:16px; padding:12px 16px; background-color:#eff6ff; border-radius:8px; border:1px solid #dbeafe;'>
      <p style='margin:0; font-size:13px; color:#1e40af; line-height:1.5;'>
        <strong>Petunjuk Pengerjaan:</strong> Harap segera menindaklanjuti penugasan ini dan laporkan hasil pengerjaan atau berkas bukti melalui menu <strong>Penugasan Kerja</strong> di sistem.
      </p>
    </div>";

    $html = templateEmailResmi(
        $penerima['nama'],
        'PENUGASAN KERJA TIM',
        'blue',
        'Penugasan Baru Telah Didelegasikan Kepada Anda',
        $konten,
        'Lihat Penugasan di Sistem'
    );

    kirimEmailNotifikasi($penerima['email'], $penerima['nama'], "Penugasan Baru: " . $judul_tugas . " - SIM Tim Ad Hoc LPAIP UKDW", $html);
}

// ==============================================================================
// 4. FUNGSI EMAIL PENGINGAT DEADLINE TUGAS
// ==============================================================================
function emailPengingatDeadline($pdo, $notif_id, $user_id, $judul_tugas, $nama_tim, $deadline, $sisa_hari) {
    $stmt = $pdo->prepare("SELECT nama, email FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $penerima = $stmt->fetch();

    if (!$penerima || empty($penerima['email'])) return;

    if ($sisa_hari < 0) {
        $status_label = 'SUDAH MELEWATI DEADLINE';
        $badge_style  = 'background-color:#fee2e2; color:#991b1b; border:1px solid #fca5a5;';
        $alert_bg     = 'background-color:#fef2f2; border:1px solid #fecaca; color:#991b1b;';
        $theme_col    = 'red';
        $pesan_note   = 'Tugas ini telah melewati batas waktu yang ditentukan. Harap segera menyelesaikan dan melapor kepada Ketua Tim.';
    } elseif ($sisa_hari === 0) {
        $status_label = 'DEADLINE HARI INI';
        $badge_style  = 'background-color:#fee2e2; color:#b91c1c; border:1px solid #fca5a5;';
        $alert_bg     = 'background-color:#fffbeb; border:1px solid #fde68a; color:#92400e;';
        $theme_col    = 'red';
        $pesan_note   = 'Batas waktu pengerjaan tugas ini adalah hari ini. Pastikan seluruh berkas bukti pengerjaan telah diunggah sebelum hari berakhir.';
    } else {
        $status_label = "SISA $sisa_hari HARI LAGI";
        $badge_style  = 'background-color:#fef3c7; color:#92400e; border:1px solid #fde68a;';
        $alert_bg     = 'background-color:#eff6ff; border:1px solid #dbeafe; color:#1e40af;';
        $theme_col    = 'amber';
        $pesan_note   = "Tenggat waktu pengerjaan tugas ini tersisa <strong>$sisa_hari hari lagi</strong>. Pastikan progres pengerjaan berjalan sesuai target.";
    }

    $tgl_parts = explode('-', $deadline);
    $bulan_id  = ['','Januari','Februari','Maret','April','Mei','Juni','Juli','Agustus','September','Oktober','November','Desember'];
    $tgl_dl    = (count($tgl_parts) === 3) ? ltrim($tgl_parts[2],'0') . ' ' . $bulan_id[(int)$tgl_parts[1]] . ' ' . $tgl_parts[0] : $deadline;

    $konten = "
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
      <tr>
        <td style='padding:11px 16px; width:34%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Tugas</td>
        <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($judul_tugas) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Tim Ad Hoc</td>
        <td style='padding:11px 16px; font-size:14px; color:#1e293b; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($nama_tim) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Batas Waktu (Deadline)</td>
        <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#dc2626; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($tgl_dl) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b;'>Status Tenggat</td>
        <td style='padding:11px 16px;'>
          <span style='display:inline-block; {$badge_style} padding:3px 12px; border-radius:50px; font-size:12px; font-weight:700;'>{$status_label}</span>
        </td>
      </tr>
    </table>
    
    <div style='margin-top:16px; padding:12px 16px; {$alert_bg} border-radius:8px;'>
      <p style='margin:0; font-size:13px; line-height:1.5;'>
        {$pesan_note}
      </p>
    </div>";

    $html = templateEmailResmi(
        $penerima['nama'],
        'PENGINGAT TENGGAT WAKTU',
        $theme_col,
        'Pengingat Batas Waktu Tugas: ' . $judul_tugas,
        $konten,
        'Buka &amp; Selesaikan Tugas'
    );

    $subjek_email = "Pengingat Batas Waktu Tugas: " . $judul_tugas . " - SIM Tim Ad Hoc LPAIP UKDW";
    $result = kirimEmailNotifikasi($penerima['email'], $penerima['nama'], $subjek_email, $html);

    if ($result['status'] && $notif_id > 0) {
        $pdo->prepare("UPDATE notifikasi SET email_sent = 1 WHERE id = ?")->execute([$notif_id]);
    }
}

// ==============================================================================
// 5. FUNGSI EMAIL KREDENSIAL AKUN PENGGUNA BARU
// ==============================================================================
function emailUserBaru($email, $nama, $nip_nim, $role, $pass_plain) {
    $login_url = 'http://localhost/Skripsi/app/auth/login.php';

    $konten = "
    <table width='100%' cellpadding='0' cellspacing='0' border='0' style='background-color:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; overflow:hidden;'>
      <tr>
        <td style='padding:11px 16px; width:36%; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nama Lengkap</td>
        <td style='padding:11px 16px; font-size:14px; font-weight:700; color:#0f172a; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($nama) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Nomor NIP / NIM</td>
        <td style='padding:11px 16px; font-size:14px; color:#1e293b; font-family:monospace; font-weight:600; border-bottom:1px solid #edf2f7;'>" . htmlspecialchars($nip_nim) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Email (Username)</td>
        <td style='padding:11px 16px; font-size:14px; color:#1e293b; font-weight:600; border-bottom:1px solid #edf2f7; word-break:break-all;'>" . htmlspecialchars($email) . "</td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b; border-bottom:1px solid #edf2f7;'>Role Sistem</td>
        <td style='padding:11px 16px; border-bottom:1px solid #edf2f7;'>
          <span style='display:inline-block; background-color:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe; padding:2px 10px; border-radius:50px; font-size:12px; font-weight:700; text-transform:uppercase;'>" . htmlspecialchars($role) . "</span>
        </td>
      </tr>
      <tr>
        <td style='padding:11px 16px; font-size:13px; font-weight:600; color:#64748b;'>Password Sementara</td>
        <td style='padding:11px 16px;'>
          <span style='display:inline-block; background-color:#ffffff; color:#6b0c22; border:1.5px dashed #6b0c22; padding:4px 12px; border-radius:6px; font-family:monospace; font-size:14px; font-weight:700; letter-spacing:1px;'>" . htmlspecialchars($pass_plain) . "</span>
        </td>
      </tr>
    </table>
    
    <div style='margin-top:16px; padding:12px 16px; background-color:#fffbeb; border-radius:8px; border:1px solid #fef3c7;'>
      <p style='margin:0; font-size:13px; color:#92400e; line-height:1.5;'>
        <strong>Keamanan Akun:</strong> Demi menjaga kerahasiaan data Anda, harap segera login dan perbarui password sementara Anda melalui menu <strong>Profil Saya</strong> setelah berhasil masuk ke sistem.
      </p>
    </div>";

    $html = templateEmailResmi(
        $nama,
        'KREDENSIAL AKUN PENGGUNA',
        'maroon',
        'Pemberitahuan Pendaftaran Akun Pengguna Baru',
        $konten,
        'Login ke Sistem Sekarang',
        $login_url
    );

    return kirimEmailNotifikasi($email, $nama, "Informasi Kredensial Akun Pengguna - SIM Tim Ad Hoc LPAIP UKDW", $html);
}

// ==============================================================================
// 6. FUNGSI EMAIL PERSETUJUAN (APPROVAL) AKUN
// ==============================================================================
function emailApprovalUser($email, $nama) {
    $login_url = 'http://localhost/Skripsi/app/auth/login.php';

    $konten = "
    <div style='padding:16px 20px; background-color:#f0fdf4; border:1px solid #bbf7d0; border-radius:8px; margin-bottom:18px;'>
      <p style='margin:0 0 6px 0; font-size:14px; font-weight:700; color:#15803d;'>
        Status Akun: DISETUJUI &amp; AKTIF
      </p>
      <p style='margin:0; font-size:13.5px; color:#166534; line-height:1.5;'>
        Pendaftaran akun Anda telah diverifikasi dan disetujui secara resmi oleh Admin LPAIP UKDW. Anda sekarang dapat mengakses seluruh fitur ruang kerja tim ad hoc sesuai peran Anda.
      </p>
    </div>
    <p style='font-size:13.5px; color:#64748b; line-height:1.5; margin:0;'>
      Silakan klik tombol di bawah ini untuk langsung menuju halaman login menggunakan email dan password yang telah Anda daftarkan.
    </p>";

    $html = templateEmailResmi(
        $nama,
        'PERSETUJUAN PENDAFTARAN AKUN',
        'green',
        'Pendaftaran Akun Anda Telah Disetujui',
        $konten,
        'Masuk ke Sistem Sekarang',
        $login_url
    );

    return kirimEmailNotifikasi($email, $nama, "Persetujuan Pendaftaran Akun - SIM Tim Ad Hoc LPAIP UKDW", $html);
}
?>
