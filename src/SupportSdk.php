<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk;

class SupportSdk
{
    private SupportClient $client;

    public function __construct(
        string $baseUrl,
        string $appKey,
        string $appSecret,
        int $timeout = 10,
        int $retryTimes = 2,
        int $retryDelayMs = 200,
    ) {
        $this->client = new SupportClient($baseUrl, $appKey, $appSecret, $timeout, $retryTimes, $retryDelayMs);
    }

    public function mintWidgetToken(array $user, array $tenant, array $context = []): string
    {
        return (new WidgetTokenIssuer($this->client))->mint($user, $tenant, $context);
    }

    public function client(): SupportClient
    {
        return $this->client;
    }
}
