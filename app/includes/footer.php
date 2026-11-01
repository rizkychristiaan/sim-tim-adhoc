    <footer class="mt-auto bg-white border-top py-3 text-center text-muted small">
        <div class="container-fluid">
            &copy; 2026 <strong>LPAIP UKDW</strong> - Sistem Informasi Manajemen Tim Ad Hoc Berbasis Web (Metode Prototype)
        </div>
    </footer>
</div><!-- end main-content -->

<!-- Modal Universal Konfirmasi Hapus & Aksi -->
<div class="modal fade" id="modalUniversalKonfirmasi" tabindex="-1" aria-hidden="true" style="z-index: 1065;">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-body p-4 text-center">
                <div id="modalUniversalIconWrapper" class="mx-auto mb-3 d-flex align-items-center justify-content-center bg-danger-subtle text-danger rounded-circle" style="width: 68px; height: 68px;">
                    <i class="bi bi-trash3-fill fs-2" id="modalUniversalIcon"></i>
                </div>
                <h5 class="fw-bold text-dark mb-2" id="modalUniversalJudul">Konfirmasi Hapus</h5>
                <p class="text-secondary small mb-2" id="modalUniversalPesan">
                    Apakah Anda yakin ingin menghapus data ini?
                </p>
                <div id="modalUniversalSubtext" class="p-2.5 mb-3 bg-light rounded-3 border small text-dark fw-bold text-break d-none">
                </div>
                <div class="d-flex justify-content-center gap-2 mt-3">
                    <button type="button" class="btn btn-secondary rounded-pill px-4 fw-semibold" data-bs-dismiss="modal">
                        Batal
                    </button>
                    <a href="#" id="modalUniversalTombol" class="btn btn-danger rounded-pill px-4 fw-semibold shadow-sm">
                        <i class="bi bi-trash3 me-1"></i> Ya, Hapus
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bootstrap 5.3 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- jQuery & Select2 Searchable Dropdown JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Script Navigasi Sidebar Mobile & Modal Universal -->
<script>
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }

    // Fungsi pop-up konfirmasi hapus modern & elegan pengganti browser confirm()
    function showDeleteModal(options, argMsg, argItem) {
        var url = (typeof options === 'string') ? options : (options ? options.url : '#');
        var message = (typeof options === 'object' && options && options.message) ? options.message : (argMsg || 'Apakah Anda yakin ingin menghapus data ini?');
        var title = (typeof options === 'object' && options && options.title) ? options.title : 'Konfirmasi Hapus';
        var itemTitle = (typeof options === 'object' && options && options.itemTitle) ? options.itemTitle : (argItem || '');
        var btnText = (typeof options === 'object' && options && options.btnText) ? options.btnText : 'Ya, Hapus';
        var btnClass = (typeof options === 'object' && options && options.btnClass) ? options.btnClass : 'btn-danger';
        var iconClass = (typeof options === 'object' && options && options.iconClass) ? options.iconClass : 'bi bi-trash3-fill';
        var iconWrapClass = (typeof options === 'object' && options && options.iconWrapClass) ? options.iconWrapClass : 'bg-danger-subtle text-danger';

        var modalEl = document.getElementById('modalUniversalKonfirmasi');
        if (!modalEl) {
            if (confirm(message)) {
                if (url && url !== '#') window.location.href = url;
            }
            return;
        }

        document.getElementById('modalUniversalJudul').innerText = title;
        document.getElementById('modalUniversalPesan').innerText = message;
        
        var iconEl = document.getElementById('modalUniversalIcon');
        if (iconEl) iconEl.className = iconClass + ' fs-2';

        var iconWrapper = document.getElementById('modalUniversalIconWrapper');
        if (iconWrapper) iconWrapper.className = 'mx-auto mb-3 d-flex align-items-center justify-content-center rounded-circle ' + iconWrapClass;

        var subtextEl = document.getElementById('modalUniversalSubtext');
        if (itemTitle && itemTitle.toString().trim() !== '') {
            subtextEl.innerText = itemTitle;
            subtextEl.classList.remove('d-none');
        } else {
            subtextEl.classList.add('d-none');
        }

        var btn = document.getElementById('modalUniversalTombol');
        btn.className = 'btn rounded-pill px-4 fw-semibold shadow-sm ' + btnClass;
        var iconBtn = btnClass.includes('danger') ? '<i class="bi bi-trash3 me-1"></i> ' : '<i class="bi bi-check2-circle me-1"></i> ';
        btn.innerHTML = iconBtn + btnText;

        if (typeof options === 'object' && typeof options.onConfirm === 'function') {
            btn.href = '#';
            btn.onclick = function(e) {
                e.preventDefault();
                var modalInstance = bootstrap.Modal.getInstance(modalEl);
                if (modalInstance) modalInstance.hide();
                options.onConfirm();
            };
        } else {
            btn.href = url;
            btn.onclick = null;
        }

        var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modal.show();
    }
</script>

<!-- Auto-Load JS Spesifik Halaman (Jika ada file JS dengan nama yang sama) -->
<?php 
$current_page_name = basename($_SERVER['PHP_SELF'], ".php");
$custom_page_js = __DIR__ . "/../assets/js/" . $current_page_name . ".js";
if (file_exists($custom_page_js)): ?>
    <script src="<?= BASE_URL ?>assets/js/<?= $current_page_name ?>.js?v=<?= filemtime($custom_page_js) ?>"></script>
<?php endif; ?>

</body>
</html>