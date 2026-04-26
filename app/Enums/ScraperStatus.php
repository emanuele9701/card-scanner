<?php
namespace App\Enums;

enum ScraperStatus: string
{
    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case COMPLETED = 'completed';
    case FAILED = 'failed';
    case DISABLED = 'disabled';
}