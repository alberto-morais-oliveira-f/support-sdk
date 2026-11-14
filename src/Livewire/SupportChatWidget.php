<?php

declare(strict_types=1);

namespace Am2tec\SupportSdk\Livewire;

use Am2tec\SupportSdk\Exceptions\SupportApiException;
use Am2tec\SupportSdk\WidgetApi;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class SupportChatWidget extends Component
{
    public const STATE_IDLE = 'idle';

    public const STATE_OTP = 'otp';

    public const STATE_CHAT = 'chat';

    public const STATE_NOTIFY = 'notify';

    private const CONFIG_TIMEOUT = 3;

    #[Locked]
    public string $token = '';

    #[Locked]
    public string $apiUrl = '';

    #[Locked]
    public array $context = [];

    public bool $open = false;

    public string $state = self::STATE_IDLE;

    public string $phone = '';

    public bool $codeSent = false;

    public string $code = '';

    public ?int $conversationId = null;

    public array $messages = [];

    public string $draft = '';

    public string $question = '';

    public bool $notified = false;

    public ?string $error = null;

    public int $pollingMs = 3000;

    #[Locked]
    public bool $configLoaded = false;

    public function mount(string $token, ?string $apiUrl = null, array $context = []): void
    {
        $this->token = $token;
        $this->apiUrl = (string) ($apiUrl ?: config('support.api_url') ?: config('support.url'));
        $this->context = $context;
        // Sem chamada de rede aqui: não bloqueia a página host se a API estiver fora.
    }

    /** Config é opcional e carregada uma vez, sob demanda, com timeout curto. */
    private function loadConfig(): void
    {
        if ($this->configLoaded) {
            return;
        }

        $this->configLoaded = true;

        try {
            $interval = (int) ($this->api(self::CONFIG_TIMEOUT)->config()['polling_interval_ms'] ?? 0);
            if ($interval > 0) {
                $this->pollingMs = $interval;
            }
        } catch (SupportApiException) {
            // segue com o padrão de 3s.
        }
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;
        $this->error = null;

        if ($this->open && $this->state === self::STATE_IDLE) {
            $this->startChat();
        }
    }

    /** Tenta abrir a conversa; 409 = telefone não verificado → OTP. */
    public function startChat(): void
    {
        $this->error = null;

        try {
            $data = $this->api()->openConversation($this->context);
        } catch (SupportApiException $e) {
            if ($e->status === 409) {
                $this->state = self::STATE_OTP;

                return;
            }
            $this->error = $e->getMessage();

            return;
        }

        $this->conversationId = (int) ($data['conversation']['id'] ?? 0) ?: null;
        $this->messages = array_values($data['messages'] ?? []);
        $this->state = self::STATE_CHAT;
        $this->loadConfig();
    }

    public function requestCode(): void
    {
        $this->error = null;

        if (trim($this->phone) === '') {
            $this->error = __('Informe seu número com DDD.');

            return;
        }

        try {
            $this->api()->requestOtp($this->phone);
            $this->codeSent = true;
        } catch (SupportApiException $e) {
            $this->error = $e->getMessage();
        }
    }

    public function confirmCode(): void
    {
        $this->error = null;

        try {
            $this->api()->confirmOtp($this->phone, trim($this->code));
        } catch (SupportApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->code = '';
        $this->codeSent = false;
        $this->startChat();
    }

    public function skipVerification(): void
    {
        $this->error = null;
        $this->state = self::STATE_NOTIFY;
    }

    public function backToIdle(): void
    {
        $this->error = null;
        $this->notified = false;
        $this->state = self::STATE_IDLE;
        $this->startChat();
    }

    public function sendMessage(): void
    {
        $body = trim($this->draft);

        if ($body === '' || $this->conversationId === null) {
            return;
        }

        $this->error = null;

        try {
            $this->api()->sendMessage($this->conversationId, $body);
            $this->draft = '';
        } catch (SupportApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->poll();
    }

    public function poll(): void
    {
        if ($this->state !== self::STATE_CHAT || $this->conversationId === null) {
            return;
        }

        $last = (int) (collect($this->messages)->max('id') ?? 0);

        try {
            $new = $this->api()->messages($this->conversationId, $last ?: null)['messages'] ?? [];
        } catch (SupportApiException) {
            return;
        }

        $known = collect($this->messages)->pluck('id')->all();
        foreach ($new as $message) {
            if (! in_array($message['id'] ?? null, $known, true)) {
                $this->messages[] = $message;
            }
        }
    }

    public function sendNotice(): void
    {
        $this->error = null;

        if (trim($this->question) === '') {
            $this->error = __('Descreva sua dúvida para avisar o suporte.');

            return;
        }

        try {
            $this->api()->notify(trim($this->question), $this->context);
        } catch (SupportApiException $e) {
            $this->error = $e->getMessage();

            return;
        }

        $this->question = '';
        $this->notified = true;
    }

    public function render(): View
    {
        return view('support-sdk::livewire.support-chat-widget');
    }

    private function api(?int $timeout = null): WidgetApi
    {
        $default = (int) config('support.timeout', 10);

        return new WidgetApi($this->apiUrl, $this->token, $timeout !== null ? min($timeout, $default) : $default);
    }
}
