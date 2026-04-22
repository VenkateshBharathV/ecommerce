<?php

class OpenAIService
{
    private string $apiKey;
    private string $model;
    private string $endpoint;
    private int $timeout;

    public function __construct(array $config = [])
    {
        $openai = $config['openai'] ?? [];

        $this->apiKey = (string) ($openai['api_key'] ?? '');
        $this->model = (string) ($openai['model'] ?? 'gpt-4o-mini');
        $this->endpoint = (string) ($openai['endpoint'] ?? 'https://api.openai.com/v1/chat/completions');
        $this->timeout = (int) ($openai['timeout'] ?? 20);
    }

    public function isConfigured(): bool
    {
        return $this->apiKey !== '' && $this->apiKey !== 'YOUR_OPENAI_API_KEY';
    }

    public function chat(array $messages): array
    {
        if (!$this->isConfigured()) {
            return [
                'success' => false,
                'reply' => 'AI assistant is running in local fallback mode. Add your OpenAI API key in includes/config.php to enable richer answers.',
            ];
        }

        $payload = json_encode([
            'model' => $this->model,
            'messages' => $messages,
            'temperature' => 0.4,
        ]);

        if ($payload === false) {
            return [
                'success' => false,
                'reply' => 'Unable to encode the AI request payload.',
            ];
        }

        $ch = curl_init($this->endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_TIMEOUT => $this->timeout,
        ]);

        $response = curl_exec($ch);
        $statusCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            error_log('OpenAI request failed: ' . $curlError);

            return [
                'success' => false,
                'reply' => 'The AI service is temporarily unavailable. You can still search products and track orders locally.',
            ];
        }

        $decoded = json_decode($response, true);
        $content = trim((string) ($decoded['choices'][0]['message']['content'] ?? ''));

        if ($statusCode >= 400 || $content === '') {
            error_log('OpenAI API error: ' . $response);

            return [
                'success' => false,
                'reply' => 'The AI service could not generate a response right now.',
            ];
        }

        return [
            'success' => true,
            'reply' => $content,
        ];
    }
}
