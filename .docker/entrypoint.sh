#!/usr/bin/env bash
# Entrypoint for the OpenCart 3 dev container.
#  1. Copies OpenCart core into the (empty) volume on first start
#  2. Waits for the database and runs the CLI installer once
#  3. Links the plugin from the git checkout (/plugin) into OpenCart
#  4. Refreshes OCMOD modifications in the background
set -euo pipefail

WEBROOT=/var/www/html

if [ ! -f "${WEBROOT}/index.php" ]; then
  echo "[opencart] Copying OpenCart core into ${WEBROOT}..."
  rsync -a /usr/src/opencart/ "${WEBROOT}/"
  touch "${WEBROOT}/config.php" "${WEBROOT}/admin/config.php"
  chown -R www-data:www-data "${WEBROOT}"
fi

echo "[opencart] Waiting for database ${DB_HOST}:${DB_PORT}..."
until mysqladmin ping -h"${DB_HOST}" -P"${DB_PORT}" -u"${DB_USER}" -p"${DB_PASSWORD}" --silent 2>/dev/null; do
  sleep 2
done

FIRST_INSTALL=0
if [ ! -s "${WEBROOT}/config.php" ]; then
  oc-install
  FIRST_INSTALL=1
fi

oc-link

# Once Apache is up (needs an HTTP login to the admin):
#  - first install: install the MyParcel extensions
#  - every start: refresh OCMOD so install.xml is applied
(
  sleep 3
  if [ "${FIRST_INSTALL}" = 1 ]; then oc-plugin-install || echo "[opencart] plugin install failed, run 'make install-plugin'"; fi
  oc-ocmod-refresh || echo "[opencart] OCMOD refresh failed, run 'make ocmod'"
) &

exec docker-php-entrypoint "$@"
