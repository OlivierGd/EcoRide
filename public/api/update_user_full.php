<?php
// api/update_user_full.php

require_once __DIR__ . '/../vendor/autoload.php';

use Olivierguissard\EcoRide\Config\Database;
use Olivierguissard\EcoRide\Model\Users;

require_once '../functions/auth.php';
startSession();
requireAuth();

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    $currentUserRole = (int)$_SESSION['role'];
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId <= 0) {
        throw new Exception('ID utilisateur invalide');
    }

    if ($currentUserRole < 2) { // Moins que gestionnaire
        throw new Exception('Permissions insuffisantes');
    }

    $pdo = Database::getConnection();

    // Récupérer l'utilisateur actuel pour vérifier les permissions
    $sql = "SELECT role FROM users WHERE user_id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $targetUser = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$targetUser) {
        throw new Exception('Utilisateur introuvable');
    }

    $targetUserRole = (int)$targetUser['role'];

    // Vérifier si l'utilisateur connecté peut modifier cet utilisateur
    if (!Users::canModifyUser($currentUserRole, $targetUserRole)) {
        throw new Exception('Vous n\'avez pas les permissions pour modifier cet utilisateur');
    }

    // Récupérer les données du formulaire
    $firstName = trim($_POST['firstName'] ?? '');
    $lastName = trim($_POST['lastName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $newRole = (int)($_POST['role'] ?? $targetUserRole);
    $status = $_POST['status'] ?? '';

    // Validation basique
    if (empty($firstName) || empty($lastName) || empty($email)) {
        throw new Exception('Les champs prénom, nom et email sont requis');
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        throw new Exception('Email invalide');
    }

    // Vérifier si le changement de rôle est autorisé
    if ($newRole !== $targetUserRole) {
        if (!Users::canChangeUserRole($currentUserRole, $targetUserRole, $newRole)) {
            throw new Exception('Vous n\'avez pas les permissions pour changer le rôle de cet utilisateur vers ce niveau');
        }
    }

    // Commencer la transaction
    $pdo->beginTransaction();

    try {
        // Vérifier si l'email existe déjà pour un autre utilisateur
        $sql = "SELECT user_id FROM users WHERE email = ? AND user_id != ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$email, $userId]);
        if ($stmt->fetchColumn()) {
            throw new Exception('Cette adresse email est déjà utilisée par un autre utilisateur');
        }

        // Mettre à jour les informations utilisateur
        $sql = "UPDATE users SET firstname = ?, lastname = ?, email = ?, role = ?, status = ? WHERE user_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$firstName, $lastName, $email, $newRole, $status, $userId]);

        // Gestion des véhicules si autorisée
        $actions = Users::getAllowedActionsForUser($currentUserRole, $targetUserRole);
        if ($actions['can_edit_profile'] && isset($_POST['vehicles'])) {

            // Supprimer les véhicules marqués pour suppression
            if (isset($_POST['vehicles_to_delete'])) {
                $vehiclesToDelete = $_POST['vehicles_to_delete'];
                if (is_array($vehiclesToDelete)) {
                    $placeholders = str_repeat('?,', count($vehiclesToDelete) - 1) . '?';
                    $sql = "DELETE FROM vehicles WHERE id IN ($placeholders) AND user_id = ?";
                    $stmt = $pdo->prepare($sql);
                    $stmt->execute(array_merge($vehiclesToDelete, [$userId]));
                }
            }

            // Traiter les véhicules
            $vehicles = $_POST['vehicles'];
            if (is_array($vehicles)) {
                foreach ($vehicles as $vehicle) {
                    $vehicleId = (int)($vehicle['id'] ?? 0);
                    $marque = trim($vehicle['marque'] ?? '');
                    $modele = trim($vehicle['modele'] ?? '');
                    $carburant = trim($vehicle['carburant'] ?? '');
                    $immatriculation = trim($vehicle['immatriculation'] ?? '');
                    $places = (int)($vehicle['places'] ?? 4);

                    // Validation des données véhicule
                    if (empty($marque) || empty($modele) || empty($carburant) || empty($immatriculation)) {
                        continue; // Ignorer les véhicules incomplets
                    }

                    if ($vehicleId > 0) {
                        // Mettre à jour véhicule existant
                        $sql = "UPDATE vehicles SET marque = ?, modele = ?, carburant = ?, immatriculation = ?, places = ? 
                                WHERE id = ? AND user_id = ?";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$marque, $modele, $carburant, $immatriculation, $places, $vehicleId, $userId]);
                    } else {
                        // Créer nouveau véhicule
                        $sql = "INSERT INTO vehicule (id_conducteur, marque, modele, type_carburant, plaque_immatriculation, nbr_places) 
                                VALUES (?, ?, ?, ?, ?, ?)";
                        $stmt = $pdo->prepare($sql);
                        $stmt->execute([$userId, $marque, $modele, $carburant, $immatriculation, $places]);
                    }
                }
            }
        }

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Utilisateur mis à jour avec succès'
        ]);

    } catch (Exception $e) {
        $pdo->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}