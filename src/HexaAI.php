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

    public function __construct(string $apiKey, ?string $baseUrl = null, int $timeout = 30)
    {
        $this->apiKey = $apiKey;
        $this->baseUrl = $baseUrl ?? 'https://api.shipflowstore.store';
        $this->timeout = $timeout;
        $this->chat = new Chat($this);
    }

    public function request(string $method, string $endpoint, ?array $data = null): array
    {
        $url = $this->baseUrl . $endpoint;

        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'User-Agent: hexgenerative-php/1.0.0'
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

    public function getApiKey(): string
    {
        return $this->apiKey;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }
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

    /**
     * Create a chat completion
     */
    public function create(array $options): ChatCompletion
    {
        $payload = [
            'model' => $options['model'] ?? 'hexa-balanced',
            'messages' => $options['messages'],
            'temperature' => $options['temperature'] ?? 0.7,
        ];

        if (isset($options['max_tokens'])) {
            $payload['max_tokens'] = $options['max_tokens'];
        }
        if (isset($options['task'])) {
            $payload['task'] = $options['task'];
        }
        if (isset($options['optimize_for'])) {
            $payload['optimize_for'] = $options['optimize_for'];
        }
        if (isset($options['auto_select'])) {
            $payload['auto_select'] = $options['auto_select'];
        }

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

class HexaAIException extends \Exception
{
    private int $statusCode;

    public function __construct(string $message, int $statusCode = 0)
    {
        parent::__construct($message);
        $this->statusCode = $statusCode;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
