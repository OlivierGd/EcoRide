// Script pour le toggle du mot de passe existant
document.getElementById("togglePassword").addEventListener("click", function() {
    const password = document.getElementById("password");
    const icon = document.getElementById("passwordIcon");

    if (password.type === "password") {
        password.type = "text";
        icon.classList.remove("bi-eye");
        icon.classList.add("bi-eye-slash");
    } else {
        password.type = "password";
        icon.classList.remove("bi-eye-slash");
        icon.classList.add("bi-eye");
    }
})