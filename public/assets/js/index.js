(() => {
  // public/assets/js/index.js
    document.addEventListener("DOMContentLoaded", () => {
        const dateInput = document.getElementById("searchDate");
        if (dateInput && !dateInput.value) {
            dateInput.value = getTodayDate();
        }
        const form = document.getElementById("formSearchDestination");
        if (form) {
            form.addEventListener("submit", (e) => {
                const depart = document.getElementById("searchStartCity").value.trim();
                const destination = document.getElementById("searchEndCity").value.trim();
                const date = document.getElementById("searchDate").value;
                if (!depart && !destination && !date) {
                    e.preventDefault();
                    alert("Veuillez remplir au moins un champ (ville de d\xE9part, destination ou date).");
                    return;
                }
                if (date) {
                    const selectedDate = new Date(date);
                    const today = /* @__PURE__ */ new Date();
                    today.setHours(0, 0, 0, 0);
                    if (selectedDate < today) {
                        e.preventDefault();
                        alert("La date ne peut-\xEAtre dans le pass\xE9");
                        return;
                    }
                }
            });
        }
        setupCustomAutocomplete("searchStartCity", "startCitySuggestions");
        setupCustomAutocomplete("searchEndCity", "endCitySuggestions");
    });
    function getTodayDate() {
        const today = /* @__PURE__ */ new Date();
        const year = today.getFullYear();
        const month = String(today.getMonth() + 1).padStart(2, "0");
        const day = String(today.getDate()).padStart(2, "0");
        return `${year}-${month}-${day}`;
    }

    // Autocompletion des villes + Num département
    function setupCustomAutocomplete(inputId, suggestionBoxId) {
        const input = document.getElementById(inputId);
        const suggestionBox = document.getElementById(suggestionBoxId);
        if (!input || !suggestionBox) {
            console.error(`\xC9l\xE9ments non trouv\xE9s: ${inputId} ou ${suggestionBoxId}`);
            return;
        }
        let debounceTimer;
        input.addEventListener("input", () => {
            const query = input.value.trim();
            clearTimeout(debounceTimer);
            if (query.length < 2) {
                suggestionBox.style.display = "none";
                suggestionBox.innerHTML = "";
                return;
            }
            debounceTimer = setTimeout(async () => {
                try {
                    const response = await fetch(
                        `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,codeDepartement&boost=population&limit=8`
                    );
                    if (!response.ok) {
                        throw new Error("Erreur r\xE9seau");
                    }
                    const cities = await response.json();
                    suggestionBox.innerHTML = "";
                    if (cities.length === 0) {
                        suggestionBox.style.display = "none";
                        return;
                    }
                    cities.forEach((city) => {
                        const suggestionItem = document.createElement("div");
                        suggestionItem.className = "suggestion-item";
                        suggestionItem.textContent = `${city.nom} (${city.codeDepartement})`;
                        suggestionItem.addEventListener("click", () => {
                            input.value = city.nom;
                            suggestionBox.style.display = "none";
                            suggestionBox.innerHTML = "";
                        });
                        suggestionBox.appendChild(suggestionItem);
                    });
                    suggestionBox.style.display = "block";
                } catch (error) {
                    console.error("Erreur lors de l'autocompl\xE9tion :", error);
                    suggestionBox.style.display = "none";
                }
            }, 300);
        });
        document.addEventListener("click", (event) => {
            if (!input.contains(event.target) && !suggestionBox.contains(event.target)) {
                suggestionBox.style.display = "none";
            }
        });
        input.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                suggestionBox.style.display = "none";
            }
        });
    }
})();
