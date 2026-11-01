/**
 * ==============================================================================
 * FILE: app/assets/js/register.js
 * HALAMAN: Interaksi Form Registrasi Akun
 * ==============================================================================
 */

function togglePass(fieldId, btn) {
    const input = document.getElementById(fieldId);
    if (!input || !btn) return;
    const icon = btn.querySelector('i');

    if (input.type === 'password') {
        input.type = 'text';
        if (icon) {
            icon.classList.remove('bi-eye');
            icon.classList.add('bi-eye-slash');
        }
    } else {
        input.type = 'password';
        if (icon) {
            icon.classList.remove('bi-eye-slash');
            icon.classList.add('bi-eye');
        }
    }
}
