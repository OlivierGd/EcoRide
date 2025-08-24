<?php

?>
<section id="comments" class="mb-5">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="text-success">Commentaires et avis des utilisateurs</h2>
            </div>

            <!-- Filtres -->
            <div class="card mb-4">
                <div class="card-body">
                    <h6 class="card-title mb-3">
                        <i class="bi bi-funnel"></i> Filtrer les commentaires
                    </h6>

                    <form id="commentsFilterForm" class="row g-3 align-items-end">
                        <!-- Filtre par statut -->
                        <div class="col-md-2">
                            <label for="statusFilter" class="form-label">Statut</label>
                            <select id="statusFilter" name="comment_status" class="form-select form-select-sm">
                                <option value="">Tous</option>
                                <option value="approved">Approuvé</option>
                                <option value="pending" selected>En attente</option>
                                <option value="rejected">Rejeté</option>
                            </select>
                        </div>

                        <!-- Filtre par ranking/note -->
                        <div class="col-md-2">
                            <label for="ratingFilter" class="form-label">Note minimum</label>
                            <select id="ratingFilter" name="rating" class="form-select form-select-sm">
                                <option value="">Toutes</option>
                                <option value="5">5 ★ exactement</option>
                                <option value="4">4 ★ et plus</option>
                                <option value="3">3 ★ et plus</option>
                                <option value="2">2 ★ et plus</option>
                                <option value="1">1 ★ et plus</option>
                            </select>
                        </div>

                        <!-- Filtre par période prédéfinie -->
                        <div class="col-md-2">
                            <label for="periodFilter" class="form-label">Période</label>
                            <select id="periodFilter" name="period_preset" class="form-select form-select-sm">
                                <option value="">Personnalisé</option>
                                <option value="today">Aujourd'hui</option>
                                <option value="yesterday">Hier</option>
                                <option value="last_7_days">7 derniers jours</option>
                                <option value="last_30_days">30 derniers jours</option>
                                <option value="this_month">Ce mois-ci</option>
                                <option value="last_month">Mois dernier</option>
                                <option value="this_year">Cette année</option>
                            </select>
                        </div>

                        <!-- Date de début -->
                        <div class="col-md-2">
                            <label for="dateStart" class="form-label">Du</label>
                            <input type="date" id="dateStart" name="date_start" class="form-control form-control-sm">
                        </div>

                        <!-- Date de fin -->
                        <div class="col-md-2">
                            <label for="dateEnd" class="form-label">Au</label>
                            <input type="date" id="dateEnd" name="date_end" class="form-control form-control-sm">
                        </div>

                        <!-- Boutons d'action -->
                        <div class="col-md-2">
                            <div class="d-flex gap-1">
                                <button type="submit" class="btn btn-primary btn-sm flex-fill">
                                    <i class="bi bi-search"></i> Filtrer
                                </button>
                                <button type="button" id="resetFiltersBtn" class="btn btn-outline-secondary btn-sm">
                                    <i class="bi bi-arrow-clockwise"></i>
                                </button>
                            </div>
                        </div>
                    </form>

                    <!-- Indicateur de filtres actifs -->
                    <div id="activeFiltersIndicator" class="mt-2" style="display: none;">
                        <small class="text-muted">
                            <i class="bi bi-funnel-fill text-primary"></i>
                            <span id="activeFiltersText">Filtres actifs</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-2" id="clearAllFilters">
                                Supprimer tous les filtres
                            </button>
                        </small>
                    </div>
                </div>
            </div>

            <!-- Tableau des commentaires -->
            <div class="card">
                <div class="card-body p-0">
                    <div id="commentsTableContainer">
                        <!-- Chargement initial -->
                        <div class="text-center p-4">
                            <div class="spinner-border text-success" role="status">
                                <span class="visually-hidden">Chargement...</span>
                            </div>
                            <p class="mt-2 text-muted">Chargement des commentaires...</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="mt-3">
                <small class="text-muted">
                    <i class="bi bi-info-circle"></i>
                    Cliquez sur l'ID du trajet pour voir tous les détails du voyage.
                    Les dates affichées correspondent au départ du trajet et à la date du commentaire.
                </small>
            </div>
        </section>
