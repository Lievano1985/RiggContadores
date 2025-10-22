<x-layouts.app>
    <style>[x-cloak]{display:none!important}</style>

    {{-- resources/views/contador/asignaciones.blade.php --}}
    <div 
         x-data="{
            tab: 'obligaciones',
            getInitial() {
                const s = new URLSearchParams(window.location.search).get('tab');
                const h = (window.location.hash || '').replace('#','');
                return s || h || 'obligaciones';
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
         }"
         x-init="focusables = Array.from($el.querySelectorAll('[role=tab]'))"
    >
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-bold text-stone-600">Asignaciones</h2>
        </div>

        {{-- **MISMO ESTILO** que el expediente: space-x-4 + border-b + pb-2 --}}
        <nav class="flex space-x-4 border-b pb-2" role="tablist" aria-label="Asignaciones">
            <button
                id="tab-obligaciones"
                role="tab"
                :aria-selected="tab === 'obligaciones'"
                :tabindex="tab === 'obligaciones' ? 0 : -1"
                aria-controls="panel-obligaciones"
                @click="tab = 'obligaciones'"
                @keydown.arrow-right.prevent="move(1)"
                @keydown.arrow-left.prevent="move(-1)"
                :class="tab === 'obligaciones' ? 'font-bold border-b-2 border-amber-800' : ''"
                class="pb-1 focus:outline-none"
            >
                Mis obligaciones
            </button>

            <button
                id="tab-tareas"
                role="tab"
                :aria-selected="tab === 'tareas'"
                :tabindex="tab === 'tareas' ? 0 : -1"
                aria-controls="panel-tareas"
                @click="tab = 'tareas'"
                @keydown.arrow-right.prevent="move(1)"
                @keydown.arrow-left.prevent="move(-1)"
                :class="tab === 'tareas' ? 'font-bold border-b-2 border-amber-800' : ''"
                class="pb-1 focus:outline-none"
            >
                Mis tareas
            </button>
        </nav>

        <section
            id="panel-obligaciones"
            role="tabpanel"
            aria-labelledby="tab-obligaciones"
            x-show="tab === 'obligaciones'"
            x-cloak
            x-transition.opacity
            class="mt-4"
        >
            <livewire:contador.obligaciones-index :key="'obligaciones-' . auth()->id()" />
        </section>

        <section
            id="panel-tareas"
            role="tabpanel"
            aria-labelledby="tab-tareas"
            x-show="tab === 'tareas'"
            x-cloak
            x-transition.opacity
            class="mt-4">
                <livewire:contador.mis-tareas-index :key="'mis-tareas-' . auth()->id()" />
         
        </section>
    </div>
</x-layouts.app>
