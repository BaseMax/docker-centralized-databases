# phpmyadmin/Dockerfile
# Extends the official phpMyAdmin image with security hardening.
#
# Upload size (upload_max_filesize / post_max_size) and other PHP limits are
# controlled entirely through environment variables in docker-compose.yml:
#   UPLOAD_LIMIT       → upload_max_filesize + post_max_size
#   MAX_EXECUTION_TIME → max_execution_time
#   MEMORY_LIMIT       → memory_limit
#
# This Dockerfile adds two extra layers on top:
#   1. Additional PHP hardening settings (php-custom.ini)
#   2. phpMyAdmin security configuration (config.user.inc.php)

FROM phpmyadmin:latest

# ── Additional PHP hardening (loaded last due to zzz- prefix) ─────────────────
# The official image writes uploads.ini; naming ours zzz-custom.ini ensures
# our directives are loaded after the image's generated files.
COPY config/php-custom.ini /usr/local/etc/php/conf.d/zzz-custom.ini

# ── phpMyAdmin user-level security configuration ──────────────────────────────
COPY config/config.user.inc.php /etc/phpmyadmin/config.user.inc.php
