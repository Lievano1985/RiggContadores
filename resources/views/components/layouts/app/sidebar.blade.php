<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">

<head>
    @include('partials.head')
</head>

<body x-data="{ sidebarVisible: window.innerWidth >= 1024 }" class="min-h-screen bg-white dark:bg-zinc-800">

    <!-- Botón hamburguesa -->
    <flux:sidebar.toggle icon="bars-3" class="fixed top-3 left-3 z-50 flex" x-show="!sidebarVisible"
        @click="sidebarVisible = true" x-cloak />

    <!-- Sidebar -->
    <flux:sidebar x-show="sidebarVisible"
        class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900 w-64 fixed inset-y-0 left-0 z-40">

        <!-- Contenedor con transición -->
        <div class="h-full transform transition-transform duration-300" x-transition:enter="translate-x-0"
            x-transition:leave="-translate-x-full">

            <!-- Botón cerrar -->
            <flux:sidebar.toggle name="ocultar" @click="sidebarVisible = false" icon="x-mark"
                class="absolute top-3 right-3" />

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="me-10 flex items-center space-x-2 rtl:space-x-reverse mt-10 px-4"
                wire:navigate>
                <x-app-logo />
            </a>

            <!-- ============================= -->
            <!--         MENÚ COMPLETO         -->
            <!-- ============================= -->

            @if (auth()->check() && auth()->user()->hasRole('super_admin'))
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Super_admin')" class="grid">
                        <flux:navlist.item icon="plus" :href="route('despachos.index')"
                            :current="request()->routeIs('despachos.index')" wire:navigate
                            class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Crear despachos') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>
            @endif

            @if (auth()->check() && auth()->user()->hasRole('contador||admin_despacho'))
                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate
                    class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                    {{ __('Dashboard') }}
                </flux:navlist.item>
            @endif

            @if (auth()->check() && auth()->user()->hasRole('cliente'))
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Mi Portal')" class="grid">
                        <flux:navlist.item icon="user-circle" :href="route('Clientes.portal')"
                            :current="request()->routeIs('Clientes.portal')" wire:navigate
                            class="mt-2 hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Mis Obligaciones') }}
                        </flux:navlist.item>
                    </flux:navlist.group>
                </flux:navlist>
            @endif

            @hasrole('contador||admin_despacho')
                <flux:navlist variant="outline">
                    <flux:navlist.group :heading="__('Mi despacho')" class="grid">
                        <flux:navlist.group expandable
                            :expanded="request()->routeIs('despacho.perfil', 'catalogos.regimenes-crud','catalogos.tareas-crud', 'catalogos.obligaciones-crud', 'catalogos.actividades-crud')"
                            heading="Configuraciones" class="w-full dark:bg-gray-700 dark:text-white">

                            <flux:navlist.item icon="globe-americas" :href="route('catalogos.regimenes-crud')"
                                :current="request()->routeIs('catalogos.regimenes-crud')" wire:navigate
                                class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Regimenes') }}
                            </flux:navlist.item>

                            <flux:navlist.item icon="currency-dollar" :href="route('catalogos.actividades-crud')"
                                :current="request()->routeIs('catalogos.actividades-crud')" wire:navigate
                                class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Actividades Economicas') }}
                            </flux:navlist.item>
{{-- 
                            <flux:navlist.item icon="banknotes" :href="route('catalogos.obligaciones-crud')"
                                :current="request()->routeIs('catalogos.obligaciones-crud')" wire:navigate
                                class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Obligaciones') }}
                            </flux:navlist.item> --}}
                            <flux:navlist.item icon="banknotes" :href="route('catalogos.obligaciones-tareas')"
                            :current="request()->routeIs('catalogos.obligaciones-tareas')" wire:navigate
                            class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Obligaciones/Tareas') }}
                        </flux:navlist.item>

                            {{-- <flux:navlist.item icon="briefcase" :href="route('catalogos.tareas-crud')"
                                :current="request()->routeIs('catalogos.tareas-crud')" wire:navigate
                                class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Tareas') }}
                            </flux:navlist.item>
 --}}
                            <flux:navlist.item icon="pencil-square" :href="route('despacho.perfil')"
                                :current="request()->routeIs('despacho.perfil')" wire:navigate
                                class="hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Perfil de Despacho') }}
                            </flux:navlist.item>
                        </flux:navlist.group>

                        <flux:navlist.item icon="user-circle" :href="route('clientes.index')"
                            :current="request()->routeIs('clientes.index')" wire:navigate
                            class="mt-2 hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Clientes') }}
                        </flux:navlist.item>

                        <flux:navlist.item icon="paper-airplane" :href="route('contadores.asignaciones.index')"
                            :current="request()->routeIs('contadores.asignaciones.index')" wire:navigate
                            class="mt-2 hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Mis Asignaciones') }}
                        </flux:navlist.item>
                        @hasrole('admin_despacho')

                        <flux:navlist.item icon="clipboard-document-list" :href="route('control.validaciones.index')"
                            :current="request()->routeIs('control.validaciones.index')" wire:navigate
                            class="mt-2 hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                            {{ __('Validaciones') }}
                        </flux:navlist.item>

                            <flux:navlist.item icon="user-group" :href="route('Usuarios.index')"
                                :current="request()->routeIs('Usuarios.index')" wire:navigate
                                class="mt-2 hover:border-amber-600 border border-transparent transition-all duration-300 data-[current]:border-amber-600">
                                {{ __('Usuarios') }}
                            </flux:navlist.item>
                        @endhasrole
                    </flux:navlist.group>
                </flux:navlist>
            @endhasrole
            <flux:spacer />
            <!-- User Menu -->
            <flux:dropdown position="bottom" align="start">
                <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                    icon-trailing="chevrons-up-down" />
                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white">
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>
                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>
                            {{ __('Settings') }}
                        </flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                            class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </div>
    </flux:sidebar>

    <!-- Overlay (solo móviles y tablets) -->
    <div x-show="sidebarVisible && window.innerWidth < 1024" class="fixed inset-0 bg-black/50 z-30 lg:hidden"
        @click="sidebarVisible = false" x-transition.opacity>
    </div>

    <!-- Contenido principal -->
    <main class=" p-6 transition-all duration-300"
        :class="{ 'lg:ml-64': sidebarVisible && window.innerWidth >= 1024 }">
        {{ $slot }}
    </main>






    
    @fluxScripts
</body>

</html>
