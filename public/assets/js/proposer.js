document.addEventListener('DOMContentLoaded', function() {
  // —————— Gestion du calcul Places × Prix ——————
  const form = document.getElementById('suggestedTripForm');
  const seatRadios = document.querySelectorAll('input[name="available_seats"]');
  const priceInput  = document.getElementById('pricePerPassenger');
  const placeFreeEl = document.getElementById('placeFree');
  const totalPriceEl= document.getElementById('totalPrice');

  function updateDisplay() {
    const seats = Array.from(seatRadios).find(r => r.checked)?.value || 0;
    const price  = parseInt(priceInput.value) || 0;
    placeFreeEl.textContent   = seats;
    totalPriceEl.textContent  = seats * price;
  }

  seatRadios.forEach(r => r.addEventListener('change', updateDisplay));
  priceInput.addEventListener('input', updateDisplay);
  updateDisplay();


  // —————— Gestion de la modale de confirmation ——————
  const publishBtn   = document.getElementById('publishSuggestedForm');
  const modalEl      = document.getElementById('confirmationModal');
  const modalText    = document.getElementById('modalText');
  const confirmBtn   = document.getElementById('confirmSubmit');
  const bsModal      = new bootstrap.Modal(modalEl);

  publishBtn.addEventListener('click', function(e) {
    e.preventDefault();

    // Récupère les valeurs saisies
    const startCity  = document.getElementById('startCity').value.trim();
    const endCity    = document.getElementById('endCity').value.trim();
    const dateRaw    = document.getElementById('departureDate').value.trim();
    const timeRaw    = document.getElementById('departureTime').value.trim();
    const seats     = Array.from(seatRadios).find(r => r.checked)?.value || 0;
    const price      = parseInt(priceInput.value) || 0;
    const comment    = document.getElementById('commentForPassenger').value.trim();

    // Vérification rapide
    if (!startCity || !endCity || !dateRaw || !timeRaw || !seats || !price) {
      console.warn('Veuillez remplir tous les champs obligatoires.');
      return;
    }

    // Formate la date en JJ/MM/AAAA
    const [y,m,d] = dateRaw.split('-');
    const displayDate = `${d}/${m}/${y}`;

    // Compose le texte de confirmation
    modalText.innerHTML = `
      <p>Vous proposez un trajet de <strong>${startCity}</strong> à <strong>${endCity}</strong>.</p>
      <p><strong>Date :</strong> ${displayDate} <strong>Heure :</strong> ${timeRaw}</p>
      <p><strong>Places :</strong> ${seats} — <strong>Prix :</strong> ${price} crédits</p>
      ${comment ? `<p><strong>Commentaire :</strong> ${comment}</p>` : ''}
      <p>Confirmez-vous la publication ?</p>
    `;

    // Affiche la modale
    bsModal.show();
  });

  // Quand l’utilisateur confirme dans la modale
  confirmBtn.addEventListener('click', function() {
    bsModal.hide();
    form.submit();
  });
});
