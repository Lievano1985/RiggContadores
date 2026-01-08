<div>
    <div class="space-y-4">

        {{-- =============================
            Archivos existentes
        ============================== --}}
        <div>
            <h4 class="font-semibold text-sm text-stone-600 mb-2">Archivos adjuntos</h4>
    
            @forelse ($archivos as $a)
                <div class="flex items-center justify-between bg-gray-100 dark:bg-gray-800 px-3 py-2 rounded mb-2">
                    <div class="text-sm">
                        📎 {{ $a->nombre }}
                    </div>
    
                    <div class="flex items-center space-x-3 text-sm">
                        @if ($a->archivo)
                            <a href="{{ Storage::disk('public')->url($a->archivo) }}"
                               target="_blank"
                               class="text-blue-600 hover:underline">
                                Ver
                            </a>
                        @endif
    
                        <button wire:click="eliminarArchivo({{ $a->id }})"
                                class="text-red-600 hover:underline">
                            Eliminar
                        </button>
                    </div>
                </div>
            @empty
                <p class="text-sm text-gray-500">No hay archivos adjuntos.</p>
            @endforelse
        </div>
    
        {{-- =============================
            Nuevos archivos
        ============================== --}}
        <div>
            <h4 class="font-semibold text-sm text-stone-600 mb-2">Agregar archivos</h4>
    
            @foreach ($nuevosArchivos as $i => $item)
                <div class="grid grid-cols-1 md:grid-cols-5 gap-2 mb-2">
    
                    <input type="text"
                           wire:model.defer="nuevosArchivos.{{ $i }}.nombre"
                           placeholder="Nombre del archivo"
                           class="md:col-span-2 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
    
                    <input type="file"
                           wire:model="nuevosArchivos.{{ $i }}.file"
                           class="md:col-span-2 px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
    
                    <button wire:click="quitarArchivo({{ $i }})"
                            class="bg-red-600 text-white rounded px-3 py-2 hover:bg-red-700">
                        ✕
                    </button>
                </div>
            @endforeach
    
            <button wire:click="agregarArchivo"
                    class="text-sm text-amber-600 hover:underline">
                + Agregar otro archivo
            </button>
        </div>
    
        {{-- =============================
            Guardar
        ============================== --}}
        <div class="flex justify-end">
            <button wire:click="guardar"
                    class="bg-amber-600 hover:bg-amber-700 px-4 py-2 rounded text-white">
                Guardar archivos
            </button>
        </div>
    
    </div>
    </div>
