<div class="mb-4">
    <h3 class="text-md font-semibold text-stone-600 mb-2">{{ $titulo }}</h3>

    @if (!empty($items) && count($items) > 0)

        {{-- SOLO PARA OBLIGACIONES --}}
        @if($titulo === 'Obligaciones fiscales')

            @php
                $procesos = collect($items)->where('categoria', 'proceso');
                $obligaciones = collect($items)->where('categoria', 'obligacion');
            @endphp

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-sm">

                {{-- PROCESOS --}}
                <div>
                    <h4 class="font-semibold mb-1 text-stone-500">Procesos</h4>

                    @if($procesos->count())
                        <ul class="list-disc list-inside">
                            @foreach ($procesos as $item)
                                <li class="{{ !$item['activa'] ? 'text-gray-400' : '' }}">
                                    {{ $item['nombre'] }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-400">Sin procesos</p>
                    @endif
                </div>

                {{-- OBLIGACIONES --}}
                <div>
                    <h4 class="font-semibold mb-1 text-stone-500">Obligaciones</h4>

                    @if($obligaciones->count())
                        <ul class="list-disc list-inside">
                            @foreach ($obligaciones as $item)
                                <li class="{{ !$item['activa'] ? 'text-gray-400' : '' }}">
                                    {{ $item['nombre'] }}
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-400">Sin obligaciones</p>
                    @endif
                </div>

            </div>

        {{-- PARA TODO LO DEMÁS (REGÍMENES, ACTIVIDADES, ETC) --}}
        @else

            <ul class="list-disc list-inside text-sm text-gray-800 dark:text-gray-200">
                @foreach ($items as $item)
                    <li class="{{ isset($item['activa']) && !$item['activa'] ? 'text-gray-400' : '' }}">
                        {{ is_array($item) ? $item['nombre'] : $item }}
                    </li>
                @endforeach
            </ul>

        @endif

    @else
        <p class="text-sm text-gray-500">Sin seleccionar</p>
    @endif
</div>
