<div>
    <div class="space-y-4">

        {{-- Archivos existentes --}}
        <div>
            <h4 class="font-semibold text-sm text-stone-600 dark:text-white mb-2">
                Archivos adjuntos
            </h4>

            @forelse ($archivos as $a)
                <div
                    class="flex items-center justify-between
                           bg-gray-100 dark:bg-gray-800
                           px-3 py-2 rounded mb-2">
                    <div class="text-sm">
                        📎 {{ $a->nombre }}
                    </div>

                    <div class="flex items-center space-x-3 text-sm">

                        @if ($a->archivo)
                            <a href="{{ Storage::disk('public')->url($a->archivo) }}" target="_blank"
                                class="text-blue-600 hover:underline">
                                Ver
                            </a>
                        @endif

                        <button wire:click="eliminarArchivo({{ $a->id }})" class="text-amber-700 hover:underline">
                            Eliminar
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">
                    No hay archivos adjuntos.
                </p>
            @endforelse
        </div>

        {{-- Nuevos archivos --}}
        <div>
            <h4 class="font-semibold text-sm text-stone-600 dark:text-white mb-2">
                Agregar archivos
            </h4>

            @foreach ($nuevosArchivos as $i => $item)
            <div class="grid grid-cols-1 md:grid-cols-10 gap-2 mb-2 items-start">
            
                {{-- Nombre --}}
                <div class="md:col-span-4">
                    <input
                        type="text"
                        wire:model.defer="nuevosArchivos.{{ $i }}.nombre"
                        placeholder="Nombre del archivo"
                        class="w-full px-3 py-2 border text-sm rounded
                               dark:bg-gray-700 dark:text-white"
                    >
            
                    {{-- Espacio fijo para error --}}
                    <div class="min-h-[18px]">
                        @error("nuevosArchivos.$i.nombre")
                            <span class="text-red-500 text-xs">Archivo sin nombre</span>
                        @enderror
                    </div>
                </div>
            
                {{-- Archivo --}}
                <div class="relative md:col-span-5">
                    <input
                        type="file"
                        wire:model="nuevosArchivos.{{ $i }}.file"
                        class="w-full px-3 py-2 border text-sm rounded
                               dark:bg-gray-700 dark:text-white"
                    >

                    <div
                        wire:loading.flex
                        wire:target="nuevosArchivos.{{ $i }}.file"
                        class="absolute inset-0 z-10 items-center justify-center gap-2 rounded bg-white/85 text-sm font-medium text-stone-700 backdrop-blur-sm dark:bg-gray-900/85 dark:text-white"
                    >
                        <svg class="h-4 w-4 animate-spin text-amber-600" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                        </svg>
                        Cargando archivo...
                    </div>
            
                    {{-- Espacio fijo para error --}}
                    <div class="min-h-[18px]">
                        @error("nuevosArchivos.$i.file")
                            <span class="text-red-500 text-xs">No hay archivo seleccionado</span>
                        @enderror
                    </div>
                </div>
            
                {{-- Botón --}}
                <div class="flex items-start">
                    <button
                        wire:click="quitarArchivo({{ $i }})"
                        wire:loading.attr="disabled"
                        wire:target="nuevosArchivos.{{ $i }}.file"
                        class="bg-amber-600 text-white rounded
                               hover:bg-amber-700 px-2 h-[38px] disabled:opacity-50"
                    >
                        ✕
                    </button>
                </div>
            
            </div>
            @endforeach
            

            <button wire:click="agregarArchivo" wire:loading.attr="disabled" class="text-sm text-amber-600 hover:underline disabled:opacity-50">
                + Agregar otro archivo
            </button>
        </div>

        {{-- Guardar --}}
      {{--   <div class="flex justify-end">
            <button wire:click="guardar" @disabled(count($nuevosArchivos) == 0)
                class="bg-amber-600 hover:bg-amber-700
                       px-4 py-2 rounded text-white
                       disabled:opacity-50">
                Guardar archivos
            </button>
        </div> --}}


    </div>
</div>
