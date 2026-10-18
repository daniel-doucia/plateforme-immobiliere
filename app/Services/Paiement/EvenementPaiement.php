<?php

namespace App\Services\Paiement;

final class EvenementPaiement
{
    public function __construct(
        public readonly string $eventId,
        public readonly string $reference,
        public readonly int $montant,
        public readonly bool $reussi,
        public readonly array $brut = [],
    ) {}
}
