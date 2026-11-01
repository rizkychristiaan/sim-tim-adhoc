import os
import docx
from docx.shared import Inches, Pt, RGBColor
from docx.enum.text import WD_ALIGN_PARAGRAPH
from docx.enum.table import WD_TABLE_ALIGNMENT
from build_helpers import set_cell_background, set_cell_margins, add_callout, format_table

doc = docx.Document()

# Set standard margins (0.8 inch for clean printable layout)
for section in doc.sections:
    section.top_margin = Inches(0.8)
    section.bottom_margin = Inches(0.8)
    section.left_margin = Inches(0.8)
    section.right_margin = Inches(0.8)

MAROON = RGBColor(107, 12, 34)   # #6B0C22
GOLD = RGBColor(217, 119, 6)     # #D97706
DARK_GRAY = RGBColor(51, 65, 85)

def add_title(text):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(2)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(14)
    run.font.bold = True
    run.font.color.rgb = MAROON
    return p

def add_subtitle(text):
    p = doc.add_paragraph()
    p.alignment = WD_ALIGN_PARAGRAPH.CENTER
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(14)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(9.5)
    run.font.bold = True
    run.font.color.rgb = DARK_GRAY
    return p

def add_h1(text):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(14)
    p.paragraph_format.space_after = Pt(4)
    p.paragraph_format.keep_with_next = True
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(11)
    run.font.bold = True
    run.font.color.rgb = MAROON
    return p

def add_p(text, bold_prefix="", italic=False):
    p = doc.add_paragraph()
    p.paragraph_format.space_before = Pt(0)
    p.paragraph_format.space_after = Pt(3)
    p.paragraph_format.line_spacing = 1.15
    if bold_prefix:
        r_pre = p.add_run(bold_prefix)
        r_pre.font.name = 'Arial'
        r_pre.font.size = Pt(9)
        r_pre.font.bold = True
        r_pre.font.color.rgb = RGBColor(30, 41, 59)
    run = p.add_run(text)
    run.font.name = 'Arial'
    run.font.size = Pt(9)
    run.font.italic = italic
    run.font.color.rgb = DARK_GRAY
    return p

# HEADER / KOP
add_title("UNIVERSITAS KRISTEN DUTA WACANA")
add_subtitle("LEMBAGA PENGEMBANGAN AKADEMIK DAN INOVASI PEMBELAJARAN (LPAIP)\nINSTRUMEN EVALUASI IMPLEMENTASI WEBSITE (ITERASI WEBSITE)\nSistem Informasi Manajemen Tim Ad Hoc Berbasis Web (Goal-Directed Design & Evolutionary Prototyping)")

add_callout(doc, 
    "Perbedaan Fokus Evaluasi:\n"
    "• Iterasi Figma (Sebelumnya): Mengevaluasi rancangan tampilan statis, wireframe, dan kelengkapan menu navigasi.\n"
    "• Iterasi Website (Instrumen Ini): Mengevaluasi fitur yang TELAH DIIMPLEMENTASIKAN secara fungsional (berbasis web riil), "
    "menguji apakah sistem bekerja akurat tanpa bug, apakah alur operasional mudah dipahami pengguna, "
    "dan apakah hasil keluaran (dokumen SK, LPJ, tugas, notifikasi email) telah sesuai dengan proses kerja resmi LPAIP UKDW.",
    "FOKUS & TUJUAN EVALUASI ITERASI WEBSITE")

# METADATA RESPONDEN
add_h1("A. IDENTITAS PENGUJI / RESPONDEN")
t_meta = doc.add_table(rows=1, cols=4)
format_table(t_meta, [Inches(1.8), Inches(1.8), Inches(1.6), Inches(1.8)],
    ["Parameter", "Keterangan", "Parameter", "Keterangan"],
    [
        ["Nama Pengguna", "...........................................", "Tanggal Pengujian", "..........................................."],
        ["NIP / NIM", "...........................................", "Peran (Role)", "[  ] Admin  [  ] Ketua  [  ] Anggota"],
        ["Jabatan / Unit Kerja", "...........................................", "Lingkungan Uji", "[  ] Laptop / PC   [  ] Tablet / HP"],
        ["Peramban (Browser)", "[  ] Chrome  [  ] Edge  [  ] Lainnya", "Waktu Sesi", "Pukul ......... s.d. ......... WIB"]
    ]
)

# PETUNJUK
add_h1("B. PETUNJUK PELAKSANAAN PENGUJIAN LANGSUNG (LIVE TESTING)")
add_p(
    "1. Responden diminta menjalankan skenario tugas fungsional secara langsung pada peramban (browser) di alamat web sistem.\n"
    "2. Peneliti bertindak sebagai fasilitator/pengamat tanpa mengarahkan tombol atau langkah klik yang harus diambil.\n"
    "3. Berikan tanda centang [✓] pada kolom Status Keberhasilan:\n"
    "   • Sesuai: Fitur berfungsi sempurna, data tersimpan/tereksekusi dengan benar sesuai alur kerja LPAIP.\n"
    "   • Sesuai Catatan: Fitur berhasil berjalan, namun responden mengalami kebingungan alur atau membutuhkan waktu adaptasi.\n"
    "   • Gagal / Kendala: Terjadi error, data tidak tersimpan, berkas gagal diproses, atau alur tidak sesuai SOP LPAIP.\n"
    "4. Tuliskan kendala teknis atau masukan perbaikan secara spesifik pada kolom Catatan."
)

# SKENARIO PENGUJIAN WEBSITE
add_h1("C. SKENARIO PENGUJIAN FUNGSIONAL & KESESUAIAN PROSES KERJA LPAIP")

t_tasks = doc.add_table(rows=1, cols=5)
format_table(t_tasks, [Inches(0.9), Inches(2.2), Inches(2.0), Inches(1.1), Inches(1.8)],
    ["Kode & Role", "Skenario Operasional Fungsional", "Hasil yang Diharapkan (Sesuai SOP LPAIP)", "Status", "Catatan Kendala & Masukan Alur"],
    [
        [
            "W-ADM-01\n(Admin)",
            "Login & Akses Dasbor:\nLakukan autentikasi menggunakan akun Admin LPAIP dan periksa informasi tim aktif semester berjalan.",
            "Sistem memverifikasi password hash, sesi aktif, dan menampilkan rekapitulasi tim ad hoc yang sesuai dengan kalender akademik.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-ADM-02\n(Admin)",
            "Persetujuan Akun (Approval):\nBuka menu kelola pengguna, tinjau permohonan akun dosen baru, lalu klik 'Setujui Akun'.",
            "Status akun berhasil berubah dari Pending (0) menjadi Aktif (1) di database, dan sistem otomatis mengirimkan email konfirmasi ke dosen bersangkutan.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-ADM-03\n(Admin)",
            "Pembentukan Tim Ad Hoc Baru:\nBuat 1 Tim Ad Hoc baru lengkap dengan Nomor SK, Masa Tugas, dan susunan personalia (Ketua & Anggota).",
            "Data tim tersimpan utuh di basis data, muncul kartu tim di dashboard, dan personalia otomatis menerima akses ke tim tersebut.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-ADM-04\n(Admin)",
            "Penerbitan & Unduh Draf SK:\nBuka ruang tim, pilih menu Cetak/Unduh SK Tim Ad Hoc (.doc).",
            "File dokumen terunduh dengan format kop resmi UKDW, konsiderans, dan nama personalia yang telah terisi otomatis dan siap diedit.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-KET-01\n(Ketua)",
            "Distribusi & Penugasan Anggota:\nSebagai Ketua Tim, buka ruang kerja tim (Tab Tugas), buat 1 penugasan baru, tentukan PIC dan tenggat waktu (deadline).",
            "Tugas tercatat pada daftar tugas tim, indikator status awal terpasang, dan anggota yang ditugaskan menerima pemberitahuan/email tugas.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-KET-02\n(Ketua)",
            "Monitoring Progres & Persentase:\nPeriksa pembaruan persentase progres capaian kerja tim pada bilah progres otomatis di Beranda Tim.",
            "Persentase otomatis terhitung proporsional berdasarkan perbandingan jumlah tugas yang telah diselesaikan terhadap total tugas.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-KET-03\n(Ketua)",
            "Pengunggahan Laporan Akhir (LPJ):\nMasuk ke Tab LPJ, unggah berkas Laporan Pertanggungjawaban akhir tim (PDF/Word).",
            "Berkas fisik berhasil tersimpan di folder uploads/, riwayat upload tercatat, dan notifikasi pelaporan terkirim ke Admin LPAIP.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-ANG-01\n(Anggota)",
            "Penyelesaian Tugas & Bukti Kerja:\nLogin sebagai Anggota Tim, buka tugas yang diberikan, ubah status menjadi 'Selesai', dan lampirkan berkas bukti kerja.",
            "Status tugas berhasil ter-update di database, berkas bukti tersimpan dan dapat diunduh ulang oleh Ketua/Admin secara utuh.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ],
        [
            "W-ANG-02\n(Anggota)",
            "Akses Repositori Dokumen Tim:\nBuka Tab Dokumen, cari dan unduh berkas panduan/SK yang diunggah oleh sekretariat.",
            "Fitur pencarian/filter berfungsi cepat dan proses pengunduhan berkas berjalan lancar tanpa berkas korup.",
            "[  ] Sesuai\n[  ] Catatan\n[  ] Gagal",
            ""
        ]
    ]
)

# KUESIONER EVALUASI KELAYAKAN ALUR & PROSES KERJA LPAIP
add_h1("D. KUESIONER EVALUASI KELAYAKAN ALUR & PROSES KERJA LPAIP")
add_p(
    "Berikan penilaian terhadap sistem yang telah Anda coba secara langsung menggunakan Skala Likert (1 - 5):\n"
    "1 = Sangat Tidak Setuju (STS) | 2 = Tidak Setuju (TS) | 3 = Cukup / Netral (N) | 4 = Setuju (S) | 5 = Sangat Setuju (SS)"
)

t_likert = doc.add_table(rows=1, cols=7)
format_table(t_likert, [Inches(0.4), Inches(4.0), Inches(0.55), Inches(0.55), Inches(0.55), Inches(0.55), Inches(0.55)],
    ["No", "Pernyataan Evaluasi Sistem Riil (Website)", "1\nSTS", "2\nTS", "3\nN", "4\nS", "5\nSS"],
    [
        ["", "DIMENSI 1: KESESUAIAN FITUR DENGAN KEBUTUHAN KERJA LPAIP", "", "", "", "", ""],
        ["1", "Fitur pembentukan Tim Ad Hoc dan pembagian peran telah sesuai dengan struktur organisasi kerja nyata di LPAIP UKDW.", "", "", "", "", ""],
        ["2", "Fitur penerbitan draf SK (.doc) otomatis mempercepat proses administrasi permohonan SK ke Rektorat.", "", "", "", "", ""],
        ["3", "Fitur monitoring tugas dan unggah berkas bukti kerja memudahkan pemantauan kinerja personalia tim.", "", "", "", "", ""],
        ["4", "Fitur repositori LPJ dan arsip dokumen digital menjamin dokumen tersimpan aman dan mudah ditelusuri saat akreditasi.", "", "", "", "", ""],
        ["", "DIMENSI 2: KEMUDAHAN ALUR KERJA (WORKFLOW & USABILITY)", "", "", "", "", ""],
        ["5", "Alur kerja sistem (dari pembuatan tim, penugasan, hingga pelaporan LPJ) runtut, logis, dan mudah dipahami.", "", "", "", "", ""],
        ["6", "Pemisahan menu dalam 5 Tab di Ruang Kerja Tim memudahkan perpindahan pekerjaan tanpa membingungkan.", "", "", "", "", ""],
        ["7", "Formulir input (tambah tim, tugas, dokumen) mudah diisi dan memberikan pesan konfirmasi/validasi yang jelas.", "", "", "", "", ""],
        ["8", "Sistem merespons aksi pengguna dengan cepat tanpa adanya kendala loading atau error fungsi.", "", "", "", "", ""],
        ["", "DIMENSI 3: KEMANFAATAN & EFEKTIVITAS SISTEM", "", "", "", "", ""],
        ["9", "Pemberitahuan otomatis (lonceng sistem dan email notifikasi) efektif mencegah kelalaian tenggat waktu tugas.", "", "", "", "", ""],
        ["10", "Secara keseluruhan, sistem berbasis web ini layak dan siap diterapkan untuk menggantikan pencatatan manual di LPAIP UKDW.", "", "", "", "", ""]
    ]
)

# KESIMPULAN & TANDA TANGAN
add_h1("E. KESIMPULAN KUALITATIF & REKOMENDASI PENGEMBANGAN")
add_p("1. Aspek Alur / Fitur yang Paling Membantu Proses Kerja LPAIP:")
add_p("....................................................................................................................................................................")
add_p("....................................................................................................................................................................")
add_p("2. Kendala Operasional atau Bagian Alur yang Perlu Disederhanakan:")
add_p("....................................................................................................................................................................")
add_p("....................................................................................................................................................................")
add_p("3. Rekomendasi Tambahan untuk Penyempurnaan Sistem Selanjutnya:")
add_p("....................................................................................................................................................................")
add_p("....................................................................................................................................................................")

t_sign = doc.add_table(rows=1, cols=2)
format_table(t_sign, [Inches(3.5), Inches(3.5)],
    ["Mengetahui,\nPeneliti Skripsi", "Yogyakarta, ................................... 2026\nResponden / Penguji Sistem"],
    [
        ["\n\n\n\n( Rizky Chrales Christiaan )\nNIM: 71220902", "\n\n\n\n( .................................................... )\nNIP/NIM: ......................................."]
    ]
)

out_ws = r"c:\Users\Lenovo\Documents\Skripsi\Lembar_Pengujian_Iterasi_Website_LPAIP.docx"
out_art = r"C:\Users\Lenovo\.gemini\antigravity\brain\1370e15a-2d95-4ab6-a646-a4cdb05bc1f3\Lembar_Pengujian_Iterasi_Website_LPAIP.docx"

doc.save(out_ws)
doc.save(out_art)
print("Website testing docx generated successfully!")
