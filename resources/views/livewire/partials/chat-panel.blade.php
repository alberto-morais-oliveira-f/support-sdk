<div class="support-widget__chat" wire:poll.{{ $pollingMs }}ms="poll">
    <div style="max-height:260px;overflow-y:auto;margin-bottom:.5rem">
        @forelse ($messages as $message)
            @php($mine = ($message['direction'] ?? '') === 'inbound')
            <div wire:key="msg-{{ $message['id'] }}" style="display:flex;justify-content:{{ $mine ? 'flex-end' : 'flex-start' }};margin-bottom:.35rem">
                <span style="max-width:80%;padding:.35rem .6rem;border-radius:.6rem;font-size:.85rem;background:{{ $mine ? '#dcf8c6' : '#f1f1f1' }}">{{ $message['body'] }}</span>
            </div>
        @empty
            <p style="font-size:.85rem;color:#6c757d">{{ __('Envie sua mensagem. Responderemos por aqui.') }}</p>
        @endforelse
    </div>

    <form wire:submit="sendMessage" style="display:flex;gap:.4rem">
        <input type="text" wire:model="draft" placeholder="{{ __('Digite sua mensagem') }}" class="form-control form-control-sm" style="flex:1">
        <button type="submit" class="btn btn-sm btn-success">{{ __('Enviar') }}</button>
    </form>
</div>
