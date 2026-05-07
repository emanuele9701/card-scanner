<?php

namespace App\Enums;

enum UrlMappingType: string
{
    case ListCard   = 'list_card';
    case SingleCard = 'single_card';

    /**
     * Etichetta leggibile per l'UI.
     */
    public function label(): string
    {
        return match($this) {
            self::ListCard   => 'Lista carte',
            self::SingleCard => 'Singola carta',
        };
    }

    /**
     * Colore badge per la UI (Tailwind class).
     */
    public function color(): string
    {
        return match($this) {
            self::ListCard   => 'gray',
            self::SingleCard => 'blue',
        };
    }
}
