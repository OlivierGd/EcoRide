(() => {
  // public/assets/js/vehicule.js
  (() => {
    (() => {
      (() => {
        (() => {
          (() => {
            var editModal = document.getElementById("editVehiculeModal");
            editModal.addEventListener("show.bs.modal", function(event) {
              const button = event.relatedTarget;
              const id = button.getAttribute("data-id");
              const marque = button.getAttribute("data-marque");
              const modele = button.getAttribute("data-modele");
              const carburant = button.getAttribute("data-carburant");
              const places = button.getAttribute("data-places");
              const immatriculation = button.getAttribute("data-immatriculation");
              console.log({
                id,
                marque,
                modele,
                carburant,
                places,
                immatriculation
              });
              document.getElementById("edit-id").value = id;
              document.getElementById("edit-marque").value = marque;
              document.getElementById("edit-modele").value = modele;
              document.getElementById("edit-carburant").value = carburant;
              document.getElementById("edit-places").value = places;
              document.getElementById("edit-immatriculation").value = immatriculation;
            });
          })();
        })();
      })();
    })();
  })();
})();
