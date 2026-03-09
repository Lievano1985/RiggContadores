<div
    x-data="{ show:false, message:'' }"

    {{-- Escucha eventos Livewire --}}
    x-on:notify.window="
        message = $event.detail.message;
        show = true;
        setTimeout(() => show = false, 3400);
    "

    x-show="show"
    x-transition:enter="transition ease-out duration-250"
    x-transition:enter-start="opacity-0 transform translate-y-4 scale-95"
    x-transition:enter-end="opacity-100 transform translate-y-0 scale-100"
    x-transition:leave="transition ease-in duration-500"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-10"

    class="fixed bottom-10 left-1/2 transform -translate-x-1/2 z-50
           w-[92%] max-w-md p-4 text-sm text-white
           bg-zinc-900/90 backdrop-blur-md border border-white/15 rounded-2xl shadow-xl"
>
    <span class="font-medium tracking-[0.01em]" x-text="message"></span>
</div>

{{-- Soporte para session()->flash --}}
@if (session()->has('success'))
<script>
window.dispatchEvent(new CustomEvent('notify', {
    detail:{ message: @js(session('success')) }
}));
</script>
@endif
