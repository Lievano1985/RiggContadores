<div wire:poll.15s class="flex items-center gap-3">
    @if ($visible)
        <a href="{{ route('mis-notificaciones') }}"
            class="relative inline-flex h-7 w-7 items-center justify-center rounded-full border border-stone-200 bg-white text-stone-700 shadow-sm transition hover:border-amber-500 hover:text-amber-600 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-amber-500 dark:hover:text-amber-400"
            aria-label="Notificaciones" wire:navigate>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9a6 6 0 1 0-12 0v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.08 5.454 1.31m5.715 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>

            @if ($total > 0)
                <span class="absolute -right-1 -top-1 inline-flex min-h-5 min-w-5 items-center justify-center rounded-full bg-rose-600 px-1 text-[10px] font-semibold text-white">
                    {{ $total > 99 ? '99+' : $total }}
                </span>
            @endif
        </a>
    @endif
</div>
