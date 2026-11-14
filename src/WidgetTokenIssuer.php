<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk;

use Am2tec\SupportSdk\Exceptions\SupportApiException;

/**
 * Emite o token do widget (POST /api/v1/widget/token). Server-side only: usa app key/secret.
 */
class WidgetTokenIssuer
{
    public function __construct(private readonly SupportClient $client) {}

    /**
     * @param  array{external_user_id: int|string, name: string, email?: ?string, phone?: ?string}  $user
     * @param  array{external_id: int|string, name: string, slug?: ?string, domain?: ?string}  $tenant
     */
    public function mint(array $user, array $tenant, array $context = []): string
    {
        $payload = [
            'external_user_id' => (string) ($user['external_user_id'] ?? ''),
            'name' => (string) ($user['name'] ?? ''),
            'tenant' => array_filter([
                'external_id' => (string) ($tenant['external_id'] ?? ''),
                'name' => (string) ($tenant['name'] ?? ''),
                'slug' => $tenant['slug'] ?? null,
                'domain' => $tenant['domain'] ?? null,
            ], fn ($v) => $v !== null),
        ];

        foreach (['email', 'phone'] as $optional) {
            if (! empty($user[$optional])) {
                $payload[$optional] = (string) $user[$optional];
            }
        }

        if ($context !== []) {
            $payload['context'] = $context;
        }

        $token = $this->client->post('widget/token', $payload)['token'] ?? null;

        if (! is_string($token) || $token === '') {
            throw new SupportApiException('support_api returned no widget token');
        }

        return $token;
    }
}
