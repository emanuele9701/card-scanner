<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TCGCard;
use Illuminate\Http\Request;

class CardController extends Controller
{
    public function show(TCGCard $card) {
        
        $card->load('abilities','prices');
        
        return response()->json($card);
    }
}
