<?php

namespace App\Http\Controllers;

use App\Models\TCGCard;
use App\Models\TCGSet;
use App\Models\UserCardCollection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CollectionExportController extends Controller
{
    public function export(Request $request)
    {
        $user = Auth::user();
        $collections = \App\Models\UserCardCollection::with(['card', 'card.set', 'card.prices'])
            ->where('user_id', $user->id)
            ->get();
            
        $csvHeader = ['Card Name', 'Set', 'Rarity', 'Condition', 'Variants', 'Quantity', 'Estimated Unit Value', 'Estimated Total Value'];
        $csvData = [];
        
        foreach ($collections as $item) {
            $card = $item->card;
            if (!$card) continue;
            
            $price = $item->getCalculatedPrice();
            
            $csvData[] = [
                $card->name,
                $card->set->name ?? '',
                $card->rarity ?? '',
                $item->condition ?? 'NM',
                collect([
                    $item->foil_type ?: 'normal',
                    $item->is_first_edition ? '1st Edition' : null,
                    $item->is_signed ? 'Signed' : null,
                    $item->is_altered ? 'Altered' : null,
                ])->filter()->implode(', '),
                $item->quantity,
                number_format((float)$price, 2, '.', ''),
                number_format((float)$price * $item->quantity, 2, '.', ''),
            ];
        }
        
        $filename = "pokestash_collection_" . date('Y-m-d') . ".csv";
        
        return response()->streamDownload(function() use ($csvHeader, $csvData) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, $csvHeader);
            foreach ($csvData as $row) {
                fputcsv($handle, $row);
            }
            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=utf-8',
        ]);
    }

    /**
     * Esporta un file Excel (.xlsx) con 3 fogli: Possedute, Mancanti, Doppie
     */
    public function exportSetExcel(Request $request, TCGSet $set)
    {
        $user = Auth::user();

        // Fetch all cards for this set with user's collection and prices
        $allCards = TCGCard::where('set_id', $set->id)
            ->with(['prices', 'collectors' => function($q) use ($user) {
                $q->where('user_id', $user->id);
            }])
            ->orderBy('dexId', 'asc')
            ->get();

        $incomingCardsSet = \App\Models\UserIncomingCard::where('user_id', $user->id)
            ->where('set_id', $set->id)
            ->get()->groupBy('card_id');

        $ownedRows = [];
        $missingRows = [];
        $doppieRows = [];

        foreach ($allCards as $card) {
            $produced = $card->produced_variants;
            if (empty($produced)) {
                $produced = ['normal'];
            }
            $producedUnique = array_unique(array_map('strtolower', $produced));

            $ownedVariants = [];
            $variantCounts = [];

            foreach ($card->collectors as $coll) {
                $foil = strtolower(trim($coll->foil_type ?: 'normal'));
                $ownedVariants[] = $foil;
                if (!isset($variantCounts[$foil])) {
                    $variantCounts[$foil] = 0;
                }
                $variantCounts[$foil] += $coll->quantity;

                // Add row per copy to owned sheet
                $price = $coll->getCalculatedPrice();

                $ownedRows[] = [
                    $card->dexId,
                    $card->name,
                    $card->rarity ?? '',
                    ucfirst($foil),
                    strtoupper($coll->language ?? 'IT'),
                    $coll->condition ?? 'NM',
                    $coll->quantity,
                    $coll->is_first_edition ? 'Sì' : 'No',
                    round((float)$price, 2),
                    round((float)$price * $coll->quantity, 2),
                ];
            }

            $ownedVariantsUnique = array_unique($ownedVariants);
            $missingVariants = array_values(array_diff($producedUnique, $ownedVariantsUnique));

            // Check incoming status per variant
            $incomingVariantsList = [];
            if ($incomingCardsSet->has($card->id)) {
                foreach ($incomingCardsSet->get($card->id) as $inc) {
                    $incomingVariantsList[] = strtolower(trim($inc->foil_type ?: 'normal'));
                }
            }

            foreach ($missingVariants as $variant) {
                $isIncoming = in_array($variant, $incomingVariantsList);
                $missingRows[] = [
                    $card->dexId,
                    $card->name,
                    $card->rarity ?? '',
                    ucfirst($variant),
                    $isIncoming ? 'Sì' : 'No',
                ];
            }

            foreach ($variantCounts as $variant => $count) {
                if ($count > 1) {
                    $doppieRows[] = [
                        $card->dexId,
                        $card->name,
                        $card->rarity ?? '',
                        ucfirst($variant),
                        $count,
                        $count - 1,
                    ];
                }
            }
        }

        // Build Excel
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();

        // ─── Sheet 1: Possedute ───
        $sheet1 = $spreadsheet->getActiveSheet();
        $sheet1->setTitle('Possedute');
        $headers1 = ['#', 'Nome', 'Rarità', 'Variante', 'Lingua', 'Condizione', 'Quantità', '1ª Edizione', 'Valore Unit.', 'Valore Tot.'];
        $sheet1->fromArray($headers1, null, 'A1');
        if (!empty($ownedRows)) {
            $sheet1->fromArray($ownedRows, null, 'A2');
        }
        $this->styleExcelSheet($sheet1, count($headers1), count($ownedRows) + 1);

        // ─── Sheet 2: Mancanti ───
        $sheet2 = $spreadsheet->createSheet();
        $sheet2->setTitle('Mancanti');
        $headers2 = ['#', 'Nome', 'Rarità', 'Variante', 'In Arrivo'];
        $sheet2->fromArray($headers2, null, 'A1');
        if (!empty($missingRows)) {
            $sheet2->fromArray($missingRows, null, 'A2');
        }
        $this->styleExcelSheet($sheet2, count($headers2), count($missingRows) + 1);

        // ─── Sheet 3: Doppie ───
        $sheet3 = $spreadsheet->createSheet();
        $sheet3->setTitle('Doppie');
        $headers3 = ['#', 'Nome', 'Rarità', 'Variante', 'Quantità Totale', 'Extra'];
        $sheet3->fromArray($headers3, null, 'A1');
        if (!empty($doppieRows)) {
            $sheet3->fromArray($doppieRows, null, 'A2');
        }
        $this->styleExcelSheet($sheet3, count($headers3), count($doppieRows) + 1);

        // Select first sheet
        $spreadsheet->setActiveSheetIndex(0);

        // Stream download
        $setName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $set->name);
        $filename = "Collezione_{$setName}_" . date('Y-m-d') . ".xlsx";

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        return response()->streamDownload(function() use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }

    /**
     * Applica stile professionale all'header di un foglio Excel.
     */
    private function styleExcelSheet(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet, int $colCount, int $lastRow): void
    {
        $lastCol = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($colCount);

        // Header style
        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF'], 'size' => 11],
            'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '1a1a2e']],
            'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
            'borders' => ['bottom' => ['borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_MEDIUM, 'color' => ['rgb' => 'f59e0b']]],
        ];
        $sheet->getStyle("A1:{$lastCol}1")->applyFromArray($headerStyle);

        // Auto-size columns
        for ($i = 1; $i <= $colCount; $i++) {
            $col = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i);
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Zebra striping
        for ($row = 2; $row <= $lastRow; $row++) {
            if ($row % 2 === 0) {
                $sheet->getStyle("A{$row}:{$lastCol}{$row}")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('f0f4fa');
            }
        }

        // Freeze top row
        $sheet->freezePane('A2');
    }
}
