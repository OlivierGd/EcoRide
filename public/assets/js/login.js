(function() {
    document.getElementById("togglePassword").addEventListener("click", function () {
        let passwordInput = document.getElementById("password");
        let passwordToggleIcon = document.getElementById("passwordIcon");

        if (passwordInput.type === "password") {
            // Affiche le texte du mot de passe
            passwordInput.type = "text";
            passwordToggleIcon.classList.remove("bi-eye");
            passwordToggleIcon.classList.add("bi-eye-slash");

        } else {
            // Cacher le texte du mot de passe
            passwordInput.type = "password";
            passwordToggleIcon.classList.remove("bi-eye-slash");
            passwordToggleIcon.classList.add("bi-eye");
        }
    });
})();
