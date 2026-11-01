/**
 * ==============================================================================
 * FILE: app/assets/js/dokumen.js
 * HALAMAN: Manajemen Dokumen Tim Ad Hoc
 * ==============================================================================
 */

function editDokumen(doc) {
    document.getElementById('edit_dokumen_id').value = doc.id;
    document.getElementById('edit_tim_id').value = doc.tim_id;
    document.getElementById('edit_nama_dokumen').value = doc.nama_dokumen;
    document.getElementById('edit_jenis_dokumen').value = doc.jenis_dokumen;
    document.getElementById('edit_current_file').innerText = doc.file_path;

    var modal = new bootstrap.Modal(document.getElementById('modalEditDokumen'));
    modal.show();
}
