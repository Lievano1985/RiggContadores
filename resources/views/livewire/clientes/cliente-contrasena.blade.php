<div>
    <div class="space-y-4">
        <!-- Botón agregar -->
        <div class="flex justify-end">
            <button wire:click="crear" class="px-4 py-2 bg-amber-950 text-white rounded hover:bg-amber-700">
                + Nuevo portal
            </button>
        </div>

        <!-- Tarjetas de contraseñas -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            @forelse ($contrasenas as $c)
                <div
                    class="bg-white dark:bg-gray-900 p-4 rounded-lg shadow space-y-2 border border-gray-300 dark:border-gray-700">
                    <h3 class="text-lg font-semibold text-stone-600 dark:text-white">
                        {{ $c->portal }}
                    </h3>

                    @if ($c->url)
                        <p class="text-sm">
                            🌐 <a href="{{ $c->url }}" target="_blank" class="text-amber-600 hover:underline">Ir al
                                portal</a>
                        </p>
                    @endif

                    <p class="text-sm text-gray-800 dark:text-gray-300">👤 Usuario: {{ $c->usuario ?? '-' }}</p>
                    <p class="text-sm text-gray-800 dark:text-gray-300">📧 Correo: {{ $c->correo ?? '-' }}</p>
                    <p class="text-sm text-gray-800 dark:text-gray-300">🔒 Contraseña: {{ $c->contrasena }}</p>

                    @if ($c->vencimiento)
                        <p class="text-sm text-gray-800 dark:text-gray-300">
                            ⏳ Vence: {{ \Carbon\Carbon::parse($c->vencimiento)->format('d/m/Y') }}
                        </p>
                    @endif

                    <div class="text-sm text-gray-800 dark:text-gray-300 space-y-1">
                        @if ($c->archivo_certificado)
                            📄 <a href="{{ Storage::disk('public')->url($c->archivo_certificado) }}" target="_blank"
                                class="text-amber-600 hover:underline">
                                Ver Certificado (.cer)
                            </a>
                        @endif
                        <br/>
                        @if ($c->archivo_clave)
                            🔑 <a href="{{ Storage::disk('public')->url($c->archivo_clave) }}" target="_blank"    download

                                class="text-amber-600 hover:underline">
                                Clave privada (.key)
                            </a>
                        @endif
                    </div>

                    <div class="flex justify-end space-x-2 pt-2">
                        <button wire:click="editar({{ $c->id }})"
                            class="text-sm text-blue-600 hover:underline">Editar</button>
                        <button wire:click="eliminar({{ $c->id }})"
                            class="text-sm text-red-600 hover:underline">Eliminar</button>
                    </div>
                </div>
            @empty
                <p class="text-gray-500 dark:text-gray-400 col-span-full">No hay contraseñas registradas.</p>
            @endforelse
        </div>

        <!-- Modal -->
        @if ($modalFormVisible)
            <div class="fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
                <div class="bg-white dark:bg-gray-900 p-6 rounded-lg shadow-lg w-full max-w-2xl space-y-4">
                    <h2 class="text-lg font-bold text-stone-600 dark:text-white">
                        {{ $isEditing ? 'Editar' : 'Nueva' }} contraseña
                    </h2>

                    <form wire:submit.prevent="guardar" class="space-y-4">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="text-sm font-medium mb-1">Destino de archivos</label>
                                <select wire:model.defer="form.destino_archivo"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                    <option value="">Selecciona una carpeta</option>
                                    <option value="1-fiel">1 - Fiel</option>
                                    <option value="2-csd">2 - CSD</option>
                                    <option value="3-imss">3 - IMSS</option>
                                    <option value="otros">Otros</option>
                                </select>
                                @error('form.destino_archivo') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>
                            
                            <div>
                                <label class="text-sm">Portal</label>
                                <input type="text" wire:model.defer="form.portal"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                @error('form.portal')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm">URL del portal</label>
                                <input type="url" wire:model.defer="form.url"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="text-sm">Usuario</label>
                                <input type="text" wire:model.defer="form.usuario"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="text-sm">Correo</label>
                                <input type="email" wire:model.defer="form.correo"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>

                            <div>
                                <label class="text-sm">Contraseña</label>
                                <input type="text" wire:model.defer="form.contrasena"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                @error('form.contrasena')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                          

                            <div>
                                <label class="text-sm">Archivo Certificado (.cer)</label>
                                <input type="file" wire:model="archivoCertificado" accept=".cer"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                @error('archivoCertificado')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>

                            <div>
                                <label class="text-sm">Archivo Clave (.key)</label>
                                <input type="file" wire:model="archivoClave" accept=".key"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                                @error('archivoClave')
                                    <span class="text-red-500 text-xs">{{ $message }}</span>
                                @enderror
                            </div>
                            <div>
                                <label class="text-sm">Vencimiento</label>
                                <input type="date" wire:model.defer="form.vencimiento"
                                    class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white">
                            </div>
                        </div>

                        <div class="text-right space-x-2">
                            <button type="button" wire:click="$set('modalFormVisible', false)"
                                class="bg-gray-300 dark:bg-gray-600 text-black dark:text-white px-4 py-2 rounded hover:bg-gray-400">
                                Cancelar
                            </button>
                            <button type="submit" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                                Guardar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>
