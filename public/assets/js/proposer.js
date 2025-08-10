(() => {
    document.addEventListener("DOMContentLoaded", function() {
        // Vérifier que vehiclesData existe
        if (typeof vehiclesData === 'undefined') {
            console.error('vehiclesData n\'est pas défini');
            return;
        }

        console.log('Données véhicules:', vehiclesData); // Debug

        let g = document.getElementById("suggestedTripForm"),
            h = document.getElementById("publishSuggestedForm"),
            y = new bootstrap.Modal(document.getElementById("confirmationModal")),
            x = document.getElementById("confirmSubmit"),
            u = document.getElementById("pricePerPassenger");


        const vehicleSelect = document.querySelector('select[name="vehicle_id"]');
        const placesSelect = document.querySelector('select[name="available_seats"]');

        console.log('Vehicle select trouvé:', vehicleSelect); // Debug
        console.log('Places select trouvé:', placesSelect); // Debug

        D();

        // Gestion du changement de véhicule
        if (vehicleSelect && placesSelect) {
            vehicleSelect.addEventListener("change", function() {
                console.log('Véhicule changé, ID:', vehicleSelect.value); // Debug
                updatePlacesSelector();
            });
        } else {
            console.error('Sélecteurs non trouvés:', {vehicleSelect, placesSelect});
        }

        function updatePlacesSelector() {
            const selectedVehicleId = parseInt(vehicleSelect.value);
            console.log('ID véhicule sélectionné:', selectedVehicleId); // Debug

            // Vider le sélecteur de places
            placesSelect.innerHTML = "";

            if (!selectedVehicleId || isNaN(selectedVehicleId)) {
                placesSelect.innerHTML = '<option value="" disabled selected>Sélectionnez d\'abord un véhicule</option>';
                return;
            }

            // Trouver le véhicule sélectionné
            const selectedVehicle = vehiclesData.find(v => v.id === selectedVehicleId);
            console.log('Véhicule trouvé:', selectedVehicle); // Debug

            if (!selectedVehicle) {
                placesSelect.innerHTML = '<option value="" disabled selected>Erreur: véhicule non trouvé</option>';
                console.error('Véhicule non trouvé pour ID:', selectedVehicleId);
                return;
            }

            // Ajouter les options de places (de 1 au nombre max de places du véhicule)
            placesSelect.innerHTML = '<option value="" disabled selected>Choisissez le nombre de places</option>';

            for (let i = 1; i <= selectedVehicle.places; i++) {
                const option = document.createElement("option");
                option.value = i;
                option.textContent = `${i} place${i > 1 ? 's' : ''}`;

                // Sélectionner par défaut la valeur 2 ou le max si moins de 2 places
                if (i === Math.min(2, selectedVehicle.places)) {
                    option.selected = true;
                }

                placesSelect.appendChild(option);
            }

            console.log(`${selectedVehicle.places} options de places ajoutées`); // Debug

            // Déclencher le calcul du prix
            updatePriceCalculation();
        }

        // Fonction pour mettre à jour le calcul de prix
        function updatePriceCalculation() {
            const price = parseInt(u.value) || 20;
            const places = parseInt(placesSelect.value) || 0;

            document.getElementById("totalPrice").textContent = price * places;
            document.getElementById("placeFree").textContent = places;
        }

        // Écouter les changements de prix et de places
        if (u) {
            u.addEventListener("input", updatePriceCalculation);
        }

        if (placesSelect) {
            placesSelect.addEventListener("change", updatePriceCalculation);
        }

        h.addEventListener("click", function() {
            E() && L();
        });

        x.addEventListener("click", function() {
            g.submit();
        });

        function E() {
            let e = [
                {id: "startCity", message: "La ville de départ est obligatoire"},
                {id: "startLocation", message: "Le lieu de départ précis est obligatoire"},
                {id: "endCity", message: "La ville de destination est obligatoire"},
                {id: "endLocation", message: "Le lieu d'arrivée précis est obligatoire"}
            ];

            for (let o of e) {
                let n = document.getElementById(o.id);
                if (!n || !n.value.trim()) {
                    i(o.message);
                    n?.focus();
                    return false;
                }
            }

            let t = [
                {id: "departureDate", message: "La date de départ est obligatoire"},
                {id: "departureTime", message: "L'heure de départ est obligatoire"}
            ];

            for (let o of t) {
                let n = document.getElementById(o.id);
                if (!n || !n.value) {
                    i(o.message);
                    n?.focus();
                    return false;
                }
            }

            let s = document.getElementById("departureDate");
            if (s && s.value) {
                let o = new Date(s.value + "T00:00:00"),
                    n = new Date;
                if (n.setHours(0, 0, 0, 0), o < n) {
                    i("La date ne peut pas être dans le passé");
                    s.focus();
                    return false;
                }
            }

            let a = document.querySelector('select[name="duration_hours"]'),
                c = document.querySelector('select[name="duration_minutes"]');
            if (!a || !c || !a.value || !c.value) {
                i("Veuillez indiquer la durée estimée du trajet");
                (a && !a.value ? a : c)?.focus();
                return false;
            }

            let r = document.querySelector('select[name="vehicle_id"]');
            if (!r || !r.value) {
                i("Veuillez sélectionner un véhicule");
                r?.focus();
                return false;
            }

            // Validation du nombre de places
            const placesSelector = document.querySelector('select[name="available_seats"]');
            if (!placesSelector || !placesSelector.value) {
                i("Veuillez sélectionner le nombre de places disponibles");
                placesSelector?.focus();
                return false;
            }

            let l = parseInt(u.value);
            if (!l || l <= 0 || l > 1000) {
                i("Le prix doit être entre 1 et 1000 crédits");
                u.focus();
                return false;
            }

            return true;
        }

        function L() {
            let e = new FormData(g),
                t = '<div class="row g-3">',
                s = e.get("start_city"),
                a = e.get("start_location"),
                c = e.get("end_city"),
                r = e.get("end_location"),
                l = e.get("departure_date"),
                o = e.get("departure_time"),
                n = e.get("duration_hours"),
                $ = e.get("duration_minutes"),
                f = e.get("available_seats"),
                v = e.get("price_per_passenger"),
                b = document.querySelector('select[name="vehicle_id"]'),
                S = b.options[b.selectedIndex].text;

            t += `
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Itinéraire</h6>
                    <p class="mb-1"><i class="bi bi-geo-alt text-success me-1"></i> <strong>${s}</strong></p>
                    <p class="mb-1 small text-muted ms-3">${a}</p>
                    <p class="mb-1"><i class="bi bi-arrow-down text-muted me-1"></i> <strong>${c}</strong></p>
                    <p class="small text-muted ms-3">${r}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Date et heure</h6>
                    <p class="mb-1"><i class="bi bi-calendar-event text-success me-1"></i> ${w(l)}</p>
                    <p class="mb-1"><i class="bi bi-clock text-success me-1"></i> ${o}</p>
                    <p class="small text-muted"><i class="bi bi-hourglass-split me-1"></i> Durée : ${n}h${$.padStart(2,"0")}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Véhicule</h6>
                    <p class="mb-1"><i class="bi bi-car-front text-success me-1"></i> ${S}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Places et prix</h6>
                    <p class="mb-1"><i class="bi bi-people text-success me-1"></i> ${f} place${f > 1 ? 's' : ''} disponible${f > 1 ? 's' : ''}</p>
                    <p class="mb-1"><i class="bi bi-currency-euro text-success me-1"></i> ${v} crédits par passager</p>
                    <p class="small text-success fw-bold">Total maximum : ${v * f} crédits</p>
                </div>
            `;

            let d = [];
            e.get("no_smoking") && d.push("🚭 Non-fumeur");
            e.get("music_allowed") && d.push("🎵 Musique autorisée");
            e.get("discuss_allowed") && d.push("💬 Discussions bienvenues");

            if (d.length > 0) {
                t += `
                    <div class="col-12">
                        <h6 class="text-muted mb-2">Préférences</h6>
                        <p class="small">${d.join(", ")}</p>
                    </div>
                `;
            }

            let p = e.get("comment");
            if (p && p.trim()) {
                t += `
                    <div class="col-12">
                        <h6 class="text-muted mb-2">Commentaire</h6>
                        <p class="small fst-italic">"${B(p.trim())}"</p>
                    </div>
                `;
            }

            t += "</div>";
            document.getElementById("modalText").innerHTML = t;
            y.show();
        }

        function D() {
            let e = document.getElementById("departureDate");
            if (e && !e.value) {
                e.min = _();
            }
        }

        function _() {
            let e = new Date,
                t = e.getFullYear(),
                s = String(e.getMonth() + 1).padStart(2, "0"),
                a = String(e.getDate()).padStart(2, "0");
            return `${t}-${s}-${a}`;
        }

        function w(e) {
            try {
                return new Date(e + "T00:00:00").toLocaleDateString("fr-FR", {
                    weekday: "long",
                    year: "numeric",
                    month: "long",
                    day: "numeric"
                });
            } catch (t) {
                console.warn("Erreur formatage date:", t);
                return e;
            }
        }

        function i(e) {
            let t = document.getElementById("errorAlert"),
                s = document.getElementById("errorMessage");
            if (t && s) {
                s.textContent = e;
                t.classList.remove("d-none");
                t.scrollIntoView({behavior: "smooth", block: "center"});
                setTimeout(() => {
                    t.classList.add("d-none");
                }, 5000);
            } else {
                alert(e);
            }
        }

        function B(e) {
            let t = document.createElement("div");
            t.textContent = e;
            return t.innerHTML;
        }

        // Initialiser le calcul de prix
        updatePriceCalculation();
    });
})();