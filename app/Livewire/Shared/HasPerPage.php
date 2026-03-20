<?php

namespace App\Livewire\Shared;

trait HasPerPage
{
    public $perPage = '10';
    public array $perPageOptions = [10, 15, 25, 'all'];

    public function updatedPerPage($value): void
    {
        $permitidos = ['10', '15', '25', 'all', 10, 15, 25];
        if (!in_array($value, $permitidos, true)) {
            $this->perPage = '10';
        }

        $this->resetPage();
    }

    protected function perPageValue($query, int $default = 10): int
    {
        $valor = is_scalar($this->perPage) ? (string) $this->perPage : '10';

        if ($valor === 'all') {
            if (!is_object($query) || !method_exists($query, 'toBase')) {
                return $default;
            }

            $total = (clone $query)->toBase()->getCountForPagination();
            return max((int) $total, 1);
        }

        $perPage = (int) $valor;
        return $perPage > 0 ? $perPage : $default;
    }
}
