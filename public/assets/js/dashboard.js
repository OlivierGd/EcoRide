(() => {
  // public/assets/js/dashboard.js
  (() => {
    (() => {
      (() => {
        (() => {
          document.getElementById("searchUserForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const input = this.query;
            const query = this.query.value.trim();
            if (!query)
              return;
            fetch("api/get_users.php?query=" + encodeURIComponent(query)).then((res) => res.json()).then((users) => {
              const detailsDiv = document.getElementById("userDetails");
              if (!Array.isArray(users) || users.length === 0) {
                detailsDiv.innerHTML = `<div class="alert alert-warning">Aucun utilisateur trouv\xE9</div>`;
                return;
              }
              let html = `
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>ID</th>
                      <th>Pr\xE9nom</th>
                      <th>Nom</th>
                      <th>Email</th>
                      <th>R\xF4le</th>
                      <th>Statut</th>
                    </tr>
                  </thead>
                  <tbody>
            `;
              users.forEach((user) => {
                html += `
                  <tr class="select-user" data-id="${user.user_id}">
                    <td>${user.user_id}</td>
                    <td>${user.firstname}</td>
                    <td>${user.lastname}</td>
                    <td>${user.email}</td>
                    <td>${roleToLabel(user.role)}</td>
                    <td>${statusToBadge(user.status)}</td>
                  </tr>
                `;
              });
              html += "</tbody></table>";
              detailsDiv.innerHTML = html;
              document.querySelectorAll(".select-user").forEach((row) => {
                row.addEventListener("click", function() {
                  const userId = this.dataset.id;
                  afficherDetailsUtilisateur(userId);
                });
              });
              input.value = "";
            });
          });
          function afficherDetailsUtilisateur(userId) {
            fetch("api/get_users_details.php?user_id=" + encodeURIComponent(userId)).then((res) => res.json()).then((user) => {
              const detailsDiv = document.getElementById("userDetails");
              if (user.error) {
                detailsDiv.innerHTML = `<div class="alert alert-danger">${user.error}</div>`;
                return;
              }
              detailsDiv.innerHTML = `
              <div class="card">
                <div class="card-body">
                  <h5 class="card-title">${user.firstname} ${user.lastname} (#${user.user_id})</h5>
                  <p>Email : ${user.email}</p>
                  <p>R\xF4le : ${roleToLabel(user.role)}</p>
                  <p>Statut : ${statusToBadge(user.status)}</p>
                  <p>Date cr\xE9ation : ${user.created_at || ""}</p>
                  <button class="btn btn-secondary mt-3" onclick="retourRecherche()">Retour</button>
                </div>
              </div>
            `;
            });
          }
          function roleToLabel(role) {
            role = parseInt(role);
            if (role === 0)
              return "Utilisateur";
            if (role === 1)
              return "Gestionnaire";
            if (role === 2)
              return "Admin";
            return "Inconnu";
          }
          function statusToBadge(status) {
            return status === "actif" ? '<span class="badge bg-success">Actif</span>' : '<span class="badge bg-secondary">Inactif</span>';
          }
          function fetchAndShowComments(filters = {}) {
            let url = "api/get_comments.php";
            const params = new URLSearchParams(filters).toString();
            if (params)
              url += "?" + params;
            fetch(url).then((res) => res.json()).then((comments) => {
              console.log(comments);
              const container = document.getElementById("commentsTableContainer");
              if (!Array.isArray(comments) || comments.length === 0) {
                container.innerHTML = `<div class="alert alert-warning">Aucun commentaire trouv\xE9</div>`;
                return;
              }
              let html = `
          <table class="table table-hover">
            <thead>
              <tr>
                <th>Voyage ID</th>
                <th>Date</th>
                <th>Voyageur</th>
                <th>D\xE9part</th>
                <th>Arriv\xE9e</th>
                <th>Montant pay\xE9</th>
                <th>Ranking</th>
                <th>Commentaire</th>
                <th>Chauffeur</th>
              </tr>
            </thead>
            <tbody>
        `;
              comments.forEach((c) => {
                html += `
            <tr>
              <td>${c.trip_id}</td>
              <td>${c.trip_date}</td>
              <td>${c.voyager_firstname} ${c.voyager_lastname}</td>
              <td>${c.start_city}</td>
              <td>${c.end_city}</td>
              <td>${c.price_per_passenger || "-"}</td>
              <td>${c.rating} \u2605</td>
              <td>${c.commentaire}</td>
              <td>${c.driver_firstname} ${c.driver_lastname}</td>
            </tr>
          `;
              });
              html += "</tbody></table>";
              container.innerHTML = html;
            });
          }
          fetchAndShowComments();
          document.getElementById("commentsFilterForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const filters = {
              rating: this.rating.value,
              date_min: this.date_min.value,
              date_max: this.date_max.value
            };
            fetchAndShowComments(filters);
          });
        })();
      })();
    })();
  })();
})();
