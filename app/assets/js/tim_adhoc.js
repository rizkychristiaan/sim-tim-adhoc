/**
 * ==============================================================================
 * FILE: app/assets/js/tim_adhoc.js
 * HALAMAN: Manajemen Master Data Tim Ad Hoc
 * ==============================================================================
 */

function resetForm() {
    document.getElementById('tim_id').value = '0';
    document.getElementById('nomor_sk').value = '';
    document.getElementById('nama_tim').value = '';
    document.getElementById('deskripsi').value = '';
    document.getElementById('tanggal_mulai').value = '';
    document.getElementById('tanggal_selesai').value = '';
    document.getElementById('status').value = 'Aktif';
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-people-fill me-2"></i> Tambah Tim Ad Hoc Baru';
}

function editTim(t) {
    document.getElementById('tim_id').value = t.id;
    document.getElementById('nomor_sk').value = t.nomor_sk;
    document.getElementById('nama_tim').value = t.nama_tim;
    document.getElementById('deskripsi').value = t.deskripsi;
    document.getElementById('tanggal_mulai').value = t.tanggal_mulai;
    document.getElementById('tanggal_selesai').value = t.tanggal_selesai;
    document.getElementById('status').value = t.status;
    document.getElementById('modalTitle').innerHTML = '<i class="bi bi-pencil-square me-2"></i> Edit Data Tim Ad Hoc';
    var myModal = new bootstrap.Modal(document.getElementById('modalTim'));
    myModal.show();
}
