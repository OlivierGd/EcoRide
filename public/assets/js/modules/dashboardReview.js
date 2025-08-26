/**
 * Dashboard Review Module - Gestion des commentaires et avis des utilisateurs
 */

// Initialisation du module commentaires au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si la section commentaires existe avant d'initialiser
    if (document.getElementById('comments')) {
        initializeCommentsModule();
    }
});

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
 * Charge et affiche les commentaires avec filtres
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

    console.log(' URL API finale:', apiUrl);

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
            console.error('Erreur lors du chargement des commentaires:', error);
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
 * Affiche le tableau des commentaires
 */
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
                <td><button class="badge bg-primary" onclick="viewTripDetails(${c.trip_id})">#${c.trip_id}</button></td>
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

/**
 * ===== FONCTIONS UTILITAIRES =====
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

function escapeHtml(str) {
    if (str === null || str === undefined) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function formatDateForDisplay(dateString) {
    if (!dateString) return 'Non défini';

    try {
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', {
            day: '2-digit',
            month: '2-digit',
            year: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    } catch (error) {
        console.error('Erreur formatage date:', error);
        return 'Date invalide';
    }
}

function showSuccessMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-success alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-check-circle-fill"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function showErrorMessage(message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = 'alert alert-danger alert-dismissible fade show position-fixed';
    alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    alertDiv.innerHTML = `
        <i class="bi bi-exclamation-triangle-fill"></i> ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    document.body.appendChild(alertDiv);

    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 7000);
}

/**
 * ===== FONCTIONS DE MODÉRATION EXISTANTES =====
 * Utilisent l'API moderate_review.php
 */

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

/**
 * ===== FONCTIONS D'ENVOI D'EMAIL EXISTANTES =====
 */

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
/**
 * ===== FONCTIONS POUR MODAL TRAJET =====
 * Intégration de la modal de détails de trajet existante
 */

/**
 * Affiche les détails d'un trajet dans une modal
 */
function viewTripDetails(tripId) {
    if (!tripId) {
        console.error('ID de trajet manquant');
        return;
    }

    // Affichage du loader
    const loadingModal = createLoadingModal();
    loadingModal.show();

    // Appel API pour récupérer les données du trajet
    fetch(`api/get_trip_details.php?trip_id=${tripId}`)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            loadingModal.hide();
            if (data.success) {
                displayTripModal(data.trip, data.driver, data.car);
            } else {
                showErrorMessage(data.message || 'Erreur lors du chargement du trajet');
            }
        })
        .catch(error => {
            loadingModal.hide();
            console.error('Erreur:', error);
            showErrorMessage('Erreur de communication avec le serveur');
        });
}

/**
 * Crée une modal de chargement
 */
function createLoadingModal() {
    let modal = document.getElementById('tripLoadingModal');
    if (!modal) {
        const modalHTML = `
            <div class="modal fade" id="tripLoadingModal" tabindex="-1">
                <div class="modal-dialog modal-sm modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-body text-center py-4">
                            <div class="spinner-border text-success mb-2" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mb-0">Chargement des détails du trajet...</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.insertAdjacentHTML('beforeend', modalHTML);
        modal = document.getElementById('tripLoadingModal');
    }
    return new bootstrap.Modal(modal);
}

/**
 * Affiche la modal du trajet avec les données récupérées
 */
function displayTripModal(trip, driver, car) {
    // Calculer l'heure d'arrivée
    const departureTime = new Date(trip.departure_at);
    const arrivalTime = new Date(departureTime.getTime() + (trip.estimated_duration * 60000));

    const modalId = `tripDetailsModal-${trip.trip_id}`;

    // Créer la modal dynamiquement
    const modalHTML = `
        <div class="modal fade" id="${modalId}" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">Détails du trajet #${trip.trip_id}</h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-12">
                                <p><strong>Conducteur :</strong> ${escapeHtml(driver.firstname)} ${escapeHtml(driver.lastname)} (⭐ ${driver.ranking}/5)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Départ :</strong></p>
                                <p class="ms-3 mb-1">${escapeHtml(trip.start_city)}</p>
                                <p class="ms-3 text-muted small">${escapeHtml(trip.start_location)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Arrivée :</strong></p>
                                <p class="ms-3 mb-1">${escapeHtml(trip.end_city)}</p>
                                <p class="ms-3 text-muted small">${escapeHtml(trip.end_location)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Départ prévu :</strong></p>
                                <p class="ms-3">${formatDateTime(trip.departure_at)}</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Arrivée estimée :</strong></p>
                                <p class="ms-3">${arrivalTime.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'})}</p>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Places</strong></p>
                                    <span class="badge bg-info">${trip.remaining_seats} disponible${trip.remaining_seats > 1 ? 's' : ''}</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Prix</strong></p>
                                    <span class="badge bg-success">${trip.price_per_passenger} crédits</span>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="text-center">
                                    <p class="mb-1"><strong>Statut</strong></p>
                                    <span class="badge bg-${getStatusColor(trip.status)}">${trip.status}</span>
                                </div>
                            </div>
                            <div class="col-12">
                                <p><strong>Véhicule :</strong></p>
                                <p class="ms-3">${escapeHtml(car.marque)} ${escapeHtml(car.modele)} - ${escapeHtml(car.carburant)} - ${car.immatriculation}</p>
                            </div>
                            ${trip.travel_preferences ? `
                            <div class="col-12">
                                <p><strong>Options de voyage :</strong></p>
                                <div class="ms-3">${formatTravelPreferences(trip.travel_preferences)}</div>
                            </div>
                            ` : ''}
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fermer</button>
                    </div>
                </div>
            </div>
        </div>
    `;

    // Supprimer la modal existante si elle existe
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        existingModal.remove();
    }

    // Ajouter la nouvelle modal
    document.body.insertAdjacentHTML('beforeend', modalHTML);

    // Afficher la modal
    const modal = new bootstrap.Modal(document.getElementById(modalId));
    modal.show();

    // Nettoyer après fermeture
    document.getElementById(modalId).addEventListener('hidden.bs.modal', function () {
        this.remove();
    });
}

/**
 * Fonctions utilitaires pour la modal
 */
function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('fr-FR') + ' à ' + date.toLocaleTimeString('fr-FR', {hour: '2-digit', minute: '2-digit'});
}

function getStatusColor(status) {
    const statusColors = {
        'a_venir': 'primary',
        'en_cours': 'warning',
        'termine': 'success',
        'annule': 'danger'
    };
    return statusColors[status] || 'secondary';
}

function formatTravelPreferences(preferences) {
    if (!preferences) return 'Aucune';

    try {
        const prefs = JSON.parse(preferences);
        const prefLabels = {
            'music': 'Musique',
            'talking': 'Discussion',
            'silence': 'Silence',
            'pets': 'Animaux acceptés',
            'no_smoking': 'Non-fumeur'
        };

        return Object.entries(prefs)
            .filter(([key, value]) => value)
            .map(([key]) => prefLabels[key] || key)
            .join(', ') || 'Aucune préférence';
    } catch (e) {
        return 'Aucune préférence';
    }
}