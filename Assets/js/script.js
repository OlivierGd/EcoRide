//Search form destination
// Waitting DOM is completly charged
document.addEventListener('DOMContentLoaded', () => {
    const form = document.getElementById('formSearchDestination');
    form.addEventListener('submit', (e) => {
        e.preventDefault();
        const depart = document.getElementById('searchStartCity').value.trim();
        const destination = document.getElementById('searchEndCity').value.trim();
        const date = document.getElementById('searchDate').value;

        if (!depart || !destination || !date) {
            alert('Merci de compléter les champs : Départ, Destination et date');
            return;
        }

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

function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, '0');
    const day = String(today.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
}
