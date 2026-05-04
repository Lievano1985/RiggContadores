<div class="space-y-6">
    <div class="flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-stone-800 dark:text-stone-100">Mis notificaciones</h1>
            <p class="text-sm text-stone-500 dark:text-stone-400">Actividad interna del modulo de solicitudes.</p>
        </div>

        <div class="flex items-center gap-2">
            <span class="inline-flex items-center rounded-full bg-amber-100 px-3 py-1 text-xs font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                Pendientes {{ $pendientes }}
            </span>
            <button type="button" wire:click="marcarTodasLeidas"
                class="rounded-md bg-stone-700 px-3 py-2 text-sm font-medium text-white hover:bg-stone-800 dark:bg-stone-600 dark:hover:bg-stone-500">
                Marcar todas como leidas
            </button>
        </div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <select wire:model.live="estado"
            class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-600 dark:bg-stone-800 dark:text-white">
            <option value="pendientes">Pendientes</option>
            <option value="leidas">Leidas</option>
            <option value="todas">Todas</option>
        </select>

        @if ($puedeVerOtras)
            <select wire:model.live="usuario"
                class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-600 dark:bg-stone-800 dark:text-white">
                <option value="">Usuario (todos)</option>
                @foreach ($usuariosFiltro as $item)
                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                @endforeach
            </select>

            <select wire:model.live="cliente"
                class="rounded-md border border-stone-300 bg-white px-3 py-2 text-sm dark:border-stone-600 dark:bg-stone-800 dark:text-white">
                <option value="">Cliente (todos)</option>
                @foreach ($clientesFiltro as $item)
                    <option value="{{ $item->id }}">{{ $item->nombre ?: $item->razon_social }}</option>
                @endforeach
            </select>
        @endif
    </div>

    <div class="overflow-hidden rounded-xl border border-stone-200 bg-white shadow-sm dark:border-stone-700 dark:bg-stone-900">
        <div class="divide-y divide-stone-200 dark:divide-stone-700">
            @forelse ($notificaciones as $notificacion)
                <div class="flex flex-col gap-3 p-4 md:flex-row md:items-start md:justify-between">
                    <div class="min-w-0 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <div class="font-medium text-stone-800 dark:text-stone-100">{{ $notificacion->titulo }}</div>
                            @if (is_null($notificacion->leida_at))
                                <span class="inline-flex items-center rounded-full bg-rose-100 px-2.5 py-1 text-xs font-medium text-rose-700 dark:bg-rose-900/40 dark:text-rose-300">
                                    Nueva
                                </span>
                            @endif
                        </div>

                        @if ($notificacion->mensaje)
                            <p class="text-sm text-stone-600 dark:text-stone-300">{{ $notificacion->mensaje }}</p>
                        @endif

                        <div class="text-xs text-stone-500 dark:text-stone-400">
                            Para:
                            {{ $notificacion->requerimiento?->destinatario?->name ?? $notificacion->solicitud?->responsable?->name ?? '-' }}
                        </div>

                        @if ($notificacion->solicitud?->cliente)
                            <div class="text-xs text-stone-500 dark:text-stone-400">
                                Cliente: {{ $notificacion->solicitud->cliente->nombre ?: $notificacion->solicitud->cliente->razon_social }}
                            </div>
                        @endif
                    </div>

                    <div class="flex items-center gap-2">
                        @if (is_null($notificacion->leida_at) && $notificacion->user_id === auth()->id())
                            <button type="button" wire:click="marcarLeida({{ $notificacion->id }})"
                                class="rounded-md border border-stone-300 px-3 py-2 text-sm text-stone-700 hover:bg-stone-50 dark:border-stone-600 dark:text-stone-200 dark:hover:bg-stone-800">
                                Marcar leida
                            </button>
                        @endif

                        @if ($notificacion->url)
                            <a href="{{ $notificacion->url }}"
                                class="rounded-md bg-amber-600 px-3 py-2 text-sm font-medium text-white hover:bg-amber-700">
                                Abrir
                            </a>
                        @endif
                    </div>
                </div>
            @empty
                <div class="p-6 text-sm text-stone-500 dark:text-stone-400">
                    No hay notificaciones para mostrar.
                </div>
            @endforelse
        </div>
    </div>

    <div>
        {{ $notificaciones->links() }}
    </div>
</div>
