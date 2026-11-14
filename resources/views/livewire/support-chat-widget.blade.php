<div class="support-widget" style="position:fixed;bottom:1rem;right:1rem;z-index:1050;font-family:inherit">
    @if ($open)
        <div class="support-widget__panel" style="width:340px;max-width:92vw;background:#fff;border:1px solid #dee2e6;border-radius:.75rem;box-shadow:0 8px 24px rgba(0,0,0,.18);margin-bottom:.75rem;overflow:hidden">
            <div style="display:flex;justify-content:space-between;align-items:center;padding:.6rem .9rem;background:#25d366;color:#fff;font-weight:600">
                <span>{{ __('Suporte') }}</span>
                <button type="button" wire:click="toggle" aria-label="{{ __('Fechar') }}" style="background:none;border:0;color:#fff;font-size:1.2rem;cursor:pointer">&times;</button>
            </div>

            <div style="padding:.9rem">
                @if ($error)
                    <div role="alert" class="support-widget__error" style="color:#b02a37;font-size:.85rem;margin-bottom:.5rem">{{ $error }}</div>
                @endif

                @if ($state === 'otp')
                    @include('support-sdk::livewire.partials.otp-modal')
                @elseif ($state === 'chat')
                    @include('support-sdk::livewire.partials.chat-panel')
                @elseif ($state === 'notify')
                    @include('support-sdk::livewire.partials.notify-form')
                @else
                    <p style="font-size:.9rem">{{ __('Conectando ao suporte...') }}</p>
                    <button type="button" wire:click="startChat" class="btn btn-sm btn-primary">{{ __('Tentar novamente') }}</button>
                @endif
            </div>
        </div>
    @endif

    @include('support-sdk::livewire.partials.icon')
</div>
