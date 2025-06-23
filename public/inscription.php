<?php
$pageTitle = 'Créer un compte - EcoRide';
require_once 'header.php';


?>
<main>
    <!-- Formulaire nouvelle inscription -->
    <section class="mt-5">
        <h2 class="fw-bold mb-4">Votre inscription</h2>
        <form action="index.php" method="get" id="formSearchDestination" class="p-4 bg-white rounded-4 shadow-sm">

            <!-- Prénom utilisateur -->
            <div class="input-group mb-3 bg-light rounded-3">
                    <span class="input-group-text bg-transparent border-0">
                       <i class="bi bi-person text-secondary"></i>
                   </span>
                <input type="text" name="firstName" class="form-control border-0 bg-transparent" id="firstName" placeholder="Prénom" required>
            </div>

            <!-- Nom utilisateur -->
            <div class="input-group mb-3 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-person text-secondary"></i>
            </span>
                <input type="text" name="LastName" class="form-control border-0 bg-transparent" id="lastName" placeholder="Nom" required>
            </div>

            <!-- Email utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-envelope-at text-secondary"></i>
            </span>
                <input type="email" name="email" class="form-control border-0 bg-transparent" id="email" placeholder="Email" required>
            </div>

            <!-- Mot de passe utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-lock text-secondary"></i>
            </span>
                <input type="password" name="password" class="form-control border-0 bg-transparent" id="password" placeholder="Mot de passe" required>
            </div>

            <!-- Confirmation mot de passe utilisateur -->
            <div class="input-group mb-4 bg-light rounded-3">
            <span class="input-group-text bg-transparent border-0">
                <i class="bi bi-lock text-secondary"></i>
            </span>
                <input type="password" name="confirmPassword" class="form-control border-0 bg-transparent" id="confirmPassword" placeholder="Confirmer le mot de passe" required>
            </div>

            <!-- Bouton s'inscrire -->
            <div class="d-grid">
                <button type="submit" class="btn btn-success d-flex justify-content-center align-items-center gap-2 rounded-3">
                    <i class="bi bi-search"></i> S'inscrire
                </button>
            </div>
        </form>
    </section>
</main>

<?php
$newUser = [];
if (isset($_GET['firstname']) && isset($_GET['lastname']) && isset($_GET['email']) && isset($_GET['password']) && isset($_GET['confirmPassword'])) {
    $newUser = [
        'firstname' => $_GET['firstname'],
        'lastname' => $_GET['lastname'],
        'email' => $_GET['email'],
        'password' => $_GET['password'],
        'confirmPassword' => $_GET['confirmPassword']
    ];
}
echo '<pre>';
print_r($newUser);
echo '</pre>';
?>

<?php
require_once 'footer.php';
?>
