<div class="mt-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
    <div class="flex flex-wrap items-center gap-3 text-sm text-gray-700 dark:text-gray-200">
        <span>
            Mostrando {{ $paginator->firstItem() ?? 0 }} a {{ $paginator->lastItem() ?? 0 }} de {{ $paginator->total() }} resultados
        </span>

        <span class="font-medium">Ver:</span>
        <select
            wire:model.live="perPage"
            class="px-3 py-2 border rounded dark:bg-gray-700 dark:text-white border-gray-300 dark:border-gray-600 focus:border-amber-600 focus:ring focus:ring-amber-500/40 focus:outline-none"
        >
            <option value="10">10</option>
            <option value="15">15</option>
            <option value="25">25</option>
            <option value="all">Todos</option>
        </select>
    </div>

    <div>
        {{ $paginator->links('vendor.pagination.tailwind-links-only') }}
    </div>
</div>
