(() => {
    /**
     * Remet le formulaire de recherche à l'état initial en rechargeant la page
     * sans querystring. Évite d'avoir à vider chaque champ un par un.
     */
    function resetSearchFormToBlank() {
        window.location.href = window.location.pathname;
    }

    /**
     * Renseigne la modale de réservation avec les attributs data-* du bouton cliqué.
     * @param {HTMLElement} trigger - Le bouton qui a ouvert la modale (relatedTarget)
     */
    function fillReservationModalFromTrigger(trigger) {
        if (!trigger) return;

        // Récupération des valeurs portées par le bouton "Réserver"
        const tripId       = trigger.getAttribute("data-trip-id");
        const startCity    = trigger.getAttribute("data-start-city");
        const endCity      = trigger.getAttribute("data-end-city");
        const departureLbl = trigger.getAttribute("data-departure");
        const price        = trigger.getAttribute("data-price");

        // Cibles dans la modale
        const elStartCity   = document.getElementById("modalStartCity");
        const elEndCity     = document.getElementById("modalEndCity");
        const elDeparture   = document.getElementById("modalDeparture");
        const elPrice       = document.getElementById("modalPrice");
        const elConfirmTrip = document.getElementById("confirmTripId");

        // Sécurité : ne rien faire si la modale n'est pas dans le DOM
        if (!elStartCity || !elEndCity || !elDeparture || !elPrice || !elConfirmTrip) return;

        // Injection des valeurs
        elStartCity.textContent   = startCity ?? "";
        elEndCity.textContent     = endCity ?? "";
        elDeparture.textContent   = departureLbl ?? "";
        elPrice.textContent       = price ?? "";
        elConfirmTrip.value       = tripId ?? "";
    }


    document.addEventListener("DOMContentLoaded", () => {
        // Bouton "Vider" du formulaire de recherche
        const resetBtn = document.getElementById("resetSearchForm");
        const searchForm = document.getElementById("formSearchDestination");

        if (resetBtn && searchForm) {
            resetBtn.addEventListener("click", (evt) => {
                evt.preventDefault();
                resetSearchFormToBlank();
            });
        }

        // Écoute l'ouverture de la modale Bootstrap pour la pré-remplir
        const reservationModal = document.getElementById("reservationModal");
        if (reservationModal) {
            reservationModal.addEventListener("show.bs.modal", (evt) => {
                // Bootstrap fournit le bouton déclencheur via relatedTarget
                const trigger = evt.relatedTarget;
                fillReservationModalFromTrigger(trigger);
            });
        }
    });
})();
