@props([
    'nodo' => [],        // ['id','nombre','parent_id','children'=>[]]
    'nivel' => 0,
    'model' => 'carpeta_drive_id', // usado para name del radio
])

@php
    $iconos = ['📁','📂','🗂','🗃'];
    $icono = $iconos[min($nivel, count($iconos)-1)];
    $indent = str_repeat('— ', max(0, $nivel - 1));
@endphp

<li class="p-2 rounded odd:bg-gray-50 even:bg-white dark:odd:bg-gray-800 dark:even:bg-gray-900">
    <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 cursor-pointer">
            {{-- Selección única: mismo name y un solo x-model central --}}
            <input
                type="radio"
                name="{{ $model }}"
                x-model="seleccion"
                value="{{ $nodo['id'] }}"
                class="accent-amber-600"
            >
            <span class="text-sm {{ $nivel === 0 ? 'font-semibold' : '' }}">
                {{ $indent }}{{ $icono }} {{ $nodo['nombre'] }}
            </span>
        </label>

        @if (!empty($nodo['children']))
            <button
                type="button"
                class="text-xs text-amber-600 hover:underline"
                @click="abiertos['{{ $nodo['id'] }}'] = !abiertos['{{ $nodo['id'] }}']">
                <span x-show="!abiertos['{{ $nodo['id'] }}']">Mostrar</span>
                <span x-show="abiertos['{{ $nodo['id'] }}']">Ocultar</span>
            </button>
        @endif
    </div>

    @if (!empty($nodo['children']))
        <ul
            class="pl-4 border-l border-gray-300 dark:border-gray-700 mt-1 space-y-1"
            x-show="abiertos['{{ $nodo['id'] }}']"
            x-transition
        >
            @foreach ($nodo['children'] as $hijo)
                <x-arbol-carpetas-nodo :nodo="$hijo" :nivel="$nivel+1" :model="$model" />
            @endforeach
        </ul>
    @endif
</li>
