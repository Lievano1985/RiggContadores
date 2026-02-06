<x-layouts.app>
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    <div class="px-4 py-6 max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-amber-900">
              NOTIFICACIONES DE 
                @empty($cliente->nombre)
                    {{ $cliente->razon_social }}
                @else
                    {{ $cliente->nombre }}
                @endempty
            </h2>
            <a href="{{ route('notificaciones.clientes.index') }}" class="text-sm text-amber-600 hover:underline">
                ← Volver a clientes
            </a>
        </div>

        {{-- Tabs --}}
        <div x-data="{
            tab: 'nueva',
            getInitial() {
                const s = new URLSearchParams(window.location.search).get('tab');
                const h = (window.location.hash || '').replace('#', '');
                return s || h || 'nueva';
            },
            setUrl(v) {
                const url = new URL(window.location);
                url.searchParams.set('tab', v);
                url.hash = v;
                window.history.replaceState({ tab: v }, '', url);
            },
            init() {
                this.tab = this.getInitial();
                this.$watch('tab', (v) => this.setUrl(v));
                window.addEventListener('popstate', (e) => {
                    if (e.state && e.state.tab) this.tab = e.state.tab;
                    else this.tab = this.getInitial();
                });
            },
            focusables: [],
            move(dir) {
                if (!this.focusables.length) return;
                const current = document.activeElement;
                const i = this.focusables.indexOf(current);
                if (i === -1) return;
                const next = this.focusables[(i + dir + this.focusables.length) % this.focusables.length];
                next?.focus();
            }
        }" x-init="focusables = Array.from($el.querySelectorAll('[role=tab]'));" class="space-y-4">
            {{-- Header de tabs --}}
            <nav class="flex space-x-4 border-b pb-2" role="tablist" aria-label="Expediente del cliente">
                <button role="tab" :aria-selected="tab === 'nueva'" :tabindex="tab === 'nueva' ? 0 : -1"
                    @click="tab = 'nueva'" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                    :class="tab === 'nueva' ? 'font-bold border-b-2 border-amber-800' : ''"
                    class="pb-1 focus:outline-none ">
                    Nueva Notificación </button>

                <button role="tab" :aria-selected="tab === 'historial'" :tabindex="tab === 'historial' ? 0 : -1"
                    @click="tab = 'historial'" @keydown.arrow-right.prevent="move(1)"
                    @keydown.arrow-left.prevent="move(-1)"
                    :class="tab === 'historial' ? 'font-bold border-b-2 border-amber-800' : ''"
                    class="pb-1 focus:outline-none ">
                    Historial </button>


            </nav>

            {{-- Contenidos --}}

         
            <section x-show="tab === 'historial'" x-cloak x-transition.opacity role="tabpanel" aria-labelledby="tab-historial">
                @livewire('notificaciones.lista-notificaciones', ['cliente' => $cliente], key('historial-' . $cliente->id))
            </section>

            <section x-show="tab === 'nueva'" x-cloak x-transition.opacity role="tabpanel" aria-labelledby="tab-nueva">
                @livewire('notificaciones.crear-notificacion', ['cliente' => $cliente], key('nueva-' . $cliente->id))
            </section>

        
        </div>
    </div>


</x-layouts.app>
