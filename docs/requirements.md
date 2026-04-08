# Documentación de Requerimientos — Sistema de Gestión de Sorteos

**Versión:** 1.0.0
**Fecha:** 07 de abril de 2026
**Estado:** Borrador

---

## Tabla de Contenidos

1. [Resumen Ejecutivo](#1-resumen-ejecutivo)
2. [Alcance del Sistema](#2-alcance-del-sistema)
3. [Actores del Sistema](#3-actores-del-sistema)
4. [Reglas de Negocio](#4-reglas-de-negocio)
5. [Módulo 1 — Autenticación y Gestión de Usuarios](#5-módulo-1--autenticación-y-gestión-de-usuarios)
6. [Módulo 2 — Gestión de Sorteos](#6-módulo-2--gestión-de-sorteos)
7. [Módulo 3 — Gestión de Participantes y Boletos](#7-módulo-3--gestión-de-participantes-y-boletos)
8. [Módulo 4 — Ejecución de Sorteos y Auditoría](#8-módulo-4--ejecución-de-sorteos-y-auditoría)
9. [Módulo 5 — Notificaciones por Correo](#9-módulo-5--notificaciones-por-correo)
10. [Módulo 6 — Análisis de Datos y Reportes](#10-módulo-6--análisis-de-datos-y-reportes)
11. [Requerimientos No Funcionales](#11-requerimientos-no-funcionales)
12. [Modelo de Datos](#12-modelo-de-datos)
13. [Contrato de API REST](#13-contrato-de-api-rest)
14. [Requerimientos de Interfaz de Usuario](#14-requerimientos-de-interfaz-de-usuario)
15. [Criterios de Aceptación Globales](#15-criterios-de-aceptación-globales)
16. [Glosario](#16-glosario)

---

## 1. Resumen Ejecutivo

El sistema de gestión de sorteos es una aplicación web que permite a una organización crear, administrar y ejecutar sorteos de manera transparente y auditable. Los usuarios con rol de **Organizador** crean y gestionan sorteos; el **Administrador** controla los accesos y configuraciones generales. Toda la lógica de selección de ganadores queda registrada en un log de auditoría inmutable.

**Stack tecnológico:**
- Backend: PHP 8.2 + Laravel 11
- Frontend: Livewire 3 + Volt + Tailwind CSS
- Base de datos: MySQL 8
- Pruebas: PestPHP

---

## 2. Alcance del Sistema

| Incluido | Excluido |
|---|---|
| Creación y gestión de sorteos | Pagos en línea / cobro de boletos |
| Registro manual de participantes | Login social (Google, Facebook) |
| Generación y validación de boletos únicos | Integración con SMS o WhatsApp |
| Ejecución aleatoria con log de auditoría | Módulo de soporte técnico en tiempo real |
| Notificaciones por correo electrónico | APIs públicas para terceros |
| Análisis gráfico de participación | Sorteos en tiempo real (streaming) |
| Gestión de usuarios (Admin + Organizador) | Multi-tenancy (múltiples organizaciones) |

---

## 3. Actores del Sistema

### 3.1 Administrador

Usuario con acceso total al sistema. Solo el Administrador puede registrar nuevos usuarios.

**Responsabilidades:**
- Crear, editar y desactivar cuentas de Organizador.
- Acceder a todos los sorteos y logs del sistema.
- Configurar parámetros globales de la aplicación.
- Visualizar el panel de análisis global.

### 3.2 Organizador

Usuario operativo que gestiona sorteos dentro de la plataforma.

**Responsabilidades:**
- Crear, editar, publicar y cerrar sorteos propios.
- Gestionar participantes y boletos de sus sorteos.
- Ejecutar el sorteo y consultar resultados.
- Enviar notificaciones a participantes.
- Ver reportes de sus propios sorteos.

---

## 4. Reglas de Negocio

| ID | Regla |
|---|---|
| RN-01 | Solo el Administrador puede registrar nuevos usuarios. El auto-registro público está deshabilitado. |
| RN-02 | Un sorteo no puede ejecutarse si no tiene al menos un participante registrado. |
| RN-03 | La configuración de boletos (tipo de dígito y series) se define al crear el sorteo y no puede modificarse una vez que existen boletos asignados. |
| RN-04 | La combinación `[serie + número de boleto]` debe ser única dentro de un mismo sorteo. |
| RN-05 | Un participante puede tener múltiples boletos en el mismo sorteo, pero cada boleto es único. |
| RN-06 | Una vez ejecutado el sorteo, los resultados son inmutables. No se pueden eliminar ni editar ganadores. |
| RN-07 | El log de auditoría de un sorteo ejecutado no puede ser eliminado bajo ninguna circunstancia desde la interfaz. |
| RN-08 | Un Organizador solo puede ver, editar y ejecutar sorteos que él mismo haya creado. |
| RN-09 | Los correos de notificación se envían de forma asíncrona a través de la cola de jobs de Laravel. |
| RN-10 | Un sorteo con estado `cerrado` o `ejecutado` no puede volver a estado `activo`. |

---

## 5. Módulo 1 — Autenticación y Gestión de Usuarios

### 5.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| AUTH-BE-01 | Implementar autenticación mediante usuario (email) y contraseña con sesión persistente. | Alta |
| AUTH-BE-02 | Proteger todas las rutas de la aplicación con middleware `auth`. | Alta |
| AUTH-BE-03 | Proveer endpoint de logout que invalide la sesión activa. | Alta |
| AUTH-BE-04 | El Administrador puede crear cuentas de Organizador con: nombre, email, contraseña temporal y estado activo/inactivo. | Alta |
| AUTH-BE-05 | El Administrador puede desactivar un Organizador; el Organizador desactivado no puede iniciar sesión. | Alta |
| AUTH-BE-06 | El sistema debe bloquear el inicio de sesión tras 5 intentos fallidos consecutivos (rate limiting) y desbloquear automáticamente tras 15 minutos. | Alta |
| AUTH-BE-07 | Los usuarios deben poder cambiar su contraseña desde el perfil. La contraseña actual debe ser validada antes del cambio. | Media |
| AUTH-BE-08 | Las contraseñas deben almacenarse usando bcrypt (mínimo cost 12). | Alta |
| AUTH-BE-09 | Implementar política de contraseñas: mínimo 8 caracteres, al menos 1 mayúscula, 1 número y 1 carácter especial. | Media |
| AUTH-BE-10 | Registro de auditoría: fecha y hora del último inicio de sesión por usuario. | Media |

### 5.2 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| AUTH-FE-01 | Mostrar formulario de inicio de sesión con campos: email y contraseña. | Alta |
| AUTH-FE-02 | Mostrar mensajes de error de validación inline (email inválido, campos vacíos, credenciales incorrectas). | Alta |
| AUTH-FE-03 | Mostrar indicador de carga durante el proceso de autenticación. | Media |
| AUTH-FE-04 | Panel de administración de usuarios (solo visible para Administrador): listado de Organizadores con nombre, email, estado y fecha de creación. | Alta |
| AUTH-FE-05 | Formulario de creación de nuevo Organizador con validación en tiempo real. | Alta |
| AUTH-FE-06 | Acción de activar/desactivar usuario con confirmación modal. | Alta |
| AUTH-FE-07 | Página de perfil del usuario: nombre, email, cambio de contraseña. | Media |
| AUTH-FE-08 | El menú de navegación debe reflejar el rol del usuario (ocultar opciones no permitidas). | Alta |

### 5.3 Casos de Uso

```
CU-01: Iniciar sesión
  Actor: Administrador / Organizador
  Flujo principal:
    1. El usuario ingresa email y contraseña.
    2. El sistema valida las credenciales.
    3. El sistema crea la sesión y redirige al dashboard.
  Flujo alternativo:
    2a. Credenciales incorrectas → mostrar mensaje de error.
    2b. Cuenta desactivada → mostrar mensaje "Cuenta desactivada, contacte al administrador".
    2c. 5 intentos fallidos → bloquear por 15 minutos.

CU-02: Crear Organizador
  Actor: Administrador
  Precondición: El actor tiene rol Administrador.
  Flujo principal:
    1. El Administrador accede a "Gestión de Usuarios".
    2. Completa el formulario: nombre, email, contraseña.
    3. El sistema valida que el email no esté en uso.
    4. El sistema crea la cuenta y envía correo de bienvenida con la contraseña temporal.
  Flujo alternativo:
    3a. Email duplicado → error "El correo ya está registrado".
```

---

## 6. Módulo 2 — Gestión de Sorteos

### 6.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| RAFFLE-BE-01 | Crear un sorteo con: nombre, descripción, fecha de inicio, fecha de fin, imagen (opcional) y estado inicial `borrador`. | Alta |
| RAFFLE-BE-02 | Editar un sorteo en estado `borrador` o `activo` (sin boletos asignados). | Alta |
| RAFFLE-BE-03 | Publicar un sorteo cambiando su estado de `borrador` a `activo`. | Alta |
| RAFFLE-BE-04 | Cerrar un sorteo manualmente cambiando su estado a `cerrado` (disponible para ejecución). | Alta |
| RAFFLE-BE-05 | Eliminar un sorteo únicamente si está en estado `borrador` y no tiene participantes. | Media |
| RAFFLE-BE-06 | Gestionar los premios asociados a un sorteo: nombre del premio, descripción, orden de entrega y cantidad de ganadores por premio. | Alta |
| RAFFLE-BE-07 | Validar que la fecha de fin sea posterior a la fecha de inicio. | Alta |
| RAFFLE-BE-08 | Asociar la configuración de boletos al sorteo: tipo de dígito (`doble`, `triple`, `cuádruple`) y número de series. | Alta |
| RAFFLE-BE-09 | Listar sorteos aplicando filtros por estado, fecha y búsqueda por nombre. Soportar paginación. | Alta |
| RAFFLE-BE-10 | El Organizador solo puede listar y gestionar sorteos que le pertenecen. El Administrador puede ver todos. | Alta |

### 6.2 Estados del Sorteo

```
[borrador] ──▶ [activo] ──▶ [cerrado] ──▶ [ejecutado]
     │
     └──▶ [eliminado]  (solo desde borrador sin participantes)
```

| Estado | Descripción | Acciones permitidas |
|---|---|---|
| `borrador` | Sorteo creado pero no publicado | Editar, publicar, eliminar |
| `activo` | Sorteo visible y aceptando participantes | Editar (sin boletos), cerrar |
| `cerrado` | No acepta más participantes | Ejecutar sorteo |
| `ejecutado` | Sorteo finalizado con ganadores | Solo consulta |
| `eliminado` | Lógicamente eliminado | Ninguna |

### 6.3 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| RAFFLE-FE-01 | Dashboard con listado de sorteos: tarjetas con imagen, nombre, estado (badge de color), fecha límite y conteo de participantes. | Alta |
| RAFFLE-FE-02 | Filtros en el listado: por estado, por rango de fechas y búsqueda por nombre (reactivo, sin recarga). | Alta |
| RAFFLE-FE-03 | Formulario de creación/edición de sorteo con pasos (wizard): (1) Información general, (2) Premios, (3) Configuración de boletos. | Alta |
| RAFFLE-FE-04 | Validación en tiempo real en todos los campos del formulario. | Alta |
| RAFFLE-FE-05 | Vista de detalle de sorteo: información general, lista de premios, estadísticas básicas de participación. | Alta |
| RAFFLE-FE-06 | Confirmación modal antes de publicar, cerrar o eliminar un sorteo. | Alta |
| RAFFLE-FE-07 | Indicador visual del estado del sorteo con badge de color (ej: borrador=gris, activo=verde, cerrado=naranja, ejecutado=azul). | Media |
| RAFFLE-FE-08 | Subida de imagen opcional con previsualización antes de guardar. | Media |

---

## 7. Módulo 3 — Gestión de Participantes y Boletos

### 7.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| PART-BE-01 | Registrar un participante en un sorteo con: nombre completo, correo electrónico y número de teléfono. | Alta |
| PART-BE-02 | Validar que el correo electrónico del participante sea único dentro del mismo sorteo. | Alta |
| PART-BE-03 | Generar boletos de participación únicos para un participante. Cada boleto tiene: serie (alfanumérica), número y tipo de dígito heredado del sorteo. | Alta |
| PART-BE-04 | La combinación `[sorteo_id + serie + número]` debe ser única. Rechazar con error descriptivo si ya existe. | Alta |
| PART-BE-05 | Permitir la asignación de múltiples boletos a un mismo participante. | Alta |
| PART-BE-06 | Importación masiva de participantes desde archivo CSV. El sistema debe reportar filas con errores sin interrumpir la importación de las válidas. | Media |
| PART-BE-07 | Eliminar un participante (y sus boletos) solo si el sorteo está en estado `activo` o `borrador`. | Media |
| PART-BE-08 | Listar participantes de un sorteo con paginación y filtro por nombre/email. | Alta |
| PART-BE-09 | Exportar listado de participantes con sus boletos a formato CSV. | Media |

### 7.2 Especificación de Boletos

**Tipos de dígito por sorteo:**

| Tipo | Rango de números | Ejemplo de boleto |
|---|---|---|
| Doble | 00 – 99 | Serie A, Número 07 |
| Triple | 000 – 999 | Serie A, Número 042 |
| Cuádruple | 0000 – 9999 | Serie A, Número 0315 |

**Series:** Una serie es un identificador alfanumérico (ej: A, B, C… o 1, 2, 3…) que agrupa un conjunto de números. La cantidad de series se define al crear el sorteo. Cada serie contiene todos los números disponibles según el tipo de dígito.

**Validación de unicidad:** `UNIQUE(raffle_id, serie, numero)`.

### 7.3 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| PART-FE-01 | Tabla de participantes con: nombre, email, teléfono, boletos asignados (cantidad) y acciones (ver boletos, eliminar). | Alta |
| PART-FE-02 | Formulario de registro de participante con validación en tiempo real. | Alta |
| PART-FE-03 | Al agregar un boleto a un participante, mostrar un selector de serie y número con validación de disponibilidad instantánea. | Alta |
| PART-FE-04 | Vista de boletos de un participante: tabla con serie, número y estado (disponible/asignado/ganador). | Alta |
| PART-FE-05 | Importación CSV con UI de carga, barra de progreso y reporte de filas procesadas vs. rechazadas con motivo. | Media |
| PART-FE-06 | Botón de exportación a CSV para la lista de participantes. | Media |
| PART-FE-07 | Confirmación modal antes de eliminar un participante indicando que sus boletos también serán eliminados. | Alta |

---

## 8. Módulo 4 — Ejecución de Sorteos y Auditoría

### 8.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| EXEC-BE-01 | Ejecutar el sorteo seleccionando ganadores al azar usando un generador criptográficamente seguro (`random_int`). | Alta |
| EXEC-BE-02 | Soportar múltiples premios por sorteo; para cada premio se selecciona el número de ganadores configurado sin repetición entre premios. | Alta |
| EXEC-BE-03 | Al ejecutar el sorteo, cambiar su estado a `ejecutado` de forma atómica (dentro de una transacción de base de datos). | Alta |
| EXEC-BE-04 | Generar y persistir el log de auditoría con: timestamp UTC, premio, boleto ganador (serie + número), participante ganador, seed utilizado y versión del algoritmo. | Alta |
| EXEC-BE-05 | El log de auditoría debe ser de solo lectura; ningún endpoint debe permitir su modificación o eliminación. | Alta |
| EXEC-BE-06 | Si la ejecución falla (error inesperado), revertir la transacción y mantener el sorteo en estado `cerrado`. | Alta |
| EXEC-BE-07 | Disparar el envío de notificaciones por correo a ganadores inmediatamente después de una ejecución exitosa (job asíncrono). | Alta |
| EXEC-BE-08 | Endpoint para obtener los resultados de un sorteo ejecutado: lista de premios con sus ganadores (boleto y participante). | Alta |

### 8.2 Estructura del Log de Auditoría

```json
{
  "raffle_id": 1,
  "raffle_name": "Rifa Navideña 2026",
  "executed_at": "2026-04-07T18:00:00Z",
  "executed_by": "organizador@empresa.com",
  "algorithm_version": "1.0",
  "seed_info": "CSPRNG (PHP random_int)",
  "results": [
    {
      "prize_id": 1,
      "prize_name": "Primer Premio",
      "winners": [
        {
          "ticket_serie": "A",
          "ticket_number": "042",
          "participant_name": "Juan Pérez",
          "participant_email": "juan@email.com"
        }
      ]
    }
  ]
}
```

### 8.3 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| EXEC-FE-01 | Página de ejecución del sorteo: resumen de participantes, premios y confirmación explícita con texto "Confirmo que deseo ejecutar el sorteo". | Alta |
| EXEC-FE-02 | Animación de selección aleatoria visible durante la ejecución (efecto de "ruleta" o countdown). | Media |
| EXEC-FE-03 | Página de resultados post-ejecución: lista de premios con ganadores (nombre, boleto ganador). | Alta |
| EXEC-FE-04 | Vista del log de auditoría en formato legible: tabla con timestamp, premio, ganador y datos del boleto. | Alta |
| EXEC-FE-05 | Botón para descargar el log de auditoría en PDF. | Media |
| EXEC-FE-06 | El botón "Ejecutar Sorteo" solo está habilitado cuando el sorteo está en estado `cerrado`. | Alta |

---

## 9. Módulo 5 — Notificaciones por Correo

### 9.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| NOTIF-BE-01 | Enviar correo de bienvenida al Organizador cuando el Administrador crea su cuenta, incluyendo su contraseña temporal. | Alta |
| NOTIF-BE-02 | Enviar correo de confirmación de participación cuando un participante es registrado en un sorteo. | Media |
| NOTIF-BE-03 | Enviar correo de recordatorio a todos los participantes 24 horas antes de la fecha de cierre del sorteo (job programado). | Media |
| NOTIF-BE-04 | Enviar correo de resultado (ganador/no ganador) a todos los participantes al ejecutarse el sorteo. | Alta |
| NOTIF-BE-05 | Los correos deben enviarse mediante la cola de jobs de Laravel (`queue:work`) para no bloquear el request. | Alta |
| NOTIF-BE-06 | Registrar en tabla `notification_logs`: destinatario, tipo de notificación, estado (enviado/fallido), timestamp. | Media |
| NOTIF-BE-07 | Reintentar automáticamente el envío fallido hasta 3 veces con backoff exponencial. | Media |

### 9.2 Plantillas de Correo

| Tipo | Asunto | Destinatario |
|---|---|---|
| `welcome_organizer` | "Bienvenido a Rifas — Tus credenciales de acceso" | Nuevo Organizador |
| `participation_confirmed` | "Tu participación en [nombre del sorteo] ha sido confirmada" | Participante registrado |
| `raffle_reminder` | "Recordatorio: EL sorteo [nombre] cierra mañana" | Todos los participantes |
| `raffle_winner` | "¡Felicidades! Ganaste en [nombre del sorteo]" | Participante ganador |
| `raffle_not_winner` | "Resultados del sorteo [nombre del sorteo]" | Participante no ganador |

### 9.3 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| NOTIF-FE-01 | Historial de notificaciones enviadas por sorteo: destinatario, tipo, estado y fecha. | Media |
| NOTIF-FE-02 | Botón para reenviar manualmente una notificación fallida. | Baja |
| NOTIF-FE-03 | Indicador en el dashboard cuando hay notificaciones fallidas pendientes. | Media |

---

## 10. Módulo 6 — Análisis de Datos y Reportes

### 10.1 Requerimientos Funcionales — Backend

| ID | Requerimiento | Prioridad |
|---|---|---|
| ANALYTICS-BE-01 | Proveer datos para dashboard global (Admin): total de sorteos por estado, total de participantes en el sistema, sorteos ejecutados este mes. | Alta |
| ANALYTICS-BE-02 | Proveer datos para dashboard del Organizador: sus sorteos por estado, participantes por sorteo (top 5), próximos cierres. | Alta |
| ANALYTICS-BE-03 | Serie temporal: número de participantes registrados por día para un sorteo dado. | Media |
| ANALYTICS-BE-04 | Comparativo entre sorteos del mismo Organizador: total de participantes, porcentaje de boletos vendidos vs. disponibles. | Media |
| ANALYTICS-BE-05 | Los datos de análisis deben calcularse con queries optimizadas; no procesarse en memoria para grandes volúmenes. | Alta |

### 10.2 Requerimientos Funcionales — Frontend

| ID | Requerimiento | Prioridad |
|---|---|---|
| ANALYTICS-FE-01 | Dashboard principal con KPIs: total sorteos activos, total participantes registrados hoy, próximo cierre. | Alta |
| ANALYTICS-FE-02 | Gráfica de barras: comparativo de participantes entre sorteos del Organizador. | Media |
| ANALYTICS-FE-03 | Gráfica de línea: evolución de registros de participantes por día para un sorteo seleccionado. | Media |
| ANALYTICS-FE-04 | Gráfica de dona: distribución de sorteos por estado. | Media |
| ANALYTICS-FE-05 | Filtros en el panel de reportes: rango de fechas, sorteo específico. | Media |
| ANALYTICS-FE-06 | Las gráficas deben ser responsivas y adaptarse a dispositivos móviles. | Media |

---

## 11. Requerimientos No Funcionales

### 11.1 Rendimiento

| ID | Requerimiento |
|---|---|
| NFR-PERF-01 | El tiempo de respuesta de cualquier endpoint debe ser ≤ 500ms bajo carga normal (hasta 100 usuarios concurrentes). |
| NFR-PERF-02 | La ejecución de un sorteo con hasta 10,000 boletos debe completarse en ≤ 3 segundos. |
| NFR-PERF-03 | Las consultas a la base de datos deben tener índices definidos en todas las columnas usadas para filtros y joins. |
| NFR-PERF-04 | Usar caché (Laravel Cache) para los datos del dashboard con TTL de 5 minutos. |

### 11.2 Seguridad

| ID | Requerimiento |
|---|---|
| NFR-SEC-01 | Todas las comunicaciones deben realizarse sobre HTTPS (TLS 1.2 o superior). |
| NFR-SEC-02 | Proteger todos los formularios con tokens CSRF. |
| NFR-SEC-03 | Sanitizar y validar todas las entradas del usuario en el backend (no confiar en validación del frontend). |
| NFR-SEC-04 | Usar Eloquent ORM o query bindings parametrizados para prevenir inyección SQL. |
| NFR-SEC-05 | Implementar autorización en cada endpoint verificando que el usuario tiene permiso sobre el recurso solicitado (Policy / Gate de Laravel). |
| NFR-SEC-06 | Los archivos subidos (imágenes) deben ser validados por tipo MIME y tamaño (máx. 2MB). Almacenarlos fuera de `public/`. |
| NFR-SEC-07 | Las contraseñas temporales enviadas por correo deben obligar al cambio en el primer inicio de sesión. |
| NFR-SEC-08 | Registrar en log de actividad las acciones críticas: creación de usuarios, ejecución de sorteos, cambios de estado. |

### 11.3 Mantenibilidad y Calidad de Código

| ID | Requerimiento |
|---|---|
| NFR-MAINT-01 | Cobertura de pruebas automatizadas (PestPHP) ≥ 80% en la lógica de negocio. |
| NFR-MAINT-02 | Cada módulo debe tener Feature Tests para sus flujos principales y Unit Tests para servicios y helpers. |
| NFR-MAINT-03 | Aplicar PSR-12 como estándar de codificación. Usar Laravel Pint para formateo automático. |
| NFR-MAINT-04 | Los servicios de negocio deben estar en clases `Service` separadas de los controladores. |
| NFR-MAINT-05 | Usar migraciones de base de datos para todos los cambios de esquema; prohibido modificar la BD manualmente. |

### 11.4 Usabilidad

| ID | Requerimiento |
|---|---|
| NFR-UX-01 | La interfaz debe ser responsiva (mobile-first) utilizando Tailwind CSS. |
| NFR-UX-02 | Todas las acciones destructivas o irreversibles deben solicitar confirmación explícita al usuario. |
| NFR-UX-03 | Los errores de validación deben mostrarse de forma inline junto al campo correspondiente, no en alertas globales. |
| NFR-UX-04 | El estado de carga (loading) debe indicarse visualmente en botones y secciones que esperan respuesta del servidor. |

---

## 12. Modelo de Datos

### 12.1 Diagrama Entidad-Relación (descripción textual)

```
users
  id, name, email, password, role (admin|organizer),
  is_active, must_change_password, last_login_at,
  created_at, updated_at

raffles
  id, user_id (FK → users), name, description, image_path,
  starts_at, ends_at, status (draft|active|closed|executed|deleted),
  ticket_digit_type (double|triple|quadruple), series_count,
  created_at, updated_at

prizes
  id, raffle_id (FK → raffles), name, description,
  winner_count, order, created_at, updated_at

participants
  id, raffle_id (FK → raffles), name, email, phone,
  created_at, updated_at
  UNIQUE(raffle_id, email)

tickets
  id, raffle_id (FK → raffles), participant_id (FK → participants),
  serie, number, is_winner,
  created_at, updated_at
  UNIQUE(raffle_id, serie, number)

raffle_results
  id, raffle_id (FK → raffles), prize_id (FK → prizes),
  ticket_id (FK → tickets), created_at
  [INMUTABLE — sin updated_at]

raffle_audit_logs
  id, raffle_id (FK → raffles), executed_at, executed_by (FK → users),
  algorithm_version, seed_info, payload (JSON),
  created_at
  [INMUTABLE — sin updated_at]

notification_logs
  id, raffle_id (FK → raffles, nullable), recipient_email,
  notification_type, status (sent|failed), attempts,
  sent_at, created_at

activity_logs
  id, user_id (FK → users), action, subject_type,
  subject_id, payload (JSON), ip_address, created_at
```

### 12.2 Índices Requeridos

| Tabla | Columna(s) | Tipo |
|---|---|---|
| `raffles` | `user_id`, `status` | Index |
| `raffles` | `ends_at` | Index |
| `participants` | `raffle_id`, `email` | Unique |
| `tickets` | `raffle_id`, `serie`, `number` | Unique |
| `tickets` | `participant_id` | Index |
| `raffle_results` | `raffle_id`, `prize_id` | Index |
| `notification_logs` | `status`, `created_at` | Index |

---

## 13. Contrato de API REST

> Todas las rutas requieren autenticación. Responden en JSON. Los errores siguen el formato `{"message": "...", "errors": {...}}`.

### 13.1 Autenticación

| Método | Ruta | Descripción | Roles |
|---|---|---|---|
| POST | `/login` | Iniciar sesión | Todos |
| POST | `/logout` | Cerrar sesión | Autenticado |
| PUT | `/profile/password` | Cambiar contraseña | Autenticado |

### 13.2 Usuarios

| Método | Ruta | Descripción | Roles |
|---|---|---|---|
| GET | `/users` | Listar organizadores | Admin |
| POST | `/users` | Crear organizador | Admin |
| PATCH | `/users/{id}/toggle-status` | Activar/desactivar | Admin |

### 13.3 Sorteos

| Método | Ruta | Descripción | Roles |
|---|---|---|---|
| GET | `/raffles` | Listar sorteos (paginado, filtros) | Admin, Organizer |
| POST | `/raffles` | Crear sorteo | Organizer |
| GET | `/raffles/{id}` | Detalle del sorteo | Admin, Organizer |
| PUT | `/raffles/{id}` | Editar sorteo | Organizer (propio) |
| DELETE | `/raffles/{id}` | Eliminar sorteo (borrador) | Organizer (propio) |
| PATCH | `/raffles/{id}/publish` | Publicar sorteo | Organizer (propio) |
| PATCH | `/raffles/{id}/close` | Cerrar sorteo | Organizer (propio) |
| POST | `/raffles/{id}/execute` | Ejecutar sorteo | Organizer (propio) |
| GET | `/raffles/{id}/results` | Ver resultados | Admin, Organizer |
| GET | `/raffles/{id}/audit-log` | Ver log de auditoría | Admin, Organizer |

### 13.4 Participantes y Boletos

| Método | Ruta | Descripción | Roles |
|---|---|---|---|
| GET | `/raffles/{id}/participants` | Listar participantes | Organizer (propio) |
| POST | `/raffles/{id}/participants` | Registrar participante | Organizer (propio) |
| DELETE | `/raffles/{id}/participants/{pid}` | Eliminar participante | Organizer (propio) |
| POST | `/raffles/{id}/participants/{pid}/tickets` | Asignar boleto | Organizer (propio) |
| GET | `/raffles/{id}/participants/{pid}/tickets` | Ver boletos del participante | Organizer (propio) |
| POST | `/raffles/{id}/participants/import` | Importar CSV | Organizer (propio) |
| GET | `/raffles/{id}/participants/export` | Exportar CSV | Organizer (propio) |

### 13.5 Analytics

| Método | Ruta | Descripción | Roles |
|---|---|---|---|
| GET | `/analytics/dashboard` | KPIs del dashboard | Admin, Organizer |
| GET | `/analytics/raffles/{id}/timeline` | Serie temporal de registros | Organizer (propio) |
| GET | `/analytics/raffles/comparison` | Comparativo entre sorteos | Organizer |

---

## 14. Requerimientos de Interfaz de Usuario

### 14.1 Layout General

- **Barra lateral de navegación** (sidebar) con: logo, menú principal (Dashboard, Sorteos, Usuarios*, Análisis, Perfil). *Solo visible para Admin.
- **Topbar**: nombre del usuario, rol, botón de logout.
- **Área de contenido**: responsiva, con breadcrumb de navegación.

### 14.2 Páginas Requeridas

| Página | Ruta | Rol |
|---|---|---|
| Login | `/login` | Público |
| Dashboard | `/dashboard` | Admin, Organizer |
| Lista de Sorteos | `/raffles` | Admin, Organizer |
| Crear/Editar Sorteo | `/raffles/create`, `/raffles/{id}/edit` | Organizer |
| Detalle del Sorteo | `/raffles/{id}` | Admin, Organizer |
| Participantes del Sorteo | `/raffles/{id}/participants` | Organizer |
| Ejecutar Sorteo | `/raffles/{id}/execute` | Organizer |
| Resultados del Sorteo | `/raffles/{id}/results` | Admin, Organizer |
| Log de Auditoría | `/raffles/{id}/audit` | Admin, Organizer |
| Análisis y Reportes | `/analytics` | Admin, Organizer |
| Gestión de Usuarios | `/users` | Admin |
| Perfil | `/profile` | Admin, Organizer |

### 14.3 Componentes Reutilizables

| Componente | Descripción |
|---|---|
| `<x-raffle-status-badge>` | Badge de color según estado del sorteo |
| `<x-confirm-modal>` | Modal de confirmación genérico con mensaje configurable |
| `<x-data-table>` | Tabla con paginación, búsqueda y acciones |
| `<x-alert>` | Alertas de éxito, error, advertencia e información |
| `<x-form-input>` | Campo de formulario con label, error inline y helpertext |
| `<x-loading-button>` | Botón con estado de carga |
| `<x-chart-bar>` | Gráfica de barras (wraper de librería) |
| `<x-chart-line>` | Gráfica de línea |
| `<x-chart-donut>` | Gráfica de dona |

---

## 15. Criterios de Aceptación Globales

Los siguientes criterios aplican a todo el sistema:

1. **Seguridad OWASP:** El sistema no debe presentar vulnerabilidades de tipo Inyección SQL, XSS, CSRF ni Broken Access Control.
2. **Cobertura de pruebas:** Los módulos de Ejecución de Sorteos, Gestión de Boletos y Autenticación deben tener cobertura ≥ 90% en pruebas automatizadas.
3. **Accesibilidad:** Los formularios deben tener atributos `aria-label` y ser navegables por teclado.
4. **Responsividad:** La interfaz debe funcionar correctamente en resoluciones desde 375px (móvil) hasta 1440px (escritorio).
5. **Consistencia de datos:** Los resultados de un sorteo ejecutado nunca deben modificarse. Las pruebas deben verificar que no existe endpoint para ello.
6. **Auditabilidad:** Cada sorteo ejecutado debe generar un log de auditoría persistente con toda la información necesaria para reproducir y verificar el resultado.
7. **Manejo de errores:** Todos los endpoints deben devolver códigos HTTP apropiados (200, 201, 400, 401, 403, 404, 422, 500) con mensajes de error descriptivos.

---

## 16. Glosario

| Término | Definición |
|---|---|
| **Sorteo** | Evento organizado donde se seleccionan ganadores al azar entre los participantes. |
| **Boleto** | Identificador único (serie + número) asociado a un participante dentro de un sorteo. |
| **Serie** | Prefijo alfanumérico que agrupa un conjunto de números de boleto dentro de un sorteo. |
| **Tipo de dígito** | Cantidad de dígitos que tiene el número de un boleto: doble (2), triple (3) o cuádruple (4). |
| **Premio** | Recompensa asociada a un sorteo que se entrega a uno o más ganadores seleccionados. |
| **Log de auditoría** | Registro inmutable que documenta el proceso de ejecución de un sorteo: timestamp, algoritmo, seed y resultados. |
| **Organizador** | Usuario que crea y gestiona sorteos en la plataforma. |
| **Administrador** | Usuario con acceso total que gestiona otros usuarios y supervisa la plataforma. |
| **CSPRNG** | Generador de números pseudoaleatorios criptográficamente seguro (Cryptographically Secure Pseudo-Random Number Generator). |
| **Estado del sorteo** | Fase del ciclo de vida de un sorteo: borrador → activo → cerrado → ejecutado. |
