@props(['title' => null])
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    @include('partials.head')
</head>

<body x-data="{
    sidebarVisible: window.innerWidth >= 1024,
    openToggleVisible: false,
    init() {
        this.openToggleVisible = !this.sidebarVisible;
    },
    showSidebar() {
        this.openToggleVisible = false;
        this.sidebarVisible = true;
    },
    hideSidebar() {
        this.sidebarVisible = false;
        setTimeout(() => { this.openToggleVisible = true }, 260);
    }
}" class="app-shell-body min-h-screen bg-white dark:bg-zinc-800">

    <div class="fixed left-3 top-3 z-50 lg:hidden"
        x-show="openToggleVisible && !sidebarVisible"
        x-transition:enter="transform transition ease-out duration-220"
        x-transition:enter-start="-translate-y-2 opacity-0"
        x-transition:enter-end="translate-y-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-180"
        x-transition:leave-start="translate-y-0 opacity-100"
        x-transition:leave-end="-translate-y-2 opacity-0"
        x-cloak>
        <flux:sidebar.toggle icon="bars-3"
            class="app-shell-mobile-toggle flex"
            @click="showSidebar()" />
    </div>

    <div class="fixed left-0 top-1/2 z-50 hidden -translate-y-1/2 lg:flex"
        x-show="openToggleVisible && !sidebarVisible"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="-translate-x-4 opacity-0"
        x-transition:enter-end="translate-x-0 opacity-100"
        x-transition:leave="transform transition ease-in duration-220"
        x-transition:leave-start="translate-x-0 opacity-100"
        x-transition:leave-end="-translate-x-4 opacity-0"
        x-cloak>
        <button type="button"
            class="app-shell-open-toggle app-shell-open-tab flex h-[80px] w-[15px] items-center justify-center"
            @click="showSidebar()">
            <span class="app-shell-open-symbol">&#8250;</span>
        </button>
    </div>

    <flux:sidebar x-show="sidebarVisible"
        x-transition:enter="transform transition ease-out duration-300"
        x-transition:enter-start="-translate-x-full"
        x-transition:enter-end="translate-x-0"
        x-transition:leave="transform transition ease-in duration-260"
        x-transition:leave-start="translate-x-0"
        x-transition:leave-end="-translate-x-full"
        class="app-shell-sidebar fixed inset-y-0 left-0 z-40 w-72 border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">

        <div class=" absolute right-0 top-1/2 z-20 -translate-y-1/2 translate-x-[70%] lg:block">
            <button type="button" @click="hideSidebar()"
                class="app-shell-close-toggle app-shell-close-tab flex h-[80px] w-[15px] items-center justify-center">
                <span class="app-shell-close-symbol">&#8249;</span>
            </button>
        </div>

        <div class="relative flex h-full flex-col">

            <div class="pt-2">

                <a href="{{ route('dashboard') }}" class="flex w-full items-center justify-center px-4 pt-4"
                    wire:navigate>
                    <x-app-logo />
                </a>
            </div>

            <div class="sidebar-scroll flex-1 overflow-y-auto px-3 pb-4 pt-4">

                <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')"
                    wire:navigate
                    class="rigg-shell-link mt-10 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                    {{ __('Dashboard') }}
                </flux:navlist.item>

                @if (auth()->check() && auth()->user()->hasRole('super_admin'))
                    <flux:navlist variant="outline">
                        <flux:navlist.group :heading="__('Super_admin')" class="grid">
                            <flux:navlist.item icon="plus" :href="route('despachos.index')"
                                :current="request()->routeIs('despachos.index')" wire:navigate
                                class="rigg-shell-link border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                {{ __('Crear despachos') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    </flux:navlist>
                @endif

                @if (auth()->check() && auth()->user()->hasRole('cliente'))
                    <flux:navlist variant="outline">
                        <flux:navlist.group :heading="__('Mi Portal')" class="grid">
                            <flux:navlist.item icon="user-circle" :href="route('Clientes.portal')"
                                :current="request()->routeIs('Clientes.portal')" wire:navigate
                                class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                {{ __('Mis Obligaciones') }}
                            </flux:navlist.item>
                        </flux:navlist.group>
                    </flux:navlist>
                @endif

                @hasanyrole('contador|admin_despacho|supervisor')
                    <flux:navlist variant="outline">
                        <flux:navlist.group :heading="__('Mi despacho')" class="grid">
                            <flux:navlist.group expandable
                                :expanded="request()->routeIs('despacho.perfil', 'catalogos.regimenes-crud', 'catalogos.tareas-crud', 'catalogos.obligaciones-crud', 'catalogos.actividades-crud')"
                                heading="Configuraciones" class="w-full dark:bg-gray-700 dark:text-white">

                                <flux:navlist.item icon="globe-americas" :href="route('catalogos.regimenes-crud')"
                                    :current="request()->routeIs('catalogos.regimenes-crud')" wire:navigate
                                    class="rigg-shell-link border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                    {{ __('Regimenes') }}
                                </flux:navlist.item>

                                <flux:navlist.item icon="currency-dollar" :href="route('catalogos.actividades-crud')"
                                    :current="request()->routeIs('catalogos.actividades-crud')" wire:navigate
                                    class="rigg-shell-link border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                    {{ __('Actividades Economicas') }}
                                </flux:navlist.item>

                                <flux:navlist.item icon="banknotes" :href="route('catalogos.obligaciones-tareas')"
                                    :current="request()->routeIs('catalogos.obligaciones-tareas')" wire:navigate
                                    class="rigg-shell-link border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                    {{ __('Obligaciones/Tareas') }}
                                </flux:navlist.item>

                                @if (auth()->check() && auth()->user()->hasRole('admin_despacho'))
                                    <flux:navlist.item icon="pencil-square" :href="route('despacho.perfil')"
                                        :current="request()->routeIs('despacho.perfil')" wire:navigate
                                        class="rigg-shell-link border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                        {{ __('Perfil de Despacho') }}
                                    </flux:navlist.item>
                                @endif
                            </flux:navlist.group>

                            <flux:navlist.item icon="user-circle" :href="route('clientes.index')"
                                :current="request()->routeIs('clientes.index')" wire:navigate
                                class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                {{ __('Clientes') }}
                            </flux:navlist.item>

                            <flux:navlist.item icon="paper-airplane" :href="route('contadores.asignaciones.index')"
                                :current="request()->routeIs('contadores.asignaciones.index')" wire:navigate
                                class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                {{ __('Mis Asignaciones') }}
                            </flux:navlist.item>

                            @hasanyrole('supervisor|admin_despacho')
                                <flux:navlist.item icon="clipboard-document-list" :href="route('control.validaciones.index')"
                                    :current="request()->routeIs('control.validaciones.index')" wire:navigate
                                    class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                    {{ __('Validaciones') }}
                                </flux:navlist.item>

                                @hasanyrole('admin_despacho')
                                    <flux:navlist.item icon="envelope-open" :href="route('notificaciones.clientes.index')"
                                        :current="request()->routeIs('notificaciones.clientes.index')" wire:navigate
                                        class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                        {{ __('Notificaciones') }}
                                    </flux:navlist.item>
                                @endhasanyrole

                                @if (auth()->check() && auth()->user()->hasRole('admin_despacho'))
                                    <flux:navlist.item icon="user-group" :href="route('Usuarios.index')"
                                        :current="request()->routeIs('Usuarios.index')" wire:navigate
                                        class="rigg-shell-link mt-2 border border-transparent transition-all duration-300 hover:border-amber-600 data-[current]:border-amber-600">
                                        {{ __('Usuarios') }}
                                    </flux:navlist.item>
                                @endif
                            @endhasanyrole
                        </flux:navlist.group>
                    </flux:navlist>
                @endhasanyrole
            </div>

            <div class="overflow-visible border-t border-zinc-200 px-2 py-3 dark:border-zinc-700">
                <flux:dropdown position="top" align="end">
                    <flux:profile :name="auth()->user()->name" :initials="auth()->user()->initials()"
                        icon-trailing="chevrons-up-down" />
                    <flux:menu class="!min-w-0 w-[calc(18rem-1rem)] max-w-[calc(18rem-1rem)] -translate-x-2">
                        <flux:menu.radio.group>
                            <div class="p-0 text-sm font-normal">
                                <div class="rigg-profile-menu-header flex flex-col items-center px-1 py-1.5 text-sm">
                                    <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                        <span
                                            class="rigg-profile-menu-avatar flex h-full w-full items-center justify-center rounded-lg border border-amber-600 bg-amber-600 text-white dark:border-zinc-600 dark:bg-zinc-700 dark:text-white">
                                            {{ auth()->user()->initials() }}
                                        </span>
                                    </span>
                                    <div class="rigg-profile-menu-copy mt-2 grid w-full text-center text-sm leading-tight">
                                        <span class="rigg-profile-menu-name block w-full text-center font-semibold">{{ auth()->user()->name }}</span>
                                        <span class="rigg-profile-menu-email block w-full text-center text-xs !text-[#163a63]">{{ auth()->user()->email }}</span>
                                    </div>
                                </div>
                            </div>
                        </flux:menu.radio.group>

                        <div class="my-2 border-t border-gray-300"></div>

                        <flux:menu.radio.group>
                            <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate class="rigg-profile-menu-action rigg-profile-menu-action-secondary">
                                {{ __('Configuración') }}
                            </flux:menu.item>
                        </flux:menu.radio.group>

                        <form method="POST" action="{{ route('logout') }}" class="w-full">
                            @csrf
                            <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle"
                                class="rigg-profile-menu-action rigg-profile-menu-action-primary w-full">
                                {{ __('Cerrar sesión') }}
                            </flux:menu.item>
                        </form>
                    </flux:menu>
                </flux:dropdown>
            </div>
        </div>
    </flux:sidebar>

    <div x-show="sidebarVisible && window.innerWidth < 1024" class="app-shell-overlay fixed inset-0 z-30 bg-black/50 lg:hidden"
        @click="sidebarVisible = false" x-transition.opacity>
    </div>

    <main class="app-shell-main p-6 transition-all duration-300"
        :class="{ 'lg:ml-72': sidebarVisible && window.innerWidth >= 1024 }">
        {{ $slot }}
    </main>

    <x-notification />
    <x-button-theme-style />
    @fluxScripts
    <x-spinner />
</body>

</html>





