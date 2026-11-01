/**
 * ==============================================================================
 * FILE: app/assets/js/tim_detail.js
 * HALAMAN: Interaksi Ruang Kerja Tim Ad Hoc
 * ==============================================================================
 */

function toggleUserMode(mode) {
    const existingSec = document.getElementById('sectionExistingUser');
    const newSec = document.getElementById('sectionNewUser');
    const selectUser = document.getElementById('select_user_id');
    const newNip = document.getElementById('new_nip_nim');
    const newNama = document.getElementById('new_nama');
    const newEmail = document.getElementById('new_email');

    if (!existingSec || !newSec) return;

    if (mode === 'new') {
        existingSec.style.display = 'none';
        newSec.style.display = 'block';
        if (selectUser) selectUser.removeAttribute('required');
        if (newNip) newNip.setAttribute('required', 'required');
        if (newNama) newNama.setAttribute('required', 'required');
        if (newEmail) newEmail.setAttribute('required', 'required');
    } else {
        existingSec.style.display = 'block';
        newSec.style.display = 'none';
        if (selectUser) selectUser.setAttribute('required', 'required');
        if (newNip) newNip.removeAttribute('required');
        if (newNama) newNama.removeAttribute('required');
        if (newEmail) newEmail.removeAttribute('required');
    }
}

// ==============================================================================
// FITUR PEMBUATAN BANYAK TUGAS SEKALIGUS (BULK TASK CREATION)
// ==============================================================================
var taskRowCounter = 1;

function tambahBarisTugas() {
    taskRowCounter++;
    var container = document.getElementById('task_cards_container');
    var template = document.getElementById('task_row_template');
    if (!container || !template) {
        console.error('Container atau template tidak ditemukan:', container, template);
        return;
    }

    var newRow = template.cloneNode(true);
    newRow.id = 'task_row_' + taskRowCounter;
    newRow.style.display = 'block';

    // Update label nomor penugasan
    var titleEl = newRow.querySelector('.task-row-title');
    if (titleEl) {
        titleEl.innerHTML = '<i class="bi bi-card-checklist me-1"></i> Penugasan #' + taskRowCounter;
    }

    // Aktifkan required pada input di baris baru
    var inputs = newRow.querySelectorAll('input[data-required], select[data-required]');
    inputs.forEach(function(el) {
        el.required = true;
        el.setAttribute('required', 'required');
    });

    container.appendChild(newRow);

    // Auto-scroll ke baris tugas baru
    setTimeout(function() {
        newRow.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }, 50);
}

function hapusBarisTugas(buttonEl) {
    var card = buttonEl.closest('.task-row-item');
    if (card) {
        card.remove();
    }
}
