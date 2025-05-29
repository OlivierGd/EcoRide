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

//Ajouter un écouteur lors du changement de prix proposé
document.getElementById('priceRequested').addEventListener('input', updateDisplay);

//Affiche la valeur du prix selon le nombre de places sélectionnées
document.addEventListener('DOMContentLoaded', updateDisplay);


//Fonction pour récupérer les données du formulaire

const dataSuggestedForm = [];
function getDataSuggestedForm(e) {
    e.preventDefault(); //Empeche le rechargement du formulaire

    const inputStartCity = document.getElementById('suggestedStartCity');
    const inputEndCity = document.getElementById('suggestedEndCity');
    const inputStartDate = document.getElementById('proposalDate');
    const inputStartTime = document.getElementById('proposalTime');
    const inputPlaceAvailable = getSelectedPlace();
    const inputProposalCredits = document.getElementById('priceRequested');

    const startCity = inputStartCity.value.trim();
    const endCity = inputEndCity.value.trim();
    const startDate = inputStartDate.value.trim();
    const startTime = inputStartTime.value.trim();
    const placeAvailable = getSelectedPlace();
    const proposalCredits = parseInt(inputProposalCredits.value.trim());
    console.log(startCity, endCity, startDate, startTime, placeAvailable, proposalCredits);

    if (!startCity || !endCity || !startDate || !startTime || !placeAvailable || !proposalCredits) {  //Vérifie si les champs sont complétés
        console.warn("Veuillez remplir tous les champs !");
        return;
    }

    // Afficher la modale de confirmation des données du formulaire
    const modal = document.getElementById('confirmationModal');
    const modalText = document.getElementById('modalText');
    modalText.innerHTML = `Vous proposez un trajet de <strong>${startCity} </strong> à <strong>${endCity} </strong>le <strong>${startDate}</strong>. Confirmez-vous ?`;
    const modalInstance = new bootstrap.Modal(modal);
    modalInstance.show();

    // Réponse et validation de la confirmation
    document.getElementById('confirmSubmit').onclick = () => {
        const modaleValidation = bootstrap.Modal.getInstance(modal);
        modaleValidation.hide();// Ferme la modale
    }

    const formData = {
        startCity: startCity,
        endCity: endCity,
        startDate: startDate,
        startTime: startTime,
        placeAvailable: placeAvailable,
        proposalCredits: proposalCredits,
    }
    dataSuggestedForm.push(formData);
    console.log("Données du formulaire : ",formData);
    console.log("Tableau complet : ", dataSuggestedForm);

}
document.addEventListener('DOMContentLoaded', () => {
    const publishButton = document.getElementById('publishSuggestedForm');
    publishButton.addEventListener('click', getDataSuggestedForm);
})
