<x-layouts.app>
    <style>
        [x-cloak] {
            display: none !important
        }
    </style>

    <div class="px-4 py-6 max-w-7xl mx-auto">
        <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-amber-900">
                EXPEDIENTE DE
                @empty($cliente->nombre)
                    {{ $cliente->razon_social }}
                @else
                    {{ $cliente->nombre }}
                @endempty
            </h2>
            <a href="{{ route('clientes.index') }}" class="text-sm text-amber-600 hover:underline">
                ← Volver a clientes
            </a>
        </div>

        {{-- Tabs --}}
        <div x-data="{
            tab: 'datos',
            getInitial() {
                const s = new URLSearchParams(window.location.search).get('tab');
                const h = (window.location.hash || '').replace('#', '');
                return s || h || 'datos';
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
                <button role="tab" :aria-selected="tab === 'datos'" :tabindex="tab === 'datos' ? 0 : -1"
                    @click="tab = 'datos'" @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                    :class="tab === 'datos' ? 'font-bold border-b-2 border-amber-800' : ''"
                    class="pb-1 focus:outline-none ">
                    Datos Generales
                </button>

                <button role="tab" :aria-selected="tab === 'fiscales'" :tabindex="tab === 'fiscales' ? 0 : -1"
                    @click="tab = 'fiscales'" @keydown.arrow-right.prevent="move(1)"
                    @keydown.arrow-left.prevent="move(-1)"
                    :class="tab === 'fiscales' ? 'font-bold border-b-2 border-amber-800' : ''"
                    class="pb-1 focus:outline-none ">
                    Datos Fiscales
                </button>

                <button role="tab" :aria-selected="tab === 'contrasenas'" :tabindex="tab === 'contrasenas' ? 0 : -1"
                    @click="tab = 'contrasenas'" @keydown.arrow-right.prevent="move(1)"
                    @keydown.arrow-left.prevent="move(-1)"
                    :class="tab === 'contrasenas' ? 'font-bold border-b-2 border-amber-800' : ''"
                    class="pb-1 focus:outline-none ">
                    Contraseñas
                </button>

                @if (auth()->check() && auth()->user()->hasRole('admin_despacho'))
                    <button id="tab-regularizaciones" role="tab" :aria-selected="tab === 'tareas'"
                        :tabindex="tab === 'regularizaciones' ? 0 : -1" @click="tab = 'regularizaciones'"
                        @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                        :class="tab === 'regularizaciones' ? 'font-bold border-b-2 border-amber-800' : ''"
                        class="pb-1 focus:outline-none ">
                        Regularizaciones
                    </button>


                    <button id="tab-obligaciones" role="tab" :aria-selected="tab === 'obligaciones'"
                        :tabindex="tab === 'obligaciones' ? 0 : -1" @click="tab = 'obligaciones'"
                        @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                        :class="tab === 'obligaciones' ? 'font-bold border-b-2 border-amber-800' : ''"
                        class="pb-1 focus:outline-none
                               {{ $obligacionesCompletadas ? 'text-green-600 border-green-600' : 'text-red-600 border-red-600' }}">
                        Asignar Obligaciones
                    </button>

                    <button id="tab-tareas" role="tab" :aria-selected="tab === 'tareas'"
                        :tabindex="tab === 'tareas' ? 0 : -1" @click="tab = 'tareas'"
                        @keydown.arrow-right.prevent="move(1)" @keydown.arrow-left.prevent="move(-1)"
                        :class="tab === 'tareas' ? 'font-bold border-b-2 border-amber-800' : ''"
                        class="pb-1 focus:outline-none ">
                        Asignar Tareas
                    </button>
                @endif

            </nav>

            {{-- Contenidos --}}
            <section x-show="tab === 'tareas'" x-cloak x-transition.opacity role="tabpanel"
                aria-labelledby="tab-tareas">
                @livewire('control.tareas-asignadas-crud', ['cliente' => $cliente], key('tareas-' . $cliente->id))
            </section>

            <section x-show="tab === 'obligaciones'" x-cloak x-transition.opacity role="tabpanel"
                aria-labelledby="tab-obligaciones">
                @livewire('control.obligaciones-asignadas', ['cliente' => $cliente], key('obligaciones-' . $cliente->id))
            </section>

            <section x-show="tab === 'regularizaciones'" x-cloak x-transition.opacity role="tabpanel"
                aria-labelledby="tab-regularizaciones">
                @livewire('clientes.regularizacion-obligaciones', ['cliente' => $cliente], key('regularizaciones-' . $cliente->id))
            </section>


            <section x-show="tab === 'fiscales'" x-cloak x-transition.opacity role="tabpanel"
                aria-labelledby="tab-fiscales">
                @livewire('clientes.datos-fiscales', ['cliente' => $cliente], key('fiscales-' . $cliente->id))
            </section>

            <section x-show="tab === 'contrasenas'" x-cloak x-transition.opacity role="tabpanel"
                aria-labelledby="tab-contrasenas">
                @livewire('clientes.cliente-contrasena', ['cliente' => $cliente], key('contrasenas-' . $cliente->id))
            </section>

            <section x-show="tab === 'datos'" x-cloak x-transition.opacity role="tabpanel" aria-labelledby="tab-datos">
                @livewire('clientes.datos-generales', ['cliente' => $cliente], key('datos-' . $cliente->id))
            </section>
        </div>
    </div>

    {{-- Script para actualizar colores en tiempo real --}}
    <script>
        document.addEventListener('estado-obligaciones', e => {
            const tab = document.getElementById('tab-obligaciones');
            if (!tab) return;

            if (e.detail.completed) {
                tab.classList.add('text-green-600', 'border-green-600');
                tab.classList.remove('text-red-600', 'border-red-600');
            } else {
                tab.classList.add('text-red-600', 'border-red-600');
                tab.classList.remove('text-green-600', 'border-green-600');
            }
        });


        document.addEventListener('estado-tareas', e => {
            const tab = document.getElementById('tab-tareas');
            if (!tab) return;

            // Quita cualquier color previo
            tab.classList.remove(
                'text-green-600', 'border-green-600',
                'text-red-600', 'border-red-600',
                'text-yellow-600', 'border-yellow-600'
            );

            if (e.detail.completed) {
                // ✅ Completado → verde
                tab.classList.add('text-green-600', 'border-green-600');
            } else {
                // ⚠️ Incompleto → amarillo (coincide con tu clase inicial del Blade)
                tab.classList.add('text-yellow-600', 'border-yellow-600');
            }
        });
    </script>
</x-layouts.app>
