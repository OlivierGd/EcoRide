<?php

require_once __DIR__ . '/../../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;

require_once __DIR__ . '/../functions/auth.php';
startSession();
requireAuth();
updateActivity();

?>

    <!-- === SECTION UTILISATEURS === -->
    <section id="users" class="mb-5">
        <div class="d-flex align-items-center mb-4">
            <h2 class="text-success me-3">Gestion des utilisateurs</h2>
            <button class="btn btn-success" onclick="createUser()">
                <i class="bi bi-person-plus"></i> Créer un utilisateur
            </button>
        </div>

        <!-- Recherche et filtres -->
        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <div class="position-relative">
                    <input type="text" class="form-control" id="searchUserInput"
                           placeholder="Rechercher par nom, prénom ou email (min. 3 caractères)" autocomplete="off">
                    <div id="searchSuggestions" class="position-absolute w-100 bg-white border border-top-0 rounded-bottom shadow-sm" style="z-index: 1000; display: none; max-height: 200px; overflow-y: auto;">

                    </div>
                </div>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="roleFilter">
                    <option value="">Tous les rôles</option>
                    <option value="0">Passager</option>
                    <option value="1">Passager / Chauffeur</option>
                    <option value="2">Gestionnaire</option>
                    <option value="3">Administrateur</option>
                </select>
            </div>
            <div class="col-md-2">
                <select class="form-select" id="statusFilter">
                    <option value="">Tous les statuts</option>
                    <option value="actif">Actif</option>
                    <option value="inactif">Inactif</option>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary" id="searchButton">
                    <i class="bi bi-search"></i> Rechercher
                </button>
            </div>
            <div class="col-md-2">
                <button class="btn btn-outline-secondary" id="resetFilters">
                    <i class="bi bi-arrow-clockwise"></i> Réinitialiser
                </button>
            </div>
        </div>

        <!-- Résultats de recherche -->
        <div id="userResults" class="table-responsive" style="display: none;">
            <table class="table table-striped">
                <thead class="table-dark">
                <tr>
                    <th>Nom</th>
                    <th>Rôle</th>
                    <th>Statut</th>
                    <th>Crédits</th>
                    <th>Ranking</th>
                    <th>Créé le</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody id="userTableBody">
                <!-- Les résultats seront injectés ici -->
                </tbody>
            </table>
        </div>

        <!-- Détails utilisateur sélectionné (gardé pour compatibilité avec l'ancien système) -->
        <div id="userDetails" style="display: none;"></div>
    </section>

    <!-- Modal Édition d'utilisateur -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Éditer l'utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="editUserForm">
                    <input type="hidden" id="editUserId" name="user_id">
                    <div class="modal-body">
                        <!-- Informations personnelles -->
                        <h6 class="text-success mb-3">Informations personnelles</h6>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="editFirstName" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="editFirstName" name="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editLastName" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="editLastName" name="lastName" required>
                            </div>
                            <div class="col-12">
                                <label for="editEmail" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="editEmail" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="editRole" class="form-label">Rôle *</label>
                                <select class="form-select" id="editRole" name="role" required>
                                    <option value="0">Passager</option>
                                    <option value="1">Passager / Chauffeur</option>
                                    <option value="2">Gestionnaire</option>
                                    <option value="3">Administrateur</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="editStatus" class="form-label">Statut *</label>
                                <select class="form-select" id="editStatus" name="status" required>
                                    <option value="actif">Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                        </div>

                        <!-- Véhicules -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h6 class="text-success mb-0">Véhicules</h6>
                            <button type="button" class="btn btn-sm btn-success" id="addVehicleBtn">
                                <i class="bi bi-plus-circle"></i> Ajouter un véhicule
                            </button>
                        </div>
                        <div id="vehiclesList">
                            <!-- Les véhicules seront injectés ici -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-check-circle"></i> Sauvegarder
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Template pour nouveau véhicule -->
    <template id="vehicleTemplate">
        <div class="vehicle-item border rounded p-3 mb-3">
            <div class="d-flex justify-content-between align-items-start mb-2">
                <h6 class="mb-0">Véhicule</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-vehicle">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
            <input type="hidden" class="vehicle-id" name="vehicles[0][id]" value="">
            <div class="row g-2">
                <div class="col-md-6">
                    <label class="form-label">Marque</label>
                    <input type="text" class="form-control vehicle-marque" name="vehicles[0][marque]" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Modèle</label>
                    <input type="text" class="form-control vehicle-modele" name="vehicles[0][modele]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Carburant</label>
                    <select class="form-select vehicle-carburant" name="vehicles[0][carburant]" required>
                        <option value="">Sélectionner</option>
                        <option value="Electrique">Électrique</option>
                        <option value="Hybride">Hybride</option>
                        <option value="Essence">Essence</option>
                        <option value="Gasoil">Gasoil</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Immatriculation</label>
                    <input type="text" class="form-control vehicle-immatriculation" name="vehicles[0][immatriculation]" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Places</label>
                    <input type="number" class="form-control vehicle-places" name="vehicles[0][places]" min="1" max="8" required>
                </div>
            </div>
        </div>
    </template>
    </section>

    <!-- Modal Création d'utilisateur -->
    <div class="modal fade" id="createUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Créer un nouvel utilisateur</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="createUserForm">
                    <div class="modal-body">
                        <!-- Affiche les messages d'erreurs -->
                        <div id="createUserErrors" class="mb-3"></div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="firstName" class="form-label">Prénom *</label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="col-md-6">
                                <label for="lastName" class="form-label">Nom *</label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                            <div class="col-12">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="col-md-6">
                                <label for="role" class="form-label">Rôle *</label>
                                <select class="form-select" id="role" name="role" required>
                                    <!-- Les options seront chargées dynamiquement selon les permissions -->
                                </select>
                                <small class="text-muted">Seuls les rôles que vous pouvez créer sont affichés</small>
                            </div>
                            <div class="col-md-6">
                                <label for="status" class="form-label">Statut *</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="actif" selected>Actif</option>
                                    <option value="inactif">Inactif</option>
                                </select>
                            </div>
                        </div>
                        <div class="mt-3">
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle"></i>
                                <strong>Procédure :</strong> Un email sera automatiquement envoyé à l'utilisateur
                                pour qu'il crée son propre mot de passe. Le lien sera valable 24 heures.
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-person-plus"></i> Créer et envoyer l'email
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</main>


