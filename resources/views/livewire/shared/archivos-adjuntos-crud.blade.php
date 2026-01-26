<div>
    <div class="space-y-4">

        {{-- Archivos existentes --}}
        <div>
            <h4 class="font-semibold text-sm text-stone-600 mb-2">
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

                        <button wire:click="eliminarArchivo({{ $a->id }})" class="text-red-600 hover:underline">
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
            <h4 class="font-semibold text-sm text-stone-600 mb-2">
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
                <div class="md:col-span-5">
                    <input
                        type="file"
                        wire:model="nuevosArchivos.{{ $i }}.file"
                        class="w-full px-3 py-2 border text-sm rounded
                               dark:bg-gray-700 dark:text-white"
                    >
            
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
                        class="bg-red-600 text-white rounded
                               hover:bg-red-700 px-2 h-[38px]"
                    >
                        ✕
                    </button>
                </div>
            
            </div>
            @endforeach
            

            <button wire:click="agregarArchivo" class="text-sm text-amber-600 hover:underline">
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

        <x-notification />

    </div>
</div>
