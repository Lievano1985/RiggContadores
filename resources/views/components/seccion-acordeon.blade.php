

@props(['titulo', 'open' => false])
{{-- 
Componente: seccion-acordeon
Autor: Luis Liévano - JL3 Digital
Descripción: Contenedor tipo acordeón (expandible/colapsable) para secciones del formulario.
--}}
<div x-data="{ expanded: @js($open) }" class="border-b border-gray-300 dark:border-gray-700 py-2">
    <h2>
        <button type="button"
            class="flex items-center justify-between w-full text-left font-semibold py-2 text-stone-700 dark:text-white"
            @click="expanded = !expanded"
            :aria-expanded="expanded">
            <span>{{ $titulo }}</span>
            <svg class="shrink-0 w-4 h-4 transition-transform duration-200"
                :class="{ 'rotate-180': expanded }"
                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M19 9l-7 7-7-7" />
            </svg>
        </button>
    </h2>

    <div class="grid text-sm text-slate-600 overflow-hidden transition-all duration-300 ease-in-out"
        :class="expanded ? 'grid-rows-[1fr] opacity-100' : 'grid-rows-[0fr] opacity-0'">
        <div class="overflow-hidden">
            {{ $slot }}
        </div>
    </div>
</div>
