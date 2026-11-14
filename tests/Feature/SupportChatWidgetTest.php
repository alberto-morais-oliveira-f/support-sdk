<?php

declare(strict_types=1);

use Am2tec\SupportSdk\Livewire\SupportChatWidget;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

function widget(array $fake)
{
    Http::fake($fake + ['support.test/api/v1/widget/config' => Http::response(['reverb' => null, 'polling_interval_ms' => 3000])]);

    return Livewire::test(SupportChatWidget::class, ['token' => 'tok', 'apiUrl' => 'https://support.test']);
}

it('opens OTP when API says phone is unverified', function () {
    widget(['support.test/api/v1/conversations/open' => Http::response(['message' => 'Valide'], 409)])
        ->call('toggle')
        ->assertSet('state', 'otp')
        ->assertSee('Cadastre seu número para suporte personalizado')
        ->assertSee('Continuar sem verificar');

    Http::assertSent(fn (Request $r) => $r->hasHeader('Authorization', 'Bearer tok'));
});

it('requests and confirms OTP then enters chat with polling', function () {
    widget([
        'support.test/api/v1/otp/request' => Http::response(['message' => 'ok']),
        'support.test/api/v1/otp/confirm' => Http::response(['phone' => '5511999999999']),
        'support.test/api/v1/conversations/open' => Http::sequence()
            ->push([], 409)
            ->push(['conversation' => ['id' => 7, 'status' => 'open'], 'messages' => [
                ['id' => 1, 'direction' => 'outbound', 'origin' => 'agent', 'body' => 'Olá!', 'status' => 'sent'],
            ]]),
    ])
        ->call('toggle')
        ->set('phone', '11999999999')
        ->call('requestCode')
        ->assertSet('codeSent', true)
        ->set('code', '123456')
        ->call('confirmCode')
        ->assertSet('state', 'chat')
        ->assertSet('conversationId', 7)
        ->assertSee('Olá!')
        ->assertSeeHtml('wire:poll.3000ms="poll"');

    Http::assertSent(fn (Request $r) => str_contains($r->url(), 'otp/confirm')
        && $r['phone'] === '11999999999' && $r['code'] === '123456');
});

it('shows API error on invalid OTP code and stays in otp state', function () {
    widget([
        'support.test/api/v1/conversations/open' => Http::response([], 409),
        'support.test/api/v1/otp/confirm' => Http::response(['message' => 'x', 'errors' => ['code' => ['Código inválido.']]], 422),
    ])
        ->call('toggle')
        ->set('code', '000000')
        ->call('confirmCode')
        ->assertSet('state', 'otp')
        ->assertSee('Código inválido.');
});

it('sends messages and polls only new ones with after_id', function () {
    $c = widget([
        'support.test/api/v1/conversations/open' => Http::response(['conversation' => ['id' => 7], 'messages' => [
            ['id' => 1, 'direction' => 'outbound', 'origin' => 'agent', 'body' => 'Olá!', 'status' => 'sent'],
        ]]),
        'support.test/api/v1/conversations/7/messages*' => Http::sequence()
            ->push(['message' => ['id' => 2]], 201)
            ->push(['messages' => [['id' => 2, 'direction' => 'inbound', 'origin' => 'widget', 'body' => 'Preciso de ajuda', 'status' => 'sent']]])
            ->push(['messages' => [['id' => 3, 'direction' => 'outbound', 'origin' => 'agent', 'body' => 'Claro!', 'status' => 'sent']]]),
    ])->call('toggle');

    $c->set('draft', 'Preciso de ajuda')->call('sendMessage')
        ->assertSet('draft', '')
        ->assertSee('Preciso de ajuda');

    $c->call('poll')->assertSee('Claro!');

    expect($c->get('messages'))->toHaveCount(3);
    Http::assertSent(fn (Request $r) => $r->method() === 'GET' && str_contains($r->url(), '/7/messages') && str_contains($r->url(), 'after_id=2'));
});

it('uses polling_interval_ms from widget config', function () {
    Http::fake([
        'support.test/api/v1/widget/config' => Http::response(['polling_interval_ms' => 5000]),
        'support.test/api/v1/conversations/open' => Http::response(['conversation' => ['id' => 1], 'messages' => []]),
    ]);

    Livewire::test(SupportChatWidget::class, ['token' => 'tok', 'apiUrl' => 'https://support.test'])
        ->call('toggle')
        ->assertSeeHtml('wire:poll.5000ms="poll"');
});

it('Path B: skip verification and send notice to support', function () {
    widget([
        'support.test/api/v1/conversations/open' => Http::response([], 409),
        'support.test/api/v1/notify' => Http::response(['notification' => ['id' => 9]]),
    ])
        ->call('toggle')
        ->call('skipVerification')
        ->assertSet('state', 'notify')
        ->assertSee('Enviar aviso ao suporte')
        ->set('question', 'Como emito boleto?')
        ->call('sendNotice')
        ->assertSet('notified', true)
        ->assertSee('Aviso enviado');

    Http::assertSent(fn (Request $r) => str_contains($r->url(), '/notify') && $r['question'] === 'Como emito boleto?');
});

it('shows error when notify fails', function () {
    widget([
        'support.test/api/v1/conversations/open' => Http::response([], 409),
        'support.test/api/v1/notify' => Http::response(['error' => 'Não foi possível avisar o suporte agora. Tente novamente.'], 502),
    ])
        ->call('toggle')->call('skipVerification')
        ->set('question', 'Oi')->call('sendNotice')
        ->assertSet('notified', false)
        ->assertSee('Não foi possível avisar o suporte agora');
});

it('does not allow tampering with the token from the client', function () {
    widget(['support.test/api/v1/conversations/open' => Http::response([], 409)])
        ->set('token', 'evil');
})->throws(Exception::class, 'Cannot update locked property');

it('does not call widget/config on mount (no network on page load)', function () {
    Http::fake();

    Livewire::test(SupportChatWidget::class, ['token' => 'tok', 'apiUrl' => 'https://support.test'])
        ->assertSet('open', false);

    Http::assertNothingSent();
});

it('loads widget/config lazily once, after chat opens', function () {
    Http::fake([
        'support.test/api/v1/widget/config' => Http::response(['polling_interval_ms' => 5000]),
        'support.test/api/v1/conversations/open' => Http::response(['conversation' => ['id' => 1], 'messages' => []]),
    ]);

    $c = Livewire::test(SupportChatWidget::class, ['token' => 'tok', 'apiUrl' => 'https://support.test'])
        ->call('toggle')->call('toggle')->call('toggle')
        ->assertSet('pollingMs', 5000);

    Http::assertSentCount(2);
});

it('skips config and shows fixed pt-BR error when API is unreachable', function () {
    $calls = 0;
    Http::fake(function () use (&$calls) {
        $calls++;

        throw new ConnectionException('cURL error 6: Could not resolve host: secret.internal');
    });

    Livewire::test(SupportChatWidget::class, ['token' => 'tok', 'apiUrl' => 'https://support.test'])
        ->call('toggle')
        ->assertSet('error', 'Não foi possível conectar ao suporte. Tente novamente.')
        ->assertDontSee('cURL')
        ->assertDontSee('secret.internal')
        ->assertDontSee('unreachable');

    expect($calls)->toBe(1);
});

it('shows fixed pt-BR copy on 5xx without API error, ignoring framework message', function () {
    widget(['support.test/api/v1/conversations/open' => Http::response(['message' => 'Server Error: SQLSTATE host db1'], 500)])
        ->call('toggle')
        ->assertSet('error', 'Não foi possível conectar ao suporte. Tente novamente.')
        ->assertDontSee('SQLSTATE');

    widget(['support.test/api/v1/conversations/open' => Http::response('<html>Bad Gateway</html>', 502)])
        ->call('toggle')
        ->assertSet('error', 'Não foi possível conectar ao suporte. Tente novamente.');
});
