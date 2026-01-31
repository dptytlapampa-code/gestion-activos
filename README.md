# Gestion Activos

## Requisitos

- Docker Engine + Docker Compose (plugin `docker compose`)
- Git

## Quickstart

```bash
git clone git@github.com:dptytlapampa-code/gestion-activos.git
cd gestion-activos
docker compose up -d --build
```

La aplicación queda disponible en `http://localhost:8080`.

## Cómo cambiar puertos/DB por variables

- **Puerto HTTP**: ajustá el mapeo en `docker-compose.yml` (servicio `web`).
- **Base de datos**: cambiá las variables `DB_*` en `backend/.env`.
- **Ejecución de migraciones/seeders**: seteá `RUN_MIGRATIONS=true` en `backend/.env`.

## Acceso inicial

Si activás `RUN_MIGRATIONS=true`, el seeder crea un usuario inicial para autenticación.

- **Email**: `admin@gestion-activos.local`
- **Password**: `password`

La llave `APP_KEY` se genera automáticamente en el primer arranque del contenedor.

## Comandos útiles

```bash
docker compose ps
docker compose logs -f --tail=200
docker compose exec app php -v
docker compose exec app php artisan --version
```

Para resetear todo:

```bash
docker compose down -v
docker compose up -d --build
```
