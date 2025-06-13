//Form search destination
// Waitting DOM is completly charged
document.addEventListener('DOMContentLoaded', () => {
    //Rechercher le formulaire
    const form = document.getElementById('formSearchDestination');
    //Soumettre le formulaire
    form.addEventListener('submit', (e) => {
        //Récupérer les valeurs des champs saisis
        const depart = document.getElementById('searchStartCity').value.trim();
        const destination = document.getElementById('searchEndCity').value.trim();
        const date = document.getElementById('searchDate').value;
        //Afficher un message d'alerte si un champ est vide
        if (!depart || !destination || !date) {
            alert('Merci de compléter les champs : Départ, Destination et date');
            e.preventDefault();
            return;
        }
        //Vérifier dans la console le résultat
        console.log(`Départ : ${depart}`);
        console.log(`Destination: ${destination}`);
        console.log(`Date : ${date}`);
    })
})


//add day date of today into the form when charging page//
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('searchDate');
    if (dateInput) {
        dateInput.value = getTodayDate();
    }
});
//Fonction pour renvoyer un format de date jj/mm/yyyy en FO et un format de date yyyy-mm-dd en console
function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}

//Suggest a trip for passenger

