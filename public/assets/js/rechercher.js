(() => {
  // public/assets/js/rechercher.js
  (() => {
    (() => {
      (() => {
        (() => {
          document.addEventListener("DOMContentLoaded", function() {
            const resetBtn = document.getElementById("resetSearchForm");
            const form = document.getElementById("formSearchDestination");
            if (resetBtn && form) {
              resetBtn.addEventListener("click", function(e) {
                e.preventDefault();
                window.location.href = window.location.pathname;
              });
            }
          });

          // Modale de validation de réservation
          const reservationModal = document.getElementById('reservationModal');

          reservationModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const tripId = button.getAttribute('data-trip-id');
            const startCity = button.getAttribute('data-start-city');
            const endCity = button.getAttribute('data-end-city');
            const departure = button.getAttribute('data-departure');
            const price = button.getAttribute('data-price');

            document.getElementById('modalStartCity').textContent = startCity;
            document.getElementById('modalEndCity').textContent = endCity;
            document.getElementById('modalDeparture').textContent = departure;
            document.getElementById('modalPrice').textContent = price;
            document.getElementById('confirmTripId').value = tripId;
          });
        })();
      })();
    })();
  })();
})();
