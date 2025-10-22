<?php

namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegimenSeeder extends Seeder
{
    public function run()
    {
        DB::table('regimenes')->insert([
            ['clave_sat' => '605', 'nombre' => 'Sueldos y Salarios e Ingresos Asimilados', 'tipo_persona' => 'física'],
            ['clave_sat' => '606', 'nombre' => 'Arrendamiento de Inmuebles', 'tipo_persona' => 'física'],
            ['clave_sat' => '612', 'nombre' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'tipo_persona' => 'física'],
            ['clave_sat' => '601', 'nombre' => 'General de Ley Personas Morales', 'tipo_persona' => 'moral'],
            ['clave_sat' => '603', 'nombre' => 'Personas Morales con Fines no Lucrativos', 'tipo_persona' => 'moral'],
        ]);
    }
}