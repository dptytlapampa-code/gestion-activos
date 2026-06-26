# AGENTS.md — Gestión de Activos

## Proyecto

Proyecto: **Gestión de Activos**.

Este archivo define reglas obligatorias para cualquier cambio realizado en este repositorio. Las instrucciones aplican a todo el árbol del proyecto salvo que exista un `AGENTS.md` más específico en un subdirectorio.

## Stack fijo

El stack del proyecto es fijo y no debe cambiarse sin una decisión explícita del equipo responsable:

- Laravel 11.x
- PHP 8.3
- PostgreSQL
- Blade
- TailwindCSS
- Alpine.js
- Docker
- Docker Compose

## Tecnologías prohibidas

Está prohibido introducir, instalar, configurar o migrar el proyecto hacia:

- React
- Vue
- Keycloak
- cualquier otro framework frontend
- cualquier otro motor de base de datos distinto de PostgreSQL
- upgrades automáticos de Laravel o de dependencias

No se deben ejecutar upgrades implícitos ni modificar versiones de dependencias sin una solicitud explícita y acotada.

## Arquitectura

La arquitectura debe mantenerse simple, clara y alineada con convenciones oficiales de Laravel:

- controladores livianos
- lógica de negocio en servicios
- modelos coherentes con el dominio
- vistas Blade simples
- separación clara de responsabilidades

No mezclar lógica de negocio en vistas Blade. Evitar sobre-ingeniería y mantener el código legible, trazable y mantenible.

## Base de datos

Reglas obligatorias para cambios de base de datos:

- no modificar migraciones históricas
- crear nuevas migraciones para cualquier cambio de esquema
- mantener integridad referencial
- dentro de Docker, Laravel debe usar `DB_HOST=db`

No cambiar PostgreSQL por otro motor de base de datos.

## Docker

El proyecto debe poder levantarse con:

```bash
docker compose build --no-cache
docker compose up -d
```

Todo comando de Laravel debe ejecutarse dentro del contenedor `app`. Ejemplos:

```bash
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test
docker compose exec app composer install
```

Está prohibido ejecutar `composer`, `php artisan` o comandos equivalentes de Laravel directamente en el host.

## UX y manejo de errores

La experiencia debe ser clara para personal hospitalario no técnico:

- los usuarios no deben ver errores SQL, stack traces ni excepciones técnicas
- los errores técnicos deben traducirse a mensajes humanos
- usar alertas visuales claras con Tailwind
- mantener flujos e interfaces simples y comprensibles

## Calidad

Prioridades del proyecto:

- estabilidad
- trazabilidad
- integridad de datos
- seguridad
- mantenibilidad

Reglas de calidad obligatorias:

- no dejar TODOs
- no crear cambios parciales
- no romper funcionalidades existentes
- no hacer upgrades de dependencias sin autorización explícita
- no modificar código funcional de Laravel salvo que la tarea lo solicite expresamente
- no modificar migraciones históricas
