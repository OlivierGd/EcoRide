(() => {
    /**
     * Construit le conteneur de suggestions sous le champ de texte.
     * @param {string} inputId ID du champ texte
     * @param {string} suggestionId ID du conteneur de suggestions
     */
    function createSuggestionBox(inputId, suggestionId) {
        const inputEl = document.getElementById(inputId);
        if (!inputEl) {
            console.warn(`Champ ${inputId} non trouvé`);
            return;
        }

        // Cherche le conteneur parent pour positionner correctement la liste
        const container = inputEl.closest(".input-group") || inputEl.parentElement;
        if (!container) {
            console.warn(`Conteneur parent non trouvé pour ${inputId}`);
            return;
        }

        // Si le conteneur existe déjà, ne rien faire
        if (document.getElementById(suggestionId)) return;

        // Création du bloc suggestions
        const box = document.createElement("div");
        box.id = suggestionId;
        box.className = "suggestion-box";
        box.style.cssText = `
          position: absolute;
          top: 100%;
          left: 0;
          right: 0;
          background: white;
          border: 1px solid #dee2e6;
          border-top: none;
          border-radius: 0 0 0.375rem 0.375rem;
          max-height: 200px;
          overflow-y: auto;
          z-index: 1000;
          display: none;
          box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        `;

        container.style.position = "relative";
        container.appendChild(box);
    }

    /**
     * Configure l’autocomplétion sur un champ texte et son conteneur de suggestions.
     * @param {string} inputId ID du champ texte
     * @param {string} suggestionId ID du conteneur de suggestions
     */
    function setupAutocomplete(inputId, suggestionId) {
        const inputEl = document.getElementById(inputId);
        const suggestionEl = document.getElementById(suggestionId);

        if (!inputEl || !suggestionEl) {
            console.error(`Éléments non trouvés : ${inputId} ou ${suggestionId}`);
            return;
        }

        let debounceTimeout;

        // Lors de la saisie : recherche avec un délai
        inputEl.addEventListener("input", () => {
            const query = inputEl.value.trim();
            clearTimeout(debounceTimeout);

            if (query.length < 2) {
                suggestionEl.style.display = "none";
                suggestionEl.innerHTML = "";
                return;
            }

            debounceTimeout = setTimeout(async () => {
                try {
                    const response = await fetch(`https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,codeDepartement&boost=population&limit=8`);
                    if (!response.ok) throw new Error("Erreur réseau");
                    const data = await response.json();

                    suggestionEl.innerHTML = "";
                    if (data.length === 0) {
                        suggestionEl.style.display = "none";
                        return;
                    }

                    // Génère chaque suggestion
                    data.forEach((item) => {
                        const option = document.createElement("div");
                        option.className = "suggestion-item";
                        option.style.cssText = `
                          padding: 10px 15px;
                          cursor: pointer;
                          border-bottom: 1px solid #f8f9fa;
                          transition: background-color 0.2s;
                        `;
                        option.textContent = `${item.nom} (${item.codeDepartement})`;

                        option.addEventListener("mouseenter", () => { option.style.backgroundColor = "#f8f9fa"; });
                        option.addEventListener("mouseleave", () => { option.style.backgroundColor = "white"; });
                        option.addEventListener("click", () => {
                            inputEl.value = item.nom;
                            suggestionEl.style.display = "none";
                            suggestionEl.innerHTML = "";
                            // Déclenche un nouvel événement "input" pour propager le changement
                            inputEl.dispatchEvent(new Event("input", { bubbles: true }));
                        });

                        suggestionEl.appendChild(option);
                    });

                    suggestionEl.style.display = "block";
                } catch (err) {
                    console.error("Erreur lors de l'autocomplétion :", err);
                    suggestionEl.style.display = "none";
                }
            }, 300);
        });

        // Masque la liste lors d’un clic à l’extérieur
        document.addEventListener("click", (evt) => {
            if (!inputEl.contains(evt.target) && !suggestionEl.contains(evt.target)) {
                suggestionEl.style.display = "none";
            }
        });

        // Ferme la liste avec la touche Échap
        inputEl.addEventListener("keydown", (evt) => {
            if (evt.key === "Escape") suggestionEl.style.display = "none";
        });
    }

    /**
     * Initialise l’autocomplétion pour une liste de champs.
     * @param {{inputId: string, suggestionId: string}[]} configs
     */
    function initAutoCompleteForFields(configs) {
        configs.forEach(({ inputId, suggestionId }) => {
            createSuggestionBox(inputId, suggestionId);
            setupAutocomplete(inputId, suggestionId);
        });
    }

    // Configuration des champs selon les pages
    const pageConfig = {
        search: [
            { inputId: "searchStartCity", suggestionId: "startCitySuggestions" },
            { inputId: "searchEndCity",   suggestionId: "endCitySuggestions"   },
        ],
        propose: [
            { inputId: "startCity", suggestionId: "startCitySuggestions" },
            { inputId: "endCity",   suggestionId: "endCitySuggestions"   },
        ],
    };

    // Lance l’autocomplétion une fois la page chargée
    document.addEventListener("DOMContentLoaded", () => {
        if (document.getElementById("searchStartCity") && document.getElementById("searchEndCity")) {
            console.log("Initialisation de l’autocomplétion pour la page de recherche");
            initAutoCompleteForFields(pageConfig.search);
        }

        if (document.getElementById("startCity") && document.getElementById("endCity")) {
            console.log("Initialisation de l’autocomplétion pour la page de proposition");
            initAutoCompleteForFields(pageConfig.propose);
        }
    });
})();
