/**
 * ==============================================================================
 * FILE: app/assets/js/login.js
 * HALAMAN: Interaksi Login Pengguna
 * ==============================================================================
 */

function fillDemo(email, pass) {
    const elEmail = document.getElementById('email');
    const elPass = document.getElementById('password');
    if (elEmail && elPass) {
        elEmail.value = email;
        elPass.value = pass;
    }
}

function togglePasswordVisibility(inputId, iconId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);
    if (!input || !icon) return;

    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    }
}
