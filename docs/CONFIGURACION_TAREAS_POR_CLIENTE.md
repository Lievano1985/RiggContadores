# Configuración de tareas por cliente

## Estado

Primera versión implementada localmente. Este documento describe el comportamiento acordado, su impacto y los puntos que deben validarse en operación.

## Objetivo

No todos los clientes requieren todas las tareas definidas en el catálogo de una obligación. El sistema debe permitir habilitar o deshabilitar una tarea **para un cliente específico**, sin eliminar registros ni alterar la configuración de otros clientes.

La configuración debe funcionar igual sin importar cómo se creó la obligación: desde Datos fiscales, una regularización, una asignación manual o la generación programada.

## Experiencia de usuario acordada

En la tabla de tareas del cliente se agregará el botón **Configurar tareas**.

El botón abrirá un panel lateral con:

- Las obligaciones activas del cliente.
- Una sección colapsable por cada obligación.
- Las tareas activas del catálogo de esa obligación.
- Un check `Activa para este cliente` por tarea.
- Filtros para ver tareas activas, inactivas o todas.
- Un botón `Guardar configuración`; los checks no guardan ni muestran notificaciones individualmente.

Al desmarcar una tarea:

- No se elimina la tarea del catálogo global.
- No se elimina la tarea asignada ni sus archivos, comentarios o historial.
- La tarea se considera inactiva únicamente para ese cliente.
- No se generará en los periodos futuros de ese cliente.
- Permanecerá visible al filtrar por tareas inactivas y podrá reactivarse.

Al marcarla de nuevo, volverá a participar en las generaciones futuras del cliente.

La carpeta, el contador y el estado de una tarea asignada son configuraciones operativas de un periodo concreto. No determinan si una tarea aplica para el cliente y permanecen fuera de este panel.

## Alcance temporal

La configuración de este panel afecta las generaciones futuras por defecto.

No debe borrar, ocultar de manera irreversible ni cambiar automáticamente tareas de periodos anteriores o tareas en curso. Si posteriormente se requiere aplicar un cambio al periodo actual, deberá ser una acción explícita y confirmada, por ejemplo: `Aplicar también al periodo actual`.

## Modelo de datos propuesto

Crear una tabla de preferencias, por ejemplo `cliente_tarea_configuraciones`:

| Campo | Propósito |
| --- | --- |
| `id` | Identificador. |
| `cliente_id` | Cliente al que aplica la configuración. |
| `tarea_catalogo_id` | Tarea de catálogo configurada. |
| `activa` | Indica si se genera para este cliente. |
| `timestamps` | Auditoría básica. |

Debe existir una restricción única para `cliente_id + tarea_catalogo_id`.

La obligación se obtiene desde `tareas_catalogo.obligacion_id`, por lo que no es necesario duplicar `obligacion_id` en la tabla de preferencias.

### Regla de compatibilidad

Para no cambiar el comportamiento de clientes existentes, la ausencia de una preferencia significa que la tarea está activa para el cliente. Solo será necesario guardar una fila cuando una tarea se desactive, o se puede guardar cada cambio de manera explícita manteniendo el mismo resultado.

Una tarea inactiva globalmente en `tareas_catalogo.activo` no debe generarse para ningún cliente, aunque una preferencia individual diga que está activa.

## Cambios requeridos en la generación

Actualmente `GeneradorObligaciones::crearTareasPara()` crea todas las tareas de catálogo con `activo = true` para cada obligación generada. Deberá excluir las tareas cuya configuración para el cliente tenga `activa = false`.

La misma regla debe aplicarse en estos puntos:

1. Generación mensual programada: comando `obligaciones:generar`.
2. Generación manual de obligaciones atrasadas desde Regularización.
3. Alta de obligación periódica desde Datos fiscales.
4. Alta de obligación única desde Datos fiscales.
5. Sincronización de una tarea de catálogo a periodos existentes, cuando corresponda.

El método común de generación debe concentrar la regla para evitar que cada flujo aplique criterios distintos.

## Comportamiento que debe conservarse

- Las tareas ya asignadas conservan sus relaciones, archivos, comentarios, carpeta y trazabilidad.
- El catálogo de tareas sigue siendo global: desactivar una tarea para Cliente A no afecta a Cliente B.
- Los clientes que no hayan configurado nada continúan recibiendo todas las tareas activas del catálogo.
- La restricción existente que evita duplicar una tarea por cliente, catálogo, obligación y periodo debe mantenerse.
- La generación de obligaciones continúa siendo responsabilidad de los flujos actuales; este cambio solo determina qué tareas hijas se crean.

## Riesgos e impactos a revisar durante la implementación

1. **Fuentes duplicadas de creación.** Datos fiscales actualmente crea tareas directamente; el servicio `GeneradorObligaciones` también las crea para generaciones y regularizaciones. La consulta de preferencias debe reutilizarse en todos esos lugares.
2. **Tarea nueva en catálogo.** Deberá aparecer activa por defecto para los clientes que tengan esa obligación, salvo que exista una preferencia explícita en contrario.
3. **Desactivación global de catálogo.** Es distinta de desactivar para un cliente. La primera afecta a todos; la segunda solo bloquea futuras generaciones para un cliente.
4. **Periodo actual.** La desactivación desde el panel no debe eliminar ni modificar tareas actuales automáticamente. Una acción futura para aplicarlo al periodo actual requerirá definir qué hacer con tareas ya iniciadas o terminadas.
5. **Estados de tareas.** El código existente usa en algunos puntos el estado `cancelada`, pero la migración original de `tareas_asignadas` no lo contempla. Si se necesita un estado visual para una tarea desactivada, debe revisarse el esquema real y definir una migración compatible; la preferencia por cliente no debe depender de borrar tareas.
6. **Obligaciones dadas de baja.** El panel debe mostrar solo obligaciones activas del cliente. Las preferencias pueden conservarse para mantener la configuración si la obligación se reactiva más adelante.
7. **Permisos.** Deben conservarse las reglas actuales para que solo los roles autorizados puedan cambiar la configuración del cliente.

## Inventario técnico de cambios

| Archivo o área | Cambio requerido |
| --- | --- |
| Nueva migración | Crear `cliente_tarea_configuraciones`, sus llaves foráneas e índice único por cliente y tarea de catálogo. |
| Nuevo modelo `ClienteTareaConfiguracion` | Exponer la preferencia individual y relaciones a cliente y tarea de catálogo. |
| `app/Models/Cliente.php` | Agregar la relación a las configuraciones de tareas, si ayuda a las consultas y pruebas. |
| `app/Services/GeneradorObligaciones.php` | Filtrar tareas inactivas para el cliente en `crearTareasPara()` y en `sincronizarTareaEnRango()`. |
| `app/Livewire/Clientes/DatosFiscales.php` | Sustituir su creación duplicada de tareas por la misma regla compartida del generador o un servicio auxiliar. Hay dos bloques: obligaciones periódicas y únicas. |
| `app/Livewire/Control/TareasAsignadasCrud.php` | Agregar estado, consultas y acciones del panel: abrir/cerrar, listar obligaciones activas, aplicar filtros y activar/desactivar una preferencia. Ajustar `verificarTareasCompletadas()` para que no considere pendiente una tarea desactivada para el cliente. |
| `resources/views/livewire/control/tareas-asignadas.blade.php` | Agregar el botón `Configurar tareas`, panel lateral, secciones colapsables, checks y filtros. |
| `app/Livewire/Catalogos/TareasCrud.php` y `app/Livewire/Catalogos/ObligacionesTareas.php` | No requieren lógica propia si la sincronización se corrige en el servicio, pero deben probarse porque disparan `sincronizarTareaPeriodoActual()` y la sincronización por rango. |

### Punto de interfaz confirmado

El panel se incorporará en `TareasAsignadasCrud`, que se renderiza en la pestaña **Asignar Tareas** del expediente del cliente. Esta vista ya requiere los roles `admin_despacho` o `supervisor`, por lo que el botón y las acciones deben respetar esa misma autorización.

### Ajustes que no forman parte del cambio

- Las solicitudes del portal de cliente no crean `TareaAsignada`; no requieren modificaciones por esta funcionalidad.
- La asignación de carpeta, contador, vencimiento y tiempo estimado permanecerá en el formulario actual de cada tarea asignada.

## Impacto en métricas, validaciones y envíos al cliente

### Regla base

La preferencia cliente–tarea decide qué tareas se crearán en **periodos futuros**. No modifica tareas ya asignadas. Por esa razón, los indicadores y envíos históricos se preservan y las métricas futuras reflejan de forma natural solo las tareas que realmente fueron generadas para el cliente.

### Dashboard administrativo y de supervisor

`app/Services/Dashboard/OperationalDashboardBuilder.php` calcula, entre otros, tareas sin contador, sin carpeta e incompletas. Su base actual excluye tareas con estatus `cancelada`.

- No se deben ocultar ni excluir tareas ya existentes mediante la nueva preferencia, pues cambiarían artificialmente los KPIs históricos y de configuración.
- Las tareas desactivadas antes de generar el siguiente periodo no existirán para ese periodo; por lo tanto no deben contar como faltantes de contador, carpeta o configuración. Esto se obtiene al impedir su creación, sin filtros adicionales en el dashboard.
- Deben validarse los KPIs de clientes completos/incompletos y sus listas de detalle después de cada escenario de prueba.

### Dashboard del contador y pantalla de clientes

- `app/Livewire/Dashboard/DashboardContador.php` cuenta tareas asignadas, terminadas, pendientes, atrasadas, urgentes y rechazadas. Los periodos futuros con tareas no generadas se comportarán correctamente sin alterar consultas históricas.
- `app/Livewire/Clientes/ClientesIndex.php` usa tareas asignadas sin contador para determinar si un cliente tiene sus asignaciones completas. También debe conservar el cálculo de tareas existentes y no tomar la preferencia como una eliminación retroactiva.
- El dashboard del contador actualmente trata `cancelada` como cerrada, mientras que el dashboard operativo la excluye de su base. No es parte de esta funcionalidad, pero debe revisarse aparte si se decide usar ese estatus en el futuro.

### Validación y cierre de obligaciones

Una obligación se puede finalizar solo si sus tareas asignadas cumplen las reglas actuales:

- `ObligacionClienteContador::tieneTareasPendientes()` y `progresoTareas` consultan tareas realmente asignadas.
- `Contador\\ObligacionesIndex::hayTareasPendientes()` bloquea el cierre si hay tareas no cerradas.
- `Control\\ValidacionesIndex::finalizarObligacion()` exige que las tareas estén revisadas.

No se deben cambiar estas consultas para revisar configuraciones futuras. Para un periodo nuevo, una tarea desactivada simplemente no se creará y por tanto no bloqueará el avance de su obligación. Para un periodo histórico ya creado, seguirá aplicando y conservará su trazabilidad.

### Envíos por correo al cliente

`app/Livewire/Notificaciones/CrearNotificacion.php` envía correos con obligaciones, no con tareas individuales. Una obligación aparece disponible cuando:

1. Pertenece al cliente y periodo seleccionado.
2. Tiene estatus `finalizado` o `enviada_cliente`.
3. Su catálogo indica `requiere_envio_cliente = true`.

Por tanto, no se requiere cambiar el envío de Brevo ni las tablas de notificaciones. La configuración de tareas solo afecta indirectamente la posibilidad de finalizar una obligación futura: al no generarse una tarea desactivada para ese cliente, esa tarea no será requisito de revisión antes del envío.

### Decisión que debe mantenerse

Si en el futuro se quiere que desactivar una tarea también la oculte de un periodo actual o histórico, será una funcionalidad distinta. Requerirá definir cómo se recalculan métricas, validaciones y si una obligación ya finalizada puede volver a abrirse. No debe incluirse en la primera implementación.

## Criterios de aceptación

- Un usuario autorizado puede abrir `Configurar tareas` desde la vista de tareas del cliente.
- El panel agrupa las tareas por obligación y permite colapsarlas.
- Desactivar una tarea para un cliente no elimina datos ni la desactiva para otros clientes.
- La tarea desactivada aparece al filtrar por inactivas y se puede reactivar.
- Una nueva generación de obligación para ese cliente no crea tareas configuradas como inactivas.
- Una nueva generación para otro cliente sí crea esa tarea si sigue activa en el catálogo y no tiene una preferencia individual inactiva.
- Los flujos de alta normal, regularización y generación programada respetan la misma configuración.

## Plan de validación

1. Crear dos clientes con la misma obligación y tareas de catálogo.
2. Desactivar una tarea para el primer cliente desde el panel.
3. Verificar que la tarea siga visible como inactiva y que el segundo cliente no sea afectado.
4. Generar un periodo nuevo para ambos clientes.
5. Confirmar que solo el segundo cliente reciba esa tarea.
6. Reactivarla para el primer cliente y generar un periodo posterior.
7. Confirmar que vuelva a crearse sin alterar los registros históricos.

## Punto de respaldo

Antes de esta implementación se dejó identificado el commit `05ed221` con el mensaje `Base antes de configuracion de tareas por cliente`.
