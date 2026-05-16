# Docker Centralized Databases

> A single Docker Compose stack that runs **MariaDB, PostgreSQL, MongoDB, ClickHouse, InfluxDB, Elasticsearch, Neo4j, Redis, Memcached** and their web admin UIs as shared, centralized database services for all your projects.

Instead of spinning up separate database containers per project, run this stack once and connect every project container to the shared Docker network. Each project gets its own database and user while sharing the same server instance.

Every service is **100% optional** — enable only what you need via `COMPOSE_PROFILES` in your `.env` file.

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](LICENSE)

---

## Services

| Category | Profile | Service | Image | Host Access | Built-in UI |
|---|---|---|---|---|---|
| **Relational** | `mariadb` | MariaDB | `mariadb:latest` | `127.0.0.1:3306` | — |
| | `phpmyadmin` | phpMyAdmin | custom | http://localhost:8080 | yes |
| | `postgres` | PostgreSQL | `postgres:alpine` | `127.0.0.1:5432` | — |
| | `pgadmin` | pgAdmin 4 | `dpage/pgadmin4:latest` | http://localhost:5050 | yes |
| | `adminer` | Adminer | `adminer:latest` | http://localhost:8081 | yes |
| **Document** | `mongodb` | MongoDB | `mongo:latest` | `127.0.0.1:27017` | — |
| | `mongo-express` | Mongo Express | `mongo-express:latest` | http://localhost:8082 | yes |
| **Analytics / OLAP** | `clickhouse` | ClickHouse | `clickhouse/clickhouse-server:latest` | `127.0.0.1:8123` / `:9000` | http://localhost:8123/play |
| **Time-series** | `influxdb` | InfluxDB | `influxdb:alpine` | http://localhost:8086 | yes |
| **Search** | `elasticsearch` | Elasticsearch | `elasticsearch:8` | `127.0.0.1:9200` | — |
| | `kibana` | Kibana | `kibana:8` | http://localhost:5601 | yes |
| **Graph** | `neo4j` | Neo4j | `neo4j:latest` | `127.0.0.1:7474` / `:7687` | http://localhost:7474 |
| **Cache** | `redis` | Redis | `redis:alpine` | `127.0.0.1:6379` | — |
| | `redisinsight` | Redis Insight | `redis/redisinsight:latest` | http://localhost:5540 | yes |
| | `memcached` | Memcached | `memcached:alpine` | `127.0.0.1:11211` | — |

All ports bind to **127.0.0.1** by default — admin UIs are never accessible from the network.

---

## Architecture

Two Docker networks provide clean separation between data access and administration:

- **`centralized-db-network`** -- Shared external network. Your project containers join this to reach any database.
- **`admin-network`** -- Private internal network. Only admin UIs talk across this. Project containers never join it.

```
+-------------------------------------------------------------------------+
|     centralized-db-network   (external project containers join here)    |
|                                                                         |
|  MariaDB:3306  PostgreSQL:5432  MongoDB:27017  ClickHouse:8123/9000     |
|  InfluxDB:8086  Elasticsearch:9200  Neo4j:7474/7687  Redis:6379         |
|  Memcached:11211                                                        |
+-----------------------------------+-------------------------------------+
                                    | admin-network (private)
               +--------------------+------------------------+
               |  phpMyAdmin    :8080                        |
               |  pgAdmin       :5050                        |
               |  Adminer       :8081                        |
               |  Mongo Express :8082                        |
               |  InfluxDB UI   :8086 (built-in)             |
               |  Kibana        :5601                        |
               |  Neo4j Browser :7474 (built-in)             |
               |  Redis Insight :5540                        |
               +--------------------------------------------+
               All UI ports: 127.0.0.1 only
```

---

## Prerequisites

- [Docker](https://docs.docker.com/get-docker/) >= 24
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

Open `.env` and:

1. Set `COMPOSE_PROFILES` to the services you want (see [Profiles](#profiles) below)
2. Set **strong, unique passwords** for every active service

```env
COMPOSE_PROFILES=mariadb,phpmyadmin,redis,redisinsight

MARIADB_ROOT_PASSWORD=your_very_strong_root_password
MARIADB_PASSWORD=your_very_strong_user_password
REDIS_PASSWORD=your_very_strong_redis_password
```

> **Security:** `.env` is in `.gitignore`. Never commit it.

### 3. Start the stack

```bash
docker compose up -d
```

---

## Profiles

Profiles let you start only the services you actually need. Set `COMPOSE_PROFILES` in your `.env`:

| Want to run | Set `COMPOSE_PROFILES` to |
|---|---|
| Classic (default) | `mariadb,phpmyadmin,redis,redisinsight` |
| SQL only | `mariadb,phpmyadmin,postgres,pgadmin,adminer` |
| NoSQL bundle | `mongodb,mongo-express,redis,redisinsight` |
| Analytics bundle | `clickhouse,influxdb,elasticsearch,kibana` |
| Graph + Search | `neo4j,elasticsearch,kibana` |
| Cache only | `redis,redisinsight,memcached` |
| Everything | `mariadb,phpmyadmin,postgres,pgadmin,adminer,mongodb,mongo-express,clickhouse,influxdb,memcached,elasticsearch,kibana,neo4j,redis,redisinsight` |

You can also override profiles on the command line:

```bash
# One-off: start only postgres and pgadmin
docker compose --profile postgres --profile pgadmin up -d

# One-off: start the full stack
docker compose --profile mariadb --profile phpmyadmin \
               --profile postgres --profile pgadmin \
               --profile adminer \
               --profile redis --profile redisinsight up -d
```

---

## Admin UI Access

| UI | URL | Login |
|---|---|---|
| phpMyAdmin | http://localhost:8080 | `root` / `MARIADB_ROOT_PASSWORD` |
| pgAdmin 4 | http://localhost:5050 | `PGADMIN_EMAIL` / `PGADMIN_PASSWORD` |
| Adminer | http://localhost:8081 | choose server + credentials |
| Mongo Express | http://localhost:8082 | `MONGO_EXPRESS_USER` / `MONGO_EXPRESS_PASSWORD` |
| ClickHouse Play | http://localhost:8123/play | `CLICKHOUSE_USER` / `CLICKHOUSE_PASSWORD` |
| InfluxDB | http://localhost:8086 | `INFLUXDB_USER` / `INFLUXDB_PASSWORD` |
| Kibana | http://localhost:5601 | `elastic` / `ELASTICSEARCH_PASSWORD` |
| Neo4j Browser | http://localhost:7474 | `neo4j` / `NEO4J_PASSWORD` |
| Redis Insight | http://localhost:5540 | *(add connection -- see below)* |

**Connecting Redis Insight to Redis:**
1. Open http://localhost:5540 â†’ **Add Redis Database**
2. Host: `redis` · Port: `6379` · Password: value of `REDIS_PASSWORD`

**Connecting pgAdmin to PostgreSQL:**
1. Open http://localhost:5050 â†’ **Add New Server**
2. Connection tab â†’ Host: `postgres` · Port: `5432`
3. Username: `POSTGRES_USER` · Password: `POSTGRES_PASSWORD`

---

## Configuration Reference

### MariaDB

| Variable | Default | Required when profile active |
|---|---|---|
| `MARIADB_ROOT_PASSWORD` | â€” | yes |
| `MARIADB_DATABASE` | `app` | no |
| `MARIADB_USER` | `app_user` | no |
| `MARIADB_PASSWORD` | â€” | yes |
| `MARIADB_HOST` | `127.0.0.1` | no |
| `MARIADB_PORT` | `3306` | no |

### phpMyAdmin

| Variable | Default | Description |
|---|---|---|
| `PMA_MAX_UPLOAD_SIZE` | `256M` | Max SQL import size |
| `PMA_MAX_EXECUTION_TIME` | `600` | PHP `max_execution_time` (s) |
| `PMA_MEMORY_LIMIT` | `512M` | PHP `memory_limit` |
| `PHPMYADMIN_PORT` | `8080` | Host port |

### PostgreSQL

| Variable | Default | Required when profile active |
|---|---|---|
| `POSTGRES_DB` | `app` | no |
| `POSTGRES_USER` | `app_user` | no |
| `POSTGRES_PASSWORD` | â€” | yes |
| `POSTGRES_HOST` | `127.0.0.1` | no |
| `POSTGRES_PORT` | `5432` | no |

### pgAdmin

| Variable | Default | Required when profile active |
|---|---|---|
| `PGADMIN_EMAIL` | `admin@example.com` | no |
| `PGADMIN_PASSWORD` | â€” | yes |
| `PGADMIN_PORT` | `5050` | no |

### Adminer

| Variable | Default | Description |
|---|---|---|
| `ADMINER_DEFAULT_SERVER` | `mariadb` | Default server on login form |
| `ADMINER_PORT` | `8081` | Host port |

### MongoDB

| Variable | Default | Description |
|---|---|---|
| `MONGO_ROOT_USER` | `root` | Root username |
| `MONGO_ROOT_PASSWORD` | — | required |
| `MONGO_DATABASE` | `app` | Default database |
| `MONGO_HOST` | `127.0.0.1` | Host binding |
| `MONGO_PORT` | `27017` | Host port |

### Mongo Express

| Variable | Default | Description |
|---|---|---|
| `MONGO_EXPRESS_USER` | `admin` | Basic-auth username |
| `MONGO_EXPRESS_PASSWORD` | — | required |
| `MONGO_EXPRESS_PORT` | `8082` | Host port |

### ClickHouse

| Variable | Default | Description |
|---|---|---|
| `CLICKHOUSE_DB` | `default` | Default database |
| `CLICKHOUSE_USER` | `admin` | Admin user |
| `CLICKHOUSE_PASSWORD` | — | required |
| `CLICKHOUSE_HOST` | `127.0.0.1` | Host binding |
| `CLICKHOUSE_HTTP_PORT` | `8123` | HTTP + Play UI port |
| `CLICKHOUSE_NATIVE_PORT` | `9000` | Native protocol port |

### InfluxDB

| Variable | Default | Description |
|---|---|---|
| `INFLUXDB_USER` | `admin` | Admin username |
| `INFLUXDB_PASSWORD` | — | required |
| `INFLUXDB_ORG` | `my-org` | Initial organisation |
| `INFLUXDB_BUCKET` | `default` | Initial bucket |
| `INFLUXDB_TOKEN` | — | Admin API token (generate: `openssl rand -hex 32`) |
| `INFLUXDB_PORT` | `8086` | Host port + UI |

### Memcached

| Variable | Default | Description |
|---|---|---|
| `MEMCACHED_MEMORY` | `128` | Max memory in MB |
| `MEMCACHED_CONNECTIONS` | `1024` | Max simultaneous connections |
| `MEMCACHED_HOST` | `127.0.0.1` | Host binding |
| `MEMCACHED_PORT` | `11211` | Host port |

> Memcached has no authentication. Keep it bound to 127.0.0.1.

### Elasticsearch

| Variable | Default | Description |
|---|---|---|
| `ELASTICSEARCH_PASSWORD` | — | Password for `elastic` superuser |
| `ELASTICSEARCH_HOST` | `127.0.0.1` | Host binding |
| `ELASTICSEARCH_PORT` | `9200` | Host port |
| `ELASTICSEARCH_JAVA_OPTS` | `-Xms512m -Xmx512m` | JVM heap settings |

> **Linux hosts only:** Elasticsearch requires `vm.max_map_count ≥ 262144`.
> Run once (or add to `/etc/sysctl.conf`):
> ```bash
> sudo sysctl -w vm.max_map_count=262144
> ```

### Kibana

| Variable | Default | Description |
|---|---|---|
| `KIBANA_ENCRYPTION_KEY` | — | Must be ≥ 32 characters. Generate: `openssl rand -hex 32` |
| `KIBANA_PORT` | `5601` | Host port |

### Neo4j

| Variable | Default | Description |
|---|---|---|
| `NEO4J_PASSWORD` | — | Must be ≥ 8 characters |
| `NEO4J_HTTP_PORT` | `7474` | Browser UI port |
| `NEO4J_BOLT_PORT` | `7687` | Bolt driver port |
| `NEO4J_HEAP_INITIAL` | `512m` | JVM initial heap |
| `NEO4J_HEAP_MAX` | `512m` | JVM max heap |
| `NEO4J_PAGECACHE` | `256m` | Page cache size |

### Redis

| Variable | Default | Required when profile active |
|---|---|---|
| `REDIS_PASSWORD` | â€” | yes |
| `REDIS_HOST` | `127.0.0.1` | no |
| `REDIS_PORT` | `6379` | no |

### Redis Insight

| Variable | Default | Description |
|---|---|---|
| `REDISINSIGHT_PORT` | `5540` | Host port |

---

## Managing Databases and Users

### MariaDB

```bash
# From init script (first start only)
cp mariadb/init/00-example.sql.example mariadb/init/01-my-project.sql
# Edit: replace MY_PROJECT, MY_USER, MY_PASSWORD
```
```bash
# Or live via CLI
docker exec -it centralized-mariadb mariadb -u root -p
```
```sql
CREATE DATABASE my_project CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'my_project_user'@'%' IDENTIFIED BY 'strong_password';
GRANT ALL PRIVILEGES ON my_project.* TO 'my_project_user'@'%';
FLUSH PRIVILEGES;
```

### PostgreSQL

```bash
cp postgres/init/00-example.sh.example postgres/init/01-my-project.sh
chmod +x postgres/init/01-my-project.sh
```
```bash
docker exec -it centralized-postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"
```
```sql
CREATE USER my_project_user WITH ENCRYPTED PASSWORD 'strong_password';
CREATE DATABASE my_project OWNER my_project_user ENCODING 'UTF8';
\c my_project
GRANT ALL ON SCHEMA public TO my_project_user;  -- required on PostgreSQL 15+
```

### MongoDB

```bash
cp mongodb/init/00-example.js.example mongodb/init/01-my-project.js
# Edit: replace MY_PROJECT, MY_USER, MY_PASSWORD
```
```bash
docker exec -it centralized-mongodb mongosh \
  -u "$MONGO_ROOT_USER" -p "$MONGO_ROOT_PASSWORD" --authenticationDatabase admin
```
```js
use my_project
db.createUser({ user: 'my_project_user', pwd: 'strong_password',
  roles: [{ role: 'readWrite', db: 'my_project' }] })
```

### ClickHouse

```bash
# Open the built-in Play UI: http://localhost:8123/play
docker exec -it centralized-clickhouse clickhouse-client \
  --user "$CLICKHOUSE_USER" --password "$CLICKHOUSE_PASSWORD"
```
```sql
CREATE DATABASE IF NOT EXISTS my_project;
CREATE USER IF NOT EXISTS my_project_user IDENTIFIED BY 'strong_password';
GRANT ALL ON my_project.* TO my_project_user;
```

### InfluxDB

Open http://localhost:8086, log in, and manage buckets/tokens via the built-in UI.

```bash
docker exec -it centralized-influxdb influx bucket create \
  --name my-bucket --org "$INFLUXDB_ORG" --token "$INFLUXDB_TOKEN"
```

### Neo4j

Open http://localhost:7474, connect with bolt URL `bolt://localhost:7687`, user `neo4j`, password = `NEO4J_PASSWORD`.

```bash
docker exec -it centralized-neo4j cypher-shell \
  -u neo4j -p "$NEO4J_PASSWORD"
```

> **Init scripts** in `*/init/` directories run **only when the data volume is empty**. To re-apply: `docker compose down -v` destroys all data.

---

## Connecting from Other Projects

In your project's `docker-compose.yml`, declare the shared network as external and use container names as hostnames:

```yaml
# your-project/docker-compose.yml

networks:
  centralized-db-network:
    external: true   # must already exist (this stack must be running)

services:
  app:
    image: your-app-image
    networks:
      - default
      - centralized-db-network
    environment:
      # MariaDB
      DB_HOST:     centralized-mariadb
      DB_PORT:     3306
      DB_NAME:     my_project
      DB_USER:     my_project_user
      DB_PASSWORD: strong_password

      # PostgreSQL
      PG_HOST:     centralized-postgres
      PG_PORT:     5432
      PG_NAME:     my_project
      PG_USER:     my_project_user
      PG_PASSWORD: strong_password

      # Redis
      REDIS_HOST:     centralized-redis
      REDIS_PORT:     6379
      REDIS_PASSWORD: your_redis_password

      # MongoDB
      MONGO_HOST:     centralized-mongodb
      MONGO_PORT:     27017

      # ClickHouse
      CLICKHOUSE_HOST: centralized-clickhouse
      CLICKHOUSE_PORT: 9000

      # Elasticsearch
      ELASTICSEARCH_HOST: centralized-elasticsearch
      ELASTICSEARCH_PORT: 9200

      # Neo4j
      NEO4J_HOST: centralized-neo4j
      NEO4J_BOLT_PORT: 7687

      # Memcached
      MEMCACHED_HOST: centralized-memcached
      MEMCACHED_PORT: 11211
```

> Start the centralized stack **before** your project stack so `centralized-db-network` exists.

---

## phpMyAdmin Upload Size

Controlled by `PMA_MAX_UPLOAD_SIZE` in `.env` (default `256M`). The official phpMyAdmin image reads this at startup and sets `upload_max_filesize` and `post_max_size` accordingly.

The custom `phpmyadmin/Dockerfile` adds on top:
- `phpmyadmin/config/php-custom.ini` -- PHP hardening (`session.cookie_httponly`, `expose_php = Off`, ...)
- `phpmyadmin/config/config.user.inc.php` -- phpMyAdmin security settings (session timeout, auth logging, arbitrary server disabled)

---

## Security Notes

| Area | Measure |
|---|---|
| Secrets | All passwords from `.env` -- never hard-coded |
| Port binding | All ports bound to `127.0.0.1` only |
| Network isolation | Admin UIs on private `admin-network`; project containers only see `db-network` |
| MariaDB | `skip-name-resolve`, `local-infile=0`, `symbolic-links=0` |
| phpMyAdmin | `PMA_ARBITRARY=0`, session idle timeout, auth logging, cookie hardening |
| PostgreSQL | `scram-sha-256` password encryption, slow-query logging |
| Redis | `protected-mode yes`, password auth, dangerous commands can be disabled in `redis/redis.conf` |
| MongoDB | Root-only access; authentication enabled by default in `mongo:latest` |
| ClickHouse | Default user restricted to localhost via `users.d/default-settings.xml` |
| Elasticsearch | X-Pack security enabled; password auth required for `elastic` superuser |
| Neo4j | Password auth required; Bolt protocol only on port 7687 |
| Memcached | No auth -- keep bound to `127.0.0.1`; never expose externally |
| PHP | `expose_php=Off`, `display_errors=Off`, `session.cookie_httponly=1`, `session.cookie_samesite=Strict` |

---

## Common Commands

```bash
# Start with profiles from .env
docker compose up -d

# Start a specific profile on the fly
docker compose --profile postgres --profile pgadmin up -d

# View logs for all running services
docker compose logs -f

# View logs for a specific service
docker compose logs -f mariadb

# Rebuild the phpMyAdmin image (after editing Dockerfile or configs)
docker compose build phpmyadmin && docker compose up -d phpmyadmin

# Stop all services (data volumes preserved)
docker compose down

# Stop and remove all data volumes  -- destructive
docker compose down -v

# Open a MariaDB shell
docker exec -it centralized-mariadb mariadb -u root -p

# Open a PostgreSQL shell
docker exec -it centralized-postgres psql -U "$POSTGRES_USER" -d "$POSTGRES_DB"

# Open a Redis CLI session
docker exec -it centralized-redis redis-cli -a "$REDIS_PASSWORD"
```

---

## File Structure

```
docker-centralized-databases/
+-- docker-compose.yml
+-- .env.example           <- copy to .env and fill in passwords
+-- .gitignore
|
+-- mariadb/
|   +-- conf.d/custom.cnf              <- server tuning & security
|   +-- init/00-example.sql.example   <- create DB + user template
|
+-- phpmyadmin/
|   +-- Dockerfile                     <- extends official phpMyAdmin image
|   +-- config/
|       +-- php-custom.ini             <- PHP hardening
|       +-- config.user.inc.php        <- phpMyAdmin security settings
|
+-- postgres/
|   +-- conf/postgresql.conf           <- server tuning & security
|   +-- init/00-example.sh.example    <- create DB + user template
|
+-- mongodb/
|   +-- init/00-example.js.example    <- create DB + user template
|
+-- clickhouse/
|   +-- config/config.d/custom.xml    <- server settings
|   +-- users/users.d/
|       +-- default-settings.xml       <- restricts default user to localhost
|
+-- redis/
|   +-- redis.conf                     <- security-hardened config
|
+-- LICENSE
+-- README.md
```

---

## License

MIT License

Copyright (c) 2026 Seyyed Ali Mohammadiyeh (Max Base)
