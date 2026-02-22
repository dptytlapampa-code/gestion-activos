# Gestion Activos

## Requisitos

- Docker Engine + Docker Compose (plugin `docker compose`)
- Git

## Inicio rápido (desde clone limpio)

```bash
git clone https://github.com/dptytlapampa-code/gestion-activos.git
cd gestion-activos
docker compose build --no-cache
docker compose up -d
docker compose exec app composer install
docker compose exec app php artisan migrate --force
docker compose exec app php artisan test
```

La aplicación queda disponible en `http://localhost:8080`.

## Acceso inicial

Si activás `RUN_MIGRATIONS=true`, el seeder crea un usuario inicial para autenticación.

- **Email**: `admin@gestion-activos.local`
- **Password**: `password`

## Comandos útiles

```bash
docker compose ps
docker compose logs -f --tail=200
docker compose exec app php -v
docker compose exec app php artisan --version
```
