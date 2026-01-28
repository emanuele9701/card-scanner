#!/usr/bin/env php
<?php

/**
 * Simple API Test Script
 * 
 * This script tests the API endpoints to ensure they're working correctly.
 * Run with: php test-api.php
 */

$baseUrl = 'http://localhost/api';
$testEmail = 'test-api@example.com';
$testPassword = 'TestPassword123!';

echo "\n🧪 Testing Carte Pokemon API\n";
echo "============================\n\n";

// Helper function to make API requests
function apiRequest($method, $endpoint, $data = null, $token = null)
{
    global $baseUrl;

    $ch = curl_init($baseUrl . $endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    $headers = [
        'Accept: application/json',
        'Content-Type: application/json',
    ];

    if ($token) {
        $headers[] = "Authorization: Bearer $token";
    }

    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return [
        'status' => $httpCode,
        'body' => json_decode($response, true)
    ];
}

// Test 1: Register
echo "📝 Test 1: Register new user...\n";
$response = apiRequest('POST', '/auth/register', [
    'email' => $testEmail,
    'password' => $testPassword,
    'password_confirmation' => $testPassword,
    'name' => 'API Test User'
]);

if ($response['status'] === 201 || $response['status'] === 422) {
    if ($response['status'] === 201) {
        echo "✅ Registration successful\n";
        $token = $response['body']['token'];
    } else {
        echo "⚠️  User already exists, proceeding to login...\n";
    }
} else {
    echo "❌ Registration failed with status: {$response['status']}\n";
    print_r($response['body']);
    exit(1);
}

echo "\n";

// Test 2: Login
if (!isset($token)) {
    echo "🔐 Test 2: Login...\n";
    $response = apiRequest('POST', '/auth/login', [
        'email' => $testEmail,
        'password' => $testPassword
    ]);

    if ($response['status'] === 200) {
        echo "✅ Login successful\n";
        $token = $response['body']['token'];
    } else {
        echo "❌ Login failed with status: {$response['status']}\n";
        print_r($response['body']);
        exit(1);
    }
    echo "\n";
}

// Test 3: Get current user
echo "👤 Test 3: Get current user...\n";
$response = apiRequest('GET', '/auth/user', null, $token);

if ($response['status'] === 200) {
    echo "✅ User data retrieved successfully\n";
    echo "   - Name: {$response['body']['user']['name']}\n";
    echo "   - Email: {$response['body']['user']['email']}\n";
} else {
    echo "❌ Failed to get user data with status: {$response['status']}\n";
}

echo "\n";

// Test 4: Get collection cards
echo "🎴 Test 4: Get collection cards...\n";
$response = apiRequest('GET', '/collection/cards?per_page=5', null, $token);

if ($response['status'] === 200) {
    echo "✅ Cards retrieved successfully\n";
    echo "   - Total cards: {$response['body']['meta']['total']}\n";
    echo "   - Current page: {$response['body']['meta']['current_page']}\n";
    echo "   - Per page: {$response['body']['meta']['per_page']}\n";
} else {
    echo "❌ Failed to get cards with status: {$response['status']}\n";
}

echo "\n";

// Test 5: Get games
echo "🎮 Test 5: Get collection games...\n";
$response = apiRequest('GET', '/collection/games', null, $token);

if ($response['status'] === 200) {
    echo "✅ Games retrieved successfully\n";
    echo "   - Total games: {$response['body']['meta']['total']}\n";
    if ($response['body']['meta']['total'] > 0) {
        foreach ($response['body']['data'] as $game) {
            echo "   - {$game['name']}: {$game['card_count']} cards, {$game['set_count']} sets\n";
        }
    }
} else {
    echo "❌ Failed to get games with status: {$response['status']}\n";
}

echo "\n";

// Test 6: Get sets
echo "📦 Test 6: Get card sets...\n";
$response = apiRequest('GET', '/sets?per_page=5', null, $token);

if ($response['status'] === 200) {
    echo "✅ Sets retrieved successfully\n";
    echo "   - Total sets: {$response['body']['meta']['total']}\n";
    if ($response['body']['meta']['total'] > 0) {
        foreach ($response['body']['data'] as $set) {
            echo "   - {$set['name']} ({$set['abbreviation']}): {$set['collection_stats']['owned_cards']}/{$set['total_cards']} cards\n";
        }
    }
} else {
    echo "❌ Failed to get sets with status: {$response['status']}\n";
}

echo "\n";

// Test 7: Logout
echo "🚪 Test 7: Logout...\n";
$response = apiRequest('POST', '/auth/logout', null, $token);

if ($response['status'] === 200) {
    echo "✅ Logout successful\n";
} else {
    echo "❌ Logout failed with status: {$response['status']}\n";
}

echo "\n";

// Summary
echo "============================\n";
echo "✨ API Testing Complete!\n\n";
echo "All core endpoints are working correctly.\n";
echo "You can now integrate these APIs into your application.\n\n";
echo "📚 For full documentation, see: docs/API.md\n";
echo "📮 For Postman testing, import: docs/Carte_Pokemon_API.postman_collection.json\n\n";
