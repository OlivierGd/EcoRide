<?php
require_once 'header.php';
$pageTitle = 'Mes véhicules';
?>


<main>
    <div class="container my-3">

        <div class="d-flex justify-content-between align-items-center mb-3">
            <button class="btn btn-link text-dark p-0"><i class="bi bi-chevron-left fs-5"></i></button>
            <h5 class="fw-bold m-0">Mes Véhicules</h5>
            <button class="btn btn-link text-success p-0"><i class="bi bi-plus fs-4"></i></button>
        </div>

        <!-- Véhicule 1 -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-car-front-fill text-primary fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold">Tesla Model 3</h6>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge bg-success-subtle text-success border border-success me-2">Électrique</span>
                        <small class="text-muted me-2">5 places</small>
                        <small class="text-muted">AB-123-CD</small>
                    </div>
                </div>
                <div class="ms-2">
                    <i class="bi bi-three-dots-vertical text-muted"></i>
                </div>
            </div>
        </div>

        <!-- Véhicule 2 -->
        <div class="card mb-3 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-car-front-fill text-primary fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold">Peugeot e-208</h6>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge bg-success-subtle text-success border border-success me-2">Électrique</span>
                        <small class="text-muted me-2">4 places</small>
                        <small class="text-muted">EF-456-GH</small>
                    </div>
                </div>
                <div class="ms-2">
                    <i class="bi bi-three-dots-vertical text-muted"></i>
                </div>
            </div>
        </div>

        <!-- Véhicule 3 -->
        <div class="card mb-5 border-0 shadow-sm">
            <div class="card-body d-flex align-items-center">
                <div class="me-3 bg-light rounded-circle d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                    <i class="bi bi-car-front-fill text-primary fs-5"></i>
                </div>
                <div class="flex-grow-1">
                    <h6 class="mb-1 fw-semibold">Renault Clio</h6>
                    <div class="d-flex align-items-center flex-wrap">
                        <span class="badge bg-warning-subtle text-warning border border-warning me-2">Hybride</span>
                        <small class="text-muted me-2">5 places</small>
                        <small class="text-muted">IJ-789-KL</small>
                    </div>
                </div>
                <div class="ms-2">
                    <i class="bi bi-three-dots-vertical text-muted"></i>
                </div>
            </div>
        </div>

        <!-- Bouton flottant pour ajouter un véhicule -->
        <button class="btn btn-success rounded-circle position-fixed bottom-1 end-0 m-4 shadow" data-bs-toggle="modal" data-bs-target="#ajoutVehiculeModal" style="width: 56px; height: 56px;">
            <i class="bi bi-plus-lg fs-4"></i>
        </button>

        <!-- Modale ajout d'un véhicule -->
        <div class="modal fade" id="ajoutVehiculeModal" tabindex="-1" aria-labelledby="ajoutVehiculeLabel" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content rounded-4">
                    <div class="modal-header">
                        <h5 class="modal-title fw-bold" id="ajoutVehiculeLabel">Ajouter un véhicule</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>
                    <div class="modal-body">
                        <form id="formAjoutVehicule">

                            <!-- Modèle -->
                            <div class="mb-3">
                                <label for="modeleVehicule" class="form-label">Modèle du véhicule :</label>
                                <input type="text" class="form-control" id="modeleVehicule" placeholder="Ex: Tesla Model 3">
                            </div>

                            <!-- Type -->
                            <div class="mb-3">
                                <label for="typeVehicule" class="form-label">Énergie utilisé :</label>
                                <select class="form-select" id="typeVehicule">
                                    <option value="" selected disabled>Type d'énergie</option>
                                    <option value="electrique">Électrique</option>
                                    <option value="hybride">Hybride</option>
                                    <option value="essence">Essence</option>
                                    <option value="gasoil">Gasoil</option>
                                </select>
                            </div>

                            <!-- Plaque -->
                            <div class="mb-3">
                                <label for="plaqueVehicule" class="form-label">Plaque d'immatriculation :</label>
                                <input type="text" class="form-control" id="plaqueVehicule" placeholder="Ex: AB-123-CD">
                            </div>

                            <!-- Nombre de places -->
                            <div class="mb-3">
                                <label for="nbPlaces" class="form-label">Nombre de places :</label>
                                <select class="form-select" id="nbPlaces">
                                    <option value="" selected disabled>Choisissez</option>
                                    <option>1</option>
                                    <option>2</option>
                                    <option>3</option>
                                    <option>4</option>
                                    <option>5</option>
                                </select>
                            </div>

                        </form>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" form="formAjoutVehicule" class="btn btn-success w-100">Ajouter</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</main>

<?php require_once 'footer.php'; ?>

<script src="assets/js/vehicule.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
