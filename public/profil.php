<?php
require 'functions/auth.php';
utilisateur_connecte();
$pageTitle = 'Mon profil - EcoRide';
require 'header.php';
?>

<body>
<nav class="navbar fixed-top bg-body-tertiary">
    <div class="container" style="max-width: 900px;">
        <a class="navbar-brand" href="index.html">
            <img src="assets/pictures/logoEcoRide.png" alt="Logo EcoRide" width="60" class="rounded">
        </a>
        <h2>Mon Profil</h2>
        <a href="#"><i class="bi bi-gear"></i></a>
    </div>
</nav>


<main>

    <!-- Section Profil personne connectée -->
    <section>
        <div class="container mt-5 pt-5">
            <div class="row justify-content-center">
                <div class="col-md-8">
                    <div class="profile-container">
                        <div class="text-center">
                            <img src="./assets/pictures/ThomasDubois.jpg" alt="Photo du profil du conducteur" class="profile-picture rounded-circle mb-3" style="width: 100px; height: 100px; object-fit: cover">
                            <h3>Thomas Dubois</h3>
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="text-gold">
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-fill"></i>
                                    <i class="bi bi-star-half"></i>
                                </span>
                                <span>(4.5)</span>
                            </div>
                            <div class="d-flex align-items-center justify-content-center mb-3">
                                <span class="badge bg-success text-white">Conducteur Vérifié</span>
                            </div>
                            <button class="btn btn-success btn-sm mt-2"><i class="fas fa-edit"></i> Modifier le profil</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats track done -->
    <section>
        <h2>Statistiques</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-4">
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-ev-front text-white"></i></h5>
                        <p class="card-text">Trajets effectués</p>
                        <p class="card-text"><strong>47</strong></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-person text-white"></i></h5>
                        <p class="card-text">Economies de CO2</p>
                        <p class="card-text"><strong>128 kg</strong></p>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card h-80">
                    <div class="card-body d-flex align-items-center flex-column">
                        <h5 class="card-title rounded-circle bg-success d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;"><i class="bi bi-leaf text-white"></i></h5>
                        <p class="card-text">Economies</p>
                        <p class="card-text"><strong>215 €</strong></p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Next Trip for connected user -->
    <section>
        <h2>Trajets à venir</h2>
        <div class="px-5 mb-8">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3 class="h5 font-weight-semibold text-dark">Trajets à venir</h3>
                <a href="#" class="text-primary small font-weight-medium">Voir tout</a>
            </div>

            <div class="upcoming-trips d-flex overflow-auto pb-2">
                <div class="min-vw-280 bg-white rounded shadow-sm p-4 border border-light">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="small font-weight-medium text-dark">Lundi, 13 mai</p>
                            <p class="small text-muted">Départ à 08:30</p>
                        </div>
                        <span class="px-2 py-1 bg-success text-white small rounded-pill">Confirmé</span>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 d-flex flex-column align-items-center">
                            <div class="rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                            <div class="bg-light" style="width: 2px; height: 40px;"></div>
                            <div class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                        </div>

                        <div>
                            <p class="small font-weight-medium text-dark">Lyon, Gare Part-Dieu</p>
                            <p class="small font-weight-medium text-dark mt-2">Grenoble, Centre-ville</p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle overflow-hidden mr-2" style="width: 32px; height: 32px;">
                                <img src="#" alt="Conducteur" class="w-100 h-100">
                            </div>
                            <p class="small text-muted">Marie L.</p>
                        </div>
                        <p class="small font-weight-bold text-dark">12,50 €</p>
                    </div>
                </div>

                <div class="min-vw-280 bg-white rounded shadow-sm p-4 border border-light">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <p class="small font-weight-medium text-dark">Vendredi, 17 mai</p>
                            <p class="small text-muted">Départ à 17:15</p>
                        </div>
                        <span class="px-2 py-1 bg-warning text-dark small rounded-pill">En attente</span>
                    </div>

                    <div class="d-flex align-items-center mb-3">
                        <div class="mr-3 d-flex flex-column align-items-center">
                            <div class="rounded-circle bg-primary" style="width: 12px; height: 12px;"></div>
                            <div class="bg-light" style="width: 2px; height: 40px;"></div>
                            <div class="rounded-circle bg-secondary" style="width: 12px; height: 12px;"></div>
                        </div>

                        <div>
                            <p class="small font-weight-medium text-dark">Lyon, Part-Dieux</p>
                            <p class="small font-weight-medium text-dark mt-2">Annecy, Gare</p>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center">
                            <div class="rounded-circle overflow-hidden mr-2" style="width: 32px; height: 32px;">
                                <img src="#" alt="Conducteur" class="w-100 h-100">
                            </div>
                            <p class="small text-muted">Lucas B.</p>
                        </div>
                        <p class="small font-weight-bold text-dark">15,00 €</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!--Preference de covoiturage-->
    <section>
        <div class="px-5 mb-8">
            <h3 class="h6 mb-3">
                Préférences de covoiturage
            </h3>
            <div class="bg-white rounded shadow-sm border border-light">
                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Conversation</p>
                            <p class="mb-0 text-muted">Je préfère discuter pendant le trajet</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" checked />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Musique</p>
                            <p class="mb-0 text-muted">J'aime écouter de la musique</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" checked />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4 border-bottom">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Animaux acceptés</p>
                            <p class="mb-0 text-muted">Petits animaux en cage uniquement</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>

                <div class="p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="mb-0 font-weight-medium">Fumeur</p>
                            <p class="mb-0 text-muted">Je préfère les trajets non-fumeur</p>
                        </div>
                        <label class="d-flex align-items-center cursor-pointer">
                            <input type="checkbox" />
                            <span class="custom-checkbox"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Paramètres du compte -->
    <section>
        <div class="pb-5">
            <h3>Paramètres du compte</h3>
            <div class="container my-4">
                <div class="list-group">
                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-geo-alt-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Informations personnelles</h6>
                            <small class="text-muted">Mes préférences</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-geo-alt-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Mes Trajets</h6>
                            <small class="text-muted">Trajets à venir et historique</small>
                        </div>
                    </a>

                    <a href="vehicule.html" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-primary">
                            <i class="bi bi-car-front-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Mes Véhicules</h6>
                            <small class="text-muted">Gérer vos véhicules enregistrés</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-purple">
                            <i class="bi bi-credit-card-2-front-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Paiements et Transactions</h6>
                            <small class="text-muted">Méthodes de paiement et historique</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-success">
                            <i class="bi bi-leaf-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Préférences Écologiques</h6>
                            <small class="text-muted">Options de voyage écologique</small>
                        </div>
                    </a>

                    <a href="#" class="list-group-item list-group-item-action d-flex align-items-start">
                        <div class="me-3 text-secondary">
                            <i class="bi bi-question-circle-fill fs-4"></i>
                        </div>
                        <div>
                            <h6 class="mb-1 fw-semibold">Aide et Support</h6>
                            <small class="text-muted">Questions fréquentes et assistance</small>
                        </div>
                    </a>
                </div>
            </div>
        </div>
    </section>
</main>

<footer>
    <?php require 'footer.php'; ?>
</footer>


<script src="assets/js/profil.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
