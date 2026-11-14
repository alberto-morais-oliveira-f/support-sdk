<div class="support-widget__notify">
    @if ($notified)
        <p style="font-size:.9rem">{{ __('Aviso enviado! O suporte entrará em contato.') }}</p>
    @else
        <textarea wire:model="question" rows="4" placeholder="{{ __('Descreva sua dúvida') }}" class="form-control form-control-sm mb-2" style="width:100%"></textarea>
        <button type="button" wire:click="sendNotice" class="btn btn-sm btn-success">{{ __('Enviar aviso ao suporte') }}</button>
    @endif

    <div style="margin-top:.75rem">
        <a href="#" wire:click.prevent="backToIdle" style="font-size:.8rem">{{ __('Verificar meu número') }}</a>
    </div>
</div>
