(() => {
    document.addEventListener("DOMContentLoaded", function() {
        // Vérifier que vehiclesData existe (données passées depuis PHP)
        if (typeof vehiclesData === 'undefined') {
            console.error('vehiclesData n\'est pas défini');
            return;
        }

        // === ÉLÉMENTS DOM ===
        // Formulaire principal de création de trajet
        let tripForm = document.getElementById("suggestedTripForm");

        // Bouton pour publier le trajet
        let publishButton = document.getElementById("publishSuggestedForm");

        // Modal de confirmation avant publication
        let confirmationModal = new bootstrap.Modal(document.getElementById("confirmationModal"));

        // Bouton de confirmation dans la modal
        let confirmSubmitButton = document.getElementById("confirmSubmit");

        // Champ de saisie du prix par passager
        let priceInput = document.getElementById("pricePerPassenger");

        // Sélecteurs pour véhicule et places
        const vehicleSelect = document.querySelector('select[name="vehicle_id"]');
        const placesSelect = document.querySelector('select[name="available_seats"]');

        console.log('Vehicle select trouvé:', vehicleSelect); // Debug
        console.log('Places select trouvé:', placesSelect); // Debug

        // === INITIALISATION ===
        initializeDateField();

        // === GESTION DES ÉVÉNEMENTS ===

        // Gestion du changement de véhicule pour mettre à jour les places disponibles
        if (vehicleSelect && placesSelect) {
            vehicleSelect.addEventListener("change", function() {
                console.log('Véhicule changé, ID:', vehicleSelect.value); // Debug
                updateAvailableSeatsSelector();
            });
        } else {
            console.error('Sélecteurs non trouvés:', {vehicleSelect, placesSelect});
        }

        // Gestion du clic sur le bouton de publication
        publishButton.addEventListener("click", function() {
            if (validateForm()) {
                showConfirmationModal();
            }
        });

        // Gestion de la confirmation finale de publication
        confirmSubmitButton.addEventListener("click", function() {
            tripForm.submit();
        });

        // === FONCTIONS ===

        /**
         * Met à jour le sélecteur de places disponibles en fonction du véhicule sélectionné
         */
        function updateAvailableSeatsSelector() {
            const selectedVehicleId = parseInt(vehicleSelect.value);
            console.log('ID véhicule sélectionné:', selectedVehicleId); // Debug

            // Vider le sélecteur de places
            placesSelect.innerHTML = "";

            // Si aucun véhicule sélectionné ou ID invalide
            if (!selectedVehicleId || isNaN(selectedVehicleId)) {
                placesSelect.innerHTML = '<option value="" disabled selected>Sélectionnez d\'abord un véhicule</option>';
                return;
            }

            // Trouver le véhicule sélectionné dans les données
            const selectedVehicle = vehiclesData.find(vehicle => vehicle.id === selectedVehicleId);
            console.log('Véhicule trouvé:', selectedVehicle); // Debug

            if (!selectedVehicle) {
                placesSelect.innerHTML = '<option value="" disabled selected>Erreur: véhicule non trouvé</option>';
                console.error('Véhicule non trouvé pour ID:', selectedVehicleId);
                return;
            }

            // Ajouter les options de places (de 1 au nombre max de places du véhicule)
            placesSelect.innerHTML = '<option value="" disabled selected>Choisissez le nombre de places</option>';

            for (let seatNumber = 1; seatNumber <= selectedVehicle.places; seatNumber++) {
                const option = document.createElement("option");
                option.value = seatNumber;
                option.textContent = `${seatNumber} place${seatNumber > 1 ? 's' : ''}`;

                // Sélectionner par défaut la valeur 2 ou le max si moins de 2 places
                if (seatNumber === Math.min(2, selectedVehicle.places)) {
                    option.selected = true;
                }

                placesSelect.appendChild(option);
            }

            console.log(`${selectedVehicle.places} options de places ajoutées`); // Debug
        }

        /**
         * Valide tous les champs du formulaire avant soumission
         * @returns {boolean} true si le formulaire est valide, false sinon
         */
        function validateForm() {
            // Validation des champs d'itinéraire (obligatoires)
            let routeFields = [
                {id: "startCity", message: "La ville de départ est obligatoire"},
                {id: "startLocation", message: "Le lieu de départ précis est obligatoire"},
                {id: "endCity", message: "La ville de destination est obligatoire"},
                {id: "endLocation", message: "Le lieu d'arrivée précis est obligatoire"}
            ];

            for (let field of routeFields) {
                let element = document.getElementById(field.id);
                if (!element || !element.value.trim()) {
                    showErrorMessage(field.message);
                    element?.focus();
                    return false;
                }
            }

            // Validation des champs de date et heure (obligatoires)
            let dateTimeFields = [
                {id: "departureDate", message: "La date de départ est obligatoire"},
                {id: "departureTime", message: "L'heure de départ est obligatoire"}
            ];

            for (let field of dateTimeFields) {
                let element = document.getElementById(field.id);
                if (!element || !element.value) {
                    showErrorMessage(field.message);
                    element?.focus();
                    return false;
                }
            }

            // Validation que la date n'est pas dans le passé
            let departureDateField = document.getElementById("departureDate");
            if (departureDateField && departureDateField.value) {
                let departureDate = new Date(departureDateField.value + "T00:00:00");
                let today = new Date();
                today.setHours(0, 0, 0, 0);

                if (departureDate < today) {
                    showErrorMessage("La date ne peut pas être dans le passé");
                    departureDateField.focus();
                    return false;
                }
            }

            // Validation de la durée du trajet
            let hoursSelect = document.querySelector('select[name="duration_hours"]');
            let minutesSelect = document.querySelector('select[name="duration_minutes"]');

            if (!hoursSelect || !minutesSelect) {
                console.error(`Le champ de sélection de l'heure ou des minutes est introuvable`);
                return false;
            }
            if (hoursSelect.value && !minutesSelect.value) {
                minutesSelect.value = "0";
            }
            if (!hoursSelect.value || !minutesSelect.value) {
                showErrorMessage('Veuillez indiquer la durée estimée du trajet.');
                return false;
            }

             if (!hoursSelect.value && !minutesSelect.value) {
                showErrorMessage('Veuillez indiquer la durée estimée du trajet.');
                 (hoursSelect.value ? minutesSelect : hoursSelect)?.focus();
                return false;
             }


            // Validation du véhicule sélectionné
            let vehicleSelector = document.querySelector('select[name="vehicle_id"]');
            if (!vehicleSelector) {
                console.error("Le champ de sélection est introuvable");
                return false;
            }
            if (!vehicleSelector.value.trim()) {
                showErrorMessage('Veuillez sélectionner un véhicule');
                return false;
            }

            // Validation du nombre de places disponibles
            const placesSelector = document.querySelector('select[name="available_seats"]');
            if (!placesSelector || !placesSelector.value) {
                showErrorMessage("Veuillez sélectionner le nombre de places disponibles");
                placesSelector?.focus();
                return false;
            }

            // Validation du prix par passager
            let priceValue = parseInt(priceInput.value);
            if (!priceValue || priceValue <= 0 || priceValue > 1000) {
                showErrorMessage("Le prix doit être entre 1 et 1000 crédits");
                priceInput.focus();
                return false;
            }

            return true;
        }

        /**
         * Affiche la modal de confirmation avec un récapitulatif du trajet
         */
        function showConfirmationModal() {
            let formData = new FormData(tripForm);
            let modalContent = '<div class="row g-3">';

            // Récupération des données du formulaire
            let startCity = formData.get("start_city");
            let startLocation = formData.get("start_location");
            let endCity = formData.get("end_city");
            let endLocation = formData.get("end_location");
            let departureDate = formData.get("departure_date");
            let departureTime = formData.get("departure_time");
            let durationHours = formData.get("duration_hours");
            let durationMinutes = formData.get("duration_minutes");
            let availableSeats = formData.get("available_seats");
            let pricePerPassenger = formData.get("price_per_passenger");

            // Récupération du nom du véhicule sélectionné
            let vehicleSelector = document.querySelector('select[name="vehicle_id"]');
            let vehicleName = vehicleSelector.options[vehicleSelector.selectedIndex].text;

            // Section itinéraire
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
                    <p class="mb-1"><i class="bi bi-calendar-event text-success me-1"></i> ${formatDateForDisplay(departureDate)}</p>
                    <p class="mb-1"><i class="bi bi-clock text-success me-1"></i> ${departureTime}</p>
                    <p class="small text-muted"><i class="bi bi-hourglass-split me-1"></i> Durée : ${durationHours}h${durationMinutes.padStart(2,"0")}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Véhicule</h6>
                    <p class="mb-1"><i class="bi bi-car-front text-success me-1"></i> ${vehicleName}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Places et prix</h6>
                    <p class="mb-1"><i class="bi bi-people text-success me-1"></i> ${availableSeats} place${availableSeats > 1 ? 's' : ''} disponible${availableSeats > 1 ? 's' : ''}</p>
                    <p class="mb-1"><i class="bi bi-currency-euro text-success me-1"></i> ${pricePerPassenger} crédits par passager</p>
                    <p class="small text-success fw-bold">Total maximum : ${pricePerPassenger * availableSeats} crédits</p>
                </div>
            `;

            // Section préférences (si cochées)
            let preferences = [];
            if (formData.get("no_smoking")) preferences.push("🚭 Non-fumeur");
            if (formData.get("music_allowed")) preferences.push("🎵 Musique autorisée");
            if (formData.get("discuss_allowed")) preferences.push("💬 Discussions bienvenues");

            if (preferences.length > 0) {
                modalContent += `
                    <div class="col-12">
                        <h6 class="text-muted mb-2">Préférences</h6>
                        <p class="small">${preferences.join(", ")}</p>
                    </div>
                `;
            }

            // Section commentaire (si présent)
            let comment = formData.get("comment");
            if (comment && comment.trim()) {
                modalContent += `
                    <div class="col-12">
                        <h6 class="text-muted mb-2">Commentaire</h6>
                        <p class="small fst-italic">"${escapeHtml(comment.trim())}"</p>
                    </div>
                `;
            }

            modalContent += "</div>";

            // Injection du contenu dans la modal et affichage
            document.getElementById("modalText").innerHTML = modalContent;
            confirmationModal.show();
        }

        /**
         * Initialise le champ date avec la date minimum (aujourd'hui)
         */
        function initializeDateField() {
            let departureDateField = document.getElementById("departureDate");
            if (departureDateField && !departureDateField.value) {
                departureDateField.min = getTodayDateString();
            }
        }

        /**
         * Retourne la date du jour au format YYYY-MM-DD
         * @returns {string} Date du jour formatée
         */
        function getTodayDateString() {
            let today = new Date();
            let year = today.getFullYear();
            let month = String(today.getMonth() + 1).padStart(2, "0");
            let day = String(today.getDate()).padStart(2, "0");
            return `${year}-${month}-${day}`;
        }

        /**
         * Formate une date pour l'affichage en français
         * @param {string} dateString Date au format YYYY-MM-DD
         * @returns {string} Date formatée en français
         */
        function formatDateForDisplay(dateString) {
            try {
                return new Date(dateString + "T00:00:00").toLocaleDateString("fr-FR", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric"
                });
            } catch (error) {
                console.warn("Erreur formatage date:", error);
                return dateString;
            }
        }

        /**
         * Affiche un message d'erreur à l'utilisateur
         * @param {string} message Message d'erreur à afficher
         */
        function showErrorMessage(message) {
            let errorAlert = document.getElementById("errorAlert");
            let errorMessageElement = document.getElementById("errorMessage");

            if (errorAlert && errorMessageElement) {
                errorMessageElement.textContent = message;
                errorAlert.classList.remove("d-none");
                errorAlert.scrollIntoView({behavior: "smooth", block: "center"});

                // Masquer automatiquement après 5 secondes
                setTimeout(() => {
                    errorAlert.classList.add("d-none");
                }, 5000);
            } else {
                // Fallback avec alert() si les éléments DOM ne sont pas trouvés
                alert(message);
            }
        }

        /**
         * Échappe les caractères HTML pour éviter les injections XSS
         * @param {string} text Texte à échapper
         * @returns {string} Texte avec caractères HTML échappés
         */
        function escapeHtml(text) {
            let temporaryElement = document.createElement("div");
            temporaryElement.textContent = text;
            return temporaryElement.innerHTML;
        }
    });
})();