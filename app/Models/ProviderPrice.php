<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProviderPrice extends Model
{
    protected $table = 'provider_prices';

    protected $fillable = [
        'name',
        'id'
    ];
}
