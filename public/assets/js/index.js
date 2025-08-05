document.addEventListener("DOMContentLoaded", () => {
  // Initialiser la date par défaut
  const dateInput = document.getElementById("searchDate");
  if (dateInput && !dateInput.value) {
    dateInput.value = getTodayDate();
  }

  // Gérer la soumission du formulaire
  const form = document.getElementById("formSearchDestination");
  if (form) {
    form.addEventListener("submit", (e) => {
      const depart = document.getElementById("searchStartCity").value.trim();
      const destination = document.getElementById("searchEndCity").value.trim();
      const date = document.getElementById("searchDate").value;

      // Validation basique
      if (!depart && !destination && !date) {
        e.preventDefault();
        alert("Veuillez remplir au moins un champ (ville de départ, destination ou date).");
        return;
      }

      // Validation de la date (Ne peut être dans le passé)
      if (date) {
        const selectedDate = new Date(date);
        const today = new Date();
        today.setHours(0, 0, 0, 0); // Reset l'heure pour comparer seulement la date

        if (selectedDate < today) {
          e.preventDefault();
          alert("La date ne peut-être dans le passé");
          return;
        }
      }
    });
  }

  // Configurer l'autocomplétion
  setupCustomAutocomplete("searchStartCity", "startCitySuggestions");
  setupCustomAutocomplete("searchEndCity", "endCitySuggestions");
});

function getTodayDate() {
  const today = new Date();
  const year = today.getFullYear();
  const month = String(today.getMonth() + 1).padStart(2, "0");
  const day = String(today.getDate()).padStart(2, "0");
  return `${year}-${month}-${day}`;
}

function setupCustomAutocomplete(inputId, suggestionBoxId) {
  const input = document.getElementById(inputId);
  const suggestionBox = document.getElementById(suggestionBoxId);

  if (!input || !suggestionBox) {
    console.error(`Éléments non trouvés: ${inputId} ou ${suggestionBoxId}`);
    return;
  }

  let debounceTimer;

  input.addEventListener('input', () => {
    const query = input.value.trim();

    // Effacer le timer précédent
    clearTimeout(debounceTimer);

    // Cacher les suggestions si moins de 2 caractères
    if (query.length < 2) {
      suggestionBox.style.display = 'none';
      suggestionBox.innerHTML = '';
      return;
    }

    // Débouncer les requêtes pour éviter trop d'appels API
    debounceTimer = setTimeout(async () => {
      try {
        const response = await fetch(
            `https://geo.api.gouv.fr/communes?nom=${encodeURIComponent(query)}&fields=nom,codeDepartement&boost=population&limit=8`
        );

        if (!response.ok) {
          throw new Error('Erreur réseau');
        }

        const cities = await response.json();

        // Vider les suggestions précédentes
        suggestionBox.innerHTML = '';

        if (cities.length === 0) {
          suggestionBox.style.display = 'none';
          return;
        }

        // Créer les éléments de suggestion
        cities.forEach(city => {
          const suggestionItem = document.createElement('div');
          suggestionItem.className = 'suggestion-item';
          suggestionItem.textContent = `${city.nom} (${city.codeDepartement})`;

          suggestionItem.addEventListener('click', () => {
            input.value = city.nom;
            suggestionBox.style.display = 'none';
            suggestionBox.innerHTML = '';
          });

          suggestionBox.appendChild(suggestionItem);
        });

        // Afficher la boîte de suggestions
        suggestionBox.style.display = 'block';

      } catch (error) {
        console.error("Erreur lors de l'autocomplétion :", error);
        suggestionBox.style.display = 'none';
      }
    }, 300); // Attendre 300ms après la dernière frappe
  });

  // Cacher les suggestions quand on clique ailleurs
  document.addEventListener('click', (event) => {
    if (!input.contains(event.target) && !suggestionBox.contains(event.target)) {
      suggestionBox.style.display = 'none';
    }
  });

  // Gérer les touches clavier (optionnel : navigation avec flèches)
  input.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
      suggestionBox.style.display = 'none';
    }
  });
}