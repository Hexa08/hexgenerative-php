<?php
/**
 * Test PHP SDK
 */

require_once __DIR__ . '/src/HexaAI.php';

use HexaGenerative\HexaAI;
use HexaGenerative\HexaAIException;

$client = new HexaAI(
    'hgx-hQzGz5V7RrXgz2bMkuUso0HQgMzMi0D8Pt859Lqx7Xg',
    'http://127.0.0.1:8001'
);

echo "==================================================\n";
echo "TESTING PHP SDK\n";
echo "==================================================\n\n";

// Test Tools
echo "[1] Tools List:\n";
try {
    $tools = $client->tools->list();
    $count = $tools['data']['count'] ?? 0;
    echo "   Found {$count} tools\n\n";
} catch (HexaAIException $e) {
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Test RAG
echo "[2] RAG Search:\n";
try {
    $results = $client->rag->search('platform');
    $count = count($results['data']['results'] ?? []);
    echo "   Found {$count} results\n\n";
} catch (HexaAIException $e) {
    echo "   Error: " . $e->getMessage() . "\n\n";
}

// Test Code
echo "[3] Code Execution:\n";
try {
    $result = $client->code->execute('print(42 * 3)');
    echo "   Result: " . ($result['data']['output'] ?? 'N/A') . "\n\n";
} catch (HexaAIException $e) {
    echo "   Error: " . $e->getMessage() . "\n\n";
}

echo "PHP SDK Test Complete.\n";
