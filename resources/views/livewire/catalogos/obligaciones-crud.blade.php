{{-- 
Componente Blade: Catálogo de Obligaciones
Autor: Luis Liévano - JL3 Digital
Descripción: CRUD del catálogo de obligaciones. Incluye opción 'Única' y oculta campos no aplicables.
--}}

<div class="p-6 bg-white dark:bg-gray-900 text-gray-900 dark:text-white space-y-4">

    <!-- Encabezado -->
    <div class="flex justify-between items-center">
        <h2 class="text-xl font-bold text-stone-600 dark:text-white">Catálogo de Obligaciones</h2>
        <button wire:click="showCreateForm"
            class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700 transition">
            + Nueva obligación
        </button>
    </div>

    <!-- Buscador -->
    <div class="flex items-center gap-4">
        <input type="text" wire:model.live.debounce.500ms="search"
        class="w-1/2 px-3 py-2 border rounded-md 
        dark:bg-gray-700 dark:text-white 
        border-gray-300 dark:border-gray-600 
        focus:border-amber-600 focus:ring focus:ring-amber-500/40 
        focus:outline-none"
            placeholder="Buscar por nombre, tipo o periodicidad...">
    </div>

    <!-- Tabla -->
    <div class="overflow-x-auto rounded shadow">
        <table class="min-w-full divide-y divide-gray-300 dark:divide-gray-700">
            <thead class="bg-stone-100 dark:bg-stone-900">
                <tr>
                    <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('nombre')">Nombre</th>
                    <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('tipo')">Tipo</th>
                    <th class="px-4 py-2 text-left cursor-pointer" wire:click="sortBy('periodicidad')">Periodicidad</th>
                    <th class="px-4 py-2 text-left">Mes de Limite</th>
                    <th class="px-4 py-2 text-left">Día Limite</th>
                    <th class="px-4 py-2 text-center">Activa</th>
                    <th class="px-4 py-2 text-left">Acciones</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-gray-800">
                @forelse ($obligaciones as $obligacion)
                    <tr>
                        <td class="px-4 py-2">{{ $obligacion->nombre }}</td>
                        <td class="px-4 py-2 capitalize">{{ $obligacion->tipo }}</td>
                        <td class="px-4 py-2 capitalize">{{ $obligacion->periodicidad }}</td>
                        <td class="px-4 py-2">{{ $obligacion->desfase_meses ?? '—' }}</td>
                        <td class="px-4 py-2">Día {{ $obligacion->dia_corte ?? '—' }}</td>
                        <td class="px-4 py-2 text-center">
                            <span
                                class="px-2 py-1 text-xs rounded {{ $obligacion->activa ? 'bg-green-600 text-white' : 'bg-gray-400 text-black' }}">
                                {{ $obligacion->activa ? 'Sí' : 'No' }}
                            </span>
                        </td>
                        <td class="px-4 py-2 space-x-2">
                            <button wire:click="showEditForm({{ $obligacion->id }})"
                                class="text-amber-600 hover:underline">Editar</button>
                            <button wire:click="confirmarEliminacion({{ $obligacion->id }})"
                                class="text-red-600 hover:underline">Eliminar</button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-4 text-center text-gray-500 dark:text-gray-300">
                            No se encontraron registros.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    <div class="mt-4">{{ $obligaciones->links() }}</div>

    <!-- Modal -->
    @if ($modalFormVisible)
        <div class="fixed inset-0 flex items-center justify-center bg-stone-600/50 z-50">
            <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl">
                <h3 class="text-lg font-semibold text-stone-600 dark:text-white mb-4">
                    {{ $isEdit ? 'Editar obligación' : 'Nueva obligación' }}
                </h3>

              
                <form wire:submit.prevent="save" class="space-y-4"
                x-data="{ unica: false }"
                x-init="unica = (String($wire.periodicidad || '').toLowerCase() === 'unica' || String($wire.periodicidad || '').toLowerCase() === 'única')">
          
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
          
                  <!-- Línea 1: Nombre y Periodicidad -->
                  <div>
                      <label class="block text-sm font-medium mb-1">Nombre</label>
                      <input type="text" wire:model.defer="nombre"
                             class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                  </div>
          
                  <div>
                      <label class="block text-sm font-medium mb-1">Periodicidad</label>
                      <select wire:model.live="periodicidad"
                              x-on:change="unica = ($event.target.value.toLowerCase() === 'unica' || $event.target.value.toLowerCase() === 'única')"
                              class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                          <option value="">Seleccione...</option>
                          <option value="mensual">Mensual</option>
                          <option value="bimestral">Bimestral</option>
                          <option value="trimestral">Trimestral</option>
                          <option value="cuatrimestral">Cuatrimestral</option>
                          <option value="semestral">Semestral</option>
                          <option value="anual">Anual</option>
                          <option value="unica">Única</option>
                      </select>
                  </div>
          
                  <!-- Línea 2: Mes límite y Día límite -->
                  <div>
                      <label class="block text-sm font-medium mb-1">Mes límite</label>
                      <input type="number" min="0" max="12" wire:model.defer="desfase_meses"
                             :disabled="unica"
                             :class="unica
                                  ? 'w-full px-3 py-2 border rounded bg-gray-200 text-gray-500 cursor-not-allowed dark:bg-gray-600 dark:text-gray-400'
                                  : 'w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600'">
                  </div>
          
                  <div>
                      <label class="block text-sm font-medium mb-1">Día límite</label>
                      <input type="number" min="1" max="31" wire:model.defer="dia_corte"
                             class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                  </div>
          
                  <!-- Línea 3: Tipo y Activa -->
                  <div>
                      <label class="block text-sm font-medium mb-1">Tipo</label>
                      <select wire:model.defer="tipo"
                              class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600">
                          <option value="">Seleccione...</option>
                          <option value="federal">Federal</option>
                          <option value="estatal">Estatal</option>
                          <option value="local">Local</option>
                          <option value="patronal">Patronal</option>
                      </select>
                  </div>
          
                  <div class="flex items-center gap-2 mt-2">
                      <input type="checkbox" wire:model.defer="activa" id="activa"
                             class="rounded border-gray-300 dark:border-gray-700">
                      <label for="activa" class="text-sm font-medium">Activa</label>
                  </div>
              </div>
          
              <!-- Botones -->
              <div class="flex justify-end space-x-2 pt-4">
                  <button type="button" wire:click="$set('modalFormVisible', false)"
                          class="bg-gray-300 dark:bg-gray-600 text-black dark:text-white px-4 py-2 rounded hover:bg-gray-400">
                      Cancelar
                  </button>
                  <button type="submit" class="bg-amber-600 text-white px-4 py-2 rounded hover:bg-amber-700">
                      Guardar
                  </button>
              </div>
          </form>
          

            </div>
        </div>
    @endif

    <x-confirmacion-eliminacion :confirmingDelete="$confirmingDelete" action="eliminarConfirmada" />
    <x-notification />
</div>
