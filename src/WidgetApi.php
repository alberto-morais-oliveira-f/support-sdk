<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk;

use Am2tec\SupportSdk\Exceptions\SupportApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente do /api/v1 do support_api autenticado com o token do widget (Bearer).
 * Usado pelo componente Livewire; nunca usa app key/secret.
 */
class WidgetApi
{
    public const MSG_UNAVAILABLE = 'Não foi possível conectar ao suporte. Tente novamente.';

    public function __construct(
        private readonly string $baseUrl,
        private readonly string $token,
        private readonly int $timeout = 10,
    ) {}

    public function config(): array
    {
        return $this->send('get', 'widget/config');
    }

    public function requestOtp(string $phone): array
    {
        return $this->send('post', 'otp/request', ['phone' => $phone]);
    }

    public function confirmOtp(string $phone, string $code): array
    {
        return $this->send('post', 'otp/confirm', ['phone' => $phone, 'code' => $code]);
    }

    public function openConversation(array $context = []): array
    {
        return $this->send('post', 'conversations/open', $context === [] ? [] : ['context' => $context]);
    }

    public function messages(int $conversationId, ?int $afterId = null): array
    {
        return $this->send('get', "conversations/{$conversationId}/messages", $afterId ? ['after_id' => $afterId] : []);
    }

    public function sendMessage(int $conversationId, string $body): array
    {
        return $this->send('post', "conversations/{$conversationId}/messages", ['body' => $body]);
    }

    public function notify(string $question, array $context = []): array
    {
        return $this->send('post', 'notify', array_filter(['question' => $question, 'context' => $context ?: null]));
    }

    private function send(string $method, string $path, array $data = []): array
    {
        $url = rtrim($this->baseUrl, '/').'/api/v1/'.ltrim($path, '/');

        try {
            $response = Http::acceptJson()->asJson()->withToken($this->token)->timeout($this->timeout)->{$method}($url, $data);
        } catch (ConnectionException $e) {
            // Nunca expõe detalhes de cURL/host ao usuário final.
            report($e);

            throw new SupportApiException(__(self::MSG_UNAVAILABLE));
        }

        if ($response->failed()) {
            $body = (array) $response->json();
            $status = $response->status();
            // Em 5xx só confiamos no `error` deliberado da API; `message` costuma ser texto
            // genérico/debug do framework (inglês, exceção, host).
            $message = $status >= 500
                ? ($body['error'] ?? null)
                : ($body['error'] ?? $body['message'] ?? null);

            if (isset($body['errors']) && is_array($body['errors'])) {
                $first = collect($body['errors'])->flatten()->first();
                $message = is_string($first) ? $first : $message;
            }

            if (! is_string($message) || trim($message) === '') {
                $message = __(self::MSG_UNAVAILABLE);
            }

            throw new SupportApiException($message, $status, $body);
        }

        return (array) $response->json();
    }
}
