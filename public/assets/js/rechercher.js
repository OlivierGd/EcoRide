document.addEventListener('DOMContentLoaded', function() {
    const resetBtn = document.getElementById('resetSearchForm');
    const form = document.getElementById('formSearchDestination');
    if (resetBtn && form) {
        resetBtn.addEventListener('click', function(e) {
            // Annule le submit par défaut
            e.preventDefault();
            // Recharge la page sans aucun paramètre GET
            window.location.href = window.location.pathname;
        });
    }
});
