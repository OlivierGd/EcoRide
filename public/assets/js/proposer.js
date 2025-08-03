document.addEventListener('DOMContentLoaded', function () {
  // ——— FORMULAIRE & ÉLÉMENTS ———
  const form = document.getElementById('tripForm');
  const startCity = document.getElementById('startCity');
  const endCity = document.getElementById('endCity');
  const departureDate = document.getElementById('departureDate');
  const departureTime = document.getElementById('departureTime');
  const comment = document.getElementById('commentForPassenger');
  const priceInput = document.getElementById('pricePerPassenger');
  const seatRadios = document.querySelectorAll('input[name="available_seats"]');
  const publishBtn = document.getElementById('publishSuggestedForm');
  const modalText = document.getElementById('modalText');
  const confirmBtn = document.getElementById('confirmSubmit');
  const modalEl = document.getElementById('confirmationModal');
  const bsModal = new bootstrap.Modal(modalEl);

  // ——— BARRE DE PROGRESSION ———
  const steps = document.querySelectorAll('.form-step');
  const circles = document.querySelectorAll('.circle');

  const observer = new IntersectionObserver(
      entries => {
        entries.forEach(entry => {
          if (entry.isIntersecting) {
            const index = Array.from(steps).indexOf(entry.target);
            circles.forEach((circle, i) => {
              if (i < index) {
                circle.classList.remove('bg-light', 'text-secondary', 'border-secondary');
                circle.classList.add('bg-success', 'text-white');
              } else if (i === index) {
                circle.classList.remove('bg-light', 'text-secondary', 'border-secondary');
                circle.classList.add('bg-success', 'text-white');
              } else {
                circle.classList.remove('bg-success', 'text-white');
                circle.classList.add('bg-light', 'text-secondary', 'border', 'border-secondary');
              }
            });
          }
        });
      },
      {
        rootMargin: '0px 0px -60% 0px',
        threshold: 0.2
      }
  );

  steps.forEach(step => observer.observe(step));

  // ——— PRIX x PLACES (console pour vérif) ———
  function updatePrice() {
    const selectedSeat = Array.from(seatRadios).find(r => r.checked)?.value || 0;
    const price = parseInt(priceInput.value) || 0;
    console.log(`Total : ${selectedSeat * price} crédits`);
  }

  seatRadios.forEach(r => r.addEventListener('change', updatePrice));
  priceInput.addEventListener('input', updatePrice);
  updatePrice();

  // ——— GESTION MODALE ———
  publishBtn.addEventListener('click', function (e) {
    e.preventDefault();

    const cityA = startCity.value.trim();
    const cityB = endCity.value.trim();
    const date = departureDate.value.trim();
    const time = departureTime.value.trim();
    const msg = comment.value.trim();
    const seats = Array.from(seatRadios).find(r => r.checked)?.value || 0;
    const price = parseInt(priceInput.value) || 0;

    if (!cityA || !cityB || !date || !time || !seats || !price) {
      alert('Veuillez remplir tous les champs obligatoires.');
      return;
    }

    const [y, m, d] = date.split('-');
    const formattedDate = `${d}/${m}/${y}`;

    modalText.innerHTML = `
      <p>Vous proposez un trajet de <strong>${cityA}</strong> à <strong>${cityB}</strong>.</p>
      <p><strong>Date :</strong> ${formattedDate} à <strong>${time}</strong></p>
      <p><strong>Places :</strong> ${seats} — <strong>Prix :</strong> ${price} crédits</p>
      ${msg ? `<p><strong>Commentaire :</strong> ${msg}</p>` : ''}
      <p class="fw-bold text-success">Souhaitez-vous confirmer la publication de ce trajet ?</p>
    `;

    bsModal.show();
  });

  confirmBtn.addEventListener('click', function () {
    form.submit();
  });
});
