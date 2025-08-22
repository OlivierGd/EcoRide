/**
 * Recherche d'utilisateurs
 * Construction du tableau Bootstrap
 */

const UserSearch = {
    config: {
        minLength: 3,
        apiUrl: 'api/get_users.php',
        delay: 500,
    },

    searchTimeout: null,

    /**
     * 1. Initialisation
     */
    init() {
        console.log('[UserSearch] Initialisation du module de recherche d\'utilisateurs');

        // Récupère les éléments
        this.searchInput = document.getElementById('searchUserInput');
        this.resultsContainer = document.getElementById('userResults');
        this.tableBody = document.getElementById('userTableBody');

        // Vérification
        if (!this.searchInput) {
            console.log('[UserSearch] searchUserInput non trouvé');
        }

        // Attacher l'évènement
        this.bindSearchEvent();
        console.log('[UserSearch] prêt!');
    },

    /**
     * 2. Evenement de saisie
     */
    bindSearchEvent() {
        this.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            console.log('[UserSearch] Recherche en cours :', query);
            clearTimeout(this.searchTimeout);
            this.searchTimeout = setTimeout(() => {
                this.search(e.target.value);
            }, this.config.delay);
        });
    },
};

// Recherche AJAX
/**
 * 3. Recherche AJAX
 */
UserSearch.performSearch = async  function (query) {
    console.log('[UserSearch] Recherche AJAX en cours');

    try {
        // Afficher Loading
        this.show.loading();

        // Appel API
        const url = `${this.config.apiUrl}?query=${encodeURIComponent(query)}`;
        const  response = await fetch(url);

        if (!response.ok) {
            throw new Error(`Erreur HTTP ${response.status}`);
        }

        const users = await response.json();
        console.log('[UserSearch] Résultat:', users);

        // Afficher le résultat
        this.displayResults(users);
    } catch (error) {
        console.error('[UserSearch] Erreur :', error);
        this.show.error(error.message);
    }
}