// proposer.js
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('suggestedTripForm');
    const publishBtn = document.getElementById('publishSuggestedForm');
    const confirmModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
    const confirmBtn = document.getElementById('confirmSubmit');
    const priceInput = document.getElementById('pricePerPassenger');
    const placeButtons = document.querySelectorAll('input[name="available_seats"]');

    // Initialisation de l'autocomplétion pour les villes
    initializeCityAutocomplete();

    // Initialisation de la date minimale (aujourd'hui)
    initializeDateInput();

    // Mise à jour du calcul de prix
    function updatePriceCalculation() {
        const price = parseInt(priceInput.value) || 0;
        const selectedPlace = document.querySelector('input[name="available_seats"]:checked');
        const places = selectedPlace ? parseInt(selectedPlace.value) : 3;

        document.getElementById('totalPrice').textContent = price * places;
        document.getElementById('placeFree').textContent = places;
    }

    // Écouteurs pour mise à jour du prix
    priceInput.addEventListener('input', updatePriceCalculation);
    placeButtons.forEach(button => {
        button.addEventListener('change', updatePriceCalculation);
    });

    // Validation du formulaire avant affichage de la modale
    publishBtn.addEventListener('click', function() {
        if (validateForm()) {
            displayConfirmationModal();
        }
    });

    // Soumission du formulaire après confirmation
    confirmBtn.addEventListener('click', function() {
        form.submit();
    });

    // Validation personnalisée du formulaire
    function validateForm() {
        // Vérification des champs texte
        const textFields = [
            { id: 'startCity', message: 'La ville de départ est obligatoire' },
            { id: 'startLocation', message: 'Le lieu de départ précis est obligatoire' },
            { id: 'endCity', message: 'La ville de destination est obligatoire' },
            { id: 'endLocation', message: 'Le lieu d\'arrivée précis est obligatoire' }
        ];

        for (let field of textFields) {
            const element = document.getElementById(field.id);
            if (!element || !element.value.trim()) {
                showError(field.message);
                element?.focus();
                return false;
            }
        }

        // Vérification des champs date et time (sans trim)
        const dateTimeFields = [
            { id: 'departureDate', message: 'La date de départ est obligatoire' },
            { id: 'departureTime', message: 'L\'heure de départ est obligatoire' }
        ];

        for (let field of dateTimeFields) {
            const element = document.getElementById(field.id);
            // console.log(`Validation ${field.id}:`, element ? `"${element.value}"` : 'Element not found'); // Debug - à retirer en production
            if (!element || !element.value) {
                showError(field.message);
                element?.focus();
                return false;
            }
        }

        // Vérification de la date (ne peut pas être dans le passé)
        const dateInput = document.getElementById('departureDate');
        if (dateInput && dateInput.value) {
            const selectedDate = new Date(dateInput.value + 'T00:00:00'); // Éviter les problèmes de timezone
            const today = new Date();
            today.setHours(0, 0, 0, 0);

            if (selectedDate < today) {
                showError('La date ne peut pas être dans le passé');
                dateInput.focus();
                return false;
            }
        }

        // Vérification des selects de durée
        const durationHours = document.querySelector('select[name="duration_hours"]');
        const durationMinutes = document.querySelector('select[name="duration_minutes"]');

        if (!durationHours || !durationMinutes || !durationHours.value || !durationMinutes.value) {
            showError('Veuillez indiquer la durée estimée du trajet');
            (durationHours && !durationHours.value ? durationHours : durationMinutes)?.focus();
            return false;
        }

        // Vérification du véhicule
        const vehicleSelect = document.querySelector('select[name="vehicle_id"]');
        if (!vehicleSelect || !vehicleSelect.value) {
            showError('Veuillez sélectionner un véhicule');
            vehicleSelect?.focus();
            return false;
        }

        // Vérification du prix
        const price = parseInt(priceInput.value);
        if (!price || price <= 0 || price > 1000) { // Limite max raisonnable
            showError('Le prix doit être entre 1 et 1000 crédits');
            priceInput.focus();
            return false;
        }

        return true;
    }

    // Affichage de la modale de confirmation
    function displayConfirmationModal() {
        const formData = new FormData(form);
        let modalContent = '<div class="row g-3">';

        // Récupération des données du formulaire
        const startCity = formData.get('start_city');
        const startLocation = formData.get('start_location');
        const endCity = formData.get('end_city');
        const endLocation = formData.get('end_location');
        const departureDate = formData.get('departure_date');
        const departureTime = formData.get('departure_time');
        const durationHours = formData.get('duration_hours');
        const durationMinutes = formData.get('duration_minutes');
        const availableSeats = formData.get('available_seats');
        const price = formData.get('price_per_passenger');
        const vehicleSelect = document.querySelector('select[name="vehicle_id"]');
        const vehicleText = vehicleSelect.options[vehicleSelect.selectedIndex].text;

        // Construction du contenu de la modale
        modalContent += `
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Itinéraire</h6>
                <p class="mb-1"><i class="bi bi-geo-alt text-success me-1"></i> <strong>${startCity}</strong></p>
                <p class="mb-1 small text-muted ms-3">${startLocation}</p>
                <p class="mb-1"><i class="bi bi-arrow-down text-muted me-1"></i> <strong>${endCity}</strong></p>
                <p class="small text-muted ms-3">${endLocation}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Date et heure</h6>
                <p class="mb-1"><i class="bi bi-calendar-event text-success me-1"></i> ${formatDate(departureDate)}</p>
                <p class="mb-1"><i class="bi bi-clock text-success me-1"></i> ${departureTime}</p>
                <p class="small text-muted"><i class="bi bi-hourglass-split me-1"></i> Durée : ${durationHours}h${durationMinutes.padStart(2, '0')}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Véhicule</h6>
                <p class="mb-1"><i class="bi bi-car-front text-success me-1"></i> ${vehicleText}</p>
            </div>
            <div class="col-md-6">
                <h6 class="text-muted mb-2">Places et prix</h6>
                <p class="mb-1"><i class="bi bi-people text-success me-1"></i> ${availableSeats} places disponibles</p>
                <p class="mb-1"><i class="bi bi-currency-euro text-success me-1"></i> ${price} crédits par passager</p>
                <p class="small text-success fw-bold">Total maximum : ${price * availableSeats} crédits</p>
            </div>
        `;

        // Ajout des préférences si cochées
        const preferences = [];
        if (formData.get('no_smoking')) preferences.push('🚭 Non-fumeur');
        if (formData.get('music_allowed')) preferences.push('🎵 Musique autorisée');
        if (formData.get('discuss_allowed')) preferences.push('💬 Discussions bienvenues');

        if (preferences.length > 0) {
            modalContent += `
                <div class="col-12">
                    <h6 class="text-muted mb-2">Préférences</h6>
                    <p class="small">${preferences.join(', ')}</p>
                </div>
            `;
        }

        // Ajout du commentaire s'il existe
        const comment = formData.get('comment');
        if (comment && comment.trim()) {
            modalContent += `
                <div class="col-12">
                    <h6 class="text-muted mb-2">Commentaire</h6>
                    <p class="small fst-italic">"${escapeHtml(comment.trim())}"</p>
                </div>
            `;
        }

        modalContent += '</div>';
        document.getElementById('modalText').innerHTML = modalContent;
        confirmModal.show();
    }

    // Initialisation de la date minimale
    function initializeDateInput() {
        const dateInput = document.getElementById('departureDate');
        if (dateInput && !dateInput.value) {
            dateInput.min = getTodayDate();
        }
    }

    // Fonction pour obtenir la date d'aujourd'hui au format YYYY-MM-DD
    function getTodayDate() {
        const today = new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, '0');
        const day = String(today.getDate()).padStart(2, '0');
        return `${year}-${month}-${day}`;
    }

    // Fonction pour formater la date en français
    function formatDate(dateString) {
        try {
            const date = new Date(dateString + 'T00:00:00'); // Éviter problèmes timezone
            return date.toLocaleDateString('fr-FR', {
                weekday: 'long',
                year: 'numeric',
                month: 'long',
                day: 'numeric'
            });
        } catch (error) {
            console.warn('Erreur formatage date:', error);
            return dateString; // Fallback
        }
    }

    // Fonction pour afficher les erreurs
    function showError(message) {
        const errorAlert = document.getElementById('errorAlert');
        const errorMessage = document.getElementById('errorMessage');

        if (errorAlert && errorMessage) {
            errorMessage.textContent = message;
            errorAlert.classList.remove('d-none');
            errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });

            // Masquer l'erreur après 5 secondes
            setTimeout(() => {
                errorAlert.classList.add('d-none');
            }, 5000);
        } else {
            alert(message);
        }
    }

    // Initialisation de l'autocomplétion des villes
    function initializeCityAutocomplete() {
        // Création des conteneurs de suggestions s'ils n'existent pas
        createSuggestionContainer('startCity', 'startCitySuggestions');
        createSuggestionContainer('endCity', 'endCitySuggestions');

        // Configuration de l'autocomplétion
        setupCustomAutocomplete('startCity', 'startCitySuggestions');
        setupCustomAutocomplete('endCity', 'endCitySuggestions');
    }

    // Création du conteneur de suggestions
    function createSuggestionContainer(inputId, suggestionId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        const parent = input.closest('.input-group');
        if (!parent) return;

        // Vérifier si le conteneur existe déjà
        if (document.getElementById(suggestionId)) return;

        const suggestionBox = document.createElement('div');
        suggestionBox.id = suggestionId;
        suggestionBox.className = 'suggestion-box';
        suggestionBox.style.cssText = `
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #dee2e6;
            border-top: none;
            border-radius: 0 0 0.375rem 0.375rem;
            max-height: 200px;
            overflow-y: auto;
            z-index: 1000;
            display: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;

        // Positionner le parent en relatif
        parent.style.position = 'relative';
        parent.appendChild(suggestionBox);
    }

    // Configuration de l'autocomplétion personnalisée
    function setupCustomAutocomplete(inputId, suggestionBoxId) {
        const input = document.getElementById(inputId);
        const suggestionBox = document.getElementById(suggestionBoxId);

        if (!input || !suggestionBox) {
            console.error(`Éléments non trouvés: ${inputId} ou ${suggestionBoxId}`);
            return;
        }

        let debounceTimer;

        input.addEventListener('input', () => {
            const query = input.value.trim();
            clearTimeout(debounceTimer);

            if (query.length < 2) {
                suggestionBox.style.display = 'none';
                suggestionBox.innerHTML = '';
                return;
            }

            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(
                        `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,codeDepartement&boost=population&limit=8`
                    );

                    if (!response.ok) {
                        throw new Error('Erreur réseau');
                    }

                    const cities = await response.json();
                    suggestionBox.innerHTML = '';

                    if (cities.length === 0) {
                        suggestionBox.style.display = 'none';
                        return;
                    }

                    cities.forEach(city => {
                        const suggestionItem = document.createElement('div');
                        suggestionItem.className = 'suggestion-item';
                        suggestionItem.style.cssText = `
                            padding: 10px 15px;
                            cursor: pointer;
                            border-bottom: 1px solid #f8f9fa;
                            transition: background-color 0.2s;
                        `;
                        suggestionItem.textContent = `${city.nom} (${city.codeDepartement})`;

                        suggestionItem.addEventListener('mouseenter', () => {
                            suggestionItem.style.backgroundColor = '#f8f9fa';
                        });

                        suggestionItem.addEventListener('mouseleave', () => {
                            suggestionItem.style.backgroundColor = 'white';
                        });

                        suggestionItem.addEventListener('click', () => {
                            input.value = city.nom;
                            suggestionBox.style.display = 'none';
                            suggestionBox.innerHTML = '';
                            // Déclencher l'événement input pour la validation
                            input.dispatchEvent(new Event('input', { bubbles: true }));
                        });

                        suggestionBox.appendChild(suggestionItem);
                    });

                    suggestionBox.style.display = 'block';
                } catch (error) {
                    console.error('Erreur lors de l\'autocomplétion :', error);
                    suggestionBox.style.display = 'none';
                }
            }, 300);
        });

        // Fermer les suggestions quand on clique ailleurs
        document.addEventListener('click', (event) => {
            if (!input.contains(event.target) && !suggestionBox.contains(event.target)) {
                suggestionBox.style.display = 'none';
            }
        });

        // Fermer avec Escape
        input.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                suggestionBox.style.display = 'none';
            }
        });
    }

    // Fonction pour échapper le HTML (sécurité)
    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Initialisation du calcul de prix au chargement
    updatePriceCalculation();
});