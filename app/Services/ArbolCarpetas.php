<?php

namespace App\Services;

use App\Models\CarpetaDrive;

class ArbolCarpetas
{
    /**
     * Devuelve un árbol anidado:
     * [
     *   ['id'=>1,'nombre'=>'Cliente XYZ','children'=>[
     *      ['id'=>5,'nombre'=>'1-Documentos','children'=>[...]],
     *   ]],
     * ]
     */
    public static function obtenerArbol(int $clienteId): array
{
    $carpetas = CarpetaDrive::where('cliente_id', $clienteId)
        ->select('id','nombre','parent_id')
        ->orderBy('nombre')
        ->get();

    $byId = [];
    foreach ($carpetas as $c) {
        $byId[$c->id] = [
            'id' => $c->id,
            'nombre' => $c->nombre,
            'parent_id' => $c->parent_id,
            'children' => [],
        ];
    }

    $root = [];
    foreach ($byId as $id => &$n) {
        if ($n['parent_id'] && isset($byId[$n['parent_id']])) {
            $byId[$n['parent_id']]['children'][] = &$n;
        } else {
            $root[] = &$n;
        }
    }
    unset($n);

    $ordenar = function (&$nodos) use (&$ordenar) {
        usort($nodos, fn($a,$b)=>strcmp($a['nombre'],$b['nombre']));
        foreach ($nodos as &$n) {
            if (!empty($n['children'])) $ordenar($n['children']);
        }
    };
    $ordenar($root);

    return $root;
}
}
