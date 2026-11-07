@if ($adjuntos)
    <ul class="mt-2 space-y-1 border-l-2 border-sky-200 pl-3 dark:border-sky-800">
        @foreach ($adjuntos as $adjunto)
            @php
                $archivo = $adjunto['archivo'];
                $discoPublico = \Illuminate\Support\Facades\Storage::disk('public');
                $urlArchivo = $archivo->archivo && $discoPublico->exists($archivo->archivo)
                    ? $discoPublico->url($archivo->archivo)
                    : $archivo->archivo_drive_url;
            @endphp
            <li class="break-words text-sm">
                <span class="text-xs text-gray-600 dark:text-gray-300">{{ $adjunto['titulo'] }}</span>
                @if ($urlArchivo)
                    <a href="{{ $urlArchivo }}" target="_blank" rel="noopener noreferrer"
                        class="font-medium text-sky-700 underline hover:text-sky-900 dark:text-sky-300 dark:hover:text-sky-100">{{ $archivo->nombre }}</a>
                @else
                    <span class="text-gray-600 dark:text-gray-300">{{ $archivo->nombre }} (no disponible)</span>
                @endif
                @if ($adjunto['eliminable'])
                    <button type="button"
                        wire:click="$dispatchTo('shared.archivos-adjuntos-crud', 'eliminar-adjunto-del-historial', { archivoId: {{ $archivo->id }} })"
                        class="ml-2 text-xs text-amber-700 hover:underline dark:text-amber-300">
                        Eliminar
                    </button>
                @endif
            </li>
        @endforeach
    </ul>
@endif
