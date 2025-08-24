/**
 * Dashboard Finance Module - Gestion des graphiques financiers
 */

// Initialisation des graphiques au chargement de la page
document.addEventListener('DOMContentLoaded', function() {
    // Vérifier si les éléments canvas existent avant d'initialiser les graphiques
    if (document.getElementById('chartTripsByDay')) {
        initializeTripsByDayChart();
    }
    if (document.getElementById('chartCommissionsMonthly')) {
        initializeMonthlyCommissionsChart();
    }
});

function initializeTripsByDayChart() {
    // Vérifier si les données sont disponibles
    if (typeof tripsByDay === 'undefined' || !tripsByDay || tripsByDay.length === 0) {
        console.warn('Aucune donnée de trajets disponible pour le graphique');
        return;
    }

    const ctx = document.getElementById('chartTripsByDay').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: tripsByDay.map(item => item.day),
            datasets: [{
                label: 'Trajets valides',
                data: tripsByDay.map(item => parseInt(item.valid_trips) || 0),
                backgroundColor: 'rgba(40, 167, 69, 0.7)',
                borderColor: 'rgba(40, 167, 69, 1)',
                borderWidth: 1
            }, {
                label: 'Trajets annulés',
                data: tripsByDay.map(item => parseInt(item.cancelled_trips) || 0),
                backgroundColor: 'rgba(220, 53, 69, 0.7)',
                borderColor: 'rgba(220, 53, 69, 1)',
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

function initializeMonthlyCommissionsChart() {
    // Vérifier si les données sont disponibles
    if (typeof monthlyData === 'undefined' || !monthlyData || monthlyData.length === 0) {
        console.warn('Aucune donnée mensuelle disponible pour le graphique des commissions');
        return;
    }

    const ctx = document.getElementById('chartCommissionsMonthly').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: monthlyData.map(item => item.month),
            datasets: [{
                label: 'Commissions (en crédits)',
                data: monthlyData.map(item => parseFloat(item.total) || 0),
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
                        stepSize: function(context) {
                            const max = Math.max(...monthlyData.map(item => parseFloat(item.total) || 0));
                            return max > 10 ? Math.ceil(max / 10) : 1;
                        },
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
                            return `${context.dataset.label}: ${context.raw} crédits`;
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

// Fonction pour recharger les graphiques (utile pour les filtres)
function refreshFinanceCharts() {
    // Détruire les graphiques existants s'ils existent
    Chart.helpers.each(Chart.instances, function(instance) {
        if (instance.canvas.id === 'chartTripsByDay' || instance.canvas.id === 'chartCommissionsMonthly') {
            instance.destroy();
        }
    });

    // Réinitialiser les graphiques
    initializeTripsByDayChart();
    initializeMonthlyCommissionsChart();
}

// Export des fonctions pour utilisation externe si nécessaire
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        initializeTripsByDayChart,
        initializeMonthlyCommissionsChart,
        refreshFinanceCharts
    };
}