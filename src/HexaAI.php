<?php
/**
 * Hexa Generative AI - PHP SDK
 * Official PHP client for Hexa AI API
 */

namespace HexaGenerative;

class HexaAI
{
    private string $apiKey;
    private string $baseUrl;
    private int $timeout;

    public Chat $chat;
    public Agent $agent;
    public RAG $rag;
    public Context $context;
    public Code $code;
    public Tools $tools;
    public DisasterAlert $disaster;
    public Audio $audio;

    public function __construct(string $apiKey, ?string $baseUrl = null, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? 'https://api.shipflowstore.store';
        $this->timeout = $timeout;
        $this->chat = new Chat($this);
        $this->agent = new Agent($this);
        $this->rag = new RAG($this);
        $this->context = new Context($this);
        $this->code = new Code($this);
        $this->tools = new Tools($this);
        $this->disaster = new DisasterAlert($this);
        $this->audio = new Audio($this);
    }

    public function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: hexgenerative-php/1.1.0'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            }
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new HexaAIException("Request failed: " . $error);
        }

        $result = json_decode($response, true);

        if ($httpCode !== 200) {
            $message = $result['error']['message'] ?? 'Request failed';
            throw new HexaAIException($message, $httpCode);
        }

        return $result;
    }

    public function getApiKey(): string { return $this->apiKey; }
    public function getBaseUrl(): string { return $this->baseUrl; }
}

class Chat
{
    private HexaAI $client;
    public Completions $completions;

    public function __construct(HexaAI $client)
    {
        $this->client = $client;
        $this->completions = new Completions($client);
    }
}

class Completions
{
    private HexaAI $client;

    public function __construct(HexaAI $client)
    {
        $this->client = $client;
    }

    public function create(array $options): ChatCompletion
    {
        $payload = [
            'model' => $options['model'] ?? 'hexa-balanced',
            'messages' => $options['messages'],
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['max_tokens'])) $payload['max_tokens'] = $options['max_tokens'];
        if (isset($options['task'])) $payload['task'] = $options['task'];
        if (isset($options['optimize_for'])) $payload['optimize_for'] = $options['optimize_for'];
        if (isset($options['auto_select'])) $payload['auto_select'] = $options['auto_select'];

        $response = $this->client->request('POST', '/v1/chat/completions', $payload);
        return new ChatCompletion($response);
    }
}

class ChatCompletion
{
    public string $id;
    public string $object;
    public int $created;
    public string $model;
    public array $choices;
    public array $usage;
    public string $provider;
    public ?array $hexaMetadata;

    public function __construct(array $data)
    {
        $this->id = $data['id'] ?? '';
        $this->object = $data['object'] ?? 'chat.completion';
        $this->created = $data['created'] ?? 0;
        $this->model = $data['model'] ?? '';
        $this->choices = $data['choices'] ?? [];
        $this->usage = $data['usage'] ?? [];
        $this->provider = $data['provider'] ?? 'Hexa AI';
        $this->hexaMetadata = $data['hexa_metadata'] ?? null;
    }

    public function getContent(): string
    {
        return $this->choices[0]['message']['content'] ?? '';
    }
}

// --- Agent ---
class Agent
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /** Run an agentic task */
    public function run(array $options): array
    {
        return $this->client->request('POST', '/v1/agent/run', [
            'task' => $options['task'],
            'model' => $options['model'] ?? 'hexa-ultra',
            'servers' => $options['servers'] ?? null,
            'max_steps' => $options['max_steps'] ?? 10,
        ]);
    }
}

// --- RAG ---
class RAG
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /** Upload document to knowledge base */
    public function upload(string $title, string $content, ?array $metadata = null): array
    {
        return $this->client->request('POST', '/v1/rag/upload', [
            'title' => $title,
            'content' => $content,
            'metadata' => $metadata,
        ]);
    }

    /** Search knowledge base */
    public function search(string $query, int $topK = 5): array
    {
        return $this->client->request('POST', '/v1/rag/search', [
            'query' => $query,
            'top_k' => $topK,
        ]);
    }
}

// --- Context ---
class Context
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /** Create a 300K token context session */
    public function create(?string $systemPrompt = null): array
    {
        return $this->client->request('POST', '/v1/context/create', [
            'system_prompt' => $systemPrompt ?? 'You are a helpful assistant.',
        ]);
    }

    /** Add message to context session */
    public function add(string $sessionId, array $message): array
    {
        return $this->client->request('POST', '/v1/context/add', [
            'session_id' => $sessionId,
            'message' => $message,
        ]);
    }
}

// --- Code ---
class Code
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /** Execute Python code in sandbox */
    public function execute(string $code, ?array $context = null): array
    {
        return $this->client->request('POST', '/v1/code/execute', [
            'code' => $code,
            'context' => $context,
        ]);
    }
}

// --- Tools ---
class Tools
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    public function list(): array
    {
        return $this->client->request('GET', '/v1/tools');
    }
}

// --- Disaster Alert ---
class DisasterAlert
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /** 
     * Check for emergencies in area (earthquake, tornado, etc).
     * Returns siren instructions for frontend.
     */
    public function checkEmergency(float $lat, float $lon, float $radius = 111): array
    {
        return $this->client->request('POST', '/v1/emergency/check', [
            'latitude' => $lat,
            'longitude' => $lon,
            'radius_km' => $radius,
        ]);
    }
}

class HexaAIException extends \Exception
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 0)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int { return $this->statusCode; }
}

// --- Audio ---
class Audio
{
    private HexaAI $client;

    public function __construct(HexaAI $client) { $this->client = $client; }

    /**
     * Generate speech from text.
     * Returns raw WAV binary string.
     */
    public function speech(array $options): string
    {
        // Custom request logic for binary response
        $url = $this->client->getBaseUrl() . '/v1/audio/speech';
        $payload = [
            'input' => $options['input'],
            'model' => $options['model'] ?? 'hexa-tts',
            'voice' => $options['voice'] ?? 'tara',
            'speed' => $options['speed'] ?? 1.0,
            'stream' => $options['stream'] ?? false,
        ];

        $headers = [
            'Authorization: Bearer ' . $this->client->getApiKey(),
            'Content-Type: application/json',
            'User-Agent: hexgenerative-php/1.1.0'
        ];

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw new HexaAIException("Request failed: " . $error);
        }

        if ($httpCode !== 200) {
           // Attempt to decode error
           $json = json_decode($response, true);
           $msg = $json['error']['message'] ?? "Request failed ($httpCode)";
           throw new HexaAIException($msg, $httpCode);
        }

        return $response;
    }
}

