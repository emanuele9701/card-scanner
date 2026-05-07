<?php

namespace App\Enums;

enum UrlMappingStatus: string
{
    case Pending  = 'pending';
    case Active   = 'active';
    case Scraping = 'scraping';
    case Done     = 'done';
    case Failed   = 'failed';

    /**
     * Etichetta leggibile per l'UI.
     */
    public function label(): string
    {
        return match($this) {
            self::Pending  => 'In attesa',
            self::Active   => 'Attivo',
            self::Scraping => 'In scraping',
            self::Done     => 'Completato',
            self::Failed   => 'Fallito',
        };
    }

    /**
     * Colore badge per la UI (Tailwind class).
     */
    public function color(): string
    {
        return match($this) {
            self::Pending  => 'gray',
            self::Active   => 'blue',
            self::Scraping => 'yellow',
            self::Done     => 'green',
            self::Failed   => 'red',
        };
    }
}
