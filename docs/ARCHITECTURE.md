# Arquitectura del proyecto (Laravel 11 + Docker + Nginx + PostgreSQL)

## 🐳 Filosofía Docker aplicada al proyecto

- **La imagen es la única fuente de verdad del código.** El código se copia durante el `docker build` y no se monta con volúmenes. Esto elimina dependencias del filesystem del host y garantiza builds reproducibles.
- **Los assets (Vite) se construyen en build-time.** El contenedor ya incluye `public/build` generado.
- **Solo se usa volumen para datos persistentes de PostgreSQL.** No hay volúmenes para `/var/www/app`.
- **El contenedor PHP-FPM es el responsable de ejecutar Laravel.** Nginx sirve `/public` y delega a PHP-FPM.

## 🧱 Estructura final esperada de contenedores

| Contenedor | Imagen/Build | Propósito | Puertos |
| --- | --- | --- | --- |
| `app` | `Dockerfile` (target `app`) | PHP 8.3 + Laravel 11 (FPM) | Interno 9000 |
| `web` | `Dockerfile` (target `nginx`) | Nginx sirviendo `/public` | Host `8080` → `80` |
| `db` | `postgres:16.2` | PostgreSQL | Interno 5432 |

## 🔐 Variables de entorno requeridas (sin secretos reales)

Estas variables se inyectan en `docker compose` (no se escribe `.env` dentro de la imagen):

- `APP_KEY` (obligatoria)
- `APP_ENV`
- `APP_DEBUG`
- `APP_URL`
- `APP_TIMEZONE`
- `APP_LOCALE`
- `APP_FALLBACK_LOCALE`
- `LOG_CHANNEL`
- `LOG_LEVEL`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`
- `SESSION_DRIVER`
- `CACHE_DRIVER`
- `QUEUE_CONNECTION`
- `RUN_MIGRATIONS`

## 🔑 Credenciales de ejemplo (DEV)

> **Solo de referencia local.** No usar en producción.

```
APP_KEY=base64:GENERAR_CON_LARAVEL
DB_DATABASE=gestion_activos
DB_USERNAME=gestion_activos
DB_PASSWORD=secret
```

Para generar un `APP_KEY` local (ejemplo):

```
docker compose run --rm app php artisan key:generate --show
```

## ▶️ Flujo correcto

1) **Clone**

```
git clone https://github.com/dptytlapampa-code/gestion-activos.git
cd gestion-activos
```

2) **Build**

```
docker compose build --no-cache
```

3) **Up**

```
docker compose up -d
```

4) **Migrate**

```
docker compose exec app php artisan migrate --force
```

## ❗ Errores comunes y por qué no deben resolverse con volúmenes

- **“No veo mis cambios locales en el contenedor.”**  
  No se usan volúmenes para el código por diseño. El código vive en la imagen y se actualiza con `docker compose build`. Esto asegura builds consistentes y elimina dependencias del host.

- **“Quiero editar el código dentro del contenedor.”**  
  Se debe modificar el repo local y reconstruir la imagen. Esto evita drift entre entornos.

- **“Faltan assets en `/public/build`.”**  
  Los assets se generan en build-time. Si faltan, el build falló o el contexto estaba incompleto. Rehacer `docker compose build --no-cache`.

