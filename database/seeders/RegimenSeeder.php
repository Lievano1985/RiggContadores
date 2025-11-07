<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RegimenSeeder extends Seeder
{
    public function run()
    {
        

        DB::table('regimenes')->insert([
            ['clave_sat' => '601', 'nombre' => 'General de Ley Personas Morales', 'tipo_persona' => 'moral'],
            ['clave_sat' => '603', 'nombre' => 'Personas Morales con Fines no Lucrativos', 'tipo_persona' => 'moral'],
            ['clave_sat' => '605', 'nombre' => 'Sueldos y Salarios e Ingresos Asimilados', 'tipo_persona' => 'física'],
            ['clave_sat' => '606', 'nombre' => 'Arrendamiento de Inmuebles', 'tipo_persona' => 'física'],
            ['clave_sat' => '607', 'nombre' => 'Régimen de Enajenación o Adquisición de Bienes', 'tipo_persona' => 'física'],
            ['clave_sat' => '608', 'nombre' => 'Demás ingresos', 'tipo_persona' => 'física'],
            ['clave_sat' => '610', 'nombre' => 'Residentes en el Extranjero sin Establecimiento Permanente en México', 'tipo_persona' => 'moral'],
            ['clave_sat' => '611', 'nombre' => 'Ingresos por Dividendos (socios y accionistas)', 'tipo_persona' => 'física'],
            ['clave_sat' => '612', 'nombre' => 'Personas Físicas con Actividades Empresariales y Profesionales', 'tipo_persona' => 'física'],
            ['clave_sat' => '614', 'nombre' => 'Ingresos por intereses', 'tipo_persona' => 'física'],
            ['clave_sat' => '615', 'nombre' => 'Régimen de los ingresos por obtención de premios', 'tipo_persona' => 'física'],
            ['clave_sat' => '616', 'nombre' => 'Sin obligaciones fiscales', 'tipo_persona' => 'física'],
            ['clave_sat' => '620', 'nombre' => 'Sociedades Cooperativas de Producción que optan por diferir sus ingresos', 'tipo_persona' => 'moral'],
            ['clave_sat' => '621', 'nombre' => 'Incorporación Fiscal', 'tipo_persona' => 'física'],
            ['clave_sat' => '622', 'nombre' => 'Actividades Agrícolas, Ganaderas, Silvícolas y Pesqueras', 'tipo_persona' => 'física'],
            ['clave_sat' => '623', 'nombre' => 'Opcional para Grupos de Sociedades', 'tipo_persona' => 'moral'],
            ['clave_sat' => '624', 'nombre' => 'Coordinados', 'tipo_persona' => 'moral'],
            ['clave_sat' => '625', 'nombre' => 'Actividades Empresariales con ingresos a través de Plataformas Tecnológicas', 'tipo_persona' => 'física'],
            ['clave_sat' => '626', 'nombre' => 'Régimen Simplificado de Confianza (RESICO)', 'tipo_persona' => 'física'],
            ['clave_sat' => '628', 'nombre' => 'Hidrocarburos', 'tipo_persona' => 'moral'],
            ['clave_sat' => '629', 'nombre' => 'De los Regímenes Fiscales Preferentes y de las Empresas Multinacionales', 'tipo_persona' => 'moral'],
        ]);
    }
}
