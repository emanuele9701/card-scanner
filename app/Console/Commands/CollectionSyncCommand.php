<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\UserCardCollection;
use App\Models\TCGCard;
use App\Models\TCGSet;
use App\Models\User;
use Illuminate\Support\Facades\File;

class CollectionSyncCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'collection:sync 
                            {action : L\'azione da eseguire (export o import)} 
                            {user_id : L\'ID dell\'utente} 
                            {file : Il percorso del file JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Esporta o importa la collezione di un utente tramite un file JSON senza usare ID del database.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $userId = $this->argument('user_id');
        $file = $this->argument('file');

        $user = User::find($userId);
        if (!$user) {
            $this->error("Utente con ID {$userId} non trovato.");
            return 1;
        }

        if ($action === 'export') {
            return $this->exportCollection($userId, $file);
        } elseif ($action === 'import') {
            return $this->importCollection($userId, $file);
        } else {
            $this->error("Azione non valida. Usa 'export' o 'import'.");
            return 1;
        }
    }

    protected $supportedLanguages = ['it', 'fr', 'de', 'es', 'pt', 'ja', 'ko', 'zh'];

    protected function exportCollection($userId, $file)
    {
        $this->info("Esportazione collezione utente {$userId} in corso...");

        $collection = UserCardCollection::with(['card.set'])->where('user_id', $userId)->get();
        $report = [];

        $bar = $this->output->createProgressBar(count($collection));
        $bar->start();

        foreach ($collection as $item) {
            if (!$item->card || !$item->card->set) {
                $bar->advance();
                continue;
            }

            // Estrai la lingua dai variants, se presente
            $collectionLanguage = $item->card->language; // default base card language (usually 'en')
            $variants = $item->variants ?? [];
            $filteredVariants = [];
            
            if (is_array($variants)) {
                foreach ($variants as $variant) {
                    if (in_array($variant, $this->supportedLanguages)) {
                        $collectionLanguage = $variant;
                    } else {
                        $filteredVariants[] = $variant;
                    }
                }
            }

            $report[] = [
                'set_abbreviation' => $item->card->set->abbreviation_official,
                'card_dexId'       => $item->card->dexId,
                'card_language'    => $collectionLanguage,
                'card_name'        => $item->card->name,
                'quantity'         => $item->quantity,
                'variants'         => empty($filteredVariants) ? null : $filteredVariants,
                'condition'        => $item->condition,
                'notes'            => $item->notes,
            ];

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        File::put($file, json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->newLine();
        $this->info("Esportazione completata! " . count($report) . " carte esportate nel file: {$file}");
        return 0;
    }

    protected function importCollection($userId, $file)
    {
        if (!File::exists($file)) {
            $this->error("File non trovato: {$file}");
            return 1;
        }

        $this->info("Importazione collezione per utente {$userId} in corso...");

        $content = File::get($file);
        $data = json_decode($content, true);

        if (!is_array($data)) {
            $this->error("Il file non è un JSON valido o è vuoto.");
            return 1;
        }

        $added = 0;
        $updated = 0;
        $errors = 0;

        $bar = $this->output->createProgressBar(count($data));
        $bar->start();

        foreach ($data as $index => $row) {
            $set = TCGSet::where('abbreviation_official', $row['set_abbreviation'])->first();
            if (!$set) {
                $this->newLine();
                $this->warn("Riga {$index}: Set con abbreviazione '{$row['set_abbreviation']}' non trovato. Salto.");
                $errors++;
                $bar->advance();
                continue;
            }

            // La carta base nel DB è solitamente in inglese o nella sua lingua originale. 
            // Cerchiamo semplicemente per set_id e dexId.
            $card = TCGCard::where('set_id', $set->id)
                ->where('dexId', $row['card_dexId'])
                ->first();

            if (!$card) {
                $this->newLine();
                $this->warn("Riga {$index}: Carta con dexId '{$row['card_dexId']}' non trovata nel set '{$row['set_abbreviation']}'. Salto.");
                $errors++;
                $bar->advance();
                continue;
            }

            $requestedLanguage = $row['card_language'] ?? 'en';
            
            // Costruiamo i variants includendo la lingua se diversa da quella base della carta
            $variants = $row['variants'] ?? [];
            if (!is_array($variants)) {
                $variants = [];
            }
            
            if ($requestedLanguage !== $card->language && !in_array($requestedLanguage, $variants)) {
                $variants[] = $requestedLanguage;
            }
            
            if (empty($variants)) {
                $variants = null;
            }

            $existingCollection = UserCardCollection::where('user_id', $userId)
                ->where('card_id', $card->id)
                ->where('condition', $row['condition'])
                ->get()
                ->first(function ($item) use ($variants) {
                    return $item->variants === $variants;
                });

            if ($existingCollection) {
                $existingCollection->quantity = $row['quantity'];
                $existingCollection->notes = $row['notes'] ?? $existingCollection->notes;
                $existingCollection->save();
                $updated++;
            } else {
                UserCardCollection::create([
                    'user_id'   => $userId,
                    'set_id'    => $set->id,
                    'serie_id'  => $set->serie_id,
                    'card_id'   => $card->id,
                    'quantity'  => $row['quantity'],
                    'variants'  => $variants,
                    'condition' => $row['condition'],
                    'notes'     => $row['notes'] ?? null,
                ]);
                $added++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $this->newLine();
        $this->info("Importazione completata!");
        $this->info("Carte aggiunte: {$added}");
        $this->info("Carte aggiornate: {$updated}");
        if ($errors > 0) {
            $this->warn("Errori (carte saltate): {$errors}");
        }

        return 0;
    }
}
