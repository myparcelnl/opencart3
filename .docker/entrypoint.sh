#!/usr/bin/env bash
# Entrypoint for the OpenCart 3 dev container.
#  1. Copies OpenCart core into the (empty) volume on first start
#  2. Waits for the database and runs the CLI installer once
#  3. Links the plugin from the git checkout (/plugin) into OpenCart
#  4. Installs the plugin (first start only) and refreshes OCMOD in the background
# Each start logs one line "[opencart] setup finished: OK" or
# "[opencart] setup finished: FAILED (<step>)".
set -euo pipefail

WEBROOT=/var/www/html

fail_setup() {
  echo "[opencart] setup finished: FAILED ($1)"
  exit 1
}

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
  oc-install || fail_setup "opencart install"
  FIRST_INSTALL=1
fi

oc-link || fail_setup "plugin link"

# Once Apache is up (oc-admin-token waits for the admin login page):
#  - first install: install the MyParcel extensions
#  - every start: refresh OCMOD modifications
(
  if [ "${FIRST_INSTALL}" = 1 ]; then
    oc-plugin-install || fail_setup "plugin install"
  fi
  oc-ocmod-refresh || fail_setup "ocmod refresh"
  echo "[opencart] setup finished: OK"
) &

exec docker-php-entrypoint "$@"
