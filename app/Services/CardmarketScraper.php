<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;
use Symfony\Component\DomCrawler\Crawler;

/**
 * CardmarketScraper
 *
 * Recupera le offerte presenti nella section#table di una pagina prodotto
 * di Cardmarket e le restituisce come Collection di array associativi.
 *
 * Dipendenze da aggiungere al progetto:
 *   composer require symfony/dom-crawler symfony/css-selector
 *
 * Lo script Node.js (scraper.js) deve essere raggiungibile al percorso
 * definito in CARDMARKET_SCRAPER_SCRIPT (default: base_path('scraper.js')).
 * Richiede Node.js e Playwright installati:
 *   npm install playwright
 *   npx playwright install chromium
 */
class CardmarketScraper
{
    /**
     * URL base di Cardmarket (usato per risolvere i link relativi del venditore).
     */
    private const BASE_URL = 'https://www.cardmarket.com';

    /**
     * Timeout massimo in secondi per l'esecuzione dello script Node.js.
     * Cardmarket può impiegare qualche secondo a caricare il JS della pagina.
     */
    private const SCRAPER_TIMEOUT = 60;

    /**
     * Percorso assoluto allo script Playwright.
     * Configurabile tramite la variabile d'ambiente CARDMARKET_SCRAPER_SCRIPT.
     */
    private function scriptPath(): string
    {
        // Recupero il percorso dello script dalla variabile d'ambiente CARDMARKET_SCRAPER_SCRIPT
        // Se non esiste, uso il default: base_path('scraper.js')
        $path = env('CARDMARKET_SCRAPER_SCRIPT', base_path('scraper.js'));
        logger()->debug('[CardmarketScraper] scriptPath() - Percorso script determinato', ['path' => $path]);
        return $path;
    }

    /**
     * Recupera le offerte direttamente da un URL di Cardmarket.
     *
     * Lancia lo script Node.js `scraper.js` come processo esterno passando
     * l'URL come argomento. Lo script apre Chromium tramite Playwright,
     * carica la pagina e scrive l'HTML completo (post-JS) su stdout.
     * I log diagnostici dello script vengono scritti su stderr e catturati
     * separatamente per non inquinare l'output.
     *
     * @param  string  $url  URL completo della pagina prodotto
     *                       es. "https://www.cardmarket.com/it/Pokemon/Products/Singles/..."
     * @return string Html completo restituito dallo script Node.js, pronto per essere parsato.
     *
     * @throws \RuntimeException  Se lo script non esiste, fallisce o restituisce HTML vuoto.
     */
    public function scrapeFromUrl(string $url): string
    {
        logger()->info('[CardmarketScraper] INIZIO: Scraping da URL', ['url' => $url]);
        
        // Recupero il percorso dello script scraper.js dal .env
        $scriptPath = $this->scriptPath();
        logger()->debug('[CardmarketScraper] Percorso script', ['path' => $scriptPath]);

        // Verifico che lo script esista, altrimenti genero un'eccezione
        if (! file_exists($scriptPath)) {
            logger()->error('[CardmarketScraper] ERRORE: Script non trovato', ['path' => $scriptPath]);
            throw new \RuntimeException(
                "Script scraper non trovato: {$scriptPath}. "
                . 'Imposta CARDMARKET_SCRAPER_SCRIPT nel file .env.'
            );
        }

        logger()->debug('[CardmarketScraper] Script trovato, avvio processo Node.js');

        // Validazione URL prima di lanciare il processo Node.js
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            logger()->error('[CardmarketScraper] ERRORE: URL non valido', ['url' => $url]);
            throw new \RuntimeException(
                "URL non valido: {$url}. Verificare che il protocollo sia corretto (https://)."
            );
        }

        // Costruiamo il comando: node scraper.js "<url>"
        // Usiamo array per evitare qualsiasi rischio di shell injection sull'URL
        // Il timeout è impostato a 60 secondi per permettere a Cardmarket di caricare il JS
        logger()->debug('[CardmarketScraper] Esecuzione comando Node.js con timeout', ['timeout' => self::SCRAPER_TIMEOUT]);
        $result = Process::timeout(self::SCRAPER_TIMEOUT)
            ->run(['node', $scriptPath, $url]);
        logger()->debug('[CardmarketScraper] Processo Node.js completato', ['exit_code' => $result->exitCode()]);

        // Lo stderr contiene i log diagnostici dello script (es. "[1.23s] ✅ Pagina caricata")
        // Li registriamo nel log di Laravel per debug, senza bloccare il flusso
        if ($result->errorOutput()) {
            logger()->debug('[CardmarketScraper] Output diagnostico scraper.js ricevuto');
            logger()->debug('[CardmarketScraper] Output diagnostico scraper.js', [
                'url'    => $url,
                'stderr' => $result->errorOutput(),
            ]);
        }

        // Se il processo è fallito (exit code != 0), genero un'eccezione
        if (! $result->successful()) {
            logger()->error('[CardmarketScraper] ERRORE: Processo fallito', ['exit_code' => $result->exitCode()]);
            throw new \RuntimeException(
                "Lo script scraper.js è terminato con errore (exit code {$result->exitCode()}). "
                . "Controlla i log per i dettagli."
            );
        }

        // Estraggo l'HTML dall'output del processo
        $html = $result->output();
        logger()->debug('[CardmarketScraper] HTML ricevuto da scraper.js', ['html_length' => strlen($html)]);

        // Verifico che l'HTML non sia vuoto
        if (empty(trim($html))) {
            logger()->error('[CardmarketScraper] ERRORE: HTML vuoto ricevuto');
            throw new \RuntimeException(
                "Lo script scraper.js non ha restituito alcun HTML per l'URL: {$url}"
            );
        }

        logger()->info('[CardmarketScraper] HTML validato, inizio parsing');
        // Passo l'HTML al parser per estrarre i dati
        return $html;
    }

    /**
     * Esegue il parsing dell'HTML già disponibile (es. letto da file o cache).
     *
     * Restituisce un array con due chiavi:
     *   - 'product_info': dati della scheda prodotto da div#tabContent-info
     *   - 'offers':       Collection delle offerte da section#table
     *
     * @param  string  $html  Contenuto HTML grezzo
     * @return array{product_info: array, offers: Collection<int, array>}
     */
    public function parseHtml(string $html): array
    {
        logger()->debug('[CardmarketScraper] parseHtml - INIZIO parsing HTML');
        
        // Creo un nuovo crawler Symfony per navigare l'HTML come DOM
        $crawler = new Crawler($html);
        logger()->debug('[CardmarketScraper] parseHtml - Crawler Symfony creato');

        logger()->debug('[CardmarketScraper] parseHtml - Estrazione informazioni prodotto');
        $productInfo = $this->parseProductInfo($crawler);
        
        logger()->debug('[CardmarketScraper] parseHtml - Estrazione offerte da tabella');
        $offers = $this->parseOffers($crawler);
        
        logger()->info('[CardmarketScraper] parseHtml - Parsing completato', [
            'offers_count' => $offers->count(),
        ]);

        return [
            'product_info' => $productInfo,
            'offers'       => $offers,
        ];
    }

    /**
     * Estrae le informazioni prodotto da div#tabContent-info.
     *
     * La sezione contiene una <dl class="labeled"> con coppie <dt>/<dd>.
     * Itera su ogni coppia usando il testo del <dt> come chiave e ricava
     * il valore dal <dd> con logica specifica per ciascun campo.
     *
     * @return array{
     *   rarita:              string,
     *   numero:              string,
     *   stampata_in:         string,
     *   ristampe_url:        string|null,
     *   offerte_ristampe_url:string|null,
     *   specie:              string,
     *   articoli_disponibili:int|null,
     *   prezzo_da:           string,
     *   prezzo_da_numeric:   float|null,
     *   tendenza_prezzo:     string,
     *   prezzo_medio_30g:    string,
     *   prezzo_medio_7g:     string,
     *   prezzo_medio_1g:     string,
     * }
     */
    private function parseProductInfo(Crawler $crawler): array
    {
        logger()->debug('[CardmarketScraper] parseProductInfo - INIZIO estrazione info prodotto');
        
        // Inizializzo array con tutti i campi prodotto di default vuoti/null
        $info = [
            'rarita'               => '',
            'numero'               => '',
            'stampata_in'          => '',
            'ristampe_url'         => null,
            'offerte_ristampe_url' => null,
            'specie'               => '',
            'articoli_disponibili' => null,
            'prezzo_da'            => '',
            'prezzo_da_numeric'    => null,
            'tendenza_prezzo'      => '',
            'prezzo_medio_30g'     => '',
            'prezzo_medio_7g'      => '',
            'prezzo_medio_1g'      => '',
        ];

        // Cerco il contenitore principale con le informazioni di gioco
        $infoDiv = $crawler->filter('div#tabContent-info dl.labeled');
        logger()->debug('[CardmarketScraper] parseProductInfo - Sezione info trovata', ['found' => $infoDiv->count() > 0 ? 'si' : 'no']);

        // Se la sezione non esiste, ritorno l'array con i valori di default
        if (! $infoDiv->count()) {
            logger()->warning('[CardmarketScraper] parseProductInfo - div#tabContent-info non trovato');
            return $info;
        }

        // Raccoglie tutti i <dt> (etichette) e <dd> (valori) mantenendo la corrispondenza posizionale
        $dts = $infoDiv->filter('dt');
        $dds = $infoDiv->filter('dd');
        logger()->debug('[CardmarketScraper] parseProductInfo - Elementi dt/dd trovati', [
            'dt_count' => $dts->count(),
            'dd_count' => $dds->count(),
        ]);

        // Itero su ogni coppia dt/dd
        $dts->each(function (Crawler $dt, int $i) use ($dds, &$info) {
            // Verifico che l'indice dd corrispondente esista
            if ($i >= $dds->count()) {
                return;
            }

            // Estraggo l'etichetta (label) dal <dt>
            $label = trim($dt->text());
            // Estraggo l'elemento <dd> all'indice corrispondente
            $dd    = $dds->eq($i);
            
            logger()->debug('[CardmarketScraper] parseProductInfo - Elaborazione campo', [
                'index' => $i,
                'label' => $label,
            ]);

            // A seconda dell'etichetta, applico la logica di estrazione specifica
            
            switch ($label) {

                case 'Rarity':
                case 'Rarità':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Rarità');
                    // Il valore è un SVG con aria-label (es. "Double Rare")
                    $svg = $dd->filter('svg');
                    $rarita = $svg->count()
                        ? ($svg->attr('aria-label') ?? '')
                        : trim($dd->text());
                    $info['rarita'] = $rarita;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Rarità estratta', ['value' => $rarita]);
                    break;

                case 'Numero':
                case 'Number':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Numero');
                    $numero = trim($dd->text());
                    $info['numero'] = $numero;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Numero estratto', ['value' => $numero]);
                    break;

                case 'Stampata in':
                case 'Printed in':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Stampata in');
                    // Prendiamo il testo dell'<a> con il nome dell'espansione
                    // (il secondo link, quello senza icona sprite)
                    $links = $dd->filter('a');
                    // L'ultimo <a> contiene il nome testuale dell'espansione
                    if ($links->count()) {
                        $last = $links->eq($links->count() - 1);
                        $expansion = trim($last->text());
                        $info['stampata_in'] = $expansion;
                        logger()->debug('[CardmarketScraper] parseProductInfo - Stampata in estratta', ['value' => $expansion]);
                    }
                    break;

                case 'Ristampe':
                case 'Reprints':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Ristampe (link)');
                    // Due link: "Mostra le ristampe (N)" e "Mostra le offerte"
                    $links = $dd->filter('a');
                    // Primo link: ristampe_url
                    if ($links->count() >= 1) {
                        $href = $links->eq(0)->attr('href') ?? '';
                        $info['ristampe_url'] = $href ? self::BASE_URL . $href : null;
                        logger()->debug('[CardmarketScraper] parseProductInfo - Link ristampe', ['url' => $info['ristampe_url']]);
                    }
                    // Secondo link: offerte_ristampe_url
                    if ($links->count() >= 2) {
                        $href = $links->eq(1)->attr('href') ?? '';
                        $info['offerte_ristampe_url'] = $href ? self::BASE_URL . $href : null;
                        logger()->debug('[CardmarketScraper] parseProductInfo - Link offerte ristampe', ['url' => $info['offerte_ristampe_url']]);
                    }
                    break;

                case 'Specie':
                case 'Species':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Specie');
                    // Se esiste un link, estraggo il testo del link; altrimenti uso il testo diretto
                    $link = $dd->filter('a');
                    $specie = $link->count()
                        ? trim($link->text())
                        : trim($dd->text());
                    $info['specie'] = $specie;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Specie estratta', ['value' => $specie]);
                    break;

                case 'Articoli disponibili':
                case 'Available items':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Articoli disponibili');
                    // Converto il testo in intero se è numerico
                    $raw = trim($dd->text());
                    $count = is_numeric($raw) ? (int) $raw : null;
                    $info['articoli_disponibili'] = $count;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Articoli disponibili estratti', ['value' => $count]);
                    break;

                case 'Da':
                case 'From':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing prezzo Da');
                    // Estraggo il prezzo nel formato stringa e lo normalizzo a float
                    $raw = trim($dd->text());
                    $info['prezzo_da']         = $raw;
                    $info['prezzo_da_numeric'] = $this->normalizePrice($raw);
                    logger()->debug('[CardmarketScraper] parseProductInfo - Prezzo Da estratto', [
                        'value' => $raw,
                        'numeric' => $info['prezzo_da_numeric'],
                    ]);
                    break;

                case 'Tendenza di prezzo':
                case 'Price Trend':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Tendenza prezzo');
                    $trend = trim($dd->text());
                    $info['tendenza_prezzo'] = $trend;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Tendenza estratta', ['value' => $trend]);
                    break;

                case 'Prezzo medio 30 giorni':
                case '30-days average price':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Prezzo medio 30g');
                    $price30d = trim($dd->text());
                    $info['prezzo_medio_30g'] = $price30d;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Prezzo medio 30g estratto', ['value' => $price30d]);
                    break;

                case 'Prezzo medio 7 giorni':
                case '7-days average price':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Prezzo medio 7g');
                    $price7d = trim($dd->text());
                    $info['prezzo_medio_7g'] = $price7d;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Prezzo medio 7g estratto', ['value' => $price7d]);
                    break;

                case 'Prezzo medio 1 giorno':
                case '1-days average price':
                    logger()->debug('[CardmarketScraper] parseProductInfo - Parsing Prezzo medio 1g');
                    $price1d = trim($dd->text());
                    $info['prezzo_medio_1g'] = $price1d;
                    logger()->debug('[CardmarketScraper] parseProductInfo - Prezzo medio 1g estratto', ['value' => $price1d]);
                    break;
            }
        });

        logger()->info('[CardmarketScraper] parseProductInfo - Completato', ['fields_found' => count(array_filter($info))]);
        return $info;
    }

    /**
     * Estrae le offerte dalla section#table.
     *
     * @return Collection<int, array>
     */
    private function parseOffers(Crawler $crawler): Collection
    {
        logger()->debug('[CardmarketScraper] parseOffers - Ricerca sezione principale (section#table)');
        
        // Cerco il contenitore principale delle offerte
        $tableSection = $crawler->filter('section#table');
        logger()->debug('[CardmarketScraper] parseOffers - Section#table trovata', ['found' => $tableSection->count() > 0 ? 'si' : 'no']);

        // Se la sezione non esiste, ritorno una Collection vuota
        if (! $tableSection->count()) {
            logger()->warning('[CardmarketScraper] parseOffers - Section#table non trovata, ritorno collection vuota');
            return collect();
        }

        // Estraggo tutte le righe delle offerte (div.article-row) dal table-body
        logger()->debug('[CardmarketScraper] parseOffers - Ricerca righe offerte (div.article-row)');
        $rows = $tableSection->filter('div.table-body div.article-row');
        logger()->info('[CardmarketScraper] parseOffers - Righe trovate', ['total' => $rows->count()]);

        // Itero su ogni riga e applico il parsing della singola offerta
        $parsedOffers = collect($rows->each(fn (Crawler $row) => $this->parseRow($row)));
        logger()->info('[CardmarketScraper] parseOffers - Parsing offerte completato', ['offers_parsed' => $parsedOffers->count()]);
        
        return $parsedOffers;
    }

    // -------------------------------------------------------------------------
    // Logica di parsing di una singola riga
    // -------------------------------------------------------------------------

    /**
     * Estrae i dati di interesse da un singolo div.article-row.
     *
     * @return array{
     *   article_id:    string,
     *   seller_name:   string,
     *   seller_url:    string,
     *   language:      string,
     *   comment:       string|null,
     *   price:         string,
     *   price_numeric: float|null,
     * }
     */
    private function parseRow(Crawler $row): array
    {
        logger()->debug('[CardmarketScraper] parseRow - Parsing di una singola riga offerta');
        
        // Estraggo tutti i dati dalla riga con i vari metodi di parsing specializzati
        $parsedData = [
            'article_id'    => $this->parseArticleId($row),              // ID univoco dell'offerta
            'seller_name'   => $this->parseSellerName($row),             // Nome del venditore
            'seller_url'    => $this->parseSellerUrl($row),              // Profilo del venditore
            'language'      => $this->parseLanguage($row),               // Lingua della carta
            'comment'       => $this->parseComment($row),                // Commento del venditore (opzionale)
            'price'         => $this->parsePrice($row),                  // Prezzo formattato (es. "6,50 €")
            'price_numeric' => $this->parsePriceNumeric($row),           // Prezzo come float (es. 6.50)
        ];
        
        logger()->debug('[CardmarketScraper] parseRow - Riga parsata', [
            'article_id' => $parsedData['article_id'],
            'seller' => $parsedData['seller_name'],
            'price' => $parsedData['price'],
        ]);
        
        return $parsedData;
    }

    /**
     * ID univoco della riga (estratto dall'attributo id del div, es. "articleRow2046683206").
     */
    private function parseArticleId(Crawler $row): string
    {
        // Estraggo l'attributo 'id' dal div, es: "articleRow2046683206"
        $id = $row->attr('id') ?? '';
        logger()->debug('[CardmarketScraper] parseArticleId - ID grezzo estratto', ['raw_id' => $id]);
        
        // Rimuovo il prefisso "articleRow" lasciando solo il numero ID
        $cleanedId = str_replace('articleRow', '', $id);
        logger()->debug('[CardmarketScraper] parseArticleId - ID ripulito', ['cleaned_id' => $cleanedId]);
        
        return $cleanedId;
    }

    /**
     * Nome del venditore: testo dell'<a> all'interno di .seller-name.
     */
    private function parseSellerName(Crawler $row): string
    {
        // Cerco l'elemento <a> che contiene il nome del venditore dentro .seller-name
        $node = $row->filter('.col-seller .seller-name a');
        logger()->debug('[CardmarketScraper] parseSellerName - Link trovati', ['count' => $node->count()]);

        // Se l'elemento esiste, estraggo il testo e lo ripulio da spazi
        // Altrimenti ritorno una stringa vuota
        $sellerName = $node->count() ? trim($node->text()) : '';
        logger()->debug('[CardmarketScraper] parseSellerName - Nome estratto', ['seller' => $sellerName]);
        
        return $sellerName;
    }

    /**
     * URL del profilo venditore: href dell'<a> all'interno di .seller-name,
     * risolto come URL assoluto.
     */
    private function parseSellerUrl(Crawler $row): string
    {
        logger()->debug('[CardmarketScraper] parseSellerUrl - Ricerca link venditore');
        
        // Cerco l'elemento <a> che contiene il link del profilo venditore
        $node = $row->filter('.col-seller .seller-name a');

        // Se il link non esiste, ritorno stringa vuota
        if (! $node->count()) {
            logger()->debug('[CardmarketScraper] parseSellerUrl - Link non trovato');
            return '';
        }

        // Estraggo l'attributo href dal link
        $href = $node->attr('href') ?? '';
        logger()->debug('[CardmarketScraper] parseSellerUrl - URL grezzo', ['url' => $href]);

        // Se il link è relativo (es. "/it/Pokemon/Users/pokestouvi")
        // lo converto in URL assoluto aggiungendo il base URL di Cardmarket
        if ($href && ! str_starts_with($href, 'http')) {
            logger()->debug('[CardmarketScraper] parseSellerUrl - URL relativo convertito in assoluto', ['base_url' => self::BASE_URL]);
            $href = self::BASE_URL . $href;
        }

        logger()->debug('[CardmarketScraper] parseSellerUrl - URL finale', ['url' => $href]);
        return $href;
    }

    /**
     * Lingua della carta: valore di aria-label sullo span del flag linguaggio
     * all'interno di .product-attributes.
     *
     * Esempio di markup:
     *   <span aria-label="Francese" data-original-title="Francese" class="icon me-2" ...>
     */
    private function parseLanguage(Crawler $row): string
    {
        logger()->debug('[CardmarketScraper] parseLanguage - Ricerca lingua della carta');
        
        // Il flag della lingua è lo span con class "icon me-2" e aria-label valorizzato
        // all'interno di .product-attributes (NON quello della nazione del venditore)
        $productAttributes = $row->filter('.product-attributes');
        logger()->debug('[CardmarketScraper] parseLanguage - Product attributes trovati', ['count' => $productAttributes->count()]);

        // Se non esiste il contenitore degli attributi prodotto, ritorno stringa vuota
        if (! $productAttributes->count()) {
            logger()->debug('[CardmarketScraper] parseLanguage - Product attributes non trovati');
            return '';
        }

        // Scorre tutti gli <span> cercando quello con aria-label che non sia
        // un badge condizione (NM, MT, EX...) e che abbia data-original-title
        $language = '';
        logger()->debug('[CardmarketScraper] parseLanguage - Scansione span.icon per riconoscere lingua');

        $productAttributes->filter('span.icon')->each(function (Crawler $span) use (&$language) {
            // Estraggo l'attributo aria-label che contiene il nome della lingua
            $ariaLabel = $span->attr('aria-label') ?? '';
            
            // Gli span icona-lingua hanno anche l'attributo onmouseover che chiama showMsgBox
            // Questo è il discriminante per distinguere la lingua da altri badge
            $onMouseOver = $span->attr('onmouseover') ?? '';
            logger()->debug('[CardmarketScraper] parseLanguage - Span esaminato', [
                'aria_label' => $ariaLabel,
                'has_show_msgbox' => str_contains($onMouseOver, 'showMsgBox') ? 'si' : 'no',
            ]);

            // Se sia aria-label che onmouseover sono presenti e contiene showMsgBox, ho trovato la lingua
            if ($ariaLabel && str_contains($onMouseOver, 'showMsgBox')) {
                logger()->debug('[CardmarketScraper] parseLanguage - Lingua identificata', ['language' => $ariaLabel]);
                $language = $ariaLabel;
            }
        });

        logger()->debug('[CardmarketScraper] parseLanguage - Risultato finale', ['language' => $language]);
        return $language;
    }

    /**
     * Commento del venditore, se presente.
     *
     * Il commento compare in due posti nell'HTML:
     *   1. Desktop: <span class="d-block text-truncate ...">testo commento</span>
     *              all'interno di .product-comments .d-none.d-lg-block
     *   2. Mobile:  <span class="fonticon-comments ..." aria-label="testo commento">
     *
     * Utilizziamo prima la versione desktop (più affidabile), poi quella mobile.
     */
    private function parseComment(Crawler $row): ?string
    {
        logger()->debug('[CardmarketScraper] parseComment - Ricerca commento del venditore');
        
        // Versione desktop: il testo nel blocco visibile solo su schermi grandi
        // Questa è la versione più affidabile del commento
        $desktopNode = $row->filter('.product-comments .d-none.d-lg-block span.d-block');
        logger()->debug('[CardmarketScraper] parseComment - Versione desktop cercata', ['found' => $desktopNode->count() > 0 ? 'si' : 'no']);

        // Se esiste il commento nella versione desktop, lo estraggo e ripulisco
        if ($desktopNode->count()) {
            $text = trim($desktopNode->text());
            logger()->debug('[CardmarketScraper] parseComment - Commento desktop estratto', ['comment' => substr($text, 0, 50)]);
            // Ritorno il testo solo se non è vuoto, altrimenti null
            return $text !== '' ? $text : null;
        }

        logger()->debug('[CardmarketScraper] parseComment - Versione desktop non trovata, provo mobile');
        
        // Fallback versione mobile: aria-label sull'icona commento
        // Se la pagina viene renderizzata su mobile, il commento è nell'aria-label iconona
        $mobileNode = $row->filter('.product-comments .fonticon-comments');
        logger()->debug('[CardmarketScraper] parseComment - Versione mobile cercata', ['found' => $mobileNode->count() > 0 ? 'si' : 'no']);

        // Se esiste il commento nella versione mobile, lo estraggo da aria-label
        if ($mobileNode->count()) {
            $ariaLabel = trim($mobileNode->attr('aria-label') ?? '');
            logger()->debug('[CardmarketScraper] parseComment - Commento mobile estratto', ['comment' => substr($ariaLabel, 0, 50)]);
            // Ritorno il testo solo se non è vuoto, altrimenti null
            return $ariaLabel !== '' ? $ariaLabel : null;
        }

        logger()->debug('[CardmarketScraper] parseComment - Nessun commento trovato');
        return null;
    }

    /**
     * Prezzo formattato come stringa (es. "6,50 €").
     *
     * Utilizziamo il .price-container nel .col-offer (versione desktop),
     * che è sempre presente anche se nascosto su mobile via CSS.
     */
    private function parsePrice(Crawler $row): string
    {
        logger()->debug('[CardmarketScraper] parsePrice - Ricerca prezzo formattato');
        
        // Cerco l'elemento che contiene il prezzo formattato (es. "6,50 €")
        // Usiamo il selettore .col-offer .price-container .color-primary
        $node = $row->filter('.col-offer .price-container .color-primary');
        logger()->debug('[CardmarketScraper] parsePrice - Elemento trovato', ['found' => $node->count() > 0 ? 'si' : 'no']);

        // Se l'elemento esiste, estraggo il testo pulito
        // Altrimenti ritorno stringa vuota
        $price = $node->count() ? trim($node->text()) : '';
        logger()->debug('[CardmarketScraper] parsePrice - Prezzo estratto', ['price' => $price]);
        
        return $price;
    }

    /**
     * Prezzo come float (es. 6.50) per ordinamenti e confronti numerici.
     * Gestisce sia il formato italiano "6,50 €" sia quello internazionale "6.50 €".
     */
    private function parsePriceNumeric(Crawler $row): ?float
    {
        logger()->debug('[CardmarketScraper] parsePriceNumeric - Conversione prezzo a float');
        
        // Richiamo il metodo che estrae il prezzo formattato
        $raw = $this->parsePrice($row);
        logger()->debug('[CardmarketScraper] parsePriceNumeric - Prezzo greggio', ['raw' => $raw]);
        
        // Richiamo il metodo helper per normalizzare il prezzo
        $numeric = $this->normalizePrice($raw);
        logger()->debug('[CardmarketScraper] parsePriceNumeric - Prezzo normalizzato', ['numeric' => $numeric]);
        
        return $numeric;
    }

    /**
     * Converte una stringa prezzo (es. "6,50 €") in float (es. 6.5).
     * Restituisce null se la stringa non è un numero valido.
     */
    private function normalizePrice(string $raw): ?float
    {
        logger()->debug('[CardmarketScraper] normalizePrice - Input ricevuto', ['raw' => $raw]);
        
        // Se la stringa è vuota, non c'è niente da normalizzare
        if ($raw === '') {
            logger()->debug('[CardmarketScraper] normalizePrice - Stringa vuota, ritorno null');
            return null;
        }

        // Rimuove il simbolo valuta (€) e spazi (sia normali che non-breaking space)
        logger()->debug('[CardmarketScraper] normalizePrice - Pulizia simboli valuta e spazi');
        $normalized = str_replace(['€', ' ', "\u{00a0}"], '', $raw); // rimuove €, spazio, nbsp
        logger()->debug('[CardmarketScraper] normalizePrice - Dopo rimozione simboli', ['value' => $normalized]);
        
        // Normalizza il separatore decimale: virgola italiana (,) → punto (.)
        // Questo permette a PHP di riconoscere il valore come numero
        $normalized = str_replace(',', '.', $normalized);
        logger()->debug('[CardmarketScraper] normalizePrice - Dopo normalizzazione separatore decimale', ['value' => $normalized]);

        // Verifica che il valore sia effettivamente numerico, poi lo converte a float
        // Se non è numerico, ritorna null poiché il formato è invalido
        $result = is_numeric($normalized) ? (float) $normalized : null;
        logger()->debug('[CardmarketScraper] normalizePrice - Risultato finale', [
            'is_numeric' => is_numeric($normalized) ? 'si' : 'no',
            'float_value' => $result,
        ]);
        
        return $result;
    }
}