/**
 * ==============================================================================
 * FILE: app/assets/js/tugas.js
 * HALAMAN: Manajemen & Pemantauan Tugas Tim Ad Hoc
 * ==============================================================================
 */

function openUpdateModal(t) {
    document.getElementById('status_tugas_id').value = t.id;
    document.getElementById('modal_judul_tugas').innerText = t.judul_tugas;
    document.getElementById('modal_select_status').value = t.status;

    // Render current attached files list
    var container = document.getElementById('modal_uploaded_files');
    container.innerHTML = '';

    var filesMap = window.taskFilesMap || {};
    var currentUserId = window.currentUserId || 0;
    var currentUserRole = window.currentUserRole || '';

    var files = filesMap[t.id] || [];
    if (files.length === 0) {
        container.innerHTML = '<div class="list-group-item text-muted small py-3 text-center">Belum ada berkas lampiran yang diunggah untuk tugas ini.</div>';
    } else {
        files.forEach(function(f) {
            var itemHtml = '<div class="list-group-item d-flex justify-content-between align-items-center py-2 px-3">' +
                '<div>' +
                '<i class="bi bi-file-earmark-text text-primary me-2 fs-6"></i>' +
                '<a href="uploads/tugas/' + encodeURIComponent(f.file_path) + '" target="_blank" download class="fw-semibold text-dark text-decoration-none small me-2">' + escapeHtml(f.nama_file_asli) + '</a>' +
                '<small class="text-muted d-block" style="font-size: 0.75rem;">Diunggah oleh ' + escapeHtml(f.uploader_nama) + ' pada ' + f.uploaded_at + '</small>' +
                '</div>' +
                '<div class="d-flex align-items-center gap-2">' +
                '<a href="uploads/tugas/' + encodeURIComponent(f.file_path) + '" target="_blank" download class="btn btn-sm btn-light text-primary border rounded-pill px-2.5 py-1" style="font-size: 0.75rem;"><i class="bi bi-download me-1"></i>Unduh</a>';

            if (currentUserRole === 'admin' || currentUserRole === 'ketua' || f.uploader_id == currentUserId) {
                itemHtml += '<a href="tugas.php?hapus_file=' + f.id + '" class="btn btn-sm btn-light text-danger border rounded-pill px-2.5 py-1" style="font-size: 0.75rem;" onclick="return confirm(\'Hapus berkas ini?\')"><i class="bi bi-trash me-1"></i>Hapus</a>';
            }

            itemHtml += '</div></div>';
            container.innerHTML += itemHtml;
        });
    }

    var modal = new bootstrap.Modal(document.getElementById('modalStatus'));
    modal.show();
}

function escapeHtml(text) {
    if (!text) return '';
    return text
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}
