<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActividadEconomicaSeeder extends Seeder
{
    public function run()
    {
        DB::table('actividad_economicas')->insert([
            ['nombre' => 'Comercio al por menor de abarrotes', 'clave' => '461110', 'ponderacion' => 3],
            ['nombre' => 'Servicios contables y de auditoría', 'clave' => '541219', 'ponderacion' => 5],
            ['nombre' => 'Servicios de consultoría en informática', 'clave' => '541512', 'ponderacion' => 4],
            ['nombre' => 'Construcción de edificios residenciales', 'clave' => '236115', 'ponderacion' => 4],
            ['nombre' => 'Transporte de carga general', 'clave' => '484121', 'ponderacion' => 3],
        ]);
    }
}
