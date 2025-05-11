//add date into the form//
document.addEventListener('DOMContentLoaded', () => {
    const dateInput = document.getElementById('formSearchDate');
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
