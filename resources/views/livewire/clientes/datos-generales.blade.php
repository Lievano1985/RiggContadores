<div class="space-y-6">
    @php
        $vigencia = \Carbon\Carbon::parse($cliente->vigencia); // La fecha de vigencia del cliente
        $hoy = \Carbon\Carbon::now();
        $unMesAntes = $hoy->copy()->addMonth(); // Fecha límite para el mes siguiente
    @endphp

    <h1
        class="text-md font-bold text-right
    @if ($vigencia <= $hoy) text-red-500
    @elseif ($vigencia <= $unMesAntes) text-red-500
    @else text-gray-800 @endif">
        Vigencia:
        @if ($vigencia <= $hoy)
            Sin vigencia
        @else
            {{ $vigencia->format('d/m/Y') }}
        @endif
    </h1>

    <div wire:key="modo-{{ $modoKey }}">
        <label class="inline-flex items-center cursor-pointer">
            <input type="checkbox" wire:model.live="modoEdicion" class="sr-only peer">
            <div
                class="relative w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-amber-300 dark:peer-focus:ring-amber-800 rounded-full peer dark:bg-gray-700 peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-gray-600 peer-checked:bg-amber-600 dark:peer-checked:bg-amber-600">
            </div>
            <span class="ms-3 text-sm font-medium text-gray-900 dark:text-gray-300">Modo edición</span>
        </label>
    </div>

    <!-- Formulario -->
    <form wire:submit.prevent="guardar">
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">

            <!-- Nombre comercial -->
            <div>
                <label class="block text-sm font-medium mb-1">Nombre comercial</label>
                <input type="text" wire:model="nombre"
                    class="uppercase w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion) oninput="this.value = this.value.toUpperCase()" required>
                @error('nombre') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Razón social -->
            <div>
                <label class="block text-sm font-medium mb-1">Razón social</label>
                <input type="text" wire:model="razon_social"
                    class="uppercase w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion) oninput="this.value = this.value.toUpperCase()" required>
                @error('razon_social') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- RFC -->
            <div>
                <label class="block text-sm font-medium mb-1">RFC</label>
                <input type="text" wire:model="rfc"
                    class="uppercase w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion) oninput="this.value = this.value.toUpperCase()" required>
                @error('rfc') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- CURP -->
            <div>
                <label class="block text-sm font-medium mb-1">CURP</label>
                <input type="text" wire:model.defer="curp" @disabled(!$modoEdicion || $tipo_persona === 'moral')
                    class="uppercase w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                           oninput="this.value = this.value.toUpperCase()"

                           @required($tipo_persona === 'fisica')>
                           @error('curp')
                           <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                       @enderror
            </div>

            <!-- Correo -->
            <div>
                <label class="block text-sm font-medium mb-1">Correo</label>
                <input type="email" wire:model="correo"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('correo') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Teléfono -->
            <div>
                <label class="block text-sm font-medium mb-1">Teléfono</label>
                <input type="text" wire:model="telefono"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('telefono') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Código postal -->
            <div>
                <label class="block text-sm font-medium mb-1">Código postal</label>
                <input type="text" wire:model="codigo_postal"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('codigo_postal') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Dirección -->
            <div>
                <label class="block text-sm font-medium mb-1">Dirección</label>
                <input type="text" wire:model="direccion"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('direccion') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Tipo de persona -->
            <div>
                <label class="block text-sm font-medium mb-1">Tipo de persona</label>
                <select wire:model.live="tipo_persona"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                    <option value="fisica">Persona Física</option>
                    <option value="moral">Persona Moral</option>
                </select>
                @error('tipo_persona') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Tiene trabajadores -->
            <div>
                <label class="block text-sm font-medium mb-1">¿Tiene trabajadores?</label>
                <select wire:model.live="tiene_trabajadores"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                    <option value="1">Sí</option>
                    <option value="0">No</option>
                </select>
                @error('tiene_trabajadores') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Inicio de obligaciones -->
            <div>
                <label class="block text-sm font-medium mb-1">Inicio de obligaciones</label>
                <input type="date" wire:model="inicio_obligaciones"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('inicio_obligaciones') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Fin de obligaciones -->
            <div>
                <label class="block text-sm font-medium mb-1">Fin de obligaciones</label>
                <input type="date" wire:model="fin_obligaciones"
                    class="w-full px-3 py-2 border rounded-md 
                           dark:bg-gray-700 dark:text-white 
                           border-gray-300 dark:border-gray-600 
                           focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                           focus:outline-none"
                    @disabled(!$modoEdicion)>
                @error('fin_obligaciones') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
            </div>

            <!-- Contrato -->
            @if ($modoEdicion)
                <div>
                    <label class="block text-sm mb-1">Contrato (PDF)</label>
                    <input type="file" wire:model="archivoContrato" accept="application/pdf"
                        class="w-full px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none">
                    @error('archivoContrato') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                </div>

                <!-- Vigencia -->
                <div>
                    <label class="block text-sm font-medium mb-1">Vigencia</label>
                    <input type="date" wire:model="vigencia"
                        class="w-full px-3 py-2 border rounded-md 
                               dark:bg-gray-700 dark:text-white 
                               border-gray-300 dark:border-gray-600 
                               focus:border-amber-600 focus:ring focus:ring-amber-500/40 
                               focus:outline-none"
                        @disabled(!$modoEdicion)>
                    @error('vigencia') <div class="text-red-500 text-xs mt-1">{{ $message }}</div> @enderror
                </div>
        </div>
    @else
        @if (in_array($cliente->despacho->politica_almacenamiento, ['storage_only', 'both']))
            <p class="text-sm text-gray-700 dark:text-gray-300">
                @if ($cliente->contrato)
                    📄 <a href="{{ Storage::disk('public')->url($cliente->contrato) }}" target="_blank"
                        class="text-amber-600 hover:underline">
                        Ver contrato
                    </a>
                    <span class="ml-2 text-xs text-gray-500 dark:text-gray-400">
                        (Vigente hasta:
                        {{ $cliente->vigencia ? \Carbon\Carbon::parse($cliente->vigencia)->format('d/m/Y') : 'Sin vigencia' }})
                    </span>
                @else
                    No hay contrato cargado.
                @endif
            </p>
        @endif
    @endif

        @if ($modoEdicion)
            <div class="mt-6 text-right">
                <button type="submit" class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
                    Guardar
                </button>
            </div>
            <x-spinner target="guardar" />

        @endif
    </form>

    @if (session()->has('message'))
        <div x-data="{ show: true }" x-init="setTimeout(() => show = false, 3000)" x-show="show"
            x-transition:leave="transition ease-in duration-500"
            x-transition:leave-start="opacity-100 transform translate-y-0"
            x-transition:leave-end="opacity-0 transform -translate-y-10"
            class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-sm p-4 text-sm text-green-800 bg-green-200 rounded-lg shadow-lg dark:bg-green-200 dark:text-green-900">
            {{ session('message') }}
        </div>
    @endif
</div>
