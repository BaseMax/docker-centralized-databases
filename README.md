# Docker Centralized Databases

> A single Docker Compose stack that runs **MariaDB**, **phpMyAdmin**, **Redis**, and **Redis Insight** as shared, centralized database services for all your projects.

Instead of spinning up a separate database container per project, you run this stack once and connect every project container to the shared Docker network. Each project gets its own database and user while sharing the same server instance.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## Services

| Service        | Image                       | Default URL               | Description                     |
|----------------|-----------------------------|---------------------------|---------------------------------|
| **MariaDB**    | `mariadb:11`                | `127.0.0.1:3306`          | Relational database server      |
| **phpMyAdmin** | Custom (official base)      | http://localhost:8080     | MariaDB web admin UI            |
| **Redis**      | `redis:7-alpine`            | `127.0.0.1:6379`          | In-memory key-value store       |
| **Redis Insight** | `redis/redisinsight`     | http://localhost:5540     | Redis web admin UI              |

---

## Architecture

Two Docker networks provide clean separation between data access and administration:

- **`centralized-db-network`** – External network that your project containers join to reach MariaDB and Redis.
- **`admin-network`** – Internal network used only for admin UIs (phpMyAdmin, Redis Insight) to reach the databases. Project containers never join this network.

```
┌───────────────────────────────────────────────────────────────┐
│          centralized-db-network  (external projects join)     │
│                                                               │
│   ┌─────────────────┐             ┌─────────────────┐         │
│   │    MariaDB      │             │     Redis       │         │
│   │  :3306          │             │  :6379          │         │
│   └────────┬────────┘             └────────┬────────┘         │
└────────────│──────────────────────────────│──────────────────-┘
             │   admin-network (internal)   │
             │                             │
     ┌───────┴──────┐             ┌────────┴──────────┐
     │ phpMyAdmin   │             │  Redis Insight    │
     │ :8080        │             │  :5540            │
     └──────────────┘             └───────────────────┘
          ↑                                ↑
   127.0.0.1 only                  127.0.0.1 only
   (never network-exposed)         (never network-exposed)
```

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) ≥ 24
- [Docker Compose](https://docs.docker.com/compose/install/) v2 (bundled with Docker Desktop)

---

## Quick Start

### 1. Clone the repository

```bash
git clone https://github.com/BaseMax/docker-centralized-databases.git
cd docker-centralized-databases
```

### 2. Create your environment file

```bash
cp .env.example .env
```

Open `.env` and set **strong, unique passwords** for every variable that says `change_this_…`:

```env
MARIADB_ROOT_PASSWORD=your_very_strong_root_password
MARIADB_PASSWORD=your_very_strong_user_password
REDIS_PASSWORD=your_very_strong_redis_password
```

> **Security:** `.env` is listed in `.gitignore`. Never commit it.

### 3. Start the stack

```bash
docker compose up -d
```

### 4. Open the admin UIs

| UI             | URL                     | Login                                    |
|----------------|-------------------------|------------------------------------------|
| phpMyAdmin     | http://localhost:8080   | `root` / value of `MARIADB_ROOT_PASSWORD` |
| Redis Insight  | http://localhost:5540   | *(add connection – see below)*           |

**Connecting Redis Insight to Redis:**
1. Open http://localhost:5540
2. Click **Add Redis Database**
3. Host: `redis`, Port: `6379`
4. Password: value of `REDIS_PASSWORD` from your `.env`

---

## Configuration Reference

All tuneable parameters live in `.env`. Copy `.env.example` for the full list.

| Variable               | Default   | Description                                              |
|------------------------|-----------|----------------------------------------------------------|
| `MARIADB_ROOT_PASSWORD`| *(required)* | MariaDB root password                                 |
| `MARIADB_DATABASE`     | `app`     | Default database created on first start                  |
| `MARIADB_USER`         | `app_user`| Default unprivileged user created on first start         |
| `MARIADB_PASSWORD`     | *(required)* | Password for `MARIADB_USER`                           |
| `MARIADB_HOST`         | `127.0.0.1` | Host binding for the MariaDB port                      |
| `MARIADB_PORT`         | `3306`    | Host port for MariaDB                                    |
| `REDIS_PASSWORD`       | *(required)* | Redis authentication password                         |
| `REDIS_HOST`           | `127.0.0.1` | Host binding for the Redis port                        |
| `REDIS_PORT`           | `6379`    | Host port for Redis                                      |
| `PMA_MAX_UPLOAD_SIZE`  | `256M`    | Max SQL import size (sets `upload_max_filesize` + `post_max_size`) |
| `PMA_MAX_EXECUTION_TIME` | `600`   | PHP `max_execution_time` in seconds                      |
| `PMA_MEMORY_LIMIT`     | `512M`    | PHP `memory_limit`                                       |
| `PHPMYADMIN_PORT`      | `8080`    | Host port for phpMyAdmin                                 |
| `REDISINSIGHT_PORT`    | `5540`    | Host port for Redis Insight                              |

---

## Managing Databases and Users

### Add a database for a new project

**Option A – via init script (applied on first start):**

```bash
cp mariadb/init/00-example.sql.example mariadb/init/01-my-project.sql
# Edit 01-my-project.sql: replace MY_PROJECT, MY_USER, MY_PASSWORD
```

Init scripts run automatically only when the data volume is empty (first start). After the volume exists, use Option B.

**Option B – via MariaDB CLI (any time):**

```bash
docker exec -it centralized-mariadb mariadb -u root -p
```

```sql
CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'my_project_user'@'%' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON my_project.* TO 'my_project_user'@'%';
FLUSH PRIVILEGES;
```

**Option C – via phpMyAdmin:** http://localhost:8080 → Databases → Create database.

---

## Connecting from Other Projects

In your project's `docker-compose.yml`, declare the shared network as external and use the container names as hostnames:

```yaml
# your-project/docker-compose.yml

networks:
  centralized-db-network:
    external: true   # must already exist (started by this stack)

services:
  app:
    image: your-app-image
    networks:
      - default
      - centralized-db-network
    environment:
      DB_HOST:       centralized-mariadb
      DB_PORT:       3306
      DB_NAME:       my_project
      DB_USER:       my_project_user
      DB_PASSWORD:   strong_password
      REDIS_HOST:    centralized-redis
      REDIS_PORT:    6379
      REDIS_PASSWORD: your_redis_password
```

> **Important:** Start this centralized stack (`docker compose up -d`) **before** starting your project stack, so the `centralized-db-network` network exists.

---

## phpMyAdmin Upload Size

The maximum size for SQL file imports is controlled by `PMA_MAX_UPLOAD_SIZE` in `.env` (default `256M`). The official phpMyAdmin image reads this variable at container startup and writes the corresponding `upload_max_filesize` and `post_max_size` values into PHP configuration.

The custom `phpmyadmin/Dockerfile` extends the official image to add:
- `phpmyadmin/config/php-custom.ini` – extra PHP hardening (`session.cookie_httponly`, `expose_php = Off`, etc.)
- `phpmyadmin/config/config.user.inc.php` – phpMyAdmin-level security settings (arbitrary server disabled, session timeout, auth logging)

---

## Security Notes

| Area | Measure |
|------|---------|
| Secrets | All passwords come from `.env` – never hard-coded |
| Port binding | Admin UIs bound to `127.0.0.1` only (not `0.0.0.0`) |
| Network isolation | phpMyAdmin and Redis Insight are on a private `admin-network`; project containers only see the DB network |
| MariaDB | `skip-name-resolve`, `local-infile=0`, `symbolic-links=0` |
| phpMyAdmin | `PMA_ARBITRARY=0`, session idle timeout, auth logging, cookie hardening |
| Redis | `protected-mode yes`, password authentication, dangerous commands can be disabled in `redis/redis.conf` |
| PHP | `expose_php=Off`, `display_errors=Off`, `session.cookie_httponly=1`, `session.cookie_samesite=Strict` |

---

## Common Commands

```bash
# Start in the background
docker compose up -d

# View logs for all services
docker compose logs -f

# View logs for a single service
docker compose logs -f mariadb

# Stop all services (data is preserved in volumes)
docker compose down

# Stop and wipe all data volumes  ⚠️ destructive
docker compose down -v

# Rebuild the phpMyAdmin image (after editing the Dockerfile or configs)
docker compose build phpmyadmin
docker compose up -d phpmyadmin

# Open a MariaDB shell
docker exec -it centralized-mariadb mariadb -u root -p

# Open a Redis CLI session
docker exec -it centralized-redis redis-cli -a "$REDIS_PASSWORD"
```

---

## File Structure

```
docker-centralized-databases/
├── docker-compose.yml              # Main Compose file
├── .env.example                    # Environment variable template
├── .gitignore
├── mariadb/
│   ├── conf.d/
│   │   └── custom.cnf              # MariaDB server configuration
│   └── init/
│       └── 00-example.sql.example  # Template: create DB + user on first start
├── phpmyadmin/
│   ├── Dockerfile                  # Extends official phpMyAdmin image
│   └── config/
│       ├── php-custom.ini          # PHP hardening settings
│       └── config.user.inc.php     # phpMyAdmin security configuration
├── redis/
│   └── redis.conf                  # Redis security-hardened configuration
├── LICENSE
└── README.md
```

---

## License

MIT License

Copyright (c) 2026 Seyyed Ali Mohammadiyeh (Max Base)

See [LICENSE](LICENSE) for the full license text.