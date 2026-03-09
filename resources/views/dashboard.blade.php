<x-layouts.app :title="__('Dashboard')">
    <div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
       {{-- CONTADOR --}}
       @if(auth()->user()->hasRole('contador'))
       <livewire:dashboard.dashboard-contador />
   @endif

   {{-- SUPERVISOR (más adelante) --}}
   @if(auth()->user()->hasRole('Supervisor'))
       {{-- <livewire:dashboard.dashboard-supervisor /> --}}
   @endif

   {{-- ADMIN (más adelante) --}}
   @if(auth()->user()->hasRole('Admin'))
       {{-- <livewire:dashboard.dashboard-admin /> --}}
   @endif

   {{-- CLIENTE (más adelante) --}}
   @if(auth()->user()->hasRole('Cliente'))
       {{-- <livewire:dashboard.dashboard-cliente /> --}}
   @endif
    </div>
</x-layouts.app>
