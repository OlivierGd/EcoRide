(() => {
    (() => {
        // Récupère l'évènement de la modale d'édition
        const editVehiculemodal = document.getElementById("editVehiculeModal");
        // On ajoute un listener pour l'ouverture de la modale'
        editVehiculemodal.addEventListener("show.bs.modal", function (event) {
            // Bouton qui déclenche l'ouverture de la modale
            let triggerButton = event.relatedTarget;

            // Récupère les attributs data du bouton cliqué
            let vehiculeId = triggerButton.getAttribute("data-id");
            let vehiculeMarque = triggerButton.getAttribute("data-marque");
            let vehiculeModele= triggerButton.getAttribute("data-modele");
            let vehiculeCarburant = triggerButton.getAttribute("data-carburant");
            let vehiculePlaces = triggerButton.getAttribute("data-places");
            let vehiculeImmatriculation = triggerButton.getAttribute("data-immatriculation");

            // Remplissage du formulaire de la modale
            document.getElementById("edit-id").value = vehiculeId;
            document.getElementById("edit-marque").value = vehiculeMarque;
            document.getElementById("edit-modele").value = vehiculeModele;
            document.getElementById("edit-carburant").value = vehiculeCarburant;
            document.getElementById("edit-places").value = vehiculePlaces;
            document.getElementById("edit-immatriculation").value = vehiculeImmatriculation
        });
    })();
})();
