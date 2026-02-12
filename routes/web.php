<?php

use Illuminate\Support\Facades\Route;
use Livewire\Volt\Volt;

/* ===============================
|  Livewire Components
=================================*/
use App\Livewire\SuperAdmin\DespachosIndex;
use App\Livewire\Despachos\DespachoPerfil;
use App\Livewire\Clientes\ClientesIndex;
use App\Livewire\Clientes\ClientesPortal;
use App\Livewire\Contador\MisTareasIndex;
use App\Livewire\Contador\ObligacionesIndex;
use App\Livewire\Control\ObligacionesAsignadas;
use App\Livewire\Control\TareasAsignadasCrud;
use App\Livewire\Control\ValidacionesIndex;
use App\Livewire\Catalogos\RegimenesCrud;
use App\Livewire\Catalogos\ObligacionesCrud;
use App\Livewire\Catalogos\ActividadesCrud;
use App\Livewire\Catalogos\ObligacionesTareas;
use App\Livewire\Catalogos\TareasCrud;
use App\Livewire\Notificaciones\ListaClientes;
use App\Livewire\Usuarios\UsuariosIndex;

/* ===============================
|  Controllers
=================================*/
use App\Http\Controllers\ClienteExpedienteController;
use App\Http\Controllers\ClienteNotificacionController;
use App\Http\Controllers\ContadorAsignacionesController;

/* ===============================
|  Ruta pública
=================================*/
Route::get('/', fn () => view('welcome'))->name('home');


/* ==========================================================
|  SUPER ADMIN
==========================================================*/
Route::middleware(['auth', 'role:super_admin'])->group(function () {

    Route::get('/despacho', DespachosIndex::class)
        ->name('despachos.index');
});


/* ==========================================================
|  ADMIN DESPACHO / SUPER ADMIN
==========================================================*/
Route::middleware(['auth', 'role:admin_despacho|super_admin'])->group(function () {

    Route::get('/despacho/perfil', DespachoPerfil::class)
        ->name('despacho.perfil');
});


/* ==========================================================
|  CONFIGURACIONES DESPACHO
|  (admin_despacho | super_admin | supervisor)
==========================================================*/
Route::middleware(['auth', 'role:admin_despacho|super_admin|supervisor'])->group(function () {

    /* ===== Catálogos ===== */
    Route::get('/catalogos/regimenes', RegimenesCrud::class)
        ->name('catalogos.regimenes-crud');

    Route::get('/catalogos/tareas', TareasCrud::class)
        ->name('catalogos.tareas-crud');

    Route::get('/catalogos/obligaciones', ObligacionesCrud::class)
        ->name('catalogos.obligaciones-crud');

    Route::get('/catalogos/actividades', ActividadesCrud::class)
        ->name('catalogos.actividades-crud');

    Route::get('/catalogos/obligaciones-tareas', ObligacionesTareas::class)
        ->name('catalogos.obligaciones-tareas');


    /* ===== Control Interno ===== */
    Route::get('/control/obligaciones-asignadas', ObligacionesAsignadas::class)
        ->name('control.obligaciones-asignadas');

    Route::get('/control/tareas-asignadas-crud', TareasAsignadasCrud::class)
        ->name('control.tareas-asignadas-crud');

    Route::get('/control/validaciones', ValidacionesIndex::class)
        ->name('control.validaciones.index');


    /* ===== Usuarios ===== */
    Route::get('/usuarios/index', UsuariosIndex::class)
        ->name('Usuarios.index');


    /* ===== Notificaciones ===== */
    Route::get('/notificaciones/clientes', ListaClientes::class)
        ->name('notificaciones.clientes.index');

    Route::get('/notificaciones/{cliente}', [ClienteNotificacionController::class, 'show'])
        ->name('clientes.notificaciones.show');
});


/* ==========================================================
|  CONTADOR / SUPERVISOR / ADMIN DESPACHO
==========================================================*/
Route::middleware(['auth', 'role:admin_despacho|supervisor|contador'])->group(function () {

    /* ===== Panel Contador ===== */
    Route::get('/contador/obligaciones', ObligacionesIndex::class)
        ->name('contador.obligaciones');

    Route::get('/contador/mistareas', MisTareasIndex::class)
        ->name('contador.mistareas');

    Route::get('/contador/asignaciones', [ContadorAsignacionesController::class, 'index'])
        ->name('contadores.asignaciones.index');


    /* ===== Clientes ===== */
    Route::get('/clientes/index', ClientesIndex::class)
        ->name('clientes.index');

    Route::get('/clientes/{cliente}/expediente', [ClienteExpedienteController::class, 'show'])
        ->name('clientes.expediente.show');
});


/* ==========================================================
|  PORTAL CLIENTE
==========================================================*/
Route::middleware(['auth', 'role:cliente|admin_despacho'])->group(function () {

    Route::get('/clientes/portal', ClientesPortal::class)
        ->name('Clientes.portal');
});


/* ==========================================================
|  RUTAS GENERALES AUTENTICADAS
==========================================================*/
Route::middleware(['auth'])->group(function () {

    Route::view('/dashboard', 'dashboard')
        ->middleware('verified')
        ->name('dashboard');

    Route::redirect('/settings', '/settings/profile');

    Volt::route('settings/profile', 'settings.profile')
        ->name('settings.profile');

    Volt::route('settings/password', 'settings.password')
        ->name('settings.password');

    Volt::route('settings/appearance', 'settings.appearance')
        ->name('settings.appearance');
});


require __DIR__ . '/auth.php';
