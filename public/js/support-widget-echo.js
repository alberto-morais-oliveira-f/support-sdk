/**
 * Opcional: assina o canal Reverb e pede um poll imediato ao componente.
 * Requer window.Echo já configurado pelo host (config.reverb de /widget/config).
 * Sem Echo, o wire:poll do componente cobre a atualização.
 */
document.addEventListener('livewire:init', () => {
    if (!window.Echo) return;
    window.addEventListener('support-widget:subscribe', (event) => {
        const channel = event.detail && event.detail.channel;
        if (!channel) return;
        window.Echo.channel(channel).listen('.message.created', () => {
            document.querySelectorAll('[wire\\:id]').forEach((el) => {
                const c = window.Livewire.find(el.getAttribute('wire:id'));
                if (c && c.$wire && c.$wire.poll) c.$wire.poll();
            });
        });
    });
});
