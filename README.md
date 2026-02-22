# gestion-activos

Infraestructura base de **Inventario Salud** con stack fijo:

- Laravel **11.48.0**
- PHP **8.3.0**
- PostgreSQL **16.2**
- Blade + Tailwind CSS + Alpine.js
- Docker + Docker Compose

## Puesta en marcha desde cero

```bash
git clone <repo>
cd gestion-activos
docker compose up --build
```

Aplicación disponible en `http://localhost:8080`.

## Autenticación básica

Para inicializar usuario administrador, activar migraciones y seeders:

1. Configurar `RUN_MIGRATIONS=true` en `.env` del root (o variable de entorno al ejecutar compose).
2. Levantar contenedores con `docker compose up --build`.

Credenciales iniciales:

- Email: `admin@gestion-activos.local`
- Password: `password`

## Servicios Docker

- `app`: PHP-FPM 8.3 + Laravel
- `web`: Nginx 1.25 (sirve la app en puerto 8080)
- `db`: PostgreSQL 16.2

## Comandos útiles

```bash
docker compose ps
docker compose logs -f --tail=200
docker compose exec app php artisan --version
docker compose exec app php -v
docker compose exec app php artisan test
```
