<?php

namespace App\Enums;

/**
 * Enum des statuts de paiement standardisés
 * 
 * Utilisé pour payment_transactions.status
 */
enum PaymentStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELED = 'canceled';
    case REFUNDED = 'refunded';

    /**
     * Vérifier si le statut est final (ne peut plus changer)
     */
    public function isFinal(): bool
    {
        return in_array($this, [
            self::SUCCEEDED,
            self::FAILED,
            self::CANCELED,
            self::REFUNDED,
        ]);
    }

    /**
     * Vérifier si le statut indique un succès
     */
    public function isSuccess(): bool
    {
        return $this === self::SUCCEEDED;
    }

    /**
     * Vérifier si le statut indique un échec
     */
    public function isFailure(): bool
    {
        return in_array($this, [
            self::FAILED,
            self::CANCELED,
        ]);
    }

    /**
     * Obtenir tous les statuts possibles
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Obtenir le label lisible du statut
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'En attente',
            self::PROCESSING => 'En cours de traitement',
            self::SUCCEEDED => 'Réussi',
            self::FAILED => 'Échoué',
            self::CANCELED => 'Annulé',
            self::REFUNDED => 'Remboursé',
        };
    }
}




