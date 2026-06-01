<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\TCGCard;
use App\Models\TCGSet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class CardController extends Controller
{
    /**
     * Ricerca globale carte con supporto per:
     *  - nome carta (anche con spazi, es. "Mr. Mime")
     *  - abbreviazione set + dexId (es. "MEG 001")
     *  - nome + dexId (es. "Bulbasaur 001")
     *  - abbreviazione + nome + dexId (es. "MEG Bulbasaur 001")
     *  - qualsiasi combinazione parziale
     */
    public function search(Request $request) {
        $q = trim($request->query('q', ''));
        
        if (empty($q)) {
            $cards = TCGCard::with('set')->paginate(15);
            return $this->respondSearch($request, $cards, $q);
        }

        $parsed = $this->parseSearchQuery($q);

        $query = TCGCard::with('set');

        // Filtro per abbreviazione set (join su indice)
        if ($parsed['abbreviation']) {
            $query->whereHas('set', function ($sq) use ($parsed) {
                $sq->where('abbreviation_official', $parsed['abbreviation']);
            });
        }

        // Filtro per dexId (match esatto, con/senza zeri iniziali)
        if ($parsed['dexId'] !== null) {
            $dexId = $parsed['dexId'];
            $query->where(function ($q) use ($dexId) {
                $q->where('dexId', $dexId)
                  ->orWhere('dexId', ltrim($dexId, '0'))
                  ->orWhere('dexId', str_pad(ltrim($dexId, '0'), 3, '0', STR_PAD_LEFT));
            });
        }

        // Filtro per nome carta (LIKE)
        if ($parsed['name']) {
            $query->where('name', 'like', '%' . $parsed['name'] . '%');
        }

        // Se non abbiamo parsato nulla di strutturato, fallback a ricerca generica
        if (!$parsed['abbreviation'] && $parsed['dexId'] === null && !$parsed['name']) {
            $query->where('name', 'like', "%{$q}%");
        }

        $cards = $query->paginate(15);

        return $this->respondSearch($request, $cards, $q);
    }

    /**
     * Parsa la query di ricerca in componenti: abbreviazione, nome, dexId.
     *
     * Logica:
     * 1. L'ultimo token, se numerico → dexId
     * 2. Il primo token (dei rimanenti), se è un'abbreviazione nota → abbreviazione set
     * 3. Tutto ciò che resta → nome della carta (supporta spazi)
     */
    private function parseSearchQuery(string $q): array
    {
        $result = [
            'abbreviation' => null,
            'name' => null,
            'dexId' => null,
        ];

        $tokens = preg_split('/\s+/', trim($q));
        if (empty($tokens)) {
            return $result;
        }

        // 1. Ultimo token numerico? → dexId
        $lastToken = end($tokens);
        if (preg_match('/^\d+$/', $lastToken)) {
            $result['dexId'] = $lastToken;
            array_pop($tokens);
        }

        if (empty($tokens)) {
            return $result;
        }

        // 2. Primo token = abbreviazione nota?
        $firstToken = strtoupper($tokens[0]);
        $knownAbbreviations = $this->getKnownAbbreviations();
        
        if (isset($knownAbbreviations[$firstToken])) {
            $result['abbreviation'] = $firstToken;
            array_shift($tokens);
        }

        // 3. Tutto il resto → nome carta
        if (!empty($tokens)) {
            $result['name'] = implode(' ', $tokens);
        }

        return $result;
    }

    /**
     * Restituisce un set di abbreviazioni note, cachate per 24h.
     * La chiave è l'abbreviazione uppercase, il valore è true.
     * Formato: ['MEG' => true, 'SV' => true, ...]
     */
    private function getKnownAbbreviations(): array
    {
        return Cache::remember('tcg_set_abbreviations', 60 * 60 * 24, function () {
            return TCGSet::whereNotNull('abbreviation_official')
                ->where('abbreviation_official', '!=', '')
                ->pluck('abbreviation_official')
                ->unique()
                ->mapWithKeys(fn ($abbr) => [strtoupper($abbr) => true])
                ->toArray();
        });
    }

    /**
     * Risponde alla ricerca in formato JSON (AJAX) o con la vista.
     */
    private function respondSearch(Request $request, $cards, string $q)
    {
        if ($request->ajax()) {
            return response()->json([
                'html' => view('collezioni.partials.cards-grid', compact('cards'))->render(),
                'current_page' => $cards->currentPage(),
                'last_page' => $cards->lastPage(),
            ]);
        }

        return view('search.results', compact('cards', 'q'));
    }

    public function autocomplete(Request $request)
    {
        $q = trim($request->query('q', ''));
        if (empty($q)) {
            return response()->json([]);
        }

        $parsed = $this->parseSearchQuery($q);
        $query = TCGCard::with('set');

        if ($parsed['abbreviation']) {
            $query->whereHas('set', function ($sq) use ($parsed) {
                $sq->where('abbreviation_official', $parsed['abbreviation']);
            });
        }

        if ($parsed['dexId'] !== null) {
            $dexId = $parsed['dexId'];
            $query->where(function ($q) use ($dexId) {
                $q->where('dexId', $dexId)
                  ->orWhere('dexId', ltrim($dexId, '0'))
                  ->orWhere('dexId', str_pad(ltrim($dexId, '0'), 3, '0', STR_PAD_LEFT));
            });
        }

        if ($parsed['name']) {
            $query->where('name', 'like', '%' . $parsed['name'] . '%');
        }

        if (!$parsed['abbreviation'] && $parsed['dexId'] === null && !$parsed['name']) {
            $query->where('name', 'like', "%{$q}%");
        }

        $cards = $query->limit(8)->get(['id', 'name', 'url_image', 'language', 'set_id', 'dexId', 'rarity']);
        
        return response()->json($cards->map(function($card) {
            return [
                'id' => $card->id,
                'name' => $card->name,
                'image' => $card->url_image ? $card->url_image . '/low.png' : null,
                'language' => strtolower($card->language ?? ''),
                'set_name' => optional($card->set)->name,
                'set_symbol' => optional($card->set)->symbol ? optional($card->set)->symbol . '/low.png' : null,
                'rarity' => $card->rarity ?: 'Common',
                'dexId' => str_pad($card->dexId, 3, '0', STR_PAD_LEFT)
            ];
        }));
    }

    public function show(TCGCard $card) {
        
        $card->load('abilities', 'prices', 'priceHistory');
        
        return response()->json($card);
    }
}
