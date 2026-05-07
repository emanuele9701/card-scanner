<?php
namespace App\Helpers;


/**
 * CardMarket HTML Parser
 *
 * Estrae le carte e la paginazione da un file HTML salvato localmente,
 * e produce un file output.json con i risultati.
 */

use DiDom\Document;
use RuntimeException;

class CardMarketParser
{
    private const BASE_URL = 'https://www.cardmarket.com';

    public function __construct(private string $html
    ) {}

    /**
     * Entry point: legge l'HTML, estrae i dati e scrive il JSON.
     */
    public function run(): string
    {
        try {
            $document = new Document($this->html);

            $cards = $this->extractCards($document);
            $pagination = $this->extractPagination($document);

            $result = [
                'totalCardsFound' => count($cards),
                'maxPages'        => $pagination['maxPages'],
                'paginationUrls'  => $pagination['urls'],
                'cards'           => $cards,
            ];

            

            echo "✅ Estrazione completata con successo!" . PHP_EOL;
            echo "📄 Carte estratte: " . count($cards) . PHP_EOL;
            echo "📑 Pagine massime trovate: " . $pagination['maxPages'] . PHP_EOL;

            return $this->getJson($result);
        } catch (RuntimeException $e) {
            fwrite(STDERR, "❌ Errore durante lo scraping: " . $e->getMessage() . PHP_EOL);
            exit(1);
        }
    }

    /**
     * Estrae tutte le carte dal DOM.
     *
     * @return array<int, array{url: string|null, img: string|null, alt: string|null, title: string, price: string}>
     */
    private function extractCards(Document $document): array
    {
        $cards = [];

        foreach ($document->find('.galleryBox') as $box) {
            $url   = $box->getAttribute('href');
            $imgEl = $box->first('img');
            $img   = $imgEl?->getAttribute('src');
            $alt   = $imgEl?->getAttribute('alt');

            // Pulizia titolo: elimina spazi multipli, a capo e &nbsp;
            $titleRaw = $box->first('h2.card-title')?->text() ?? '';
            $title    = $this->cleanWhitespace($titleRaw);

            // Prezzo: <b> dentro .card-text.text-muted
            $price = trim($box->first('.card-text.text-muted b')?->text() ?? '');

            $cards[] = [
                'url'   => $url ? self::BASE_URL . $url : null,
                'img'   => $img,
                'alt'   => $alt ? trim($alt) : null,
                'title' => $title,
                'price' => $price,
            ];
        }

        return $cards;
    }

    /**
     * Estrae il numero massimo di pagine e gli URL di paginazione.
     *
     * @return array{maxPages: int, urls: list<string>}
     */
    private function extractPagination(Document $document): array
    {
        $maxPages = 1;
        $urls     = [];

        // Testo "Pagina X di Y"
        $paginationText = '';
        foreach ($document->find('span.mx-1') as $span) {
            if (preg_match('/di\s+(\d+)/u', $span->text())) {
                $paginationText = $span->text();
                break;
            }
        }

        if ($paginationText !== '' && preg_match('/di\s+(\d+)/u', $paginationText, $matches)) {
            $maxPages = (int) $matches[1];
        }

        // Ricava un href di esempio dai link di paginazione
        $sampleHref = $document->first('a[data-direction="next"]')?->getAttribute('href');

        if ($sampleHref !== null) {
            $base    = self::BASE_URL;
            $parsed  = parse_url($base . $sampleHref);
            $path    = $parsed['path'] ?? '';

            parse_str($parsed['query'] ?? '', $queryParams);

            for ($i = 2; $i <= $maxPages; $i++) {
                $queryParams['site'] = $i;
                $urls[] = $path . '?' . http_build_query($queryParams);
            }
        } elseif ($maxPages === 1) {
            $urls[] = 'Nessuna paginazione trovata, pagina singola.';
        }

        return ['maxPages' => $maxPages, 'urls' => $urls];
    }

    /**
     * Normalizza whitespace (spazi multipli, a capo, &nbsp; → spazio singolo).
     */
    private function cleanWhitespace(string $text): string
    {
        // \u00A0 = &nbsp;
        $text = str_replace("\u{00A0}", ' ', $text);
        $text = preg_replace('/\s+/', ' ', $text);

        return trim($text);
    }

    /**
     * Scrive il risultato come JSON indentato su file.
     *
     * @param array<string, mixed> $data
     */
    private function getJson(array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        if ($json === false) {
            throw new RuntimeException('Impossibile serializzare i dati in JSON.');
        }

        return $json;
    }
}