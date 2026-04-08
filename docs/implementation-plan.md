# Plan de Implementación — Sistema de Gestión de Sorteos

> Este archivo define el checklist de implementación para el agente de Copilot.
> Referencia completa de requerimientos: [`docs/requirements.md`](./requirements.md)

---

## Fase 1 — Base de datos y modelos

### Migraciones (en orden de dependencia)

- [ ] `create_users_table` — agregar columnas: `role` (enum: admin|organizer), `is_active` (bool), `must_change_password` (bool), `last_login_at` (nullable timestamp)
- [ ] `create_raffles_table` — `user_id` FK, `name`, `description`, `image_path`, `starts_at`, `ends_at`, `status` (enum: draft|active|closed|executed|deleted), `ticket_digit_type` (enum: double|triple|quadruple), `series_count`
- [ ] `create_prizes_table` — `raffle_id` FK, `name`, `description`, `winner_count`, `order`
- [ ] `create_participants_table` — `raffle_id` FK, `name`, `email`, `phone`; UNIQUE(`raffle_id`, `email`)
- [ ] `create_tickets_table` — `raffle_id` FK, `participant_id` FK, `serie`, `number`, `is_winner`; UNIQUE(`raffle_id`, `serie`, `number`)
- [ ] `create_raffle_results_table` — `raffle_id` FK, `prize_id` FK, `ticket_id` FK; sin `updated_at`
- [ ] `create_raffle_audit_logs_table` — `raffle_id` FK, `executed_at`, `executed_by` FK, `algorithm_version`, `seed_info`, `payload` (JSON); sin `updated_at`
- [ ] `create_notification_logs_table` — `raffle_id` FK nullable, `recipient_email`, `notification_type`, `status` (enum: sent|failed), `attempts`, `sent_at`
- [ ] `create_activity_logs_table` — `user_id` FK, `action`, `subject_type`, `subject_id`, `payload` (JSON), `ip_address`

### Modelos Eloquent

- [ ] `User` — relaciones: `raffles()`, scopes: `active()`, `byRole()`
- [ ] `Raffle` — relaciones: `prizes()`, `participants()`, `tickets()`, `results()`, `auditLog()`, `owner()`
- [ ] `Prize` — relaciones: `raffle()`, `winners()`
- [ ] `Participant` — relaciones: `raffle()`, `tickets()`
- [ ] `Ticket` — relaciones: `raffle()`, `participant()`
- [ ] `RaffleResult` — relaciones: `raffle()`, `prize()`, `ticket()`
- [ ] `RaffleAuditLog` — relaciones: `raffle()`, `executedBy()`
- [ ] `NotificationLog`
- [ ] `ActivityLog`

---

## Fase 2 — Autenticación y control de acceso

- [ ] Middleware para bloquear usuarios con `is_active = false`
- [ ] Middleware para forzar cambio de contraseña cuando `must_change_password = true`
- [ ] Rate limiting: 5 intentos fallidos → bloqueo 15 minutos (`ThrottleLogins`)
- [ ] Política de contraseñas: mínimo 8 caracteres, 1 mayúscula, 1 número, 1 especial
- [ ] `UserPolicy` — Admin puede crear/editar/toggle usuarios; Organizer solo se gestiona a sí mismo
- [ ] `RafflePolicy` — Organizer solo accede a sus sorteos; Admin accede a todos
- [ ] `ParticipantPolicy` — restringido al Organizador del sorteo

---

## Fase 3 — Capa de servicios

- [ ] `App\Services\RaffleService` — createRaffle, updateRaffle, publishRaffle, closeRaffle, deleteRaffle
- [ ] `App\Services\TicketService` — generateTicket, validateUniqueness, getAvailableNumbers
- [ ] `App\Services\DrawExecutionService` — execute(Raffle $raffle): usa `random_int`, transacción atómica, genera audit log
- [ ] `App\Services\NotificationService` — dispatch jobs por tipo de notificación
- [ ] `App\Services\AnalyticsService` — getAdminDashboard, getOrganizerDashboard, getTimeline, getComparison

---

## Fase 4 — Componentes Livewire / Volt

### Autenticación
- [ ] `resources/views/livewire/auth/login.blade.php` — formulario email/contraseña con validación inline
- [ ] `resources/views/livewire/auth/change-password.blade.php` — forzar cambio en primer login

### Gestión de Usuarios (Admin)
- [ ] `resources/views/livewire/users/index.blade.php` — tabla de Organizadores con acciones toggle
- [ ] `resources/views/livewire/users/create.blade.php` — formulario de nuevo Organizador

### Gestión de Sorteos
- [ ] `resources/views/livewire/raffles/index.blade.php` — listado con filtros reactivos y badges de estado
- [ ] `resources/views/livewire/raffles/create.blade.php` — wizard: paso 1 info general, paso 2 premios, paso 3 boletos
- [ ] `resources/views/livewire/raffles/show.blade.php` — detalle con acciones según estado
- [ ] `resources/views/livewire/raffles/edit.blade.php`

### Participantes y Boletos
- [ ] `resources/views/livewire/participants/index.blade.php` — tabla con paginación
- [ ] `resources/views/livewire/participants/create.blade.php` — formulario con asignación de boleto
- [ ] `resources/views/livewire/participants/import.blade.php` — upload CSV con reporte de errores

### Ejecución y Resultados
- [ ] `resources/views/livewire/raffles/execute.blade.php` — confirmación explícita + animación
- [ ] `resources/views/livewire/raffles/results.blade.php` — ganadores por premio
- [ ] `resources/views/livewire/raffles/audit.blade.php` — log inmutable en tabla + botón PDF

### Analytics
- [ ] `resources/views/livewire/analytics/dashboard.blade.php` — KPIs + gráficas

---

## Fase 5 — Notificaciones

- [ ] `App\Mail\WelcomeOrganizer` — Mailable con contraseña temporal
- [ ] `App\Mail\ParticipationConfirmed` — Mailable para participante registrado
- [ ] `App\Mail\RaffleReminder` — Mailable recordatorio 24h antes del cierre
- [ ] `App\Mail\RaffleWinner` — Mailable para ganadores
- [ ] `App\Mail\RaffleNotWinner` — Mailable para no ganadores
- [ ] `App\Jobs\SendRaffleNotification` — Job con `ShouldQueue`, reintentos=3, backoff exponencial
- [ ] `App\Jobs\SendRaffleReminder` — Job Scheduled para ejecución diaria
- [ ] `App\Console\Commands\DispatchRaffleReminders` — Comando artisan programado

---

## Fase 6 — Componentes Blade reutilizables

- [ ] `<x-raffle-status-badge>` — Badge con color por estado
- [ ] `<x-confirm-modal>` — Modal de confirmación genérico
- [ ] `<x-data-table>` — Tabla con paginación y búsqueda
- [ ] `<x-alert>` — Alertas de feedback
- [ ] `<x-form-input>` — Input con label y error inline
- [ ] `<x-loading-button>` — Botón con spinner de carga
- [ ] Layout principal con sidebar y topbar (roles-aware)

---

## Fase 7 — Pruebas automatizadas (PestPHP)

- [ ] `tests/Unit/Services/DrawExecutionServiceTest.php` — Validar selección aleatoria, sin repetición entre premios, rollback en fallo
- [ ] `tests/Unit/Services/TicketServiceTest.php` — Unicidad de boletos, validación de rango por tipo dígito
- [ ] `tests/Feature/Auth/LoginTest.php` — Rate limiting, cuenta desactivada, cambio de contraseña
- [ ] `tests/Feature/Raffles/RaffleLifecycleTest.php` — Flujo completo de estados
- [ ] `tests/Feature/Raffles/DrawExecutionTest.php` — Ejecución + auditoría inmutable
- [ ] `tests/Feature/Participants/TicketUniquenessTest.php` — Validación de unicidad `(raffle_id, serie, number)`
- [ ] `tests/Feature/Notifications/NotificationJobTest.php` — Jobs dispatched correctamente

---

## Reglas que el código debe respetar sin excepción

1. `random_int()` para toda selección aleatoria (CSPRNG).
2. Ejecución del sorteo dentro de una sola `DB::transaction()`.
3. Tablas `raffle_results` y `raffle_audit_logs` nunca tienen endpoints `PUT`/`PATCH`/`DELETE`.
4. Todas las rutas usan `authorize()` o `$this->authorize()` con la Policy correspondiente.
5. Emails enviados via `dispatch(new SendRaffleNotification(...))` — nunca `Mail::send()` directo en el request.
6. Contraseñas con `Hash::make($password, ['rounds' => 12])`.
7. Imágenes subidas: validar `mimes:jpg,jpeg,png,webp` y `max:2048`, guardar en `storage/app/private/`.
