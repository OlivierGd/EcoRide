/**
 * Dashboard d'administration EcoRide
 * Système de gestion des utilisateurs, statistiques et commentaires
 *
 * @author EcoRide Team
 */

/**
 * ===== MODULE DE GRAPHIQUES =====
 */
function initializeChartsModule() {

    // Initialiser le graphique des trajets par jour
    if (document.getElementById('chartTripsByDay')) {
        initializeTripsByDayChart();
    }
    if (document.getElementById('chartCommissionsMonthly')) {
        initializeMonthlyCommissionsChart();
    }
}
/**
 * Initialise le graphique des trajets par jour
 */
function initializeTripsByDayChart() {
    console.log('Initialisation du graphique trajets par jour');

    // Vérifier si on a des données
    if (!tripsByDay || tripsByDay.length === 0) {
        console.warn('Aucune donnée pour le graphique des trajets par jour');
        return;
    }

    // Extraire les données pour Chart.js
    const labels = tripsByDay.map(item => item.day);
    const validTripsData = tripsByDay.map(item => item.valid_trips);
    const cancelledTripsData = tripsByDay.map(item => item.cancelled_trips);

    // Créer le graphique
    const ctx = document.getElementById('chartTripsByDay').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Trajets effectués',
                    data: validTripsData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Trajets annulés',
                    data: cancelledTripsData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre de trajets'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Jours'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                },
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
}

function initializeMonthlyCommissionsChart() {
    if (!monthlyData || monthlyData.length === 0) {
        console.warn('Aucune donnée mensuelle disponible');
        return;
    }

    const ctx = document.getElementById('chartCommissionsMonthly').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [{
                label: 'Commissions (en crédits)',
                data: monthlyData.map(item => item.total),
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Crédits gagnés'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Mois'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
}
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
    setupUserEditHandler();
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
 * Gestionnaire d'édition d'utilisateur
 */
function setupUserEditHandler() {
    const editUserForm = document.getElementById('editUserForm');
    if (!editUserForm) {
        console.warn('Formulaire editUserForm non trouvé');
        return;
    }

    editUserForm.addEventListener('submit', function(event) {
        event.preventDefault();

        const formData = new FormData(this);
        const submitButton = this.querySelector('button[type="submit"]');
        const originalButtonText = submitButton.innerHTML;

        // Debug : afficher les données envoyées
        console.log('Données du formulaire d\'édition:');
        for (let [key, value] of formData.entries()) {
            console.log(`  ${key}: ${value}`);
        }

        // État de chargement
        submitButton.disabled = true;
        submitButton.innerHTML = '<i class="bi bi-hourglass-split"></i> Modification en cours...';

        fetch('api/update_user.php', {
            method: 'POST',
            body: formData
        })
            .then(response => {
                console.log('Statut de la réponse:', response.status);
                return response.json();
            })
            .then(updateResult => {
                console.log('Résultat de la mise à jour:', updateResult);

                if (updateResult.success) {
                    // Fermer la modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('editUserModal'));
                    if (modal) {
                        modal.hide();
                    }

                    // Afficher un message de succès
                    showSuccessMessage(updateResult.message || 'Utilisateur modifié avec succès');

                    // Rafraîchir les résultats de recherche si affichés
                    const userResults = document.getElementById('userResults');
                    if (userResults && userResults.style.display !== 'none') {
                        performUserSearch();
                    }
                } else {
                    showErrorMessage('Erreur: ' + (updateResult.message || 'Erreur inconnue'));
                }
            })
            .catch(error => {
                console.error('Erreur modification utilisateur:', error);
                showErrorMessage('Erreur de communication avec le serveur');
            })
            .finally(() => {
                submitButton.disabled = false;
                submitButton.innerHTML = originalButtonText;
            });
    });
}

/**
 * ===== MODULE DE GESTION DES COMMENTAIRES =====
 */
function initializeCommentsModule() {
    console.log('Initialisation du module commentaires amélioré');

    // Chargement initial des commentaires
    loadCommentsWithFilters();

    // Gestionnaire pour le formulaire de filtres
    const commentsFilterForm = document.getElementById("commentsFilterForm");
    if (commentsFilterForm) {
        commentsFilterForm.addEventListener("submit", function (event) {
            event.preventDefault();
            console.log('🔍 Soumission du formulaire de filtres');

            const filtersData = extractFiltersFromForm();
            console.log('Filtres extraits:', filtersData);

            loadCommentsWithFilters(filtersData);
            updateActiveFiltersIndicator(filtersData);
        });
    }

    // Gestionnaire pour la sélection de période prédéfinie
    const periodFilter = document.getElementById("periodFilter");
    if (periodFilter) {
        periodFilter.addEventListener("change", function() {
            handlePeriodChange(this.value);
        });
    }

    // Gestionnaire pour le bouton reset
    const resetFiltersBtn = document.getElementById("resetFiltersBtn");
    if (resetFiltersBtn) {
        resetFiltersBtn.addEventListener("click", resetAllFilters);
    }

    // Gestionnaire pour supprimer tous les filtres
    const clearAllFilters = document.getElementById("clearAllFilters");
    if (clearAllFilters) {
        clearAllFilters.addEventListener("click", resetAllFilters);
    }
}

/**
 * Extrait les filtres du formulaire
 */
function extractFiltersFromForm() {
    const form = document.getElementById("commentsFilterForm");
    if (!form) return {};

    const formData = new FormData(form);
    const filters = {};

    for (let [key, value] of formData.entries()) {
        if (value && value.trim() !== '') {
            filters[key] = value.trim();
        }
    }

    return filters;
}

/**
 * Gère le changement de période prédéfinie
 */
function handlePeriodChange(selectedPeriod) {
    const dateStart = document.getElementById("dateStart");
    const dateEnd = document.getElementById("dateEnd");

    if (!selectedPeriod) {
        // Période personnalisée - ne rien faire
        return;
    }

    const dates = calculatePeriodDates(selectedPeriod);
    if (dates) {
        dateStart.value = dates.start;
        dateEnd.value = dates.end;

        // Auto-submit si période sélectionnée
        document.getElementById("commentsFilterForm").dispatchEvent(new Event('submit'));
    }
}

/**
 * Calcule les dates pour une période prédéfinie
 */
function calculatePeriodDates(period) {
    const today = new Date();
    const formatDate = (date) => date.toISOString().split('T')[0];

    switch(period) {
        case 'today':
            return {
                start: formatDate(today),
                end: formatDate(today)
            };

        case 'yesterday':
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            return {
                start: formatDate(yesterday),
                end: formatDate(yesterday)
            };

        case 'last_7_days':
            const week = new Date(today);
            week.setDate(today.getDate() - 7);
            return {
                start: formatDate(week),
                end: formatDate(today)
            };

        case 'last_30_days':
            const month = new Date(today);
            month.setDate(today.getDate() - 30);
            return {
                start: formatDate(month),
                end: formatDate(today)
            };

        case 'this_month':
            const startOfMonth = new Date(today.getFullYear(), today.getMonth(), 1);
            return {
                start: formatDate(startOfMonth),
                end: formatDate(today)
            };

        case 'last_month':
            const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const endLastMonth = new Date(today.getFullYear(), today.getMonth(), 0);
            return {
                start: formatDate(lastMonth),
                end: formatDate(endLastMonth)
            };

        case 'this_year':
            const startOfYear = new Date(today.getFullYear(), 0, 1);
            return {
                start: formatDate(startOfYear),
                end: formatDate(today)
            };

        default:
            return null;
    }
}

/**
 * Remet à zéro tous les filtres
 */
function resetAllFilters() {
    const form = document.getElementById("commentsFilterForm");
    if (form) {
        form.reset();

        // Réinitialiser aussi les dates
        document.getElementById("dateStart").value = '';
        document.getElementById("dateEnd").value = '';
        document.getElementById("periodFilter").value = '';

        // Recharger sans filtres
        loadCommentsWithFilters({});
        updateActiveFiltersIndicator({});
    }
}

/**
 * Met à jour l'indicateur de filtres actifs
 */
function updateActiveFiltersIndicator(filters) {
    const indicator = document.getElementById("activeFiltersIndicator");
    const textElement = document.getElementById("activeFiltersText");

    if (!indicator || !textElement) return;

    const activeFilters = Object.keys(filters).filter(key => filters[key]);

    if (activeFilters.length === 0) {
        indicator.style.display = 'none';
        return;
    }

    indicator.style.display = 'block';

    const filterDescriptions = [];

    if (filters.comment_status) {
        const statusLabels = {
            'approved': 'Approuvé',
            'pending': 'En attente',
            'rejected': 'Rejeté'
        };
        filterDescriptions.push(`Statut: ${statusLabels[filters.comment_status]}`);
    }

    if (filters.rating) {
        filterDescriptions.push(`Note: ${filters.rating}★ et plus`);
    }

    if (filters.period_preset) {
        const periodLabels = {
            'today': "Aujourd'hui",
            'yesterday': 'Hier',
            'last_7_days': '7 derniers jours',
            'last_30_days': '30 derniers jours',
            'this_month': 'Ce mois-ci',
            'last_month': 'Mois dernier',
            'this_year': 'Cette année'
        };
        filterDescriptions.push(`Période: ${periodLabels[filters.period_preset]}`);
    } else if (filters.date_start || filters.date_end) {
        let dateRange = 'Date: ';
        if (filters.date_start && filters.date_end) {
            dateRange += `du ${filters.date_start} au ${filters.date_end}`;
        } else if (filters.date_start) {
            dateRange += `depuis le ${filters.date_start}`;
        } else if (filters.date_end) {
            dateRange += `jusqu'au ${filters.date_end}`;
        }
        filterDescriptions.push(dateRange);
    }

    textElement.textContent = `Filtres actifs: ${filterDescriptions.join(', ')}`;
}

/**
 * Charge et affiche les commentaires avec filtres (VERSION AMÉLIORÉE)
 */
function loadCommentsWithFilters(filters = {}) {
    let apiUrl = "api/get_comments.php";

    // Si pas de filtres explicites, récupérer depuis le formulaire
    if (Object.keys(filters).length === 0) {
        filters = extractFiltersFromForm();
    }

    const urlParams = new URLSearchParams(filters).toString();
    if (urlParams) {
        apiUrl += "?" + urlParams;
    }

    console.log('🔍 URL API finale:', apiUrl);

    const container = document.getElementById("commentsTableContainer");
    if (container) {
        container.innerHTML = `
            <div class="text-center py-4">
                <div class="spinner-border text-success" role="status">
                    <span class="visually-hidden">Chargement...</span>
                </div>
                <p class="mt-2 text-muted">Application des filtres...</p>
            </div>
        `;
    }

    fetch(apiUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            return response.json();
        })
        .then(commentsData => {
            console.log('📊 Données reçues:', commentsData);
            displayCommentsTable(commentsData);

            // Afficher le nombre de résultats
            showResultsCount(commentsData.length, filters);
        })
        .catch(error => {
            console.error('❌ Erreur lors du chargement des commentaires:', error);
            if (container) {
                container.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Erreur lors du chargement des commentaires: ${error.message}
                    </div>
                `;
            }
        });
}

/**
 * Affiche le nombre de résultats trouvés
 */
function showResultsCount(count, filters) {
    const hasFilters = Object.keys(filters).some(key => filters[key]);

    // Chercher un endroit pour afficher le compteur
    const tableContainer = document.getElementById("commentsTableContainer");
    if (tableContainer && tableContainer.querySelector('.table')) {
        const countText = hasFilters ?
            `${count} commentaire(s) trouvé(s) avec les filtres appliqués` :
            `${count} commentaire(s) au total`;

        // Ajouter ou mettre à jour le compteur
        let countElement = document.getElementById('resultsCount');
        if (!countElement) {
            countElement = document.createElement('div');
            countElement.id = 'resultsCount';
            countElement.className = 'text-muted small mb-2';
            tableContainer.insertBefore(countElement, tableContainer.firstChild);
        }

        countElement.innerHTML = `
            <i class="bi bi-info-circle"></i> ${countText}
        `;
    }
}
/**
 * ===== FONCTIONS UTILITAIRES =====
 */

/**
 * Obtient le libellé du rôle utilisateur
 * @param {number} role - Numéro du rôle
 * @returns {string} Libellé du rôle
 */
function getUserRoleLabel(role) {
    const roleLabels = {
        0: 'Passager',
        1: 'Passager / Chauffeur',
        2: 'Gestionnaire',
        3: 'Administrateur'
    };
    return roleLabels[role] || 'Rôle inconnu';
}

/**
 * Formate une date pour l'affichage
 * @param {string} dateString - Date au format ISO
 * @returns {string} Date formatée
 */
function formatDateForDisplay(dateString) {
    if (!dateString) return 'Non défini';
    
    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric'
        });
    } catch (error) {
        console.error('Erreur formatage date:', error);
        return 'Date invalide';
    }
}

/**
 * Affiche un message de succès
 * @param {string} message - Message à afficher
 */
function showSuccessMessage(message) {
    // Vous pouvez utiliser Bootstrap Toast ou une simple alerte
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-check-circle-fill"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Suppression automatique après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

/**
 * Affiche un message d'erreur
 * @param {string} message - Message à afficher
 */
function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);
    
    // Suppression automatique après 7 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 7000);
}

/**
 * Affiche la modal de résultat de création d'utilisateur
 * @param {Object} result - Résultat de la création
 */
function showUserCreationResultModal(result) {
    // Implémentation basique - vous pouvez l'améliorer selon vos besoins
    showSuccessMessage(`Utilisateur créé avec succès ! ${result.message || ''}`);
}

/**
 * Édite un utilisateur (ouverture de la modal)
 * @param {number} userId - ID de l'utilisateur
 */
function editUser(userId) {
    console.log('Édition utilisateur:', userId);
    // TODO: Charger les données utilisateur et ouvrir la modal
    // Cette fonction devra être complétée selon vos besoins
}

/**
 * Réinitialise le mot de passe d'un utilisateur
 * @param {number} userId - ID de l'utilisateur
 * @param {string} userName - Nom complet de l'utilisateur
 */
function resetPassword(userId, userName) {
    if (confirm(`Voulez-vous vraiment réinitialiser le mot de passe de ${userName} ?`)) {
        // TODO: Appel API pour réinitialiser le mot de passe
        console.log('Réinitialisation mot de passe pour:', userId);
    }
}

/**
 * ===== FONCTION PRINCIPALE MANQUANTE =====
 * Affiche le tableau des commentaires
 */
function displayCommentsTable(comments) {
    console.log('🎯 Affichage du tableau avec', comments.length, 'commentaires');
    
    const container = document.getElementById("commentsTableContainer");
    if (!container) {
        console.error('❌ Conteneur commentsTableContainer non trouvé');
        return;
    }

    if (!Array.isArray(comments) || comments.length === 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-chat-dots text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">Aucun commentaire trouvé avec ces critères</p>
            </div>
        `;
        return;
    }

    // Création du tableau
    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Trajet</th>
                        <th>Passager</th>
                        <th>Chauffeur</th>
                        <th>Trajet</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Date trajet</th>
                        <th>Date avis</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;

    comments.forEach(comment => {
        const statusBadge = getStatusBadge(comment.status_review);
        const ratingStars = generateStarsHTML(comment.rating);
        
        tableHTML += `
            <tr>
                <td>
                    <span class="badge bg-primary">#${comment.trip_id}</span>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-bold">${escapeHtml(comment.voyager_firstname)} ${escapeHtml(comment.voyager_lastname)}</div>
                            <small class="text-muted">Ranking: ${comment.voyager_ranking}/5</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        <div>
                            <div class="fw-bold">${escapeHtml(comment.driver_firstname)} ${escapeHtml(comment.driver_lastname)}</div>
                            <small class="text-muted">Ranking: ${comment.driver_ranking}/5</small>
                        </div>
                    </div>
                </td>
                <td>
                    <div class="small">
                        <strong>${escapeHtml(comment.start_city)} → ${escapeHtml(comment.end_city)}</strong><br>
                        <span class="text-muted">${escapeHtml(comment.marque)} ${escapeHtml(comment.modele)}</span><br>
                        <span class="text-success">${comment.price_per_passenger}€/personne</span>
                    </div>
                </td>
                <td class="text-center">
                    <div class="rating">
                        ${ratingStars}
                    </div>
                    <small class="text-muted">${comment.rating}/5</small>
                </td>
                <td>
                    <div class="comment-cell" style="max-width: 200px;">
                        <div class="comment-preview" title="${escapeHtml(comment.commentaire)}">
                            ${comment.commentaire ? truncateText(escapeHtml(comment.commentaire), 60) : '<em class="text-muted">Aucun commentaire</em>'}
                        </div>
                    </div>
                </td>
                <td>
                    <small>${formatDateForDisplay(comment.trip_date)}</small>
                </td>
                <td>
                    <small>${formatDateForDisplay(comment.date_review)}</small>
                </td>
                <td>
                    ${statusBadge}
                </td>
                <td>
                    <div class="btn-group" role="group">
                        ${generateActionButtons(comment)}
                    </div>
                </td>
            </tr>
        `;
    });

    tableHTML += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = tableHTML;
    
    console.log('✅ Tableau affiché avec succès');
}

/**
 * Fonctions utilitaires pour l'affichage des commentaires
 */
function getStatusBadge(status) {
    const statusConfig = {
        'approved': { class: 'bg-success', text: 'Approuvé', icon: 'bi-check-circle' },
        'pending': { class: 'bg-warning', text: 'En attente', icon: 'bi-clock' },
        'rejected': { class: 'bg-danger', text: 'Rejeté', icon: 'bi-x-circle' }
    };
    
    const config = statusConfig[status] || statusConfig['pending'];
    return `<span class="badge ${config.class}"><i class="bi ${config.icon}"></i> ${config.text}</span>`;
}

function generateStarsHTML(rating) {
    let starsHTML = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= rating) {
            starsHTML += '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            starsHTML += '<i class="bi bi-star text-muted"></i>';
        }
    }
    return starsHTML;
}

function generateActionButtons(comment) {
    let buttons = '';
    
    if (comment.status_review === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-success me-1" onclick="approveComment(${comment.review_id})" title="Approuver">
                <i class="bi bi-check"></i>
            </button>
            <button class="btn btn-sm btn-danger me-1" onclick="rejectComment(${comment.review_id})" title="Rejeter">
                <i class="bi bi-x"></i>
            </button>
        `;
    }
    
    // Bouton pour envoyer un email au chauffeur
    buttons += `
        <button class="btn btn-sm btn-primary" onclick="sendEmailToDriver(${comment.trip_id}, '${escapeHtml(comment.driver_firstname)} ${escapeHtml(comment.driver_lastname)}', '${escapeHtml(comment.voyager_firstname)} ${escapeHtml(comment.voyager_lastname)}')" title="Envoyer email au chauffeur">
            <i class="bi bi-envelope"></i>
        </button>
    `;
    
    return buttons;
}

/**
 * Ouvre la modal pour envoyer un email au chauffeur
 * @param {number} tripId - ID du trajet
 * @param {string} driverName - Nom complet du chauffeur
 * @param {string} passengerName - Nom complet du passager
 */
function sendEmailToDriver(tripId, driverName, passengerName) {
    // Créer la modal dynamiquement si elle n'existe pas
    let modal = document.getElementById('emailDriverModal');
    if (!modal) {
        createEmailDriverModal();
        modal = document.getElementById('emailDriverModal');
    }

    // Remplir les champs
    document.getElementById('emailTripId').value = tripId;
    document.getElementById('emailDriverName').value = driverName;
    document.getElementById('emailPassengerName').value = passengerName;
    
    // Pré-remplir le sujet avec des informations du trajet
    document.getElementById('emailSubject').value = `EcoRide - Concernant votre trajet #${tripId}`;
    
    // Vider le message précédent
    document.getElementById('emailMessage').value = '';
    
    // Afficher la modal
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();
}


// === AJOUTS POUR MODÉRATION DES COMMENTAIRES ET ENVOI D'EMAILS ===
// Ces définitions viennent compléter/écraser certaines fonctions précédentes pour répondre aux exigences.

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function truncateText(str, maxLen) {
    if (!str) return '';
    return str.length > maxLen ? str.substring(0, maxLen - 1) + '…' : str;
}

// Redéfinition pour afficher uniquement les colonnes demandées
function displayCommentsTable(comments) {
    const container = document.getElementById("commentsTableContainer");
    if (!container) return;

    if (!Array.isArray(comments) || comments.length === 0) {
        container.innerHTML = `
            <div class="text-center p-4">
                <i class="bi bi-chat-dots text-muted" style="font-size: 2rem;"></i>
                <p class="text-muted mt-2">Aucun commentaire trouvé avec ces critères</p>
            </div>
        `;
        return;
    }

    let tableHTML = `
        <div class="table-responsive">
            <table class="table table-hover">
                <thead class="table-dark">
                    <tr>
                        <th>ID Voyage</th>
                        <th>Date du commentaire</th>
                        <th>Note</th>
                        <th>Commentaire</th>
                        <th>Statut</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;

    comments.forEach(c => {
        const statusBadge = getStatusBadge(c.status_review);
        const rating = c.rating ? `${c.rating}/5` : '-';

        tableHTML += `
            <tr>
                <td><span class="badge bg-primary">#${c.trip_id}</span></td>
                <td><small>${formatDateForDisplay(c.date_review)}</small></td>
                <td class="text-center">${rating}</td>
                <td style="white-space: pre-wrap; max-width: 600px">${c.commentaire ? escapeHtml(c.commentaire) : '<em class="text-muted">Aucun commentaire</em>'}</td>
                <td>${statusBadge}</td>
                <td>
                    <div class="btn-group" role="group">
                        ${generateActionButtonsSimple(c)}
                    </div>
                </td>
            </tr>
        `;
    });

    tableHTML += `
                </tbody>
            </table>
        </div>
    `;

    container.innerHTML = tableHTML;
}

function generateActionButtonsSimple(comment) {
    let buttons = '';
    if (comment.status_review === 'pending') {
        buttons += `
            <button class="btn btn-sm btn-success me-1" onclick="approveComment(${comment.review_id})" title="Valider">
                <i class="bi bi-check"></i>
            </button>
            <button class="btn btn-sm btn-danger me-1" onclick="rejectComment(${comment.review_id})" title="Refuser">
                <i class="bi bi-x"></i>
            </button>
        `;
    }
    buttons += `
        <button class="btn btn-sm btn-primary" onclick="sendEmailToDriver(${comment.trip_id}, '${escapeHtml(comment.driver_firstname)} ${escapeHtml(comment.driver_lastname)}', '${escapeHtml(comment.voyager_firstname)} ${escapeHtml(comment.voyager_lastname)}')" title="Envoyer un email au chauffeur">
            <i class="bi bi-envelope"></i>
        </button>
    `;
    return buttons;
}

function approveComment(reviewId) {
    if (!reviewId) return;
    if (!confirm('Confirmer la validation de ce commentaire ?')) return;

    const formData = new FormData();
    formData.append('review_id', reviewId);
    formData.append('action', 'approve');

    fetch('api/moderate_review.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showSuccessMessage(res.message || 'Commentaire approuvé');
            loadCommentsWithFilters(extractFiltersFromForm());
        } else {
            showErrorMessage(res.message || 'Erreur lors de l\'approbation');
        }
    })
    .catch(err => {
        console.error(err);
        showErrorMessage('Erreur de communication avec le serveur');
    });
}

function rejectComment(reviewId) {
    if (!reviewId) return;
    if (!confirm('Confirmer le refus de ce commentaire ?')) return;

    const formData = new FormData();
    formData.append('review_id', reviewId);
    formData.append('action', 'reject');

    fetch('api/moderate_review.php', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        if (res.success) {
            showSuccessMessage(res.message || 'Commentaire rejeté');
            loadCommentsWithFilters(extractFiltersFromForm());
        } else {
            showErrorMessage(res.message || 'Erreur lors du refus');
        }
    })
    .catch(err => {
        console.error(err);
        showErrorMessage('Erreur de communication avec le serveur');
    });
}

// Redéfinition propre des fonctions d'email pour garantir une modal complète et un envoi fonctionnel
function sendEmailToDriver(tripId, driverName, passengerName) {
    ensureEmailDriverModal();
    document.getElementById('emailTripId').value = tripId;
    document.getElementById('emailDriverName').value = driverName;
    document.getElementById('emailPassengerName').value = passengerName;
    document.getElementById('emailSubject').value = `EcoRide - Concernant votre trajet #${tripId}`;
    document.getElementById('emailMessage').value = '';
    const modal = new bootstrap.Modal(document.getElementById('emailDriverModal'));
    modal.show();
}

function ensureEmailDriverModal() {
    if (document.getElementById('emailDriverModal')) return; // déjà créée

    const wrapper = document.createElement('div');
    wrapper.innerHTML = `
        <div class="modal fade" id="emailDriverModal" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-envelope-fill text-primary"></i>
                            Envoyer un email au chauffeur
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form id="emailDriverForm">
                        <div class="modal-body">
                            <input type="hidden" id="emailTripId" name="trip_id">
                            <input type="hidden" id="emailDriverName" name="driver_name">
                            <input type="hidden" id="emailPassengerName" name="passenger_name">
                            <div class="mb-3">
                                <label for="emailSubject" class="form-label">Objet</label>
                                <input type="text" id="emailSubject" name="subject" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label for="emailMessage" class="form-label">Message</label>
                                <textarea id="emailMessage" name="message" class="form-control" rows="8" required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send"></i> Envoyer
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    `;
    document.body.appendChild(wrapper.firstElementChild);

    const form = document.getElementById('emailDriverForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        const submitBtn = form.querySelector('button[type="submit"]');
        const original = submitBtn.innerHTML;
        submitBtn.disabled = true;
        submitBtn.innerHTML = '<i class="bi bi-hourglass-split"></i> Envoi...';

        const formData = new FormData(form);
        fetch('api/send_email_driver.php', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.success) {
                showSuccessMessage(res.message || 'Email envoyé');
                const modalEl = document.getElementById('emailDriverModal');
                const instance = bootstrap.Modal.getInstance(modalEl);
                if (instance) instance.hide();
                form.reset();
            } else {
                showErrorMessage(res.message || 'Échec de l\'envoi de l\'email');
            }
        })
        .catch(err => {
            console.error(err);
            showErrorMessage('Erreur de communication avec le serveur');
        })
        .finally(() => {
            submitBtn.disabled = false;
            submitBtn.innerHTML = original;
        });
    });
}
