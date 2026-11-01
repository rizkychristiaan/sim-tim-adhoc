/**
 * ==============================================================================
 * JAVASCRIPT: script.js
 * SISTEM: Logika Program Lembar Evaluasi Prototype Figma (Iterasi I)
 * FITUR:
 *   1. Perhitungan Otomatis Tingkat Keberhasilan Tugas (Task Success Rate %)
 *   2. Filter Tampilan Skenario Berdasarkan Peran (Admin / Ketua / Anggota)
 *   3. Penanganan Cetak Halaman (Print Handler)
 *   4. Reset Form Evaluasi
 * ==============================================================================
 */

document.addEventListener('DOMContentLoaded', function () {
    inisialisasiEventListeners();
    hitungSkor();
});

/**
 * Inisialisasi event listener untuk elemen interaktif
 */
function inisialisasiEventListeners() {
    // Listener untuk semua radio status penyelesaian
    const radios = document.querySelectorAll('input[type="radio"].status-radio');
    radios.forEach(radio => {
        radio.addEventListener('change', hitungSkor);
    });

    // Listener untuk filter dropdown role
    const filterSelect = document.getElementById('filterRole');
    if (filterSelect) {
        filterSelect.addEventListener('change', function () {
            filterBerdasarkanRole(this.value);
        });
    }

    // Listener tombol cetak
    const btnPrint = document.getElementById('btnPrint');
    if (btnPrint) {
        btnPrint.addEventListener('click', function () {
            window.print();
        });
    }

    // Listener tombol reset
    const btnReset = document.getElementById('btnReset');
    if (btnReset) {
        btnReset.addEventListener('click', resetForm);
    }
}

/**
 * Filter baris skenario tugas berdasarkan peran yang dipilih
 * @param {string} role - 'all', 'admin', 'ketua', atau 'anggota'
 */
function filterBerdasarkanRole(role) {
    const rows = document.querySelectorAll('tbody tr.scenario-row');
    let visibleIndex = 1;

    rows.forEach(row => {
        const rowRole = row.getAttribute('data-role');
        if (role === 'all' || rowRole === role) {
            row.style.display = '';
            const noCell = row.querySelector('.row-number');
            if (noCell) {
                noCell.textContent = visibleIndex++;
            }
        } else {
            row.style.display = 'none';
        }
    });

    // Sinkronkan radio role responden di kop
    const roleRadio = document.querySelector(`input[name="meta_role"][value="${role}"]`);
    if (roleRadio) {
        roleRadio.checked = true;
    }

    hitungSkor();
}

/**
 * Menghitung skor keberhasilan tugas secara otomatis (Task Success Rate)
 */
function hitungSkor() {
    const visibleRows = Array.from(document.querySelectorAll('tbody tr.scenario-row')).filter(
        row => row.style.display !== 'none'
    );

    const totalTugas = visibleRows.length;
    let mandiri = 0;
    let dibantu = 0;
    let gagal = 0;

    visibleRows.forEach(row => {
        const checked = row.querySelector('input[type="radio"].status-radio:checked');
        if (checked) {
            const val = checked.value;
            if (val === 'mandiri') mandiri++;
            else if (val === 'dibantu') dibantu++;
            else if (val === 'gagal') gagal++;
        }
    });

    const terjawab = mandiri + dibantu + gagal;
    let persentase = 0;

    if (totalTugas > 0 && terjawab > 0) {
        // Rumus Evaluasi GDD: Mandiri bernilai 1.0, Dibantu bernilai 0.5, Gagal bernilai 0
        const totalPoin = (mandiri * 1.0) + (dibantu * 0.5);
        persentase = Math.round((totalPoin / totalTugas) * 100);
    }

    // Perbarui tampilan di bar ringkasan
    const elTotal = document.getElementById('statTotal');
    const elMandiri = document.getElementById('statMandiri');
    const elDibantu = document.getElementById('statDibantu');
    const elGagal = document.getElementById('statGagal');
    const elPersen = document.getElementById('statPersen');

    if (elTotal) elTotal.textContent = `${terjawab} / ${totalTugas}`;
    if (elMandiri) elMandiri.textContent = mandiri;
    if (elDibantu) elDibantu.textContent = dibantu;
    if (elGagal) elGagal.textContent = gagal;
    if (elPersen) elPersen.textContent = `${persentase}%`;
}

/**
 * Mengosongkan form pencatatan evaluasi
 */
function resetForm() {
    if (!confirm('Apakah Anda yakin ingin mereset seluruh isian lembar evaluasi ini?')) {
        return;
    }

    // Reset radio status
    const radios = document.querySelectorAll('input[type="radio"].status-radio');
    radios.forEach(r => r.checked = false);

    // Reset textarea catatan
    const textareas = document.querySelectorAll('textarea.notes-area');
    textareas.forEach(t => t.value = '');

    // Reset meta responden
    const inputs = document.querySelectorAll('.meta-value-input');
    inputs.forEach(i => i.value = '');

    hitungSkor();
}
