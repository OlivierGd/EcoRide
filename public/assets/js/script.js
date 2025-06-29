(() => {
  // public/assets/js/script.js
  document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("formSearchDestination");
    form.addEventListener("submit", (e) => {
      const depart = document.getElementById("searchStartCity").value.trim();
      const destination = document.getElementById("searchEndCity").value.trim();
      const date = document.getElementById("searchDate").value;
      if (!depart || !destination || !date) {
        alert("Merci de compl\xE9ter les champs : D\xE9part, Destination et date");
        e.preventDefault();
        return;
      }
      console.log(`D\xE9part : ${depart}`);
      console.log(`Destination: ${destination}`);
      console.log(`Date : ${date}`);
    });
  });
  document.addEventListener("DOMContentLoaded", () => {
    const dateInput = document.getElementById("searchDate");
    if (dateInput) {
      dateInput.value = getTodayDate();
    }
  });
  function getTodayDate() {
    const today = /* @__PURE__ */ new Date();
    const year = today.getFullYear();
    const month = String(today.getMonth() + 1).padStart(2, "0");
    const day = String(today.getDate()).padStart(2, "0");
    return `${year}-${month}-${day}`;
  }
})();
