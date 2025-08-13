/**
 * Dashboard d'administration EcoRide
 * Système de gestion des utilisateurs, statistiques et commentaires
 *
 * @author EcoRide Team
 * @version 2.0 - Refactorisé pour plus de clarté
 */

/**
 * ===== INITIALISATION PRINCIPALE =====
 */
document.addEventListener('DOMContentLoaded', function () {
    console.log('Dashboard EcoRide - Initialisation');

    // Initialisation des modules
    initializeUserManagement();
    initializeCommentsModule();
    initializeChartsModule();
});

/**
 * ===== MODULE DE GESTION DES UTILISATEURS =====
 */
function initializeUserManagement() {
    console.log('Initialisation du module utilisateurs');

    // Charger les rôles autorisés
    loadUserAllowedRoles();

    // Initialiser les gestionnaires d'événements
    setupUserSearchHandlers();
    setupUserCreationHandler();
}

/**
 * Charge les rôles que l'utilisateur connecté peut créer/assigner
 */
function loadUserAllowedRoles() {
    fetch('api/get_allowed_roles.php')
        .then(response => response.json())
        .then(rolesData => {
            if (rolesData.success) {
                updateRoleSelectOptions(rolesData.allowed_roles);
            } else {
                console.error('Erreur lors du chargement des rôles:', rolesData);
            }
        })
        .catch(error => {
            console.error('Erreur API get_allowed_roles:', error);
        });
}

/**
 * Met à jour les options du select des rôles
 * @param {Array|Object} allowedRoles - Rôles autorisés
 */
function updateRoleSelectOptions(allowedRoles) {
    const roleSelectElement = document.getElementById('role');
    if (!roleSelectElement) return;

    roleSelectElement.innerHTML = '';

    if (Array.isArray(allowedRoles)) {
        allowedRoles.forEach((roleLabel, roleIndex) => {
            const optionElement = document.createElement('option');
            optionElement.value = roleIndex;
            optionElement.textContent = roleLabel;
            roleSelectElement.appendChild(optionElement);
        });
    } else if (typeof allowedRoles === 'object') {
        Object.entries(allowedRoles).forEach(([roleValue, roleLabel]) => {
            const optionElement = document.createElement('option');
            optionElement.value = roleValue;
            optionElement.textContent = roleLabel;
            roleSelectElement.appendChild(optionElement);
        });
    }
}

/**
 * Initialise les gestionnaires pour la recherche d'utilisateurs
 */
function setupUserSearchHandlers() {
    const searchButton = document.getElementById('searchButton');
    const resetFiltersButton = document.getElementById('resetFilters');

    if (searchButton) {
        searchButton.addEventListener('click', performUserSearch);
    }

    if (resetFiltersButton) {
        resetFiltersButton.addEventListener('click', resetSearchFilters);
    }
}

/**
 * Remet à zéro tous les filtres de recherche
 */
function resetSearchFilters() {
    const searchInput = document.getElementById('searchUserInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');
    const userResults = document.getElementById('userResults');

    if (searchInput) searchInput.value = '';
    if (roleFilter) roleFilter.value = '';
    if (statusFilter) statusFilter.value = '';
    if (userResults) userResults.style.display = 'none';
}

/**
 * Effectue une recherche d'utilisateurs avec les filtres actuels
 */
function performUserSearch() {
    const searchInput = document.getElementById('searchUserInput');
    const roleFilter = document.getElementById('roleFilter');
    const statusFilter = document.getElementById('statusFilter');

    const searchQuery = searchInput ? searchInput.value.trim() : '';
    const selectedRole = roleFilter ? roleFilter.value : '';
    const selectedStatus = statusFilter ? statusFilter.value : '';

    const searchParams = new URLSearchParams();
    if (searchQuery) searchParams.append('query', searchQuery);
    if (selectedRole) searchParams.append('role', selectedRole);
    if (selectedStatus) searchParams.append('status', selectedStatus);

    fetch(`api/get_users.php?${searchParams.toString()}`)
        .then(response => response.json())
        .then(usersData => {
            displayUserSearchResults(usersData);
        })
        .catch(error => {
            console.error('Erreur lors de la recherche:', error);
            alert('Erreur lors de la recherche des utilisateurs');
        });
}

/**
 * Affiche les résultats de recherche dans le tableau
 * @param {Array} users - Liste des utilisateurs trouvés
 */
function displayUserSearchResults(users) {
    const userTableBody = document.getElementById('userTableBody');
    const userResults = document.getElementById('userResults');

    if (!userTableBody || !userResults) return;

    if (!Array.isArray(users) || users.length === 0) {
        userTableBody.innerHTML = '<tr><td colspan="8" class="text-center">Aucun utilisateur trouvé</td></tr>';
        userResults.style.display = 'block';
        return;
    }

    const tableRowsHtml = users.map(user => {
        const userPermissions = user.permissions || {};
        const actionButtonsHtml = buildUserActionButtons(user.user_id, user.firstname, user.lastname, userPermissions);

        return `
            <tr>
                <td>${user.firstname} ${user.lastname}</td>
                <td>${user.email}</td>
                <td>
                    ${getUserRoleLabel(user.role)}
                    ${!userPermissions.can_edit_role && (user.role >= 2) ? '<i class="bi bi-lock text-muted ms-1" title="Rôle protégé"></i>' : ''}
                </td>
                <td><span class="badge ${user.status === 'actif' ? 'bg-success' : 'bg-warning'}">${user.status}</span></td>
                <td>${user.credits || 0}</td>
                <td>${user.ranking || 0}/5</td>
                <td>${formatDateForDisplay(user.created_at)}</td>
                <td>${actionButtonsHtml}</td>
            </tr>
        `;
    }).join('');

    userTableBody.innerHTML = tableRowsHtml;
    userResults.style.display = 'block';
}

/**
 * Construit les boutons d'action pour un utilisateur
 * @param {number} userId - ID de l'utilisateur
 * @param {string} firstName - Prénom
 * @param {string} lastName - Nom
 * @param {Object} permissions - Permissions de l'utilisateur connecté
 * @returns {string} HTML des boutons d'action
 */
function buildUserActionButtons(userId, firstName, lastName, permissions) {
    let actionButtonsHtml = '';

    if (permissions.can_edit_profile || permissions.can_edit_role) {
        actionButtonsHtml += `
            <button class="btn btn-sm btn-primary me-1" onclick="editUser(${userId})" title="Modifier">
                <i class="bi bi-pencil"></i>
            </button>
        `;
    }

    if (permissions.can_reset_password) {
        actionButtonsHtml += `
            <button class="btn btn-sm btn-warning me-1" onclick="resetPassword(${userId}, '${firstName} ${lastName}')" title="Réinitialiser mot de passe">
                <i class="bi bi-key"></i>
            </button>
        `;
    }

    if (!permissions.can_edit_profile && !permissions.can_edit_role && !permissions.can_reset_password) {
        actionButtonsHtml += `
            <span class="badge bg-secondary" title="Lecture seule">
                <i class="bi bi-eye"></i> Lecture seule
            </span>
        `;
    }

    return actionButtonsHtml;
}

/**
 * Initialise le gestionnaire de création d'utilisateur
 */
function setupUserCreationHandler() {
    const createUserForm = document.getElementById('createUserForm');
    if (!createUserForm) return;

    createUserForm.addEventListener('submit', function (event) {
        event.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;

        // État de chargement
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Création en cours...';

        fetch('api/create_user.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(creationResult => {
                if (creationResult.success) {
                    this.reset();
                    bootstrap.Modal.getInstance(document.getElementById('createUserModal')).hide();
                    showUserCreationResultModal(creationResult);

                    // Rafraîchir les résultats si affichés
                    const userResults = document.getElementById('userResults');
                    if (userResults && userResults.style.display !== 'none') {
                        performUserSearch();
                    }
                } else {
                    alert('Erreur: ' + (creationResult.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur création utilisateur:', error);
                alert('Erreur de communication avec le serveur');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
    });
}

/**
 * Affiche le modal de résultat de création d'utilisateur
 * @param {Object} creationData - Données retournées par l'API
 */
function showUserCreationResultModal(creationData) {
    const isEmailSentSuccessfully = creationData.email_sent;
    const isEmailFailed = !creationData.email_sent;

    const alertClass = isEmailSentSuccessfully ? 'alert-success' : 'alert-warning';
    const alertIcon = isEmailSentSuccessfully ? 'bi-check-circle-fill' : 'bi-exclamation-triangle-fill';
    const headerClass = isEmailSentSuccessfully ? 'bg-success' : 'bg-warning';
    const alertTitle = isEmailSentSuccessfully ?
        'Utilisateur créé et email envoyé avec succès !' :
        'Utilisateur créé, mais problème avec l\'email';

    const modalHtml = `
        <div class="modal fade" id="userCreationResultModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header ${headerClass} text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-person-check-fill"></i> 
                            Utilisateur créé avec succès
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="alert ${alertClass}">
                            <i class="bi ${alertIcon}"></i> 
                            <strong>${alertTitle}</strong>
                            ${isEmailFailed ? `<br><small>Erreur email : ${creationData.email_error || 'Erreur inconnue'}</small>` : ''}
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <h6><i class="bi bi-person-fill"></i> Informations utilisateur :</h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="mb-2"><strong>Nom :</strong> ${creationData.user_name}</div>
                                    <div class="mb-2"><strong>Email :</strong> ${creationData.user_email}</div>
                                    <div class="mb-2"><strong>ID :</strong> #${creationData.user_id}</div>
                                    <div class="mb-2"><strong>Statut :</strong> <span class="badge bg-success">Créé en base</span></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <h6><i class="bi bi-envelope-fill"></i> Statut email :</h6>
                                <div class="bg-light p-3 rounded">
                                    <div class="mb-2">
                                        <strong>Email envoyé :</strong> 
                                        <span class="badge ${isEmailSentSuccessfully ? 'bg-success">OUI' : 'bg-danger">NON'}</span>
                                    </div>
                                    <div class="small ${isEmailSentSuccessfully ? 'text-success' : 'text-danger'}">
                                        ${isEmailSentSuccessfully ?
        'L\'utilisateur peut maintenant créer son mot de passe' :
        `Erreur : ${creationData.email_error || 'Inconnue'}`
    }
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        ${isEmailFailed ? `
                            <button type="button" class="btn btn-warning" onclick="retryEmailActivation(${creationData.user_id})">
                                <i class="bi bi-arrow-repeat"></i> Renvoyer l'email
                            </button>
                        ` : ''}
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            <i class="bi bi-check-lg"></i> Compris !
                        </button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Supprimer l'ancien modal et afficher le nouveau
    const existingModal = document.getElementById('userCreationResultModal');
    if (existingModal) existingModal.remove();

    document.body.insertAdjacentHTML('beforeend', modalHtml);
    const modal = new bootstrap.Modal(document.getElementById('userCreationResultModal'));
    modal.show();

    // Nettoyage après fermeture
    document.getElementById('userCreationResultModal').addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

/**
 * ===== MODULE DE GESTION DES COMMENTAIRES =====
 */
function initializeCommentsModule() {
    console.log('Initialisation du module commentaires');

    // Chargement initial des commentaires
    loadCommentsWithFilters();

    // Gestionnaire pour le formulaire de filtres
    const commentsFilterForm = document.getElementById("commentsFilterForm");
    if (commentsFilterForm) {
        commentsFilterForm.addEventListener("submit", function (event) {
            event.preventDefault();

            const filtersData = {
                rating: this.rating.value,
                date_min: this.date_min.value,
                date_max: this.date_max.value
            };

            loadCommentsWithFilters(filtersData);
        });
    }
}

/**
 * Charge et affiche les commentaires avec filtres optionnels
 * @param {Object} filters - Filtres à appliquer
 */
function loadCommentsWithFilters(filters = {}) {
    let apiUrl = "api/get_comments.php";
    const urlParams = new URLSearchParams(filters).toString();

    if (urlParams) {
        apiUrl += "?" + urlParams;
    }

    fetch(apiUrl)
        .then(response => response.json())
        .then(commentsData => {
            displayCommentsTable(commentsData);
        })
        .catch(error => {
            console.error('Erreur lors du chargement des commentaires:', error);
        });
}

/**
 * Affiche les commentaires dans un tableau
 * @param {Array} commentsData - Liste des commentaires
 */
function displayCommentsTable(commentsData) {
    const commentsTableContainer = document.getElementById("commentsTableContainer");
    if (!commentsTableContainer) return;

    if (!Array.isArray(commentsData) || commentsData.length === 0) {
        commentsTableContainer.innerHTML = '<div class="alert alert-warning">Aucun commentaire trouvé</div>';
        return;
    }

    let commentsTableHtml = `
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Voyage ID</th>
                    <th>Date</th>
                    <th>Voyageur</th>
                    <th>Départ</th>
                    <th>Arrivée</th>
                    <th>Montant payé</th>
                    <th>Ranking</th>
                    <th>Commentaire</th>
                    <th>Chauffeur</th>
                </tr>
            </thead>
            <tbody>
    `;

    commentsData.forEach(comment => {
        commentsTableHtml += `
            <tr>
                <td>${comment.trip_id}</td>
                <td>${formatDateForDisplay(comment.trip_date)}</td>
                <td>${comment.voyager_firstname} ${comment.voyager_lastname}</td>
                <td>${comment.start_city}</td>
                <td>${comment.end_city}</td>
                <td>${comment.price_per_passenger || "-"}</td>
                <td>${comment.rating} ⭐</td>
                <td>${comment.commentaire}</td>
                <td>${comment.driver_firstname} ${comment.driver_lastname}</td>
            </tr>
        `;
    });

    commentsTableHtml += "</tbody></table>";
    commentsTableContainer.innerHTML = commentsTableHtml;
}

/**
 * ===== MODULE DE VISUALISATION (GRAPHIQUES) =====
 */
function initializeChartsModule() {
    console.log('Initialisation des graphiques');

    initializeCommissionsChart();
    initializeTripsChart();
}

/**
 * Initialise le graphique des commissions mensuelles
 */
function initializeCommissionsChart() {
    const commissionsChartCanvas = document.getElementById("chartCommissions");

    if (commissionsChartCanvas && typeof monthlyData !== "undefined") {
        new Chart(commissionsChartCanvas, {
            type: "line",
            data: {
                labels: monthlyData.map(dataPoint => dataPoint.month),
                datasets: [{
                    label: "Crédits collectés (par mois)",
                    data: monthlyData.map(dataPoint => parseFloat(dataPoint.total)),
                    backgroundColor: "rgba(25, 135, 84, 0.2)",
                    borderColor: "rgba(25, 135, 84, 1)",
                    borderWidth: 2
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value + " crédits";
                            }
                        }
                    }
                }
            }
        });
    }
}

/**
 * Initialise le graphique des trajets par jour
 */
function initializeTripsChart() {
    const tripsChartCanvas = document.getElementById("chartTripsByDay");

    if (tripsChartCanvas && typeof tripsByDay !== "undefined") {
        new Chart(tripsChartCanvas, {
            type: "bar",
            data: {
                labels: tripsByDay.map(dataPoint => dataPoint.day),
                datasets: [
                    {
                        label: "Trajets actifs",
                        data: tripsByDay.map(dataPoint => parseInt(dataPoint.valid_trips)),
                        backgroundColor: "rgba(25, 135, 84, 0.7)"
                    },
                    {
                        label: "Trajets annulés",
                        data: tripsByDay.map(dataPoint => parseInt(dataPoint.cancelled_trips)),
                        backgroundColor: "rgba(220, 53, 69, 0.7)"
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: "Nombre de trajets"
                        }
                    }
                }
            }
        });
    }
}

/**
 * ===== FONCTIONS UTILITAIRES =====
 */

/**
 * Convertit un code de rôle en libellé
 * @param {number|string} role - Code du rôle
 * @returns {string} Libellé du rôle
 */
function getUserRoleLabel(role) {
    const roleLabels = {
        0: 'Passager',
        1: 'Passager / Chauffeur',
        2: 'Gestionnaire',
        3: 'Administrateur'
    };
    return roleLabels[role] || 'Inconnu';
}

/**
 * Formate une date pour l'affichage
 * @param {string} dateString - Date au format ISO
 * @returns {string} Date formatée en français
 */
function formatDateForDisplay(dateString) {
    if (!dateString) return '';
    try {
        return new Date(dateString).toLocaleDateString('fr-FR');
    } catch (error) {
        console.error('Erreur de formatage de date:', error);
        return dateString;
    }
}

/**
 * ===== FONCTIONS GLOBALES (compatibilité HTML) =====
 */

/**
 * Édite un utilisateur (appelée depuis le HTML)
 * @param {number} userId - ID de l'utilisateur à éditer
 */
window.editUser = function (userId) {
    fetch(`api/get_user_full_details.php?id=${userId}`)
        .then(response => {
            if (!response.ok) throw new Error(`HTTP ${response.status}`);
            return response.json();
        })
        .then(userDetails => {
            if (userDetails.error) throw new Error(userDetails.error);

            fillEditUserForm(userDetails);
            new bootstrap.Modal(document.getElementById('editUserModal')).show();
        })
        .catch(error => {
            console.error('Erreur édition utilisateur:', error);
            alert(`Erreur: ${error.message}`);
        });
};

/**
 * Remplit le formulaire d'édition
 * @param {Object} userDetails - Détails de l'utilisateur
 */
function fillEditUserForm(userDetails) {
    document.getElementById('editUserId').value = userDetails.user_id;
    document.getElementById('editFirstName').value = userDetails.firstname;
    document.getElementById('editLastName').value = userDetails.lastname;
    document.getElementById('editEmail').value = userDetails.email;
    document.getElementById('editStatus').value = userDetails.status;

    const editRoleSelect = document.getElementById('editRole');
    if (userDetails.permissions && !userDetails.permissions.can_edit_role) {
        editRoleSelect.innerHTML = `
            <option value="${userDetails.role}" selected disabled>
                ${getUserRoleLabel(userDetails.role)} (lecture seule)
            </option>
        `;
        editRoleSelect.disabled = true;
    } else {
        loadEditableRoleOptions(userDetails.role);
    }
}

/**
 * Charge les options de rôles pour l'édition
 * @param {number} currentRole - Rôle actuel
 */
function loadEditableRoleOptions(currentRole) {
    fetch('api/get_allowed_roles.php')
        .then(response => response.json())
        .then(rolesData => {
            if (rolesData.success) {
                const editRoleSelect = document.getElementById('editRole');
                editRoleSelect.innerHTML = '';
                editRoleSelect.disabled = false;

                if (Array.isArray(rolesData.allowed_roles)) {
                    rolesData.allowed_roles.forEach((roleLabel, roleIndex) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = roleIndex;
                        optionElement.textContent = roleLabel;
                        if (roleIndex === currentRole) optionElement.selected = true;
                        editRoleSelect.appendChild(optionElement);
                    });
                } else {
                    Object.entries(rolesData.allowed_roles).forEach(([roleValue, roleLabel]) => {
                        const optionElement = document.createElement('option');
                        optionElement.value = roleValue;
                        optionElement.textContent = roleLabel;
                        if (parseInt(roleValue) === currentRole) optionElement.selected = true;
                        editRoleSelect.appendChild(optionElement);
                    });
                }
            }
        })
        .catch(error => {
            console.error('Erreur chargement rôles édition:', error);
        });
}

/**
 * Réinitialise un mot de passe (appelée depuis le HTML)
 * @param {number} userId - ID de l'utilisateur
 * @param {string} userName - Nom complet
 */
window.resetPassword = function(userId, userName) {
    if (confirm(`Confirmer la réinitialisation du mot de passe de ${userName} ?`)) {
        console.log(`Réinitialisation mot de passe - User ID: ${userId}`);
        alert('Fonctionnalité de réinitialisation à implémenter');
    }
};

/**
 * Renvoie un email d'activation (appelée depuis le HTML)
 * @param {number} userId - ID de l'utilisateur
 */
window.retryEmailActivation = function(userId) {
    console.log(`Renvoi email activation - User ID: ${userId}`);
    alert('Fonctionnalité de renvoi d\'email à implémenter');
};

/**
 * Retour à la recherche (appelée depuis le HTML - ancienne fonctionnalité)
 */
window.retourRecherche = function() {
    const userDetails = document.getElementById("userDetails");
    if (userDetails) {
        userDetails.style.display = 'none';
    }
};

/**
 * Fonction utilitaire globale pour formater les dates (utilisée dans le HTML généré)
 * @param {string} dateString - Date à formater
 * @returns {string} Date formatée
 */
window.formatDateFr = formatDateForDisplay;