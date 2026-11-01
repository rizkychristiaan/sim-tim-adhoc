<?php
/**
 * ==============================================================================
 * FILE: app/pages/download_sk.php (Unduh Dokumen Surat Tugas / SK format Word .doc)
 * ROLE AKSES: Admin, Ketua Tim, Anggota Tim
 * FUNGSI UTAMA:
 *   Menghasilkan dokumen resmi Surat Keputusan (SK) Rektor Tim Ad Hoc LPAIP UKDW
 *   dalam format Microsoft Word (.doc) lengkap dengan gambar kop logo UKDW
 *   di kiri atas dan banner footer UKDW di bagian bawah.
 *   Menggunakan format MHTML berstandar Office agar gambar tersemat (embedded)
 *   langsung di dalam file dokumen tanpa memerlukan koneksi internet.
 * ==============================================================================
 */

require_once __DIR__ . '/../config/database.php';
check_login();

$tim_id = intval($_GET['id'] ?? 0);
if ($tim_id <= 0) {
    $_SESSION['flash_error'] = "ID Tim Ad Hoc tidak valid!";
    header("Location: dashboard.php");
    exit();
}

// 1. Ambil data Tim Ad Hoc
$stmt_tim = $pdo->prepare("SELECT t.*, u.nama as pembuat FROM tim_adhoc t JOIN users u ON t.created_by = u.id WHERE t.id = ?");
$stmt_tim->execute([$tim_id]);
$tim = $stmt_tim->fetch();

if (!$tim) {
    $_SESSION['flash_error'] = "Data Tim Ad Hoc tidak ditemukan!";
    header("Location: dashboard.php");
    exit();
}

// 2. Ambil data Anggota Tim
$stmt_m = $pdo->prepare("
    SELECT a.peran_tim, u.nama, u.nip_nim, u.jabatan_struktural
    FROM anggota_tim a
    JOIN users u ON a.user_id = u.id
    WHERE a.tim_id = ?
    ORDER BY FIELD(a.peran_tim, 'Ketua', 'Anggota'), u.nama ASC
");
$stmt_m->execute([$tim_id]);
$members = $stmt_m->fetchAll();

// 3. Ambil data Tugas Tim untuk Lampiran
$stmt_tasks = $pdo->prepare("
    SELECT tg.*, u.nama as pic
    FROM tugas tg
    JOIN users u ON tg.assigned_to = u.id
    WHERE tg.tim_id = ?
    ORDER BY tg.id ASC
");
$stmt_tasks->execute([$tim_id]);
$tasks = $stmt_tasks->fetchAll();

// 4. Helper: Format Tanggal Indonesia
function tgl_surat($tanggal) {
    if (!$tanggal || $tanggal === '0000-00-00') return '-';
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni',
        'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'
    ];
    $p = explode('-', $tanggal);
    return (count($p) === 3) ? ltrim($p[2], '0') . ' ' . $bulan[(int)$p[1]] . ' ' . $p[0] : $tanggal;
}

// Variabel data teks surat
$nomor_sk       = htmlspecialchars($tim['nomor_sk']);
$nama_tim       = htmlspecialchars($tim['nama_tim']);
$nama_tim_upper = strtoupper($nama_tim);
$deskripsi      = htmlspecialchars($tim['deskripsi'] ?? 'pelaksanaan program kerja strategis bidang akademik dan inovasi pendidikan');
$tgl_mulai      = tgl_surat($tim['tanggal_mulai']);
$tgl_selesai    = tgl_surat($tim['tanggal_selesai']);
$tgl_penetapan  = tgl_surat($tim['tanggal_mulai']);

// Nama file download
$nomor_bersih = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $tim['nomor_sk']);
$filename = 'SK_' . $nomor_bersih . '.doc';

// Load gambar & encode ke Base64 untuk MHTML
$img_header_path = __DIR__ . '/../assets/img/header_ukdw.png';
$img_footer_path = __DIR__ . '/../assets/img/footer_ukdw.png';

$b64_header = file_exists($img_header_path) ? base64_encode(file_get_contents($img_header_path)) : '';
$b64_footer = file_exists($img_footer_path) ? base64_encode(file_get_contents($img_footer_path)) : '';

// Susun baris tabel anggota
$rows_anggota = '';
foreach ($members as $i => $m) {
    $no    = $i + 1;
    $peran = htmlspecialchars($m['peran_tim']);
    $nama  = htmlspecialchars($m['nama']);
    $nip   = htmlspecialchars($m['nip_nim']);
    $jab   = htmlspecialchars($m['jabatan_struktural'] ?: 'Staf / Dosen UKDW');
    $rows_anggota .= "
        <tr>
            <td style='text-align:center; border:1px solid #000; padding:5px 6px; font-size:10.5pt;'>$no.</td>
            <td style='border:1px solid #000; padding:5px 6px; font-size:10.5pt;'><b>$peran</b></td>
            <td style='border:1px solid #000; padding:5px 6px; font-size:10.5pt;'><b>$nama</b><br><span style='font-size:9.5pt; color:#333;'>NIP: $nip</span></td>
            <td style='border:1px solid #000; padding:5px 6px; font-size:10.5pt;'>$jab</td>
        </tr>";
}

// Susun daftar tugas untuk lampiran
$tugas_list_html = '';
if (!empty($tasks)) {
    $tugas_list_html .= "<ol style='margin-left: 20pt; padding-left: 0; line-height: 1.5;'>";
    foreach ($tasks as $t) {
        $j_tugas = htmlspecialchars($t['judul_tugas']);
        $d_tugas = htmlspecialchars($t['deskripsi_tugas'] ?? '');
        $pic_nama = htmlspecialchars($t['pic']);
        $dl = tgl_surat($t['deadline']);
        
        $tugas_list_html .= "<li style='margin-bottom: 7pt;'>";
        $tugas_list_html .= "<b>$j_tugas</b> &nbsp;(Penanggung Jawab: <u>$pic_nama</u>)<br>";
        if (!empty($d_tugas)) {
            $tugas_list_html .= "<span style='font-size:10.5pt; color:#222;'>$d_tugas</span><br>";
        }
        $tugas_list_html .= "<span style='font-size:10pt; color:#555;'>Batas Waktu: $dl</span>";
        $tugas_list_html .= "</li>";
    }
    $tugas_list_html .= "</ol>";
} else {
    $tugas_list_html .= "
    <ol style='margin-left: 20pt; padding-left: 0; line-height: 1.5;'>
        <li style='margin-bottom: 6pt;'>Melaksanakan seluruh koordinasi dan kegiatan operasional Tim $nama_tim sesuai arahan pimpinan LPAIP UKDW.</li>
        <li style='margin-bottom: 6pt;'>Menyusun instrumen, dokumen teknis, dan berita acara pelaksanaan kegiatan.</li>
        <li style='margin-bottom: 6pt;'>Menyusun serta mengumpulkan Laporan Pertanggungjawaban (LPJ) akhir setelah seluruh agenda kegiatan tim selesai dilaksanakan.</li>
    </ol>";
}

// Bangun HTML Isi Dokumen
$html_content = '<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Surat Keputusan Rektor - ' . $nomor_sk . '</title>
<style>
  @page {
    size: A4 portrait;
    margin: 1.8cm 2.2cm 1.8cm 2.2cm;
  }
  body {
    font-family: "Times New Roman", Times, serif;
    font-size: 11.5pt;
    color: #000000;
    line-height: 1.35;
  }
  p { margin: 0 0 5pt 0; text-align: justify; }
  .kop-img {
    margin-bottom: 12pt;
    text-align: left;
  }
  .sk-title {
    text-align: center;
    font-weight: bold;
    font-size: 12.5pt;
    text-transform: uppercase;
    margin-top: 6pt;
    margin-bottom: 2pt;
  }
  .sk-nomor {
    text-align: center;
    font-weight: bold;
    font-size: 11.5pt;
    margin-bottom: 6pt;
  }
  .sk-tentang-label {
    text-align: center;
    font-weight: bold;
    font-size: 11.5pt;
    margin-bottom: 3pt;
  }
  .sk-tentang-isi {
    text-align: center;
    font-weight: bold;
    font-size: 12.5pt;
    text-transform: uppercase;
    margin-bottom: 12pt;
  }
  .sk-pejabat {
    text-align: center;
    font-weight: bold;
    font-size: 11.5pt;
    text-transform: uppercase;
    margin-bottom: 12pt;
  }
  .tbl-konsiderans {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 8pt;
  }
  .tbl-konsiderans td {
    vertical-align: top;
    padding: 2pt 3pt;
    font-size: 11pt;
  }
  .tbl-konsiderans td.label {
    width: 17%;
    font-weight: bold;
  }
  .tbl-konsiderans td.colon {
    width: 3%;
    text-align: center;
    font-weight: bold;
  }
  .tbl-konsiderans td.content {
    width: 80%;
    text-align: justify;
  }
  .memutuskan-box {
    text-align: center;
    font-weight: bold;
    font-size: 11.5pt;
    letter-spacing: 1px;
    margin: 10pt 0 8pt 0;
  }
  .tbl-anggota {
    width: 100%;
    border-collapse: collapse;
    margin: 6pt 0 10pt 0;
  }
  .tbl-anggota th {
    background-color: #f2f2f2;
    border: 1px solid #000;
    padding: 5pt 6pt;
    text-align: center;
    font-size: 10.5pt;
    font-weight: bold;
  }
  .page-break {
    page-break-before: always;
  }
  .ttd-table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 14pt;
  }
  .tembusan-area {
    margin-top: 16pt;
    font-size: 9.5pt;
  }
  .tembusan-title {
    font-weight: bold;
    text-decoration: underline;
    margin-bottom: 2pt;
  }
  .footer-banner {
    margin-top: 14pt;
    text-align: center;
  }
</style>
</head>
<body>

<!-- ======================= HALAMAN 1 ======================= -->
<div class="kop-img">
  <img src="header_ukdw.png" width="310" height="73" alt="Universitas Kristen Duta Wacana Yogyakarta">
</div>

<div class="sk-title">KEPUTUSAN REKTOR UNIVERSITAS KRISTEN DUTA WACANA</div>
<div class="sk-nomor">NOMOR : ' . $nomor_sk . '</div>
<div class="sk-tentang-label">TENTANG</div>
<div class="sk-tentang-isi">PENGANGKATAN ' . $nama_tim_upper . '</div>

<div class="sk-pejabat">REKTOR UNIVERSITAS KRISTEN DUTA WACANA</div>

<table class="tbl-konsiderans">
  <tr>
    <td class="label">Membaca</td>
    <td class="colon">:</td>
    <td class="content">
      Surat Ketua Lembaga Pengembangan Akademik dan Inovasi Pembelajaran (LPAIP) perihal
      Permohonan Penerbitan Surat Keputusan Rektor tentang Pembentukan dan Pengangkatan ' . $nama_tim . '.
    </td>
  </tr>
  <tr>
    <td class="label">Menimbang</td>
    <td class="colon">:</td>
    <td class="content">
      <table style="width:100%; border-collapse:collapse;">
        <tr>
          <td style="width:5%; vertical-align:top;">a.</td>
          <td style="text-align:justify; vertical-align:top;">
            Bahwa untuk mendukung kelancaran program kerja dan kegiatan akademik di lingkungan LPAIP UKDW, khususnya mengenai <i>' . $deskripsi . '</i>, diperlukan tim kerja yang terstruktur;
          </td>
        </tr>
        <tr>
          <td style="width:5%; vertical-align:top;">b.</td>
          <td style="text-align:justify; vertical-align:top;">
            Bahwa demi terwujudnya efektivitas dan koordinasi pelaksanaan kegiatan, dipandang perlu mengangkat personil yang memiliki kompetensi sesuai bidang tugasnya;
          </td>
        </tr>
        <tr>
          <td style="width:5%; vertical-align:top;">c.</td>
          <td style="text-align:justify; vertical-align:top;">
            Bahwa berdasarkan pertimbangan pada huruf a dan b di atas, perlu menetapkan Keputusan Rektor tentang Pengangkatan ' . $nama_tim . '.
          </td>
        </tr>
      </table>
    </td>
  </tr>
  <tr>
    <td class="label">Mengingat</td>
    <td class="colon">:</td>
    <td class="content">
      <table style="width:100%; border-collapse:collapse;">
        <tr><td style="width:5%; vertical-align:top;">1.</td><td style="text-align:justify;">Undang-Undang Nomor 20 Tahun 2003 tentang Sistem Pendidikan Nasional;</td></tr>
        <tr><td style="width:5%; vertical-align:top;">2.</td><td style="text-align:justify;">Undang-Undang Nomor 12 Tahun 2012 tentang Pendidikan Tinggi;</td></tr>
        <tr><td style="width:5%; vertical-align:top;">3.</td><td style="text-align:justify;">Peraturan Pemerintah Nomor 4 Tahun 2014 tentang Penyelenggaraan Pendidikan Tinggi dan Pengelolaan Perguruan Tinggi;</td></tr>
        <tr><td style="width:5%; vertical-align:top;">4.</td><td style="text-align:justify;">Peraturan Menteri Pendidikan, Kebudayaan, Riset, dan Teknologi Nomor 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi;</td></tr>
        <tr><td style="width:5%; vertical-align:top;">5.</td><td style="text-align:justify;">Anggaran Dasar dan Anggaran Rumah Tangga Yayasan Perguruan Tinggi Kristen Duta Wacana;</td></tr>
        <tr><td style="width:5%; vertical-align:top;">6.</td><td style="text-align:justify;">Statuta Universitas Kristen Duta Wacana.</td></tr>
      </table>
    </td>
  </tr>
</table>

<div class="memutuskan-box">MEMUTUSKAN</div>

<table class="tbl-konsiderans">
  <tr>
    <td class="label">Menetapkan</td>
    <td class="colon">:</td>
    <td class="content">
      <b>KEPUTUSAN REKTOR TENTANG PENGANGKATAN ' . $nama_tim_upper . '</b>
    </td>
  </tr>
</table>

<!-- GAMBAR FOOTER HALAMAN 1 -->
<div class="footer-banner">
  <img src="footer_ukdw.png" width="600" height="80" alt="Footer UKDW">
</div>

<!-- ======================= HALAMAN 2 ======================= -->
<div class="page-break"></div>

<table class="tbl-konsiderans" style="margin-top: 10pt;">
  <tr>
    <td class="label">KESATU</td>
    <td class="colon">:</td>
    <td class="content">
      Terhitung mulai tanggal <b>' . $tgl_mulai . '</b> sampai dengan <b>' . $tgl_selesai . '</b> mengangkat nama-nama di bawah ini sebagai <b>' . $nama_tim . '</b>, sebagai berikut:
    </td>
  </tr>
</table>

<table class="tbl-anggota">
  <thead>
    <tr>
      <th style="width:6%;">No</th>
      <th style="width:24%;">Kedudukan dalam Tim</th>
      <th style="width:40%;">Nama &amp; NIP</th>
      <th style="width:30%;">Jabatan Struktural / Unit</th>
    </tr>
  </thead>
  <tbody>
    ' . $rows_anggota . '
  </tbody>
</table>

<table class="tbl-konsiderans">
  <tr>
    <td class="label">KEDUA</td>
    <td class="colon">:</td>
    <td class="content">
      ' . $nama_tim . ' sebagaimana dimaksud pada Diktum KESATU Keputusan ini memiliki tugas dan tanggung jawab sebagaimana terlampir dalam Lampiran Keputusan ini.
    </td>
  </tr>
  <tr>
    <td class="label">KETIGA</td>
    <td class="colon">:</td>
    <td class="content">
      Dalam menjalankan tugas dan tanggung jawabnya, tim bertanggung jawab kepada Rektor melalui Kepala Lembaga Pengembangan Akademik dan Inovasi Pendidikan (LPAIP) UKDW.
    </td>
  </tr>
  <tr>
    <td class="label">KEEMPAT</td>
    <td class="colon">:</td>
    <td class="content">
      Segala sesuatu akan disesuaikan dan dibetulkan sebagaimana mestinya apabila di kemudian hari terdapat kekeliruan dalam Keputusan ini.
    </td>
  </tr>
</table>

<table class="ttd-table">
  <tr>
    <td style="width: 52%;"></td>
    <td style="width: 48%; text-align: left; padding-left: 15pt;">
      <div>Ditetapkan di: Yogyakarta</div>
      <div>Pada tanggal: ' . $tgl_penetapan . '</div>
      <div style="margin-top: 4pt; font-weight: bold;">Rektor,</div>
      <div style="height: 55pt;"></div>
      <div style="font-weight: bold; text-decoration: underline;">Dr.-Ing. Wiyatiningsih, S.T., M.T.</div>
      <div style="font-size: 9.5pt; color: #444;">NIP. 19700810 199703 2 001</div>
    </td>
  </tr>
</table>

<div class="tembusan-area">
  <div class="tembusan-title">Tembusan disampaikan kepada Yth. :</div>
  <ol style="margin: 0; padding-left: 14pt; line-height: 1.35;">
    <li>Pengurus Yayasan Perguruan Tinggi Kristen Duta Wacana;</li>
    <li>Para Wakil Rektor Universitas Kristen Duta Wacana;</li>
    <li>Para Dekan Fakultas di lingkungan Universitas Kristen Duta Wacana;</li>
    <li>Kepala Lembaga Pengembangan Akademik dan Inovasi Pendidikan (LPAIP);</li>
    <li>Yang bersangkutan untuk diketahui dan dilaksanakan;</li>
    <li>Arsip.</li>
  </ol>
</div>

<!-- ======================= HALAMAN 3 (LAMPIRAN) ======================= -->
<div class="page-break"></div>

<div style="text-align: left; font-size: 10.5pt; margin-bottom: 10pt;">
  <b>LAMPIRAN KEPUTUSAN REKTOR UNIVERSITAS KRISTEN DUTA WACANA</b><br>
  NOMOR : <b>' . $nomor_sk . '</b><br>
  TANGGAL : <b>' . $tgl_penetapan . '</b><br>
  TENTANG : <b>PENGANGKATAN ' . $nama_tim_upper . '</b>
</div>

<hr style="border: 0; border-top: 1.5pt solid #000; margin-bottom: 12pt;">

<div style="text-align: center; font-weight: bold; font-size: 11.5pt; text-transform: uppercase; margin-bottom: 12pt;">
  RINCIAN TUGAS POKOK DAN PENUGASAN KERJA TIM
</div>

<p style="margin-bottom: 8pt;">
  Berikut adalah rincian tugas pokok, pembagian peran kerja, dan target penugasan bagi masing-masing anggota <b>' . $nama_tim . '</b>:
</p>

' . $tugas_list_html . '

<table class="ttd-table" style="margin-top: 20pt;">
  <tr>
    <td style="width: 52%;"></td>
    <td style="width: 48%; text-align: left; padding-left: 15pt;">
      <div>Ditetapkan di: Yogyakarta</div>
      <div>Pada tanggal: ' . $tgl_penetapan . '</div>
      <div style="margin-top: 4pt; font-weight: bold;">Rektor,</div>
      <div style="height: 50pt;"></div>
      <div style="font-weight: bold; text-decoration: underline;">Dr.-Ing. Wiyatiningsih, S.T., M.T.</div>
    </td>
  </tr>
</table>

</body>
</html>';

// 5. Rakit Dokumen MHTML dengan Gambar Tersemat
$boundary = "----=_NextPart_" . md5(uniqid(rand(), true));

$doc_content = "MIME-Version: 1.0\r\n";
$doc_content .= "Content-Type: multipart/related; boundary=\"$boundary\"\r\n\r\n";

// Bagian 1: Dokumen HTML
$doc_content .= "--$boundary\r\n";
$doc_content .= "Content-Type: text/html; charset=\"utf-8\"\r\n\r\n";
$doc_content .= $html_content . "\r\n\r\n";

// Bagian 2: Gambar Header
if (!empty($b64_header)) {
    $doc_content .= "--$boundary\r\n";
    $doc_content .= "Content-Location: header_ukdw.png\r\n";
    $doc_content .= "Content-Transfer-Encoding: base64\r\n";
    $doc_content .= "Content-Type: image/png\r\n\r\n";
    $doc_content .= chunk_split($b64_header) . "\r\n";
}

// Bagian 3: Gambar Footer
if (!empty($b64_footer)) {
    $doc_content .= "--$boundary\r\n";
    $doc_content .= "Content-Location: footer_ukdw.png\r\n";
    $doc_content .= "Content-Transfer-Encoding: base64\r\n";
    $doc_content .= "Content-Type: image/png\r\n\r\n";
    $doc_content .= chunk_split($b64_footer) . "\r\n";
}

$doc_content .= "--$boundary--\r\n";

// 6. Header Unduhan HTTP
header('Content-Type: application/msword');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($doc_content));
header('Cache-Control: max-age=0');

echo $doc_content;
exit();
?>
