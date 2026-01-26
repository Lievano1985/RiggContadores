<div
    x-data="{ show:false, message:'' }"

    {{-- Escucha eventos Livewire --}}
    x-on:notify.window="
        message = $event.detail.message;
        show = true;
        setTimeout(() => show = false, 3000);
    "

    x-show="show"
    x-transition:leave="transition ease-in duration-500"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-10"

    class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 
           w-full max-w-sm p-4 text-sm text-green-800 
           bg-green-200 rounded-lg shadow-lg 
           dark:bg-green-200 dark:text-green-900"
>
    <span x-text="message"></span>
</div>

{{-- Soporte para session()->flash --}}
@if (session()->has('success'))
<script>
window.dispatchEvent(new CustomEvent('notify', {
    detail:{ message: @js(session('success')) }
}));
</script>
@endif
