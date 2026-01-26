# Hexa Generative AI - PHP SDK

Official PHP client for Hexa AI API.

## Installation

```bash
composer require hexgenerative/ai
```

## Quick Start

```php
<?php
require_once 'vendor/autoload.php';

use HexaGenerative\HexaAI;

$client = new HexaAI('hgx-your-api-key');

$response = $client->chat->completions->create([
    'model' => 'hexa-pro',
    'messages' => [
        ['role' => 'user', 'content' => 'Hello, who are you?']
    ]
]);

echo $response->getContent();
```

## Available Models

| Model | Description |
|-------|-------------|
| `hexa-instant` | Fastest responses |
| `hexa-balanced` | General purpose |
| `hexa-reasoning` | Complex analysis |
| `hexa-advanced` | Coding tasks |
| `hexa-pro` | Premium quality |

## Smart Routing

```php
// By task type
$response = $client->chat->completions->create([
    'task' => 'coding',
    'messages' => [
        ['role' => 'user', 'content' => 'Write a PHP function']
    ]
]);

// By optimization
$response = $client->chat->completions->create([
    'optimize_for' => 'speed',
    'messages' => [
        ['role' => 'user', 'content' => 'Quick answer please']
    ]
]);
```

## Error Handling

```php
use HexaGenerative\HexaAI;
use HexaGenerative\HexaAIException;

try {
    $response = $client->chat->completions->create([
        'model' => 'hexa-pro',
        'messages' => [['role' => 'user', 'content' => 'Hello']]
    ]);
} catch (HexaAIException $e) {
    echo "Error: " . $e->getMessage();
    echo "Status: " . $e->getStatusCode();
}
```

## Agentic Features

### Agent Tasks
```php
$result = $client->agent->run([
    'task' => 'Research AI trends and summarize',
    'model' => 'hexa-ultra',
]);
print_r($result);
```

### RAG (Knowledge Base)
```php
// Upload document
$client->rag->upload('Company Policy', 'Employees get 30 days leave...');

// Search
$results = $client->rag->search('leave policy');
```

### Context Management (300K Tokens)
```php
$session = $client->context->create('You are a helpful assistant');
$sessionId = $session['data']['session_id'];

$client->context->add($sessionId, ['role' => 'user', 'content' => 'Hello!']);
```

### Code Execution
```php
$result = $client->code->execute('print(sum(range(100)))');
echo $result['data']['output'];
```

### Tools
```php
$tools = $client->tools->list();
echo "Found " . $tools['data']['count'] . " tools";
```

## License

MIT
