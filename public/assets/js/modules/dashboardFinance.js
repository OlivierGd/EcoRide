/**
 * Module graphiques Dashboard Finance
 */

/**
 * Initialise le graphique des trajets par jour
 */
function initializeTripsByDayChart() {
    console.log('Initialisation du graphique trajets par jour');

    // Vérifier si on a des données
    if (!tripsByDay || tripsByDay.length === 0) {
        console.warn('Aucune donnée pour le graphique des trajets par jour');
        return;
    }

    // Extraire les données pour Chart.js
    const labels = tripsByDay.map(item => item.day);
    const validTripsData = tripsByDay.map(item => item.valid_trips);
    const cancelledTripsData = tripsByDay.map(item => item.cancelled_trips);

    // Créer le graphique
    const ctx = document.getElementById('chartTripsByDay').getContext('2d');
    const chart = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Trajets effectués',
                    data: validTripsData,
                    backgroundColor: 'rgba(40, 167, 69, 0.7)',
                    borderColor: 'rgba(40, 167, 69, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Trajets annulés',
                    data: cancelledTripsData,
                    backgroundColor: 'rgba(220, 53, 69, 0.7)',
                    borderColor: 'rgba(220, 53, 69, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Nombre de trajets'
                    },
                    ticks: {
                        stepSize: 1,
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Jours'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                },
                legend: {
                    position: 'top',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });
}

/**
 * Initialise le graphique des commissions mensuelles
 */
function initializeMonthlyCommissionsChart() {
    if (!monthlyData || monthlyData.length === 0) {
        console.warn('Aucune donnée mensuelle disponible');
        return;
    }

    const ctx = document.getElementById('chartCommissionsMonthly').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [{
                label: 'Commissions (en crédits)',
                data: monthlyData.map(item => item.total),
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    title: {
                        display: true,
                        text: 'Crédits gagnés'
                    },
                    ticks: {
                        precision: 0
                    }
                },
                x: {
                    title: {
                        display: true,
                        text: 'Mois'
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return `${context.dataset.label}: ${context.raw}`;
                        }
                    }
                }
            }
        }
    });
}

/**
 * Fonction d'initialisation des graphiques
 */
function initializeChartsModule() {
    // Initialiser le graphique des trajets par jour
    if (document.getElementById('chartTripsByDay')) {
        initializeTripsByDayChart();
    }
    if (document.getElementById('chartCommissionsMonthly')) {
        initializeMonthlyCommissionsChart();
    }
}

// Initialisation quand le DOM est prêt (comme dans dashboard.js existant)
document.addEventListener('DOMContentLoaded', function () {
    console.log('Initialisation des graphiques Finance');
    initializeChartsModule();
});