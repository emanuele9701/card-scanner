<?php

require __DIR__ . '/../vendor/autoload.php';

$app = require __DIR__ . '/../bootstrap/app.php';

$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Http;

// 1. Get User and Token
$user = User::where('email', 'test@example.com')->first();
$token = $user->createToken('test-token')->plainTextToken;
echo "Token: " . substr($token, 0, 10) . "...\n";

// 2. Prepare Image (reuse same for test)
$imagePath = 'C:/Users/emanu/.gemini/antigravity/brain/eab0ae57-2a42-4ec1-91c5-3b7f4697798a/test_pikachu_card_1769638089978.png';
if (!file_exists($imagePath)) {
    die("Image not found\n");
}

echo "\n--- STEP 1: ANALYZE (to create a card id) ---\n";
// Create a dummy card first via analyze
$response = Http::withToken($token)
    ->attach('image', file_get_contents($imagePath), 'pikachu.png')
    ->post('http://127.0.0.1:8000/api/card/analyze');

$data = $response->json();
$cardId = $data['data']['card_id'];
echo "Created Card ID: $cardId\n";

echo "\n--- STEP 2: DELETE CARD ---\n";
$deleteResponse = Http::withToken($token)
    ->delete('http://127.0.0.1:8000/api/card/delete', [
        'card_id' => $cardId
    ]);

echo "Status: " . $deleteResponse->status() . "\n";
echo "Body: " . $deleteResponse->body() . "\n";
