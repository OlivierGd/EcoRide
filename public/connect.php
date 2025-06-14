<?php
require 'header.php';
?>

<body>
<div class="min-vh-100 d-flex flex-column px-3 py-4">
    <header class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php" class="btn btn-light p-2 rounded-circle">
            <i class="bi bi-arrow-left"></i>
        </a>
        <img src="/public/assets/pictures/logoEcoRide.png" alt="logo EcoRide" class="logo rounded" width="90em">
        <div style="width: 40px;"></div>
    </header>

    <!-- Main -->
    <main class="flex-fill">
        <h1 class="h4 fw-semibold text-center mb-4">Connexion</h1>
        <form id="loginForm" class="mb-4">
            <div class="mb-3 position-relative">
                <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                <i class="ri-mail-line"></i>
                </span>
                <input type="email" id="email" class="form-control ps-5" placeholder="Adresse e-mail" required>
            </div>

            <div class="mb-3 position-relative">
                <span class="position-absolute top-50 translate-middle-y start-0 ps-3 text-secondary">
                <i class="ri-lock-line"></i>
                </span>
                <input type="password" id="password" class="form-control ps-5 pe-5" placeholder="Mot de passe" required>
                <button type="button" id="togglePassword" class="btn position-absolute top-50 end-0 translate-middle-y pe-3">
                    <i class="ri-eye-off-line" id="passwordIcon"></i>
                </button>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="remember">
                    <label class="form-check-label" for="remember">Se souvenir de moi</label>
                </div>
                <a href="#" class="text-decoration-none text-success small">Mot de passe oublié ?</a>
            </div>

            <button type="submit" class="btn btn-success w-100">Se connecter</button>
        </form>

        <!-- Divider -->
        <div class="d-flex align-items-center my-4">
            <hr class="flex-grow-1">
            <span class="px-3 text-muted small">ou</span>
            <hr class="flex-grow-1">
        </div>

        <!-- Sign up -->
        <div class="text-center">
            <p class="text-muted mb-3">Pas encore utilisateur EcoRide ?</p>
            <button class="btn btn-outline-success w-100">Se créer un compte</button>
        </div>
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
