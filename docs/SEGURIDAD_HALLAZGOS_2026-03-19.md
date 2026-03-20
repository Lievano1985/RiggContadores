# Auditoria de Seguridad Laravel - Hallazgos Iniciales

Fecha: 2026-03-19
Estado: Pendiente de remediacion (trabajo por etapas)

## Criticos

1. IDOR en expediente de cliente
- Archivo: app/Http/Controllers/ClienteExpedienteController.php
- Bloque: show(Cliente $cliente)
- Riesgo: acceso a expediente de clientes de otros despachos por ID en URL.
- Sugerencia: policy/authorize + filtro por despacho.

2. IDOR en vista de notificaciones por cliente
- Archivo: app/Http/Controllers/ClienteNotificacionController.php
- Bloque: show(Cliente $cliente)
- Riesgo: acceso a notificaciones de clientes ajenos.
- Sugerencia: policy por cliente + filtro por despacho.

3. Acciones de validacion sin scope por recurso
- Archivo: app/Livewire/Control/ValidacionesIndex.php
- Bloques: abrirSidebar, marcarTareaRevisada, rechazarTarea, rechazarObligacion, finalizarObligacion.
- Riesgo: modificar/leer obligaciones o tareas fuera del ambito autorizado.
- Sugerencia: helper findVisible* con filtros por rol/tenant.

4. CRUD de tareas asignadas sin validar pertenencia
- Archivo: app/Livewire/Control/TareasAsignadasCrud.php
- Bloques: editar/eliminar/find de pivots.
- Riesgo: IDOR por IDs manipulados en acciones Livewire.
- Sugerencia: scope por cliente_id en todos los find/findOrFail.

5. CRUD de obligaciones asignadas sin validar pertenencia
- Archivo: app/Livewire/Control/ObligacionesAsignadas.php
- Bloques: confirmarBaja, editar, guardar.
- Riesgo: edicion/baja de registros de otro cliente.
- Sugerencia: scope por cliente_id y despacho.

6. Posible escalacion de privilegios en modulo usuarios
- Archivos: routes/web.php, app/Livewire/Usuarios/UsuariosIndex.php
- Riesgo: supervisor con acceso a ruta podria invocar mutaciones.
- Sugerencia: restringir ruta y validar rol en metodos mutables.

7. Posible escalacion de privilegios en modulo clientes
- Archivos: routes/web.php, app/Livewire/Clientes/ClientesIndex.php
- Riesgo: contador/supervisor podria mutar clientes si invoca acciones Livewire.
- Sugerencia: restringir mutaciones por rol + scope por despacho.

8. Manipulacion de IDs en notificaciones
- Archivo: app/Livewire/Notificaciones/CrearNotificacion.php
- Bloques: obligacionesSeleccionadas, update estatus whereIn.
- Riesgo: actualizar obligaciones de otros clientes si se fuerza payload.
- Sugerencia: revalidar server-side por cliente_id antes de leer/actualizar.

## Medios

1. IDOR en detalle de notificacion
- Archivo: app/Livewire/Notificaciones/ListaNotificaciones.php
- Bloque: abrirSidebar(findOrFail)
- Sugerencia: where('cliente_id', $this->cliente->id)->findOrFail().

2. Listado global de clientes en notificaciones sin scoping
- Archivo: app/Livewire/Notificaciones/ListaClientes.php
- Bloque: render()
- Sugerencia: filtrar por despacho para roles no super_admin.

3. Upload por blocklist, no por allowlist
- Archivo: app/Livewire/Shared/ArchivosAdjuntosCrud.php
- Bloque: rules() archivos
- Sugerencia: mimes/mimetypes permitidos + validacion estricta.

4. Secretos sensibles en .env local y debug activo
- Archivo: .env
- Riesgo: fuga accidental de credenciales.
- Sugerencia: rotacion de secretos + politicas de entorno + APP_DEBUG=false fuera de local.

## Bajos

1. No se observaron SQL injections directas en consultas revisadas
- Se usa mayormente Eloquent/Query Builder con whitelists de sort.

2. Rutas administrativas usan auth+role
- Reforzar con policies por recurso para defensa en profundidad.

## Nota de trabajo

Estos hallazgos se atacaran uno a uno en commits pequenos para facilitar revision y rollback.
