(() => {
  // public/assets/js/rechercher.js
  (() => {
    (() => {
      (() => {
        (() => {
          document.addEventListener("DOMContentLoaded", function() {
            const resetBtn = document.getElementById("resetSearchForm");
            const form = document.getElementById("formSearchDestination");
            if (resetBtn && form) {
              resetBtn.addEventListener("click", function(e) {
                e.preventDefault();
                window.location.href = window.location.pathname;
              });
            }
          });
        })();
      })();
    })();
  })();
})();
