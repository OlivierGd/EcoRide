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

//Fonction pour récupérer les données du formulaire

const dataSuggestedForm = [];
function getDataSuggestedForm() {
    event.preventDefault(); //Empeche le rechargement du formaulaire

    const inputStartCity = document.getElementById('suggestedStartCity');
    const inputEndCity = document.getElementById('suggestedEndCity');

    const startCity = inputStartCity.value.trim();
    const endCity = inputEndCity.value.trim();
    console.log(startCity, endCity);

    if (!startCity || !endCity) {  //Vérifie si les champs sont complétés
        console.warn("Veuillez remplir tous les champs !");
        return;
    }
    const formData = {
        startCity: startCity,
        endCity: endCity,
    }
    dataSuggestedForm.push(formData);
    console.log("Données du formulaire : ",formData);
    console.log("Tableau complet : ", dataSuggestedForm);
}
document.addEventListener('DOMContentLoaded', () => {
    const publishButton = document.getElementById('publishSuggestedForm');
    publishButton.addEventListener('click', getDataSuggestedForm);
})
