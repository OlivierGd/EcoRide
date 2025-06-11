<?php
    require 'header.php';
?>

<!-- Formulaire Multi-Étapes -->
<main class="pt-5 mt-5">
    <form class="multi-step-form">
        <!-- Étapes contrôlées par radio -->
        <input type="radio" name="step" id="step1" checked hidden>
        <input type="radio" name="step" id="step2" hidden>
        <input type="radio" name="step" id="step3" hidden>
        <input type="radio" name="step" id="step4" hidden>

        <div class="steps container py-4">

            <!-- Barre de progression -->
            <div class="progress mb-4">
                <div class="progress-bar bg-success" role="progressbar" style="width: 25%;" id="progressBar"></div>
            </div>

            <!-- Étape 1 : Itinéraire -->
            <div class="step step1">
                <h2>1. Itinéraire</h2>
                <div class="mb-3">
                    <label for="suggestedStartCity" class="form-label"><i class="bi bi-geo-alt"></i> Ville de départ</label>
                    <input type="text" class="form-control ps-5" id="suggestedStartCity" placeholder="Départ" required>
                </div>
                <div class="mb-3">
                    <label for="suggestedEndCity" class="form-label"><i class="bi bi-pin-map"></i> Destination</label>
                    <input type="text" class="form-control ps-5" id="suggestedEndCity" placeholder="Destination" required>
                </div>
                <p>+ Ajouter un arrêt supplémentaire</p>
            </div>

            <!-- Étape 2 : Date/Heure -->
            <div class="step step2">
                <h2>2. Date et Heure</h2>
                <div class="mb-3">
                    <label for="proposalDate" class="form-label">Date</label>
                    <input type="date" class="form-control" id="proposalDate" required>
                </div>
                <div class="mb-3">
                    <label for="proposalTime" class="form-label">Heure</label>
                    <input type="time" class="form-control" id="proposalTime" required>
                </div>
            </div>

            <!-- Étape 3 : Places/Prix -->
            <div class="step step3">
                <h2>3. Places et Prix</h2>
                <h3>Nombre de places disponibles</h3>
                <div class="mb-3">
                    <div class="btn-group" role="group">
                        <input type="radio" class="btn-check placeAvailable" name="places" id="place1" value="1">
                        <label class="btn btn-outline-success" for="place1">1</label>
                        <input type="radio" class="btn-check placeAvailable" name="places" id="place2" value="2">
                        <label class="btn btn-outline-success" for="place2">2</label>
                        <input type="radio" class="btn-check placeAvailable" name="places" id="place3" value="3" checked>
                        <label class="btn btn-outline-success" for="place3">3</label>
                        <input type="radio" class="btn-check placeAvailable" name="places" id="place4" value="4">
                        <label class="btn btn-outline-success" for="place4">4</label>
                        <input type="radio" class="btn-check placeAvailable" name="places" id="place5" value="5">
                        <label class="btn btn-outline-success" for="place5">5</label>
                    </div>
                </div>
                <h3>Prix par passager</h3>
                <div class="input-group flex-nowrap mb-3">
                    <span class="input-group-text">€</span>
                    <input type="number" class="form-control" id="priceRequested" placeholder="--" value="2" min="0" step="1" required>
                </div>
                <p>Jusqu'à <strong id="totalPrice"></strong> crédits pour ce trajet avec <strong id="placeFree"></strong> passagers</p>
            </div>

            <!-- Étape 4 : Options -->
            <div class="step step4">
                <h2>4. Options</h2>
                <h3>Préférences</h3>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="no-smoking" checked>
                    <label class="form-check-label" for="no-smoking">Non-fumeur</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="musicPlay" checked>
                    <label class="form-check-label" for="musicPlay">Musique autorisée</label>
                </div>
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" id="discussTogether" checked>
                    <label class="form-check-label" for="discussTogether">Discussions bienvenues</label>
                </div>
                <h3>Commentaire pour les passagers</h3>
                <div class="form-floating mb-3">
                    <textarea class="form-control" id="floatingTextarea2" style="height: 100px"></textarea>
                    <label for="floatingTextarea2">Ex: Je pars du parking de la gare de Lyon...</label>
                </div>
            </div>
        </div>
        <button type="submit" class="btn btn-lg btn-success" id="publishSuggestedForm">Publier ce trajet</button>
        <div class="p-5"></div>
    </form>
    <section>
        <div class="modal fade" id="confirmationModal" tabindex="-1" aria-labelledby="confirmationModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">

                    <div class="modal-header">
                        <h5 class="modal-title" id="confirmationModalLabel">Confirmer votre trajet</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fermer"></button>
                    </div>

                    <div class="modal-body">
                        <p id="modalText"></p>
                    </div>

                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="button" class="btn btn-primary" id="confirmSubmit">Confirmer</button>
                    </div>

                </div>
            </div>
        </div>
    </section>
</main>

<!-- Tab Bar -->
<footer>
    <?php require 'footer.php'; ?>
</footer>

<script src="assets/js/proposer.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js" integrity="sha384-j1CDi7MgGQ12Z7Qab0qlWQ/Qqz24Gc6BM0thvEMVjHnfYGF0rmFCozFSxQBxwHKO" crossorigin="anonymous"></script>
</body>
</html>
