//Fonction pour afficher les bénéfices de crédits estimés selon le nombre de places et le prix demandé
function getSelectedPlace() {
    const places = document.querySelectorAll('input[name="places"]');
    for (const place of places) {
        if (place.checked) {
            return place.value;
        }
    }
    return "0";
}
// Fonction pour récupérer le prix proposé par passager
function getPriceSuggested() {
    const priceInput = document.getElementById('priceRequested');
    const price = parseFloat(priceInput.value);
    return price;
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

//Récupérer les données dans le formulaire

function getDataSuggestedForm() {
    const inputStartCity = document.getElementById('suggestedStartCity');
    const inputEndCity = document.getElementById('suggestedEndCity');
    console.log(inputStartCity.value, inputEndCity.value);
    inputStartCity.value.push(dataForm);
    inputEndCity.value.push(dataForm);
    console.log(dataForm);
}

const dataForm = [];
const publishButton = document.getElementById('publishSuggestedForm');
publishButton.addEventListener('click', getDataSuggestedForm);
