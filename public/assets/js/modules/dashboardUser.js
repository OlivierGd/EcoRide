/**
 * Recherche d'utilisateurs
 * Construction du tableau Bootstrap
 * Version adaptée aux API existantes du projet
 */

const UserSearch = {
    config: {
        minLength: 3,
        delay: 500,
    },

    searchTimeout: null,
    currentUserRole: null,
    editingUserId: null,
    explicitSearchTriggered: false, // Flag pour savoir si une recherche a été explicitement déclenchée

    /**
     * 1. Initialisation
     */
    init() {
        console.log('[UserSearch] Initialisation du module de recherche d\'utilisateurs');

        // Récupère les éléments de recherche
        this.searchInput = document.getElementById('searchUserInput');
        this.resultsContainer = document.getElementById('userResults');
        this.tableBody = document.getElementById('userTableBody');
        this.searchButton = document.getElementById('searchButton');
        this.resetButton = document.getElementById('resetFilters');
        this.roleFilter = document.getElementById('roleFilter');
        this.statusFilter = document.getElementById('statusFilter');

        // Vérification des éléments principaux
        if (!this.searchInput) {
            console.log('[UserSearch] searchUserInput non trouvé');
            return;
        }

        if (!this.tableBody) {
            console.log('[UserSearch] userTableBody non trouvé');
            return;
        }

        // Initialiser les modals
        this.initModals();

        // Attacher les événements
        this.bindEvents();

        // Charger le rôle utilisateur
        this.loadCurrentUserRole();

        console.log('[UserSearch] prêt!');
    },

    /**
     * Initialisation des modals
     */
    initModals() {
        // Modal de création
        this.createModal = {
            element: document.getElementById('createUserModal'),
            form: document.getElementById('createUserForm'),
            firstName: document.getElementById('firstName'),
            lastName: document.getElementById('lastName'),
            email: document.getElementById('email'),
            role: document.getElementById('role'),
            status: document.getElementById('status')
        };

        // Modal d'édition (correspond aux IDs du HTML)
        this.editModal = {
            element: document.getElementById('userModal'),
            form: document.getElementById('editUserForm'),
            firstName: document.getElementById('editFirstName'),
            lastName: document.getElementById('editLastName'),
            email: document.getElementById('editEmail'),
            role: document.getElementById('editRole'),
            status: document.getElementById('editStatus'),
            userId: document.getElementById('editUserId')
        };

        console.log('[UserSearch] Modals initialisées');
    },

    /**
     * 2. Attacher tous les événements
     */
    bindEvents() {
        // Recherche en temps réel
        if (this.searchInput) {
            this.searchInput.addEventListener('input', (e) => {
                const query = e.target.value.trim();
                console.log('[UserSearch] Recherche en cours :', query);
                clearTimeout(this.searchTimeout);

                this.searchTimeout = setTimeout(() => {
                    this.performSearch(query);
                }, this.config.delay);
            });
        }

        // Bouton de recherche
        if (this.searchButton) {
            this.searchButton.addEventListener('click', () => {
                this.explicitSearchTriggered = true; // Marquer comme recherche explicite
                this.performFilterSearch();
            });
        }

        // Bouton reset
        if (this.resetButton) {
            this.resetButton.addEventListener('click', () => {
                this.resetFilters();
            });
        }

        // Filtres - déclencher la recherche même sans texte
        if (this.roleFilter) {
            this.roleFilter.addEventListener('change', () => {
                this.explicitSearchTriggered = true; // Marquer comme recherche explicite
                this.performFilterSearch();
            });
        }

        if (this.statusFilter) {
            this.statusFilter.addEventListener('change', () => {
                this.explicitSearchTriggered = true; // Marquer comme recherche explicite
                this.performFilterSearch();
            });
        }

        // Événements des modals
        this.bindModalEvents();
    },

    /**
     * Événements des modals
     */
    bindModalEvents() {
        // Formulaire de création
        if (this.createModal.form) {
            this.createModal.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleUserCreate();
            });
        }

        // Formulaire d'édition
        if (this.editModal.form) {
            this.editModal.form.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleUserEdit();
            });
        }
    },

    /**
     * 3. Recherche AJAX avec filtres - UTILISE L'API get_users.php
     */
    async performSearch(query, isExplicitSearch = false) {
        console.log('[UserSearch] Recherche AJAX en cours');

        // Vérifier si on a au moins une query ou un filtre actif
        const hasQuery = query && query.length >= this.config.minLength;
        const hasRoleFilter = this.roleFilter && this.roleFilter.value;
        const hasStatusFilter = this.statusFilter && this.statusFilter.value;

        // Permettre la recherche si elle a été explicitement déclenchée (bouton rechercher)
        // ou si on a des critères valides
        if (!hasQuery && !hasRoleFilter && !hasStatusFilter && !isExplicitSearch && !this.explicitSearchTriggered) {
            console.log('[UserSearch] Aucun critère de recherche suffisant');
            this.showEmpty();
            return;
        }

        try {
            // Afficher Loading
            this.showLoading();

            // Construire l'URL avec les filtres
            const params = new URLSearchParams();

            // Ajouter la query seulement si elle est suffisamment longue
            if (hasQuery) {
                params.append('query', query);
            }

            if (hasRoleFilter) {
                params.append('role', this.roleFilter.value);
            }

            if (hasStatusFilter) {
                params.append('status', this.statusFilter.value);
            }

            // Si aucun paramètre mais recherche explicite, on récupère tous les utilisateurs
            // En ne passant aucun paramètre à l'API
            const url = `api/get_users.php?${params.toString()}`;
            const response = await fetch(url);

            if (!response.ok) {
                throw new Error(`Erreur HTTP ${response.status}`);
            }

            const result = await response.json();
            console.log('[UserSearch] Résultat:', result);

            // L'API existante peut retourner soit un array directement, soit un objet avec erreur
            if (result.error) {
                throw new Error(result.error);
            }

            const users = Array.isArray(result) ? result : [];

            // Afficher le résultat
            this.displayResults(users);
        } catch (error) {
            console.error('[UserSearch] Erreur :', error);
            this.showError(error.message);
        }
    },

    /**
     * Recherche déclenchée par les filtres (même sans texte de recherche)
     */
    async performFilterSearch() {
        const query = this.searchInput ? this.searchInput.value.trim() : '';
        this.explicitSearchTriggered = true; // Marquer comme recherche explicite
        await this.performSearch(query, true);
    },

    /**
     * Reset des filtres
     */
    resetFilters() {
        if (this.searchInput) this.searchInput.value = '';
        if (this.roleFilter) this.roleFilter.value = '';
        if (this.statusFilter) this.statusFilter.value = '';
        this.explicitSearchTriggered = false; // Reset du flag
        this.hideResults();
    },

    /**
     * Affichage des résultats
     */
    displayResults(users) {
        console.log(`[UserSearch] Affichage ${users.length} utilisateurs`);

        // Vérifie si on a des données
        if (!Array.isArray(users) || users.length === 0) {
            this.showEmpty();
            return;
        }

        // Construire le html du tableau
        let tableHTML = '';
        users.forEach(user => {
            tableHTML += this.buildUserRow(user);
        });

        // Injecte dans le tbody
        this.tableBody.innerHTML = tableHTML;

        // Afficher le tableau
        this.showResults();
        console.log(`[UserSearch] ${users.length} utilisateurs affichés`);
    },

    /**
     * Construction d'une ligne utilisateur
     */
    buildUserRow(user) {
        const firstname = this.escapeHtml(user.firstname || '');
        const lastname = this.escapeHtml(user.lastname || '');
        const email = this.escapeHtml(user.email || '');

        // Formatage des données
        const fullName = `${firstname} ${lastname}`.trim();
        const roleLabel = this.getRoleLabel(user.role);
        const statusBadge = this.getStatusBadge(user.status);
        const credits = user.credits || 0;
        const ranking = this.getRankingStars(user.ranking || 0);
        const createdDate = this.formatDate(user.created_at);
        const actionButtons = this.getActionButtons(user);

        return `
            <tr>
                <td>
                    <div class="d-flex align-items-center">
                        <div class="avatar-sm bg-light rounded-circle d-flex align-items-center justify-content-center me-2">
                            <i class="bi bi-person"></i>
                        </div>
                        <div>
                            <div class="fw-medium">${fullName}</div>
                            <small class="text-muted">${email}</small>
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-secondary">${roleLabel}</span>
                </td>
                <td>${statusBadge}</td>
                <td>
                    <span class="fw-medium">${credits}</span>
                    <small class="text-muted">crédits</small>
                </td>
                <td>
                    <div class="d-flex align-items-center">
                        ${ranking}
                        <small class="text-muted ms-1">(${user.ranking || 0}/5)</small>
                    </div>
                </td>
                <td>
                    <small class="text-muted">${createdDate}</small>
                </td>
                <td>
                    <div class="btn-group btn-group-sm">
                        ${actionButtons}
                    </div>
                </td>
            </tr>
        `;
    },

    // ===== FONCTIONS DE FORMATAGE =====

    /**
     * Badge de statut avec couleurs
     */
    getStatusBadge(status) {
        const badges = {
            'actif': '<span class="badge bg-success"><i class="bi bi-check-circle me-1"></i>Actif</span>',
            'inactif': '<span class="badge bg-warning"><i class="bi bi-pause-circle me-1"></i>Inactif</span>',
            'suspendu': '<span class="badge bg-danger"><i class="bi bi-x-circle me-1"></i>Suspendu</span>'
        };

        return badges[status] || '<span class="badge bg-secondary">Inconnu</span>';
    },

    /**
     * Étoiles de notation avec icônes Bootstrap
     */
    getRankingStars(rating) {
        let stars = '';
        for (let i = 1; i <= 5; i++) {
            if (i <= rating) {
                stars += '<i class="bi bi-star-fill text-warning"></i>';
            } else {
                stars += '<i class="bi bi-star text-muted"></i>';
            }
        }
        return stars;
    },

    /**
     * Boutons d'action Bootstrap
     */
    getActionButtons(user) {
        const permissions = user.permissions || {};
        let buttons = '';

        // Bouton modifier (si autorisé)
        if (permissions.can_edit_profile) {
            buttons += `
                <button class="btn btn-outline-primary btn-sm" 
                        onclick="UserSearch.openEditUserModal(${user.user_id})" 
                        title="Modifier">
                    <i class="bi bi-pencil"></i>
                </button>
            `;
        }

        // Bouton reset password (si autorisé)
        if (permissions.can_reset_password) {
            buttons += `
                <button class="btn btn-outline-warning btn-sm" 
                        onclick="UserSearch.resetPassword(${user.user_id}, '${this.escapeHtml(user.firstname)} ${this.escapeHtml(user.lastname)}')" 
                        title="Reset mot de passe">
                    <i class="bi bi-key"></i>
                </button>
            `;
        }

        return buttons;
    },

    /**
     * Libellés de rôles
     */
    getRoleLabel(role) {
        const roles = {
            0: 'Passager',
            1: 'Passager / Chauffeur',
            2: 'Gestionnaire',
            3: 'Administrateur'
        };
        return roles[role] || 'Inconnu';
    },

    /**
     * Formatage de date simple
     */
    formatDate(dateString) {
        if (!dateString) return 'Non défini';

        try {
            const date = new Date(dateString);
            return date.toLocaleDateString('fr-FR', {
                day: '2-digit',
                month: '2-digit',
                year: 'numeric'
            });
        } catch {
            return 'Date invalide';
        }
    },

    /**
     * Protection XSS
     */
    escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    },

    // ===== GESTION DES ÉTATS =====

    /**
     * Afficher loading avec spinner Bootstrap
     */
    showLoading() {
        this.tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="d-flex align-items-center justify-content-center">
                    <div class="spinner-border spinner-border-sm text-primary me-2"></div>
                    <span>Recherche en cours...</span>
                </div>
            </td>
        </tr>
    `;
        this.showResults();
    },

    /**
     * Afficher un message de succès avec un Toast Bootstrap
     */
    showSuccessMessage(message) {
        console.log('[UserSearch] Message de succès:', message);

        const toastContainer = document.querySelector('.toast-container');
        if (!toastContainer) {
            console.error('Conteneur de toast non trouvé !');
            alert(message); // Solution de repli
            return;
        }

        // Créer l'élément Toast
        const toastEl = document.createElement('div');
        toastEl.classList.add('toast', 'align-items-center', 'text-bg-success', 'border-0');
        toastEl.setAttribute('role', 'alert');
        toastEl.setAttribute('aria-live', 'assertive');
        toastEl.setAttribute('aria-atomic', 'true');

        toastEl.innerHTML = `
            <div class="d-flex">
                <div class="toast-body">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    ${this.escapeHtml(message)}
                </div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        `;

        // Ajouter le toast au conteneur
        toastContainer.appendChild(toastEl);

        // Initialiser et afficher le toast
        const toast = new bootstrap.Toast(toastEl, {
            delay: 5000 // Le toast disparaîtra après 5 secondes
        });
        toast.show();

        // Nettoyer le DOM après la disparition du toast
        toastEl.addEventListener('hidden.bs.toast', () => {
            toastEl.remove();
        });
    },

    /**
     * Afficher message vide avec icône Bootstrap
     */
    showEmpty() {
        const hasFilters = (this.roleFilter && this.roleFilter.value) || (this.statusFilter && this.statusFilter.value);
        const hasQuery = this.searchInput && this.searchInput.value.trim().length >= this.config.minLength;

        let message;
        if (this.explicitSearchTriggered && !hasFilters && !hasQuery) {
            message = 'Cliquez sur "Rechercher" pour voir tous les utilisateurs ou utilisez les filtres';
        } else if (hasFilters) {
            message = 'Aucun utilisateur ne correspond aux filtres sélectionnés';
        } else {
            message = 'Saisissez au moins 3 caractères ou utilisez les filtres';
        }

        this.tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-5">
                <div class="text-muted">
                    <i class="bi bi-search" style="font-size: 2rem;"></i>
                    <div class="mt-2">Aucun utilisateur trouvé</div>
                    <small>${message}</small>
                </div>
            </td>
        </tr>
    `;
        this.showResults();
    },

    /**
     * Afficher erreur avec alerte Bootstrap
     */
    showError(message) {
        this.tableBody.innerHTML = `
        <tr>
            <td colspan="7" class="text-center py-4">
                <div class="alert alert-danger d-inline-flex align-items-center">
                    <i class="bi bi-exclamation-triangle me-2"></i>
                    ${message}
                </div>
            </td>
        </tr>
    `;
        this.showResults();
    },

    /**
     * Afficher/cacher la zone de résultats
     */
    showResults() {
        if (this.resultsContainer) {
            this.resultsContainer.style.display = 'block';
        }
    },

    hideResults() {
        if (this.resultsContainer) {
            this.resultsContainer.style.display = 'none';
        }
    },

    // ===== GESTION DES MODALS =====

    /**
     * Charger le rôle de l'utilisateur connecté : API get_allowed_roles.php
     */
    async loadCurrentUserRole() {
        try {
            const response = await fetch('api/get_allowed_roles.php');
            const data = await response.json();

            if (data.success) {
                this.currentUserRole = parseInt(data.current_user_role);
                console.log('[UserSearch] Rôle utilisateur connecté:', this.currentUserRole);

                // Charger les rôles autorisés pour la création
                this.allowedRoles = data.allowed_roles || {};
            } else {
                console.error('[UserSearch] Erreur chargement rôle:', data.message);
                this.currentUserRole = 0; // Par défaut, permissions minimales
            }
        } catch (error) {
            console.error('[UserSearch] Erreur API rôle:', error);
            this.currentUserRole = 0;
        }
    },

    /**
     * Ouvrir la modal pour créer un utilisateur
     */
    openCreateUserModal() {
        console.log('[UserSearch] Ouverture modal création utilisateur');

        if (!this.createModal.element) {
            console.error('Modal de création non disponible');
            return;
        }
        // Vide le contener lorsque la modale est ouverte
        const errorContainer = document.getElementById('createUserErrorContainer');
        if (errorContainer) {
            errorContainer.innerHTML = ''; // Vider les erreurs précédentes
        }

        // Réinitialiser le formulaire
        this.resetForm(this.createModal);

        // Configurer les options de rôle selon les permissions
        this.setupRoleOptions(this.createModal.role);

        // Ouvrir la modal
        const modalInstance = new bootstrap.Modal(this.createModal.element);
        modalInstance.show();
    },

    /**
     * Ouvrir la modal pour éditer un utilisateur : API get_user_full_details.php
     */
    async openEditUserModal(userId) {
        console.log('[UserSearch] Ouverture modal édition utilisateur:', userId);

        if (!this.editModal.element) {
            console.error('Modal d\'édition non disponible');
            return;
        }

        try {
            // Charger les données de l'utilisateur
            const response = await fetch(`api/get_user_full_details.php?id=${userId}`);
            const data = await response.json();

            if (data.error) {
                throw new Error(data.error);
            }

            const user = data;
            this.editingUserId = userId;

            // Remplir le formulaire d'édition
            if (this.editModal.userId) this.editModal.userId.value = userId;
            if (this.editModal.firstName) this.editModal.firstName.value = user.firstname || '';
            if (this.editModal.lastName) this.editModal.lastName.value = user.lastname || '';
            if (this.editModal.email) this.editModal.email.value = user.email || '';
            if (this.editModal.status) this.editModal.status.value = user.status || 'actif';
            if (this.editModal.role) this.editModal.role.value = user.role || '';

            // Ouvrir la modal
            const modalInstance = new bootstrap.Modal(this.editModal.element);
            modalInstance.show();

        } catch (error) {
            console.error('[UserSearch] Erreur chargement utilisateur:', error);
            console.error('Erreur lors du chargement des données utilisateur:', error.message);
        }
    },

    /**
     * Configurer les options de rôle
     */
    setupRoleOptions(roleSelect, currentRole = null) {
        if (!roleSelect) return;

        // Vider les options existantes
        roleSelect.innerHTML = '';

        // Ajouter une option par défaut si création
        if (currentRole === null) {
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Sélectionner un rôle';
            roleSelect.appendChild(defaultOption);
        }

        // Utiliser les rôles autorisés chargés depuis l'API
        if (this.allowedRoles) {
            Object.entries(this.allowedRoles).forEach(([roleValue, roleLabel]) => {
                const option = document.createElement('option');
                option.value = roleValue;
                option.textContent = roleLabel;

                // Sélectionner le rôle actuel si en édition
                if (currentRole !== null && roleValue == currentRole) {
                    option.selected = true;
                }

                roleSelect.appendChild(option);
            });
        }

        console.log('[UserSearch] Options de rôle configurées');
    },

    /**
     * Réinitialiser un formulaire
     */
    resetForm(modal) {
        if (modal.form) {
            modal.form.reset();
        }

        // Valeurs par défaut
        if (modal.status) modal.status.value = 'actif';

        // Supprimer les classes d'erreur
        const fields = ['firstName', 'lastName', 'email', 'role'];
        fields.forEach(field => {
            if (modal[field]) {
                modal[field].classList.remove('is-invalid');

                // Supprimer le message d'erreur
                const errorDiv = modal[field].nextElementSibling;
                if (errorDiv && errorDiv.classList.contains('invalid-feedback')) {
                    errorDiv.remove();
                }
            }
        });
    },

    /**
     * Gérer la création d'utilisateur : API admin_create_user.php
     */
    async handleUserCreate() {
        console.log('[UserSearch] Traitement création utilisateur');

        const formData = new FormData(this.createModal.form);

        try {
            const submitBtn = this.createModal.form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // État de chargement
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Création...';

            // Appel API EXISTANTE
            const response = await fetch('api/admin_create_user.php', {
                method: 'POST',
                body: formData
            });

            console.log('[UserSearch] Status de la réponse:', response.status);

            // Lire et parser la réponse JSON
            const result = await response.json();
            console.log('[UserSearch] Réponse JSON:', result);

            if (result.success) {
                // Fermer la modal
                const modalInstance = bootstrap.Modal.getInstance(this.createModal.element);
                if (modalInstance) {
                    modalInstance.hide();
                }

                // Afficher un message de succès
                this.showSuccessMessage(result.message || 'Utilisateur créé avec succès');

                // Rafraîchir si nécessaire
                this.refreshSearchIfNeeded();

                // Réinitialiser le formulaire
                this.createModal.form.reset();

            } else {
                throw new Error(result.message || 'Erreur lors de la création');
            }

            // Restaurer le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

        } catch (error) {
            console.error('[UserSearch] Erreur création:', error);

            // Restaurer le bouton en cas d'erreur
            const submitBtn = this.createModal.form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = '<i class="bi bi-person-plus"></i> Créer et envoyer l\'email';
            }
            // Gestion d'erreur dans la modale
            const errorContainer = document.getElementById('createUserErrorContainer');
            if (errorContainer) {
                errorContainer.innerHTML = `
                <div class="alert alert-danger d-flex align-items-center" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <div>
                        ${this.escapeHtml(error.message)}
                    </div>
                </div>
                `;
            } else {
                // Solution si le conteneur n'est pas trouvé
                alert('Erreur : ' + error.message);
            }
        }
    },


    /**
     * Gérer l'édition d'utilisateur : API update_user.php
     */
    async handleUserEdit() {
        console.log('[UserSearch] Traitement modification utilisateur');

        const formData = new FormData(this.editModal.form);

        try {
            const submitBtn = this.editModal.form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;

            // État de chargement
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Modification...';

            // Appel API EXISTANTE
            const response = await fetch('api/update_user.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.success) {
                // Fermer la modal
                const modalInstance = bootstrap.Modal.getInstance(this.editModal.element);
                modalInstance.hide();

                // Rafraîchir avec un petit délai pour s'assurer que l'API est à jour
                setTimeout(() => {
                    this.refreshSearchIfNeeded();
                }, 300);

            } else {
                throw new Error(result.message || 'Erreur lors de la modification');
            }

            // Restaurer le bouton
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalText;

        } catch (error) {
            console.error('[UserSearch] Erreur modification:', error);

            // Restaurer le bouton en cas d'erreur
            const submitBtn = this.editModal.form.querySelector('button[type="submit"]');
            if (submitBtn) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = 'Sauvegarder';
            }

            // Afficher l'erreur dans la modal ou dans la console
            console.error('Erreur lors de la modification:', error.message);
        }
    },

    /**
     * Reset password
     */
    resetPassword(userId, userName) {
        console.log('Reset password:', userId, userName);
        if (confirm(`Réinitialiser le mot de passe de ${userName} ?`)) {
            // TODO: Implémentation reset password
            // Note: Il faudrait créer une API pour cela ou utiliser reset_password.php existant
            console.log(`Fonctionnalité de reset password à implémenter pour: ${userName}`);
        }
    },

    /**
     * Rafraîchir la recherche si des résultats sont affichés
     */
    refreshSearchIfNeeded() {
        if (this.resultsContainer && this.resultsContainer.style.display !== 'none') {
            // Utiliser la même logique que performFilterSearch pour rafraîchir
            console.log('[UserSearch] Rafraîchissement des résultats...');
            this.performFilterSearch();
        }
    }
};

// ===== FONCTIONS GLOBALES POUR COMPATIBILITÉ =====

/**
 * Fonctions appelées depuis les boutons (pour compatibilité)
 */
function editUser(userId) {
    console.log('[Global] Édition utilisateur:', userId);
    UserSearch.openEditUserModal(userId);
}

function resetPassword(userId, userName) {
    UserSearch.resetPassword(userId, userName);
}

function createUser() {
    console.log('[Global] Création utilisateur');
    UserSearch.openCreateUserModal();
}

// ===== INITIALISATION =====

document.addEventListener('DOMContentLoaded', function() {
    console.log('[UserSearch] DOM prêt');
    UserSearch.init();
});