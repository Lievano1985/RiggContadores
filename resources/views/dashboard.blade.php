<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
        {{-- CONTADOR --}}
        @if(auth()->user()->hasRole('contador') || auth()->user()->hasRole('supervisor'))
            <livewire:dashboard.dashboard-contador />
        @endif

        {{-- SUPERVISOR --}}
        @if(auth()->user()->hasRole('supervisor'))
            <livewire:dashboard.dashboard-supervisor />
        @endif

        {{-- ADMIN --}}
        @if(auth()->user()->hasRole('admin_despacho'))
            <livewire:dashboard.dashboard-admin />
        @endif

        {{-- CLIENTE --}}
        @if(auth()->user()->hasRole('cliente'))
            {{-- <livewire:dashboard.dashboard-cliente /> --}}
        @endif
    </div>
</x-layouts.app>
