<div class="bg-white dark:bg-gray-900 text-gray-900 dark:text-white p-6 max-w-3xl mx-auto">
    <h2 class="text-xl font-bold mb-4 text-stone-600">Perfil del Despacho</h2>
    <hr class="my-6 border-gray-300 dark:border-gray-600" />




    <form wire:submit.prevent="actualizar" class="space-y-6">
        <div>
            <h3 class="text-md font-semibold mb-2 text-stone-600">Datos del Despacho</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">Nombre</label>
                    <input type="text" wire:model.defer="nombre"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('nombre')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">RFC</label>
                    <input type="text" wire:model.defer="rfc"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('rfc')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Correo de Contacto</label>
                    <input type="email" wire:model.defer="correo_contacto"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('correo_contacto')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Teléfono</label>
                    <input type="text" wire:model.defer="telefono_contacto"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('telefono_contacto')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Política de Almacenamiento</label>
                    <select wire:model.live="politica_almacenamiento"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:border-amber-600 focus:outline-none">
                        <option value="storage_only">Solo Laravel Storage</option>
                        <option value="drive_only">Solo Google Drive</option>
                        <option value="both">Ambos</option>
                    </select>
                    @error('politica_almacenamiento')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
                @if ($mostrarAdvertencia)
                    <div x-data="{ visible: @entangle('mostrarAdvertencia').live }" x-show="visible" x-transition
                        class="flex justify-between text-yellow-800 dark:text-yellow-300 bg-yellow-100 dark:bg-yellow-800 rounded p-3 shadow-inner mt-2">
                        <p class="self-center"><strong>⚠ Atención:</strong> Cambiar la política de almacenamiento puede
                            afectar la visibilidad de archivos.</p>
                        <strong @click="visible = false" class="text-xl cursor-pointer select-none">&times;</strong>
                    </div>
                @endif


                @if ($politica_almacenamiento !== 'storage_only')
                    <div>
                        <label class="block text-sm mb-1">ID Carpeta Drive</label>
                        <input type="text" wire:model.defer="drive_folder_id"
                            class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                        @error('drive_folder_id')
                            <span class="text-red-500 text-sm">{{ $message }}</span>
                        @enderror
                    </div>
                @endif
            </div>
        </div>

        <hr class="my-6 border-gray-300 dark:border-gray-600" />

        <div>
            <h3 class="text-md font-semibold mb-2 text-stone-600">Datos del Administrador</h3>

            <div class="space-y-4">
                <div>
                    <label class="block text-sm mb-1">Nombre</label>
                    <input type="text" wire:model.defer="admin_name"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('admin_name')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Correo</label>
                    <input type="email" wire:model.defer="admin_email"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('admin_email')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm mb-1">Nueva Contraseña <span
                            class="text-sm text-gray-500">(opcional)</span></label>
                    <input type="password" wire:model.defer="admin_password"
                        class="w-full px-3 py-2 border rounded dark:bg-gray-700 dark:text-white focus:outline-amber-600 focus:outline">
                    @error('admin_password')
                        <span class="text-red-500 text-sm">{{ $message }}</span>
                    @enderror
                </div>
            </div>
        </div>

        <div class="flex justify-end mt-6">
            <button type="submit" class="px-6 py-2 bg-amber-950 text-white rounded hover:bg-stone-700">
                Actualizar
            </button>
        </div>
    </form>
 
@if (session()->has('success'))
<div
    x-data="{ show: true }"
    x-init="setTimeout(() => show = false, 3000)"
    x-show="show"
    x-transition:leave="transition ease-in duration-500"
    x-transition:leave-start="opacity-100 transform translate-y-0"
    x-transition:leave-end="opacity-0 transform -translate-y-10"
    class="fixed bottom-6 left-1/2 transform -translate-x-1/2 z-50 w-full max-w-sm p-4 text-sm text-green-800 bg-green-200 rounded-lg shadow-lg dark:bg-green-200 dark:text-green-900"
>
 {{ session('succes') }}
</div>
@endif
</div>
