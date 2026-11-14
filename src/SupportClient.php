<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk;

use Am2tec\SupportSdk\Exceptions\SupportApiException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;

/**
 * Cliente HTTP autenticado (X-App-Key / X-App-Secret) para o /api/v1 do support_api.
 */
class SupportClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $appKey,
        private readonly string $appSecret,
        private readonly int $timeout = 10,
        private readonly int $retryTimes = 2,
        private readonly int $retryDelayMs = 200,
    ) {}

    public function post(string $path, array $payload = []): array
    {
        return $this->send('post', $path, $payload);
    }

    public function get(string $path, array $query = []): array
    {
        return $this->send('get', $path, $query);
    }

    private function send(string $method, string $path, array $data): array
    {
        try {
            $response = $this->request()->{$method}($this->url($path), $data);
        } catch (ConnectionException $e) {
            throw new SupportApiException('support_api unreachable: '.$e->getMessage(), null, []);
        }

        if ($response->failed()) {
            $body = (array) $response->json();

            throw new SupportApiException(
                (string) ($body['message'] ?? 'support_api request failed'),
                $response->status(),
                $body,
            );
        }

        return (array) $response->json();
    }

    private function request(): PendingRequest
    {
        $request = Http::acceptJson()
            ->asJson()
            ->withHeaders(['X-App-Key' => $this->appKey, 'X-App-Secret' => $this->appSecret])
            ->timeout($this->timeout);

        // Só retenta falha de conexão/5xx; 4xx (credencial/validação) falha direto.
        return $this->retryTimes > 0
            ? $request->retry($this->retryTimes, $this->retryDelayMs, fn ($e) => $e instanceof ConnectionException
                || ($e instanceof RequestException && $e->response->serverError()), throw: false)
            : $request;
    }

    private function url(string $path): string
    {
        return rtrim($this->baseUrl, '/').'/api/v1/'.ltrim($path, '/');
    }
}
