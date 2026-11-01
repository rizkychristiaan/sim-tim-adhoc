import asyncio
import os
from playwright.async_api import async_playwright

HTML_TEMPLATE = """<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<style>
  * { box-sizing: border-box; margin: 0; padding: 0; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
  body { background-color: #ffffff; padding: 25px; display: flex; justify-content: center; }
  .diagram-container { width: 1100px; border: 1.5px solid #222; border-radius: 8px; overflow: hidden; background: #fff; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
  .diagram-title { text-align: center; font-size: 19px; font-weight: 700; padding: 14px; border-bottom: 1.5px solid #222; background: #fafafa; letter-spacing: 0.5px; }
  .swimlanes { display: flex; width: 100%; position: relative; min-height: __MIN_HEIGHT__px; }
  .lane { flex: 1; border-right: 1.5px solid #222; position: relative; }
  .lane:last-child { border-right: none; }
  .lane-header { font-weight: 700; font-size: 15.5px; text-align: center; padding: 11px; border-bottom: 1.5px solid #222; }
  
  .lane-user .lane-header { background-color: #d7ecd9; color: #1e4620; }
  .lane-system .lane-header { background-color: #dbeafe; color: #1e3a8a; }
  .lane-db .lane-header { background-color: #fef3c7; color: #78350f; }

  /* Diagram Elements */
  .node-initial { width: 24px; height: 24px; background: #111; border-radius: 50%; position: absolute; left: 50%; transform: translateX(-50%); }
  .node-final { width: 28px; height: 28px; border: 2px solid #111; border-radius: 50%; position: absolute; left: 50%; transform: translateX(-50%); display: flex; align-items: center; justify-content: center; }
  .node-final-inner { width: 16px; height: 16px; background: #111; border-radius: 50%; }

  .act-box {
    position: absolute; width: 260px; left: 50%; transform: translateX(-50%);
    padding: 10px 14px; border-radius: 8px; font-size: 13px; text-align: center;
    font-weight: 600; line-height: 1.35; border: 1.5px solid #222; z-index: 10;
  }
  .box-green { background: #e8f5e9; color: #1b5e20; border-color: #2e7d32; }
  .box-blue { background: #eff6ff; color: #1e40af; border-color: #2563eb; }
  .box-yellow { background: #fefce8; color: #854d0e; border-color: #d97706; }
  .box-red { background: #fee2e2; color: #991b1b; border-color: #dc2626; }

  /* Diamond Decision */
  .decision {
    position: absolute; width: 125px; height: 70px; left: 50%; transform: translateX(-50%);
    background: #e0f2fe; border: 1.5px solid #0284c7; z-index: 10;
    clip-path: polygon(50% 0%, 100% 50%, 50% 100%, 0% 50%);
    display: flex; align-items: center; justify-content: center; text-align: center;
    box-shadow: 0 0 0 1px #0284c7;
  }
  .decision-inner { font-size: 11.5px; font-weight: 700; color: #0369a1; padding: 4px 12px; line-height: 1.2; }

  svg.arrows { position: absolute; top: 0; left: 0; width: 100%; height: 100%; pointer-events: none; z-index: 5; }
  .branch-label { font-size: 11.5px; font-weight: 700; fill: #333; }
</style>
</head>
<body>
<div class="diagram-container">
  <div class="diagram-title">__TITLE__</div>
  <div class="swimlanes">
    <svg class="arrows">
      <defs>
        <marker id="arrow" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#222" />
        </marker>
        <marker id="arrow-red" viewBox="0 0 10 10" refX="7" refY="5" markerWidth="6" markerHeight="6" orient="auto-start-reverse">
          <path d="M 0 1.5 L 8 5 L 0 8.5 z" fill="#dc2626" />
        </marker>
      </defs>
      __SVG_CONTENT__
    </svg>

    <div class="lane lane-user">
      <div class="lane-header">__LANE1_TITLE__</div>
      __LANE1_CONTENT__
    </div>

    <div class="lane lane-system">
      <div class="lane-header">Sistem Web (PHP)</div>
      __LANE2_CONTENT__
    </div>

    <div class="lane lane-db">
      <div class="lane-header">Database MySQL</div>
      __LANE3_CONTENT__
    </div>
  </div>
</div>
</body>
</html>
"""

# Diagrams definitions:
diagrams = [
    {
        "id": "diagram_2_register",
        "title": "2. ACTIVITY DIAGRAM: REGISTRASI PENGGUNA BARU (register.php)",
        "min_height": 880,
        "lane1_title": "Pengguna Baru",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 155 L 420 155" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="180" x2="550" y2="215" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 250 L 313 250" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="242" class="branch-label" fill="#dc2626">Tidak</text>
          <path d="M 615 250 L 787 250" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="242" class="branch-label">Ya</text>
          <line x1="917" y1="275" x2="917" y2="330" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 355 L 680 355" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="380" x2="550" y2="420" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 455 L 313 455" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="447" class="branch-label" fill="#dc2626">Ya</text>
          <line x1="550" y1="490" x2="550" y2="535" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="560" y="515" class="branch-label">Tidak</text>
          <path d="M 680 560 L 787 560" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="588" x2="917" y2="645" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 670 L 680 670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 670 L 313 670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="700" x2="183" y2="760" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Halaman Register &<br>Input Data (NIP/NIM, Nama, Email, Password)
          </div>
          <div class="act-box box-red" style="top: 225px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Form Belum Lengkap")
          </div>
          <div class="act-box box-red" style="top: 430px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Email atau NIP/NIM Sudah Terdaftar")
          </div>
          <div class="act-box box-green" style="top: 645px;">
            Melihat Notifikasi Sukses &<br>Menunggu Persetujuan Admin
          </div>
          <div class="node-final" style="top: 760px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 130px;">
            Validasi Kelengkapan Format Input
          </div>
          <div class="decision" style="top: 215px;">
            <div class="decision-inner">Format<br>Valid?</div>
          </div>
          <div class="act-box box-blue" style="top: 330px;">
            Analisis Hasil Pengecekan Akun
          </div>
          <div class="decision" style="top: 420px;">
            <div class="decision-inner">Email / NIP<br>Terdaftar?</div>
          </div>
          <div class="act-box box-blue" style="top: 535px;">
            Hash Password (BCRYPT) &<br>Set Status Akun = 0 (Pending)
          </div>
          <div class="act-box box-blue" style="top: 645px;">
            Set Flash Message Sukses &<br>Redirect ke Halaman Login
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 225px;">
            Query Cek Duplikasi Email & NIP<br>(SELECT id FROM users ...)
          </div>
          <div class="act-box box-yellow" style="top: 330px;">
            Return Hasil Pengecekan<br>(Ditemukan / Tidak)
          </div>
          <div class="act-box box-yellow" style="top: 535px;">
            Insert Data Pengguna Baru<br>(INSERT INTO users ...)
          </div>
          <div class="act-box box-yellow" style="top: 645px;">
            Return Status Sukses Penyimpanan
          </div>
        """
    },
    {
        "id": "diagram_3_approval",
        "title": "3. ACTIVITY DIAGRAM: APPROVAL AKUN PENGGUNA (kelola_user.php)",
        "min_height": 780,
        "lane1_title": "Admin",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="180" x2="183" y2="225" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 250 L 420 250" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="275" x2="550" y2="315" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 615 350 L 787 325" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="325" class="branch-label">Setujui</text>
          <path d="M 615 350 L 787 395" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="380" class="branch-label">Tolak</text>
          <path d="M 787 360 L 680 445" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="475" x2="550" y2="520" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="570" x2="550" y2="615" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 640 L 313 640" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="670" x2="183" y2="720" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Buka Menu "Kelola Pengguna" &<br>Lihat Daftar Akun Pending (status = 0)
          </div>
          <div class="act-box box-green" style="top: 225px;">
            Klik Tombol "Setujui" (Approve)<br>atau "Tolak" (Decline)
          </div>
          <div class="act-box box-green" style="top: 615px;">
            Melihat Tabel Pengguna Terupdate &<br>Alert Konfirmasi Sukses
          </div>
          <div class="node-final" style="top: 720px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 225px;">
            Menerima Request Aksi & ID User
          </div>
          <div class="decision" style="top: 315px;">
            <div class="decision-inner">Pilihan<br>Aksi?</div>
          </div>
          <div class="act-box box-blue" style="top: 420px;">
            Kirim Notifikasi Email Status Akun<br>ke User via SMTP (PHPMailer)
          </div>
          <div class="act-box box-blue" style="top: 520px;">
            Update Notifikasi Internal Sistem
          </div>
          <div class="act-box box-blue" style="top: 615px;">
            Reload Tampilan Halaman Pengguna
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 300px;">
            Update Status Pengguna Aktif<br>(UPDATE users SET status = 1 ...)
          </div>
          <div class="act-box box-yellow" style="top: 380px;">
            Hapus Record Akun Ditolak<br>(DELETE FROM users WHERE id = ...)
          </div>
        """
    },
    {
        "id": "diagram_4_buat_tim",
        "title": "4. ACTIVITY DIAGRAM: PEMBUATAN TIM AD HOC BARU (tim_tambah.php)",
        "min_height": 780,
        "lane1_title": "Admin / Pengguna Pembuat",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="180" x2="183" y2="225" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 255 L 420 255" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="280" x2="550" y2="320" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 355 L 313 355" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="347" class="branch-label" fill="#dc2626">Tidak</text>
          <path d="M 615 355 L 787 355" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="347" class="branch-label">Lengkap</text>
          <line x1="917" y1="385" x2="917" y2="445" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="500" x2="917" y2="550" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 575 L 680 575" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 575 L 313 575" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="605" x2="183" y2="670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Menu "Tim Ad Hoc" &<br>Klik Tombol "Tambah Tim"
          </div>
          <div class="act-box box-green" style="top: 225px;">
            Input Form: Nama Tim, Deskripsi, Tanggal Mulai &amp; Selesai, Nomor SK
          </div>
          <div class="act-box box-red" style="top: 330px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Data Form Wajib Diisi Lengkap")
          </div>
          <div class="act-box box-green" style="top: 550px;">
            Dialihkan ke Halaman Detail Tim Baru<br>(tim_detail.php)
          </div>
          <div class="node-final" style="top: 670px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 225px;">
            Validasi Kelengkapan Data Form
          </div>
          <div class="decision" style="top: 320px;">
            <div class="decision-inner">Form<br>Lengkap?</div>
          </div>
          <div class="act-box box-blue" style="top: 550px;">
            Redirect ke Halaman Detail Tim<br>(tim_detail.php?id=...)
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 330px;">
            Simpan Entitas Tim Ad Hoc Baru<br>(INSERT INTO tim_adhoc ...)
          </div>
          <div class="act-box box-yellow" style="top: 445px;">
            Daftarkan Pembuat Sebagai Ketua Tim<br>(INSERT INTO anggota_tim: peran='Ketua')
          </div>
          <div class="act-box box-yellow" style="top: 550px;">
            Return ID Tim Baru &amp; Status Sukses
          </div>
        """
    },
    {
        "id": "diagram_5_kelola_anggota",
        "title": "5. ACTIVITY DIAGRAM: PENGELOLAAN ANGGOTA TIM (tim_detail.php)",
        "min_height": 780,
        "lane1_title": "Ketua Tim / Admin",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="180" x2="183" y2="225" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 250 L 420 250" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="280" x2="550" y2="320" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 355 L 313 355" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="347" class="branch-label" fill="#dc2626">Ya</text>
          <path d="M 615 355 L 787 355" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="347" class="branch-label">Belum</text>
          <line x1="917" y1="385" x2="917" y2="445" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 470 L 680 470" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="500" x2="550" y2="550" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 575 L 313 575" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="605" x2="183" y2="670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Halaman Detail Tim &<br>Buka Tab "Anggota Tim"
          </div>
          <div class="act-box box-green" style="top: 225px;">
            Pilih Pengguna & Tentukan Peran<br>(Ketua / Sekretaris / Anggota)
          </div>
          <div class="act-box box-red" style="top: 330px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Pengguna Sudah Terdaftar di Tim")
          </div>
          <div class="act-box box-green" style="top: 550px;">
            Melihat Daftar Anggota Tim Baru &<br>Alert Sukses Ditambahkan
          </div>
          <div class="node-final" style="top: 670px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 225px;">
            Validasi Ketersediaan Anggota di Tim
          </div>
          <div class="decision" style="top: 320px;">
            <div class="decision-inner">Sudah<br>Ada?</div>
          </div>
          <div class="act-box box-blue" style="top: 445px;">
            Buat Notifikasi Sistem untuk Pengguna Terpilih
          </div>
          <div class="act-box box-blue" style="top: 550px;">
            Refresh Tampilan Halaman Anggota
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 330px;">
            Cek Duplikasi Anggota di Database<br>(SELECT id FROM anggota_tim WHERE tim_id & user_id)
          </div>
          <div class="act-box box-yellow" style="top: 445px;">
            Simpan Anggota Baru ke Tabel anggota_tim<br>(INSERT INTO anggota_tim ...)
          </div>
        """
    },
    {
        "id": "diagram_6_manajemen_tugas",
        "title": "6. ACTIVITY DIAGRAM: PENUGASAN TUGAS BARU & NOTIFIKASI EMAIL (tim_detail.php)",
        "min_height": 840,
        "lane1_title": "Ketua Tim / Admin",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="180" x2="183" y2="225" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 250 L 420 250" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="280" x2="550" y2="320" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 355 L 313 355" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="347" class="branch-label" fill="#dc2626">Tidak</text>
          <path d="M 615 355 L 787 355" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="690" y="347" class="branch-label">Valid</text>
          <line x1="917" y1="385" x2="917" y2="445" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 470 L 680 470" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="500" x2="550" y2="550" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="600" x2="550" y2="645" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 670 L 313 670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="700" x2="183" y2="760" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Tab "Tugas" &<br>Klik Tombol "Tambah Tugas"
          </div>
          <div class="act-box box-green" style="top: 225px;">
            Input: Judul, Deskripsi, Prioritas, Deadline, dan Pilih Anggota PIC
          </div>
          <div class="act-box box-red" style="top: 330px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Form Belum Lengkap / PIC Kosong")
          </div>
          <div class="act-box box-green" style="top: 645px;">
            Melihat Kartu Tugas Baru Ditambahkan<br>pada Daftar Tugas Tim
          </div>
          <div class="node-final" style="top: 760px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 225px;">
            Validasi Kelengkapan Form Tugas
          </div>
          <div class="decision" style="top: 320px;">
            <div class="decision-inner">Form<br>Valid?</div>
          </div>
          <div class="act-box box-blue" style="top: 445px;">
            Panggil Fungsi emailTugasBaru()<br>Kirim Notifikasi via PHPMailer
          </div>
          <div class="act-box box-blue" style="top: 550px;">
            Kirim Email ke Alamat PIC &<br>Set Notifikasi Internal Sistem
          </div>
          <div class="act-box box-blue" style="top: 645px;">
            Refresh Tampilan Halaman Tugas
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 330px;">
            Insert Tugas Baru (status = 'pending')<br>(INSERT INTO tugas ...)
          </div>
          <div class="act-box box-yellow" style="top: 445px;">
            Simpan Notifikasi Baru di DB<br>(INSERT INTO notifikasi ...)
          </div>
        """
    },
    {
        "id": "diagram_7_unggah_tugas",
        "title": "7. ACTIVITY DIAGRAM: PENGERJAAN TUGAS & UNGGAH FILE (tugas_detail.php)",
        "min_height": 840,
        "lane1_title": "Anggota Tim (PIC)",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="180" x2="183" y2="225" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 250 L 420 250" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="280" x2="550" y2="320" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 485 355 L 313 355" stroke="#dc2626" stroke-width="1.8" marker-end="url(#arrow-red)" />
          <text x="390" y="347" class="branch-label" fill="#dc2626">Tidak</text>
          <path d="M 615 355 L 680 445" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="640" y="390" class="branch-label">Valid</text>
          <line x1="550" y1="480" x2="550" y2="520" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 680 545 L 787 545" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="575" x2="917" y2="635" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 660 L 680 660" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 660 L 313 660" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="690" x2="183" y2="750" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Halaman Detail Tugas &<br>Update Status Pengerjaan
          </div>
          <div class="act-box box-green" style="top: 225px;">
            Pilih File Lampiran Hasil Kerja &<br>Klik Tombol "Unggah Lampiran"
          </div>
          <div class="act-box box-red" style="top: 330px; width: 230px; font-size: 12px;">
            Tampilkan Pesan Error<br>("Format / Ukuran File Tidak Diizinkan")
          </div>
          <div class="act-box box-green" style="top: 635px;">
            Melihat File Lampiran Terunggah &<br>Status Tugas Terupdate
          </div>
          <div class="node-final" style="top: 750px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 225px;">
            Validasi Ekstensi & Ukuran File<br>(PDF, DOCX, XLSX, ZIP)
          </div>
          <div class="decision" style="top: 320px;">
            <div class="decision-inner">File<br>Valid?</div>
          </div>
          <div class="act-box box-blue" style="top: 420px;">
            Generate Nama File Unik & Simpan ke<br>Folder uploads/tugas_files/
          </div>
          <div class="act-box box-blue" style="top: 520px;">
            Update Status Tugas Menjadi 'in_progress'<br>atau 'completed'
          </div>
          <div class="act-box box-blue" style="top: 635px;">
            Refresh Halaman Detail Tugas
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 520px;">
            Simpan Metadata File Lampiran<br>(INSERT INTO tugas_files ...)
          </div>
          <div class="act-box box-yellow" style="top: 635px;">
            Update Status Pengerjaan Tugas<br>(UPDATE tugas SET status = ...)
          </div>
        """
    },
    {
        "id": "diagram_8_unduh_sk",
        "title": "8. ACTIVITY DIAGRAM: GENERATE & UNDUH SURAT TUGAS .DOC (download_sk.php)",
        "min_height": 760,
        "lane1_title": "Pengguna / Admin",
        "svg": """
          <line x1="183" y1="95" x2="183" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 313 155 L 420 155" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 680 155 L 787 155" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="185" x2="917" y2="245" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 270 L 680 270" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="300" x2="550" y2="350" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="400" x2="550" y2="450" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="500" x2="550" y2="550" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 575 L 313 575" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="605" x2="183" y2="670" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-green" style="top: 130px;">
            Akses Halaman Detail Tim &<br>Klik Tombol "Unduh SK Tim (.doc)"
          </div>
          <div class="act-box box-green" style="top: 550px;">
            Browser Mengunduh File Dokumen Word<br>("Surat_Tugas_Tim_[NamaTim].doc")
          </div>
          <div class="node-final" style="top: 670px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="act-box box-blue" style="top: 130px;">
            Menerima Parameter ID Tim (GET id)
          </div>
          <div class="act-box box-blue" style="top: 245px;">
            Baca Gambar Kop Resmi UKDW &<br>Konversi ke Base64 (MHTML Header)
          </div>
          <div class="act-box box-blue" style="top: 350px;">
            Generate Struktur Dokumen Word 3 Halaman<br>(Konsiderans, Diktum, Anggota, Lampiran Tugas)
          </div>
          <div class="act-box box-blue" style="top: 450px;">
            Set HTTP Header File Attachment<br>(Content-Type: application/msword)
          </div>
          <div class="act-box box-blue" style="top: 550px;">
            Alirkan Stream File Word ke Browser
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 130px;">
            Query Detail Data Tim Ad Hoc<br>(SELECT * FROM tim_adhoc WHERE id = ?)
          </div>
          <div class="act-box box-yellow" style="top: 245px;">
            Query Seluruh Anggota Tim & Tugas Terkait<br>(JOIN users ON anggota_tim ...)
          </div>
        """
    },
    {
        "id": "diagram_9_reminder_email",
        "title": "9. ACTIVITY DIAGRAM: REMINDER DEADLINE & NOTIFIKASI EMAIL OTOMATIS",
        "min_height": 820,
        "lane1_title": "Pengguna (PIC Tugas)",
        "svg": """
          <line x1="550" y1="95" x2="550" y2="130" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 680 155 L 787 155" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="185" x2="917" y2="245" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 270 L 680 270" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="550" y1="300" x2="550" y2="340" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 615 375 L 720 375" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="640" y="367" class="branch-label">Tidak Ada</text>
          <line x1="550" y1="410" x2="550" y2="465" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <text x="560" y="435" class="branch-label">Ada (H-2)</text>
          <path d="M 680 490 L 787 490" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="917" y1="520" x2="917" y2="575" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 787 600 L 680 600" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <path d="M 420 600 L 313 600" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
          <line x1="183" y1="630" x2="183" y2="700" stroke="#222" stroke-width="1.8" marker-end="url(#arrow)" />
        """,
        "lane1": """
          <div class="act-box box-green" style="top: 575px;">
            Menerima Notifikasi Pengingat Deadline<br>di Inbox Email Gmail & Dashboard Web
          </div>
          <div class="node-final" style="top: 700px;">
            <div class="node-final-inner"></div>
          </div>
        """,
        "lane2": """
          <div class="node-initial" style="top: 70px;"></div>
          <div class="act-box box-blue" style="top: 130px;">
            Fungsi check_deadline_reminders()<br>Dieksekusi Otomatis Saat Akses Sistem
          </div>
          <div class="act-box box-blue" style="top: 245px;">
            Pengecekan Tugas Belum Selesai &<br>Mendekati Batas Deadline (H-2 / H-1)
          </div>
          <div class="decision" style="top: 340px;">
            <div class="decision-inner">Ada Tugas<br>Kritis?</div>
          </div>
          <div class="act-box box-blue" style="top: 350px; left: 85%; width: 140px; font-size: 11px;">
            Selesai / Lewati
          </div>
          <div class="act-box box-blue" style="top: 465px;">
            Panggil PHPMailer & Kirim Email Pengingat<br>Menggunakan Akun Gmail SMTP Resmi
          </div>
          <div class="act-box box-blue" style="top: 575px;">
            Update Flag email_sent = 1 di Database<br>(Mencegah Pengiriman Duplikat)
          </div>
        """,
        "lane3": """
          <div class="act-box box-yellow" style="top: 130px;">
            Query Cari Tugas Belum Selesai<br>(SELECT * FROM tugas WHERE status != 'completed')
          </div>
          <div class="act-box box-yellow" style="top: 245px;">
            Return Daftar Tugas & User PIC
          </div>
          <div class="act-box box-yellow" style="top: 465px;">
            Insert Notifikasi Baru ke Tabel notifikasi<br>(INSERT INTO notifikasi ...)
          </div>
          <div class="act-box box-yellow" style="top: 575px;">
            Update Status email_sent di Database<br>(UPDATE notifikasi SET email_sent = 1 ...)
          </div>
        """
    }
]

async def generate_all():
    edge_path = r'C:\Program Files (x86)\Microsoft\Edge\Application\msedge.exe'
    async with async_playwright() as p:
        browser = await p.chromium.launch(executable_path=edge_path)
        page = await browser.new_page()

        for diag in diagrams:
            html = HTML_TEMPLATE.replace('__TITLE__', diag['title'])
            html = html.replace('__MIN_HEIGHT__', str(diag['min_height']))
            html = html.replace('__LANE1_TITLE__', diag['lane1_title'])
            html = html.replace('__SVG_CONTENT__', diag['svg'])
            html = html.replace('__LANE1_CONTENT__', diag['lane1'])
            html = html.replace('__LANE2_CONTENT__', diag['lane2'])
            html = html.replace('__LANE3_CONTENT__', diag['lane3'])

            html_file = f"C:/Users/Lenovo/Documents/Skripsi/{diag['id']}.html"
            png_file = f"C:/Users/Lenovo/Documents/Skripsi/{diag['id']}.png"

            with open(html_file, 'w', encoding='utf-8') as f:
                f.write(html)

            await page.goto(f"file:///{html_file}")
            await page.locator('.diagram-container').screenshot(path=png_file)
            print(f"Generated: {png_file}")

        await browser.close()

asyncio.run(generate_all())
