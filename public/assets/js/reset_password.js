// Affiche / Masque le mot de passe
function togglePasswordVisibility(inputId, iconId, buttonId) {
    const input = document.getElementById(inputId);
    const icon = document.getElementById(iconId);

    if (input.type === 'password') {
        input.type = 'text';
        icon.className = 'bi bi-eye';
    } else {
        input.type = 'password';
        icon.className = 'bi bi-eye-slash';
    }
}

document.getElementById('togglePassword1').addEventListener('click', () => {
    togglePasswordVisibility('password', 'passwordIcon1', 'togglePassword1');
});

document.getElementById('togglePassword2').addEventListener('click', () => {
    togglePasswordVisibility('confirmPassword', 'passwordIcon2', 'togglePassword2');
});

// Validation en temps réel
document.getElementById('confirmPassword').addEventListener('input', function() {
    const password = document.getElementById('password').value;
    const confirmPassword = this.value;

    if (confirmPassword && password !== confirmPassword) {
        this.setCustomValidity('Les mots de passe ne correspondent pas');
        this.classList.add('is-invalid');
    } else {
        this.setCustomValidity('');
        this.classList.remove('is-invalid');
    }
});