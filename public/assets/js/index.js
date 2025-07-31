(() => {
  // Préremplit la date du jour dans le champ date
  document.addEventListener("DOMContentLoaded", () => {
    const dateInput = document.getElementById("searchDate");
    if (dateInput && !dateInput.value) { // Ne remplit que si le champ est vide
      dateInput.value = getTodayDate();
    }

    const form = document.getElementById("formSearchDestination");
    if (form) {
      form.addEventListener("submit", (e) => {
        const depart = document.getElementById("searchStartCity").value.trim();
        const destination = document.getElementById("searchEndCity").value.trim();
        const date = document.getElementById("searchDate").value;
        // Pour debug uniquement
        // console.log(`Départ : ${depart}`);
        // console.log(`Destination: ${destination}`);
        // console.log(`Date : ${date}`);
      });
    }
  });

  function getTodayDate() {
    const today = new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }
})();
