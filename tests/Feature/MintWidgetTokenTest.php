<?php

declare(strict_types=1);

use Am2tec\SupportSdk\Exceptions\SupportApiException;
use Am2tec\SupportSdk\SupportClient;
use Am2tec\SupportSdk\SupportSdk;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

it('mints widget token via support_api', function () {
    Http::fake(['*/api/v1/widget/token' => Http::response(['token' => 'abc', 'expires_at' => now()->toIso8601String()], 200)]);

    $sdk = new SupportSdk(baseUrl: 'https://support.test', appKey: 'k', appSecret: 's');

    expect($sdk->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'Academia']))->toBe('abc');
});

it('sends credentials headers and the contract payload', function () {
    Http::fake(['*' => Http::response(['token' => 'tok', 'expires_at' => 'x'], 200)]);

    $sdk = new SupportSdk('https://support.test/', 'key', 'secret');
    $sdk->mintWidgetToken(
        ['external_user_id' => 7, 'name' => 'Ana', 'email' => 'a@x.com', 'phone' => '5511999999999', 'ignored' => 'z'],
        ['external_id' => 9, 'name' => 'Dojo', 'slug' => 'dojo', 'domain' => 'dojo.test'],
        ['plan' => 'pro'],
    );

    Http::assertSent(function (Request $r) {
        return $r->url() === 'https://support.test/api/v1/widget/token'
            && $r->method() === 'POST'
            && $r->hasHeader('X-App-Key', 'key')
            && $r->hasHeader('X-App-Secret', 'secret')
            && $r['external_user_id'] === '7'
            && $r['name'] === 'Ana'
            && $r['email'] === 'a@x.com'
            && $r['phone'] === '5511999999999'
            && $r['tenant'] === ['external_id' => '9', 'name' => 'Dojo', 'slug' => 'dojo', 'domain' => 'dojo.test']
            && $r['context'] === ['plan' => 'pro']
            && ! isset($r['ignored']);
    });
});

it('throws on error response', function () {
    Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);

    (new SupportSdk('https://support.test', 'k', 's'))
        ->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'T']);
})->throws(SupportApiException::class, 'Unauthorized');

it('throws when response has no token', function () {
    Http::fake(['*' => Http::response(['foo' => 'bar'], 200)]);

    (new SupportSdk('https://support.test', 'k', 's'))
        ->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'T']);
})->throws(SupportApiException::class);

it('resolves from container using config and exposes client', function () {
    Http::fake(['*' => Http::response(['token' => 'from-container', 'expires_at' => 'x'], 200)]);

    $sdk = app(SupportSdk::class);

    expect($sdk->client())->toBeInstanceOf(SupportClient::class)
        ->and($sdk->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'T']))
        ->toBe('from-container');

    Http::assertSent(fn (Request $r) => str_starts_with($r->url(), 'https://support.test/api/v1/'));
});

it('retries 5xx', function () {
    Http::fake(['*' => Http::sequence()
        ->push(['message' => 'boom'], 503)
        ->push(['token' => 'ok', 'expires_at' => 'x'], 200)]);

    $sdk = new SupportSdk('https://support.test', 'k', 's', retryTimes: 2, retryDelayMs: 1);

    expect($sdk->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'T']))->toBe('ok');
    Http::assertSentCount(2);
});

it('does not retry 4xx', function () {
    Http::fake(['*' => Http::response(['message' => 'Unauthorized'], 401)]);

    $sdk = new SupportSdk('https://support.test', 'k', 's', retryTimes: 2, retryDelayMs: 1);

    try {
        $sdk->mintWidgetToken(['external_user_id' => '1', 'name' => 'A'], ['external_id' => 't', 'name' => 'T']);
    } catch (SupportApiException) {
    }

    Http::assertSentCount(1);
});
