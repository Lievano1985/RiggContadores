<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use Livewire\Volt\Volt;
use App\Livewire\Clientes\ClientesIndex;
use App\Livewire\SuperAdmin\DespachosIndex;
use App\Livewire\Despachos\DespachoPerfil;
use App\Http\Controllers\ClienteExpedienteController;
use App\Http\Controllers\ClienteNotificacionController;
use App\Http\Controllers\ContadorAsignacionesController;
use App\Livewire\Control\ObligacionesAsignadas;
use App\Livewire\Catalogos\RegimenesCrud;
use App\Livewire\Catalogos\ObligacionesCrud;
use App\Livewire\Catalogos\ActividadesCrud;
use App\Livewire\Catalogos\ObligacionesTareas;
use App\Livewire\Catalogos\TareasCrud;
use App\Livewire\Clientes\ClienteContrasena;
use App\Livewire\Clientes\ClientesPortal;
use App\Livewire\Contador\MisTareasIndex;
use App\Livewire\Contador\ObligacionesIndex;
use App\Livewire\Control\TareasAsignadasCrud;
use App\Livewire\Control\ValidacionesIndex;
use App\Livewire\Notificaciones\ListaClientes;
use App\Livewire\Usuarios\UsuariosIndex;

Route::get('/', function () {
    return view('welcome');
})->name('home');


Route::middleware(['auth', 'role:super_admin'])->group(function () {
    Route::get('/despacho', DespachosIndex::class)->name('despachos.index');
});

Route::middleware(['auth', 'role:admin_despacho||super_admin'])->group(function () {
    Route::get('/despacho/perfil', DespachoPerfil::class)->name('despacho.perfil');
    Route::get('/catalogos/regimenes', RegimenesCrud::class)->name('catalogos.regimenes-crud');
    Route::get('/catalogos/tareas', TareasCrud::class)->name('catalogos.tareas-crud');
    Route::get('/catalogos/obligaciones', ObligacionesCrud::class)->name('catalogos.obligaciones-crud');
    Route::get('/catalogos/actividades', ActividadesCrud::class)->name('catalogos.actividades-crud');
    Route::get('/usuarios/index', UsuariosIndex::class)->name('Usuarios.index');
    Route::get('/control/obligaciones-asignadas', ObligacionesAsignadas::class)->name('control.obligaciones-asignadas');
    Route::get('/control/tareas-asignadas-crud', TareasAsignadasCrud::class)->name('control.tareas-asignadas-crud');
    Route::get('/catalogos/Obligaciones-tareas', ObligacionesTareas::class)->name('catalogos.obligaciones-tareas');
    Route::get('/control/validaciones', ValidacionesIndex::class)->name('control.validaciones.index');

    //**#####Notificaciones######### */

    Route::get('/notificaciones/clientes', ListaClientes::class)->name('notificaciones.clientes.index');

    Route::get('/Notificaciones/{cliente}/Notificaciones', [ClienteNotificacionController::class, 'show'])
        ->name('clientes.notificaciones.show');

});


Route::middleware(['auth', 'role:contador||admin_despacho'])->group(function () {
    Route::get('/contador/obligaciones', ObligacionesIndex::class)->name('contador.obligaciones');
    Route::get('/contador/mistareas', MisTareasIndex::class)->name('contador.mistareas');

    Route::get('/clientes/index', ClientesIndex::class)->name('clientes.index');
    Route::get('/clientes/{cliente}/expediente', [ClienteExpedienteController::class, 'show'])
        ->name('clientes.expediente.show');
    Route::get('/contador/asignaciones', [ContadorAsignacionesController::class, 'index'])
        ->name('contadores.asignaciones.index');
});


Route::middleware(['auth', 'role:cliente||admin_despacho'])->group(function () {
    Route::get('/clientes/portal', ClientesPortal::class)->name('Clientes.portal');
});



Route::view('dashboard', 'dashboard')->middleware(['auth', 'verified'])->name('dashboard');



Route::middleware(['auth'])->group(function () {
    Route::redirect('settings', 'settings/profile');
    Volt::route('settings/profile', 'settings.profile')->name('settings.profile');
    Volt::route('settings/password', 'settings.password')->name('settings.password');
    Volt::route('settings/appearance', 'settings.appearance')->name('settings.appearance');
    //Route::view('clientes-homeClientes', 'clientes.homeClientes')->name('clientes.homeClientes');

});


require __DIR__ . '/auth.php';
