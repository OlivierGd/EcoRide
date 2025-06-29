(() => {
  // public/assets/js/proposer.js
  function getSelectedPlace() {
    const places = document.querySelectorAll('input[name="places"]');
    for (const place of places) {
      if (place.checked) {
        return parseInt(place.value);
      }
    }
    return "0";
  }
  function getPriceSuggested() {
    const priceInput = document.getElementById("priceRequested");
    const price = parseInt(priceInput.value);
    return parseInt(priceInput.value);
  }
  function updateDisplay() {
    const placeElement = document.getElementById("placeFree");
    placeElement.innerHTML = getSelectedPlace();
    const passengers = parseInt(getSelectedPlace());
    const pricePerPassenger = getPriceSuggested();
    const totalPrice = passengers * pricePerPassenger;
    const totalPriceElement = document.getElementById("totalPrice");
    totalPriceElement.innerHTML = totalPrice;
  }
  var radios = document.querySelectorAll('input[name="places"]');
  radios.forEach((radio) => {
    radio.addEventListener("change", updateDisplay);
  });
  document.getElementById("priceRequested").addEventListener("input", updateDisplay);
  document.addEventListener("DOMContentLoaded", updateDisplay);
  document.addEventListener("DOMContentLoaded", () => {
    const dataSuggestedForm = [];
    const publishButton = document.getElementById("publishSuggestedForm");
    const confirmButton = document.getElementById("confirmSubmit");
    const modal = document.getElementById("confirmationModal");
    const modalText = document.getElementById("modalText");
    const form = document.getElementById("suggestedTripForm");
    publishButton.addEventListener("click", (e) => {
      e.preventDefault();
      const startCity = document.getElementById("suggestedStartCity").value.trim();
      const endCity = document.getElementById("suggestedEndCity").value.trim();
      const rawDate = document.getElementById("proposalDate").value.trim();
      const [year, month, day] = rawDate.split("-");
      const startDate = `${day}/${month}/${year}`;
      const startTime = document.getElementById("proposalTime").value.trim();
      const placeAvailable = getSelectedPlace();
      const proposalCredits = getPriceSuggested();
      if (!startCity || !endCity || !startDate || !startTime || !placeAvailable || !proposalCredits) {
        console.warn("Veuillez remplir tous les champs !");
        return;
      }
      modalText.innerHTML = `Vous proposez un trajet de <strong>${startCity}</strong> \xE0 <strong>${endCity}</strong>
            le <strong>${startDate}</strong> avec un d\xE9part \xE0 <strong>${startTime}</strong>.</br>Voulez-vous le proposer sur EcoRide ?`;
      const modalInstance = new bootstrap.Modal(modal);
      modalInstance.show();
      confirmButton.onclick = () => {
        const formData = {
          startCity,
          endCity,
          startDate: rawDate,
          //format YYYY-MM-DD pour php
          startTime,
          placeAvailable,
          proposalCredits
        };
        dataSuggestedForm.push(formData);
        console.log("Donn\xE9es du formulaire : ", formData);
        console.log("Tableau complet : ", dataSuggestedForm);
        modalInstance.hide();
        form.submit();
      };
    });
  });
})();
