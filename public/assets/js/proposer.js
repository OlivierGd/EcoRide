(() => {
  // public/assets/js/proposer.js
  (() => {
    (() => {
      (() => {
        (() => {
          document.addEventListener("DOMContentLoaded", function() {
            const form = document.getElementById("suggestedTripForm");
            const seatRadios = document.querySelectorAll('input[name="available_seats"]');
            const priceInput = document.getElementById("pricePerPassenger");
            const placeFreeEl = document.getElementById("placeFree");
            const totalPriceEl = document.getElementById("totalPrice");
            function updateDisplay() {
              const seats = Array.from(seatRadios).find((r) => r.checked)?.value || 0;
              const price = parseInt(priceInput.value) || 0;
              placeFreeEl.textContent = seats;
              totalPriceEl.textContent = seats * price;
            }
            seatRadios.forEach((r) => r.addEventListener("change", updateDisplay));
            priceInput.addEventListener("input", updateDisplay);
            updateDisplay();
            const publishBtn = document.getElementById("publishSuggestedForm");
            const modalEl = document.getElementById("confirmationModal");
            const modalText = document.getElementById("modalText");
            const confirmBtn = document.getElementById("confirmSubmit");
            const bsModal = new bootstrap.Modal(modalEl);
            publishBtn.addEventListener("click", function(e) {
              e.preventDefault();
              const startCity = document.getElementById("startCity").value.trim();
              const endCity = document.getElementById("endCity").value.trim();
              const dateRaw = document.getElementById("departureDate").value.trim();
              const timeRaw = document.getElementById("departureTime").value.trim();
              const seats = Array.from(seatRadios).find((r) => r.checked)?.value || 0;
              const price = parseInt(priceInput.value) || 0;
              const comment = document.getElementById("commentForPassenger").value.trim();
              const startLocation = document.getElementById("startLocation").value.trim();
              const endLocation = document.getElementById("endLocation").value.trim();
              const durationH = document.getElementById("durationHours").value || "";
              const durationM = document.getElementById("durationMinutes").value || "";
              let durationText = "";
              if (durationH || durationM) {
                durationText = `<p><strong>Dur\xE9e estim\xE9e :</strong> ${durationH}h${durationM}min</p>`;
              }
              if (!startCity || !endCity || !dateRaw || !timeRaw || !seats || !price) {
                console.warn("Veuillez remplir tous les champs obligatoires.");
                return;
              }
              const [y, m, d] = dateRaw.split("-");
              const displayDate = `${d}/${m}/${y}`;
              modalText.innerHTML = `
      <p>Vous proposez un trajet de <strong>${startCity}</strong> \xE0 <strong>${endCity}</strong>.</p>
      <p><strong>Date du voyage :</strong> ${displayDate} <strong>Heure de d\xE9part :</strong> ${timeRaw}</p>
      <p><strong>Places disponnibles :</strong> ${seats} \u2014 <strong>Prix :</strong> ${price} cr\xE9dits</p>
      ${startLocation ? `<p><strong>Lieu de d\xE9part :</strong> ${startLocation}</p>` : ""}
      ${endLocation ? `<p><strong>Lieu d'arriv\xE9e :</strong> ${endLocation}</p>` : ""}
      ${durationText}
      ${comment ? `<p><strong>Commentaire :</strong> ${comment}</p>` : ""}
      <p>Proposer ce trajet pour la communaut\xE9 EcoRide ?</p>
    `;
              bsModal.show();
            });
            confirmBtn.addEventListener("click", function() {
              bsModal.hide();
              form.submit();
            });
          });
        })();
      })();
    })();
  })();
})();
