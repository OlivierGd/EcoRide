//Fonction pour afficher les bénéfices de crédits estimés selon le nombre de places et le prix demandé
function getSelectedPlace() {
    const places = document.querySelectorAll('input[name="places"]');
    for (const place of places) {
        if (place.checked) {
            return parseInt(place.value); // converti le string en num pour le nombre de places
        }
    }
    return "0";
}
// Fonction pour récupérer le prix proposé par passager
function getPriceSuggested() {
    const priceInput = document.getElementById('priceRequested');
    const price = parseInt(priceInput.value);
    return parseInt(priceInput.value);
}

//fonction pour mettre à jour l'affichage du nombre de places
function updateDisplay() {
    const placeElement = document.getElementById('placeFree');
    placeElement.innerHTML = getSelectedPlace();
    const passengers = parseInt(getSelectedPlace());
    const pricePerPassenger = getPriceSuggested();
    const totalPrice = passengers * pricePerPassenger;
    const totalPriceElement = document.getElementById('totalPrice');
    totalPriceElement.innerHTML = totalPrice;
}

//Ajout d'un écouteur pour chaque radio
const radios = document.querySelectorAll('input[name="places"]');
radios.forEach(radio => {
    radio.addEventListener('change', updateDisplay);
})

//Ajoute un écouteur lors du changement de prix proposé
document.getElementById('priceRequested').addEventListener('input', updateDisplay);

//Affiche la valeur du prix selon le nombre de places sélectionnées
document.addEventListener('DOMContentLoaded', updateDisplay);


//Fonction pour récupérer les données du formulaire

document.addEventListener('DOMContentLoaded', () => {
    const dataSuggestedForm = [];

    const publishButton = document.getElementById('publishSuggestedForm');
    const confirmButton = document.getElementById('confirmSubmit');
    const modal = document.getElementById('confirmationModal');
    const modalText = document.getElementById('modalText');
    const form = document.getElementById('suggestedTripForm');

    publishButton.addEventListener('click', (e) => {
        e.preventDefault();

        const startCity = document.getElementById('suggestedStartCity').value.trim();
        const endCity = document.getElementById('suggestedEndCity').value.trim();
        const rawDate = document.getElementById('proposalDate').value.trim(); // la date reste YYYY-MM-DD
        const [year, month, day] = rawDate.split('-');
        const startDate = `${day}/${month}/${year}`; // pour affichage seulement de la date format DD-MM-YYYY
        const startTime = document.getElementById('proposalTime').value.trim();
        const placeAvailable = getSelectedPlace();
        const proposalCredits = getPriceSuggested();

        if (!startCity || !endCity || !startDate || !startTime || !placeAvailable || !proposalCredits) {
            console.warn("Veuillez remplir tous les champs !");
            return;
        }

        modalText.innerHTML = `Vous proposez un trajet de <strong>${startCity}</strong> à <strong>${endCity}</strong>
            le <strong>${startDate}</strong> avec un départ à <strong>${startTime}</strong>.</br>Voulez-vous le proposer sur EcoRide ?`;

        const modalInstance = new bootstrap.Modal(modal);
        modalInstance.show();

        confirmButton.onclick = () => {
            const formData = {
                startCity,
                endCity,
                startDate : rawDate, //format YYYY-MM-DD pour php
                startTime,
                placeAvailable,
                proposalCredits
            };

            dataSuggestedForm.push(formData);
            console.log("Données du formulaire : ", formData);
            console.log("Tableau complet : ", dataSuggestedForm);

            modalInstance.hide();
            form.submit();
        };
    });
});
