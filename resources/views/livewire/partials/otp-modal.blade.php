<div class="support-widget__otp">
    <h6 style="font-weight:600">{{ __('Cadastre seu número para suporte personalizado') }}</h6>

    @if (! $codeSent)
        <input type="tel" wire:model="phone" wire:keydown.enter="requestCode" placeholder="{{ __('(11) 99999-9999') }}" class="form-control form-control-sm mb-2" style="width:100%">
        <button type="button" wire:click="requestCode" class="btn btn-sm btn-success">{{ __('Enviar código por WhatsApp') }}</button>
    @else
        <p style="font-size:.85rem">{{ __('Enviamos um código por WhatsApp. Digite-o abaixo.') }}</p>
        <input type="text" inputmode="numeric" wire:model="code" wire:keydown.enter="confirmCode" placeholder="{{ __('Código') }}" class="form-control form-control-sm mb-2" style="width:100%">
        <button type="button" wire:click="confirmCode" class="btn btn-sm btn-success">{{ __('Confirmar') }}</button>
    @endif

    <div style="margin-top:.75rem">
        <a href="#" wire:click.prevent="skipVerification" style="font-size:.8rem">{{ __('Continuar sem verificar') }}</a>
    </div>
</div>
