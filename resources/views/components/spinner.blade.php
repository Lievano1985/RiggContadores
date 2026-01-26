@props(['target' => null])

<div
    x-data="{ show:false }"
    x-cloak

    x-show="show"
    x-on:spinner-on.window="show=true"
    x-on:spinner-off.window="show=false"
    x-transition.opacity

    @if($target)
        wire:loading
        wire:target="{{ $target }}"
    @endif

    class="fixed inset-0 z-[99999] flex items-center justify-center bg-black/50"
>
    <div class="flex h-screen flex-col items-center justify-center space-y-2">
        <svg
            class="animate-spin h-12 w-12 text-amber-500"
            xmlns="http://www.w3.org/2000/svg"
            fill="none"
            viewBox="0 0 24 24"
        >
            <circle class="opacity-25" cx="12" cy="12" r="10"
                stroke="currentColor" stroke-width="4"/>
            <path class="opacity-75" fill="currentColor"
                d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/>
        </svg>

        <p class="text-white dark:text-gray-200 text-sm">
            Procesando Petición...
        </p>
    </div>
</div>
