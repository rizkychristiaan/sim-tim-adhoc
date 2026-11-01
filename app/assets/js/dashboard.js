/**
 * ==============================================================================
 * FILE: app/assets/js/dashboard.js
 * HALAMAN: Dashboard Utama e-Class Tim Ad Hoc LPAIP UKDW
 * FUNGSI: Modal Tambah, Edit, & Konfirmasi Hapus Data Tim Ad Hoc
 * ==============================================================================
 */

function resetForm() {
    var timId = document.getElementById('tim_id');
    var nomorSk = document.getElementById('nomor_sk');
    var namaTim = document.getElementById('nama_tim');
    var deskripsi = document.getElementById('deskripsi');
    var tanggalMulai = document.getElementById('tanggal_mulai');
    var tanggalSelesai = document.getElementById('tanggal_selesai');
    var status = document.getElementById('status');
    var modalTitle = document.getElementById('modalTitle');

    if (timId) timId.value = '0';
    if (nomorSk) nomorSk.value = '';
    if (namaTim) namaTim.value = '';
    if (deskripsi) deskripsi.value = '';
    if (tanggalMulai) tanggalMulai.value = '';
    if (tanggalSelesai) tanggalSelesai.value = '';
    if (status) status.value = 'Aktif';
    if (modalTitle) modalTitle.innerHTML = '<i class="bi bi-people-fill me-2"></i> Tambah Tim Ad Hoc Baru';
}

function editTim(t) {
    var timId = document.getElementById('tim_id');
    var nomorSk = document.getElementById('nomor_sk');
    var namaTim = document.getElementById('nama_tim');
    var deskripsi = document.getElementById('deskripsi');
    var tanggalMulai = document.getElementById('tanggal_mulai');
    var tanggalSelesai = document.getElementById('tanggal_selesai');
    var status = document.getElementById('status');
    var modalTitle = document.getElementById('modalTitle');

    if (timId) timId.value = t.id;
    if (nomorSk) nomorSk.value = t.nomor_sk;
    if (namaTim) namaTim.value = t.nama_tim;
    if (deskripsi) deskripsi.value = t.deskripsi || '';
    if (tanggalMulai) tanggalMulai.value = t.tanggal_mulai;
    if (tanggalSelesai) tanggalSelesai.value = t.tanggal_selesai;
    if (status) status.value = t.status;
    if (modalTitle) modalTitle.innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Data Tim Ad Hoc';

    var modalEl = document.getElementById('modalTim');
    if (modalEl) {
        var myModal = bootstrap.Modal.getOrCreateInstance(modalEl);
        myModal.show();
    }
}

function konfirmasiHapusTim(id, namaTim) {
    showDeleteModal({
        url: 'dashboard.php?delete_tim=' + id,
        title: 'Konfirmasi Hapus Tim',
        message: 'Apakah Anda yakin ingin menghapus Tim Ad Hoc ini beserta seluruh penugasan dan arsip di dalamnya?',
        itemTitle: namaTim,
        btnText: 'Ya, Hapus Tim'
    });
}
