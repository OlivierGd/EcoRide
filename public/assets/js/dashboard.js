(() => {
    // === Rechercher un utilisateur ===
    const form = document.getElementById("searchUserForm");
    if (form) {
        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const input = this.query;
            const query = input.value.trim();
            if (!query) return;

            fetch("api/get_users.php?query=" + encodeURIComponent(query))
                .then((res) => res.json())
                .then((users) => {
                    const detailsDiv = document.getElementById("userDetails");
                    if (!Array.isArray(users) || users.length === 0) {
                        detailsDiv.innerHTML = `<div class="alert alert-warning">Aucun utilisateur trouvé</div>`;
                        return;
                    }

                    let html = `<table class="table table-hover"><thead><tr>
              <th>ID</th><th>Prénom</th><th>Nom</th><th>Email</th><th>Rôle</th><th>Statut</th>
            </tr></thead><tbody>`;

                    users.forEach((user) => {
                        html += `<tr class="select-user" data-id="${user.user_id}">
              <td>${user.user_id}</td>
              <td>${user.firstname}</td>
              <td>${user.lastname}</td>
              <td>${user.email}</td>
              <td>${roleToLabel(user.role)}</td>
              <td>${statusToBadge(user.status)}</td>
            </tr>`;
                    });

                    html += "</tbody></table>";
                    detailsDiv.innerHTML = html;

                    document.querySelectorAll(".select-user").forEach((row) => {
                        row.addEventListener("click", function () {
                            afficherDetailsUtilisateur(this.dataset.id);
                        });
                    });

                    input.value = "";
                });
        });
    }

    // === Afficher détails utilisateur ===
    function afficherDetailsUtilisateur(userId) {
        fetch("api/get_users_details.php?user_id=" + encodeURIComponent(userId))
            .then((res) => res.json())
            .then((user) => {
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
              <p>Rôle : ${roleToLabel(user.role)}</p>
              <p>Statut : ${statusToBadge(user.status)}</p>
              <p>Date création : ${user.created_at || ""}</p>
              <button class="btn btn-secondary mt-3" onclick="retourRecherche()">Retour</button>
            </div>
          </div>
        `;
            });
    }

    // === Helpers ===
    function roleToLabel(role) {
        role = parseInt(role);
        if (role === 0 || 1) return "Utilisateur";
        if (role === 1) return "Gestionnaire";
        if (role === 2) return "Admin";
        return "Inconnu";
    }

    function statusToBadge(status) {
        return status === "actif"
            ? '<span class="badge bg-success">Actif</span>'
            : '<span class="badge bg-secondary">Inactif</span>';
    }

    // === Récupérer les commentaires ===
    function fetchAndShowComments(filters = {}) {
        let url = "api/get_comments.php";
        const params = new URLSearchParams(filters).toString();
        if (params) url += "?" + params;

        fetch(url)
            .then((res) => res.json())
            .then((comments) => {
                const container = document.getElementById("commentsTableContainer");

                if (!Array.isArray(comments) || comments.length === 0) {
                    container.innerHTML = `<div class="alert alert-warning">Aucun commentaire trouvé</div>`;
                    return;
                }

                let html = `<table class="table table-hover"><thead><tr>
            <th>Voyage ID</th><th>Date</th><th>Voyageur</th><th>Départ</th><th>Arrivée</th>
            <th>Montant payé</th><th>Ranking</th><th>Commentaire</th><th>Chauffeur</th>
          </tr></thead><tbody>`;

                comments.forEach((c) => {
                    html += `<tr>
              <td>${c.trip_id}</td>
              <td>${c.trip_date}</td>
              <td>${c.voyager_firstname} ${c.voyager_lastname}</td>
              <td>${c.start_city}</td>
              <td>${c.end_city}</td>
              <td>${c.price_per_passenger || "-"}</td>
              <td>${c.rating} ★</td>
              <td>${c.commentaire}</td>
              <td>${c.driver_firstname} ${c.driver_lastname}</td>
            </tr>`;
                });

                html += "</tbody></table>";
                container.innerHTML = html;
            });
    }

    fetchAndShowComments();

    // === Formulaire de filtre de commentaires ===
    const commentsForm = document.getElementById("commentsFilterForm");
    if (commentsForm) {
        commentsForm.addEventListener("submit", function (e) {
            e.preventDefault();
            const filters = {
                rating: this.rating.value,
                date_min: this.date_min.value,
                date_max: this.date_max.value,
            };
            fetchAndShowComments(filters);
        });
    }

  // === Graphique commissions ===
    const ctx = document.getElementById("chartCommissions");
    if (ctx && typeof monthlyData !== "undefined") {
        new Chart(ctx, {
            type: "line",
            data: {
                labels: monthlyData.map((d) => d.month),
                datasets: [
                    {
                        label: "Crédits collectés (par mois)",
                        data: monthlyData.map((d) => parseFloat(d.total)),
                        backgroundColor: "rgba(25, 135, 84, 0.2)",
                        borderColor: "rgba(25, 135, 84, 1)",
                        borderWidth: 2,
                    },
                ],
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => value + " crédits",
                        },
                    },
                },
            },
        });
    }

  // === Graphique trajets (7 jours) ===
    (() => {
        const ctx = document.getElementById("chartTripsByDay");
        if (ctx && typeof tripsByDay !== "undefined") {
            new Chart(ctx, {
                type: "bar",
                data: {
                    labels: tripsByDay.map(d => d.day),
                    datasets: [
                        {
                            label: "Trajets actifs",
                            data: tripsByDay.map(d => parseInt(d.valid_trips)),
                            backgroundColor: "rgba(25, 135, 84, 0.7)"
                        },
                        {
                            label: "Trajets annulés",
                            data: tripsByDay.map(d => parseInt(d.cancelled_trips)),
                            backgroundColor: "rgba(220, 53, 69, 0.7)"
                        }
                    ]
                },
                options: {
                    responsive: true,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: "Nombre de trajets"
                            }
                        }
                    }
                }
            });
        }
    })();

})();
