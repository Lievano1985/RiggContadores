@php
    $eventos = $solicitud->historial;
    $respuestas = ['requerimiento_respondido', 'resultado_entregado'];
    $archivosFormulario = collect($solicitud->resumen_formulario)
        ->where('type', 'file')->pluck('value')->flatten()->filter();
    $archivosPorEvento = [];

    $agruparArchivo = function ($archivo, $eventosRelacionados, $titulo, $eliminable = false) use (&$archivosPorEvento) {
        $evento = $archivo->created_at
            ? $eventosRelacionados->last(fn ($evento) => $evento->created_at && $evento->created_at->gte($archivo->created_at))
            : null;
        $evento ??= $eventosRelacionados->first();
        $archivosPorEvento[$evento?->id ?? 'sin_evento'][] = compact('archivo', 'titulo', 'eliminable');
    };

    $cambiosSolicitud = $eventos->whereIn('tipo', ['solicitud_creada', 'solicitud_actualizada']);
    foreach ($solicitud->archivos as $archivo) {
        if ($archivosFormulario->containsStrict($archivo->nombre)) {
            $respuestasFormulario = $eventos->whereIn('tipo', $respuestas)->filter(
                fn ($evento) => collect($evento->datos['formulario'] ?? [])
                    ->where('type', 'file')->pluck('value')->flatten()->containsStrict($archivo->nombre)
            );
            $agruparArchivo($archivo, $respuestasFormulario, 'Archivo de resultado');
        } else {
            $agruparArchivo($archivo, $cambiosSolicitud, 'Archivo de apoyo');
        }
    }

    foreach ($requerimientos as $requerimiento) {
        $movimientos = $eventos->where('solicitud_requerimiento_id', $requerimiento->id);
        $entregas = $movimientos->whereIn('tipo', $respuestas);
        $titulo = $requerimiento->esResultado() ? 'Archivo de resultado' : 'Archivo adjunto al requerimiento';

        foreach ($requerimiento->archivos as $archivo) {
            $agruparArchivo($archivo, $entregas, $titulo, $puedeEliminarAdjuntos ?? false);
        }
    }
@endphp

<div x-data="{ open: false }"
    class="overflow-visible rounded-xl border border-emerald-200 bg-emerald-50/70 shadow-sm dark:border-emerald-900/60 dark:bg-emerald-950/20">
    <div @click="open = !open"
        class="flex items-center justify-between gap-3 px-4 py-4 text-left hover:bg-emerald-100/70 dark:hover:bg-emerald-900/20">
        <div class="min-w-0">
            <h5 class="font-semibold text-stone-700 dark:text-white">Historial</h5>
            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ $eventos->count() }} movimiento(s) registrados
            </div>
        </div>
        <button type="button" @click.stop="open = !open" class="inline-flex items-center gap-1 text-xs font-medium text-stone-600 dark:text-stone-300">
            <span x-show="!open">Ver mas</span>
            <span x-show="open">Ocultar</span>
            <svg class="h-4 w-4 transition-transform duration-200" :class="{ 'rotate-180': open }" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.168l3.71-3.938a.75.75 0 1 1 1.08 1.04l-4.25 4.512a.75.75 0 0 1-1.08 0L5.21 8.27a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>
    </div>

    <div x-show="open" x-transition class="border-t border-emerald-200 px-4 py-4 dark:border-emerald-900/60">
        <div class="space-y-3 text-sm">
            @forelse ($eventos as $evento)
                @php
                    $eventoColor = match ($evento->tipo) {
                        'solicitud_creada', 'requerimiento_creado', 'resultado_generado' => 'bg-sky-500',
                        'solicitud_actualizada', 'requerimiento_actualizado' => 'bg-amber-500',
                        'requerimiento_respondido', 'resultado_entregado' => 'bg-violet-500',
                        'requerimiento_validado', 'resultado_validado', 'solicitud_cerrada' => 'bg-emerald-500',
                        'requerimiento_rechazado', 'resultado_rechazado', 'solicitud_cancelada', 'requerimiento_eliminado' => 'bg-rose-500',
                        default => 'bg-stone-400',
                    };
                    $detalleEvento = match ($evento->tipo) {
                        'solicitud_creada',
                        'solicitud_actualizada',
                        'solicitud_cerrada',
                        'solicitud_cancelada' => $solicitud->titulo,
                        'resultado_entregado',
                        'resultado_validado',
                        'resultado_rechazado' => 'Resultado esperado',
                        'resultado_generado' => 'Asignado a ' . (
                            $evento->requerimiento?->destinatario_tipo === 'cliente'
                                ? 'Cliente'
                                : ($evento->requerimiento?->destinatario?->name ?? 'Sin asignar')
                        ),
                        'requerimiento_creado',
                        'requerimiento_actualizado',
                        'requerimiento_respondido',
                        'requerimiento_validado',
                        'requerimiento_rechazado',
                        'requerimiento_eliminado' => $evento->requerimiento?->titulo,
                        default => $evento->descripcion,
                    };
                @endphp
                <div class="flex items-start gap-3">
                    <div class="mt-1 h-2.5 w-2.5 shrink-0 rounded-full {{ $eventoColor }}"></div>
                    <div class="min-w-0">
                        <div class="font-medium text-gray-900 dark:text-white">{{ $evento->titulo }}</div>
                        @if (filled($detalleEvento))
                            <div class="mt-0.5 whitespace-pre-line text-gray-600 dark:text-gray-300">{{ $detalleEvento }}</div>
                        @elseif ($evento->descripcion)
                            <div class="mt-0.5 whitespace-pre-line text-gray-600 dark:text-gray-300">{{ $evento->descripcion }}</div>
                        @endif
                        <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                            {{ $evento->created_at?->format('d/m/Y H:i') ?? '-' }}
                            · {{ $evento->user?->name ?? 'Sistema' }}
                            @if ($evento->requerimiento)
                                · {{ $evento->requerimiento->tipo === 'resultado' ? 'Resultado esperado' : 'Req. #' . $evento->requerimiento->id }}
                            @endif
                        </div>
                        @if (in_array($evento->tipo, $respuestas, true))
                            @php
                                $camposRespondidos = collect($evento->datos['formulario'] ?? [])
                                    ->filter(fn ($campo) => ($campo['type'] ?? null) !== 'file' && filled($campo['value'] ?? null));
                            @endphp
                            @if ($camposRespondidos->isNotEmpty())
                                <div x-data="{ formularioAbierto: false }" wire:key="historial-formulario-{{ $evento->id }}"
                                    class="mt-2 border-l-2 border-sky-200 pl-3 dark:border-sky-800">
                                    <button type="button" @click="formularioAbierto = !formularioAbierto" :aria-expanded="formularioAbierto"
                                        class="inline-flex items-center gap-1 text-xs font-semibold text-sky-700 underline dark:text-sky-300">
                                        <span x-text="formularioAbierto ? 'Ocultar respuestas' : 'Ver respuestas'">Ver respuestas</span>
                                        <span aria-hidden="true" x-text="formularioAbierto ? '−' : '+'">+</span>
                                    </button>
                                    <dl x-cloak x-show="formularioAbierto" class="mt-1 space-y-1" aria-label="Respuestas del formulario">
                                        @foreach ($camposRespondidos as $campo)
                                            <div>
                                                <dt class="text-xs font-medium text-gray-600 dark:text-gray-300">{{ $campo['label'] ?? $campo['key'] ?? 'Campo' }}</dt>
                                                <dd class="whitespace-pre-line break-words text-sm text-gray-900 dark:text-white">{{ ($campo['type'] ?? null) === 'checkbox' ? ($campo['value'] ? 'Sí' : 'No') : (is_array($campo['value']) ? implode(', ', $campo['value']) : $campo['value']) }}</dd>
                                            </div>
                                        @endforeach
                                    </dl>
                                </div>
                            @endif
                        @endif
                        @include('livewire.solicitudes.historial-archivos', ['adjuntos' => $archivosPorEvento[$evento->id] ?? []])
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500 dark:text-gray-400">
                    Aun no hay movimientos registrados para esta solicitud.
                </p>
            @endforelse

            @include('livewire.solicitudes.historial-archivos', ['adjuntos' => $archivosPorEvento['sin_evento'] ?? []])
        </div>
    </div>
</div>
