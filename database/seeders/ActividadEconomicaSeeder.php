<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ActividadEconomicaSeeder extends Seeder
{
    public function run()
    {
        DB::table('actividad_economicas')->insert([
            // Comercio
            ['nombre' => 'Comercio al por menor de abarrotes', 'clave' => 'C001', 'ponderacion' => 3],
            ['nombre' => 'Comercio al por menor de ropa y accesorios', 'clave' => 'C002', 'ponderacion' => 3],
            ['nombre' => 'Comercio al por mayor de alimentos', 'clave' => 'C003', 'ponderacion' => 4],
            ['nombre' => 'Comercio al por mayor de maquinaria y equipo', 'clave' => 'C004', 'ponderacion' => 4],
            ['nombre' => 'Tiendas de autoservicio y departamentales', 'clave' => 'C005', 'ponderacion' => 5],

            // Servicios profesionales
            ['nombre' => 'Servicios contables y de auditoría', 'clave' => 'S001', 'ponderacion' => 5],
            ['nombre' => 'Servicios legales', 'clave' => 'S002', 'ponderacion' => 5],
            ['nombre' => 'Servicios de consultoría en informática', 'clave' => 'S003', 'ponderacion' => 4],
            ['nombre' => 'Servicios de arquitectura y diseño', 'clave' => 'S004', 'ponderacion' => 4],
            ['nombre' => 'Servicios de ingeniería civil', 'clave' => 'S005', 'ponderacion' => 4],

            // Construcción
            ['nombre' => 'Construcción de edificios residenciales', 'clave' => 'B001', 'ponderacion' => 4],
            ['nombre' => 'Construcción de obras de infraestructura carretera', 'clave' => 'B002', 'ponderacion' => 5],
            ['nombre' => 'Construcción de obras hidráulicas', 'clave' => 'B003', 'ponderacion' => 5],
            ['nombre' => 'Remodelación y mantenimiento de edificios', 'clave' => 'B004', 'ponderacion' => 3],
            ['nombre' => 'Urbanización y fraccionamientos', 'clave' => 'B005', 'ponderacion' => 4],

            // Transporte
            ['nombre' => 'Transporte de carga general', 'clave' => 'T001', 'ponderacion' => 3],
            ['nombre' => 'Transporte de pasajeros por carretera', 'clave' => 'T002', 'ponderacion' => 3],
            ['nombre' => 'Transporte aéreo nacional', 'clave' => 'T003', 'ponderacion' => 5],
            ['nombre' => 'Transporte marítimo y fluvial', 'clave' => 'T004', 'ponderacion' => 4],
            ['nombre' => 'Servicios de paquetería y mensajería', 'clave' => 'T005', 'ponderacion' => 4],

            // Manufactura
            ['nombre' => 'Fabricación de productos de panadería', 'clave' => 'M001', 'ponderacion' => 3],
            ['nombre' => 'Fabricación de muebles de madera', 'clave' => 'M002', 'ponderacion' => 3],
            ['nombre' => 'Fabricación de productos químicos', 'clave' => 'M003', 'ponderacion' => 4],
            ['nombre' => 'Industria automotriz', 'clave' => 'M004', 'ponderacion' => 5],
            ['nombre' => 'Fabricación de textiles y prendas de vestir', 'clave' => 'M005', 'ponderacion' => 3],

            // Agricultura y ganadería
            ['nombre' => 'Cultivo de maíz y otros cereales', 'clave' => 'A001', 'ponderacion' => 3],
            ['nombre' => 'Cultivo de frutas y hortalizas', 'clave' => 'A002', 'ponderacion' => 3],
            ['nombre' => 'Cría de ganado bovino', 'clave' => 'A003', 'ponderacion' => 4],
            ['nombre' => 'Avicultura', 'clave' => 'A004', 'ponderacion' => 4],
            ['nombre' => 'Pesca y acuicultura', 'clave' => 'A005', 'ponderacion' => 3],

            // Educación y salud
            ['nombre' => 'Escuelas de educación básica', 'clave' => 'E001', 'ponderacion' => 4],
            ['nombre' => 'Escuelas de educación superior', 'clave' => 'E002', 'ponderacion' => 5],
            ['nombre' => 'Servicios médicos generales', 'clave' => 'H001', 'ponderacion' => 5],
            ['nombre' => 'Servicios de hospitales privados', 'clave' => 'H002', 'ponderacion' => 5],
            ['nombre' => 'Servicios odontológicos', 'clave' => 'H003', 'ponderacion' => 4],

            // Turismo y entretenimiento
            ['nombre' => 'Hoteles y moteles', 'clave' => 'R001', 'ponderacion' => 4],
            ['nombre' => 'Restaurantes con servicio de mesa', 'clave' => 'R002', 'ponderacion' => 3],
            ['nombre' => 'Bares y centros nocturnos', 'clave' => 'R003', 'ponderacion' => 3],
            ['nombre' => 'Agencias de viajes', 'clave' => 'R004', 'ponderacion' => 4],
            ['nombre' => 'Parques recreativos y temáticos', 'clave' => 'R005', 'ponderacion' => 3],

            // Servicios financieros
            ['nombre' => 'Banca múltiple', 'clave' => 'F001', 'ponderacion' => 5],
            ['nombre' => 'Aseguradoras', 'clave' => 'F002', 'ponderacion' => 5],
            ['nombre' => 'Casas de bolsa', 'clave' => 'F003', 'ponderacion' => 5],
            ['nombre' => 'Sociedades de inversión', 'clave' => 'F004', 'ponderacion' => 4],
            ['nombre' => 'Financieras populares', 'clave' => 'F005', 'ponderacion' => 4],

            // Otros servicios
            ['nombre' => 'Servicios de limpieza', 'clave' => 'O001', 'ponderacion' => 3],
            ['nombre' => 'Servicios de seguridad privada', 'clave' => 'O002', 'ponderacion' => 4],
            ['nombre' => 'Servicios de reparación de vehículos', 'clave' => 'O003', 'ponderacion' => 3],
            ['nombre' => 'Servicios de peluquería y estética', 'clave' => 'O004', 'ponderacion' => 2],
            ['nombre' => 'Servicios de fotografía', 'clave' => 'O005', 'ponderacion' => 2],
        ]);
    }
}
