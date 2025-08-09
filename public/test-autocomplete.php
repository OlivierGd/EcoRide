<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🧪 Test Autocomplétion Villes - EcoRide</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-4Q6Gf2aSP4eDXB8Miphtr37CMZZQ5oXLH2yaXMJ2w8e2ZtHTl7GptT4jmndRuHDT" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css">
</head>
<body>

<div class="container mt-5">
    <!-- Header de test -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="alert alert-info">
                <h2 class="alert-heading">🧪 Test Autocomplétion des Villes</h2>
                <p class="mb-0">Cette page teste uniquement l'autocomplétion des villes françaises, sans authentification.</p>
            </div>
        </div>
    </div>

    <!-- Logs de debug -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📋 Logs de test en temps réel</h5>
                </div>
                <div class="card-body">
                    <div id="debug-logs" style="font-family: monospace; font-size: 12px; max-height: 200px; overflow-y: auto; background: #f8f9fa; padding: 10px; border-radius: 5px;">
                        <div class="text-muted">Logs d'autocomplétion apparaîtront ici...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Formulaire de test -->
    <div class="row">
        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">
                <i class="bi bi-geo-alt text-success me-2"></i>Ville de départ
            </label>
            <div class="input-group bg-light rounded-3" style="position: relative;">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-geo-alt text-secondary"></i>
                </span>
                <input type="text" id="startCity" class="form-control border-0 bg-transparent"
                       placeholder="Tapez une ville (ex: Par...)" autocomplete="off">
            </div>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label fw-bold">
                <i class="bi bi-pin-map text-success me-2"></i>Ville d'arrivée
            </label>
            <div class="input-group bg-light rounded-3" style="position: relative;">
                <span class="input-group-text bg-transparent border-0">
                    <i class="bi bi-pin-map text-secondary"></i>
                </span>
                <input type="text" id="endCity" class="form-control border-0 bg-transparent"
                       placeholder="Tapez une ville (ex: Lyo...)" autocomplete="off">
            </div>
        </div>
    </div>

    <!-- Instructions de test -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-success">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">📝 Instructions de test</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6>✅ Tests à effectuer :</h6>
                            <ul class="list-unstyled">
                                <li>• Tapez <code>"Par"</code> → doit afficher <strong>Paris (75)</strong></li>
                                <li>• Tapez <code>"Lyo"</code> → doit afficher <strong>Lyon (69)</strong></li>
                                <li>• Tapez <code>"Mars"</code> → doit afficher <strong>Marseille (13)</strong></li>
                                <li>• Tapez <code>"Bord"</code> → doit afficher <strong>Bordeaux (33)</strong></li>
                            </ul>
                        </div>
                        <div class="col-md-6">
                            <h6>🔍 Comportements attendus :</h6>
                            <ul class="list-unstyled">
                                <li>• Suggestions après 2 caractères</li>
                                <li>• Maximum 8 suggestions</li>
                                <li>• Clic sur suggestion → remplit le champ</li>
                                <li>• Échap → ferme les suggestions</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Status de chargement -->
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📊 Status des fichiers</h5>
                </div>
                <div class="card-body">
                    <div class="row" id="status-files">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span id="status-cities-js" class="badge bg-secondary me-2">⏳</span>
                                <span>cities-autocomplete.js</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center">
                                <span id="status-api" class="badge bg-secondary me-2">⏳</span>
                                <span>API geo.gouv.fr</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Résultats du test -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5>📈 Résultats du test</h5>
                </div>
                <div class="card-body">
                    <div id="test-results">
                        <div class="text-muted">Commencez à taper dans les champs pour voir les résultats...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="alert alert-warning">
                <h6>🚀 Navigation</h6>
                <div class="btn-group">
                    <a href="index.php" class="btn btn-outline-primary">← Retour Accueil</a>
                    <a href="rechercher.php" class="btn btn-outline-success">Page Rechercher</a>
                    <a href="login.php" class="btn btn-outline-info">Page Login</a>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.6/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/cities-autocomplete.js"></script>

<script>
    // Script de test et monitoring
    document.addEventListener('DOMContentLoaded', function() {
        const debugLogs = document.getElementById('debug-logs');
        const statusCitiesJs = document.getElementById('status-cities-js');
        const statusApi = document.getElementById('status-api');
        const testResults = document.getElementById('test-results');

        // Logger custom
        function addLog(message, type = 'info') {
            const timestamp = new Date().toLocaleTimeString();
            const logEntry = document.createElement('div');
            logEntry.innerHTML = `<span class="text-muted">[${timestamp}]</span> <span class="text-${type === 'error' ? 'danger' : type === 'success' ? 'success' : 'primary'}">${message}</span>`;
            debugLogs.appendChild(logEntry);
            debugLogs.scrollTop = debugLogs.scrollHeight;
        }

        // Test si cities-autocomplete.js est chargé
        setTimeout(() => {
            if (typeof initializeCitiesAutocomplete === 'function') {
                statusCitiesJs.textContent = '✅';
                statusCitiesJs.className = 'badge bg-success me-2';
                addLog('cities-autocomplete.js chargé avec succès', 'success');
            } else {
                statusCitiesJs.textContent = '❌';
                statusCitiesJs.className = 'badge bg-danger me-2';
                addLog('ERREUR: cities-autocomplete.js non chargé', 'error');
            }
        }, 1000);

        // Test API
        async function testAPI() {
            try {
                const response = await fetch('https://geo.api.gouv.fr/communes?nom=Paris&fields=nom,codeDepartement&limit=1');
                if (response.ok) {
                    statusApi.textContent = '✅';
                    statusApi.className = 'badge bg-success me-2';
                    addLog('API geo.gouv.fr accessible', 'success');
                } else {
                    throw new Error('API non accessible');
                }
            } catch (error) {
                statusApi.textContent = '❌';
                statusApi.className = 'badge bg-danger me-2';
                addLog('ERREUR: API geo.gouv.fr non accessible - ' + error.message, 'error');
            }
        }

        testAPI();

        // Monitoring des champs
        const startCityInput = document.getElementById('startCity');
        const endCityInput = document.getElementById('endCity');

        let testCount = 0;
        let successCount = 0;

        function updateResults() {
            const successRate = testCount > 0 ? (successCount / testCount * 100).toFixed(1) : 0;
            testResults.innerHTML = `
            <div class="row">
                <div class="col-md-4">
                    <strong>Tests effectués:</strong> ${testCount}
                </div>
                <div class="col-md-4">
                    <strong>Succès:</strong> ${successCount}
                </div>
                <div class="col-md-4">
                    <strong>Taux de réussite:</strong> ${successRate}%
                </div>
            </div>
        `;
        }

        // Surveillance des inputs
        [startCityInput, endCityInput].forEach(input => {
            input.addEventListener('input', function() {
                const query = this.value.trim();
                if (query.length >= 2) {
                    testCount++;
                    addLog(`Recherche: "${query}" dans ${this.id}`, 'info');

                    // Vérifier si des suggestions apparaissent après 500ms
                    setTimeout(() => {
                        const suggestionBox = document.getElementById(this.id === 'startCity' ? 'startCitySuggestions' : 'endCitySuggestions');
                        if (suggestionBox && suggestionBox.style.display === 'block' && suggestionBox.children.length > 0) {
                            successCount++;
                            addLog(`✅ Suggestions trouvées pour "${query}" (${suggestionBox.children.length} résultats)`, 'success');
                        } else {
                            addLog(`❌ Aucune suggestion pour "${query}"`, 'error');
                        }
                        updateResults();
                    }, 500);
                }
            });
        });

        addLog('Page de test initialisée', 'success');
        updateResults();
    });
</script>

</body>
</html>
