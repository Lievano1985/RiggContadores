{{-- 
Componente: lista-resumen
Autor: Luis Liévano - JL3 Digital
Descripción: Muestra un título y una lista simple de elementos en modo lectura.
--}}

<div class="mb-4">
    <h3 class="text-md font-semibold text-stone-600 mb-2">{{ $titulo }}</h3>

    @if (!empty($items) && count($items) > 0)
        <ul class="list-disc list-inside text-sm text-gray-800 dark:text-gray-200">
            @foreach ($items as $item)
                <li>{{ $item }}</li>
            @endforeach
        </ul>
    @else
        <p class="text-sm text-gray-500">Sin seleccionar</p>
    @endif
</div>
