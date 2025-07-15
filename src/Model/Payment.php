<?php

namespace Olivierguissard\EcoRide\Model;

use Olivierguissard\EcoRide\Config\Database;
use PDO;
use PDOException;

class Payment
{
    private ?int $paymentId;
    private int $userId;
    private int $tripId;
    private int $bookingId;
    private string $typeTransaction;        // ex : reservation, credit_chauffeur, commission, remboursement
    private float $montant;
    private ?string $description;
    private string $statutTransaction;      // reserve, debite, credite, annule, rembourse
    private float $commissionPlateforme;
    private string $dateTransaction;

    public function __construct(array $data = [])
    {
        $this->paymentId            = $data['payment_id'] ?? null;
        $this->userId               = (int)($data['user_id'] ?? 0);
        $this->tripId               = (int)($data['trip_id'] ?? 0);
        $this->bookingId            = (int)($data['booking_id'] ?? 0);
        $this->typeTransaction      = $data['type_transaction'] ?? '';
        $this->montant              = (float)($data['montant'] ?? 0);
        $this->description          = $data['description'] ?? null;
        $this->statutTransaction    = $data['statut_transaction'] ?? 'reserve';
        $this->commissionPlateforme = (float)($data['commission_plateforme'] ?? 0);
        $this->dateTransaction      = $data['date_transaction'] ?? date('Y-m-d H:i:s');
    }

    /** Enregistre le paiement en BDD (retourne payment_id si succès, sinon false) */
    public function savePaymentToDatabase(): int|false
    {
        $pdo = Database::getConnection();
        try {
            $sql = "INSERT INTO payments 
                (user_id, trip_id, booking_id, type_transaction, montant, description, date_transaction, statut_transaction, commission_plateforme)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                RETURNING payment_id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $this->userId,
                $this->tripId,
                $this->bookingId,
                $this->typeTransaction,
                $this->montant,
                $this->description,
                $this->dateTransaction,
                $this->statutTransaction,
                $this->commissionPlateforme
            ]);
            $this->paymentId = (int)$stmt->fetchColumn();
            return $this->paymentId;
        } catch (\PDOException $e) {
            throw new Exception("Erreur lors de l'enregistrement du paiement : " . $e->getMessage());
        }
    }

    /** Méthode statique pour créer un paiement sans instance */
    public static function create(array $data): int|false
    {
        $payment = new self($data);
        return $payment->savePaymentToDatabase();
    }

    public function getPaymentId(): ?int
    {
        return $this->paymentId;
    }
}
