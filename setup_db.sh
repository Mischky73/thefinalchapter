#!/bin/bash
# Einmalig als Admin ausführen: bash setup_db.sh
# Legt Datenbank, optionalen User und Schema an.
# Keine Zugangsdaten hardcoden: Werte per ENV setzen.

set -euo pipefail

DB_NAME="${TFC_DB_NAME:-thefinalchapter}"
DB_USER="${TFC_DB_USER:-tlcuser}"
DB_PASS="${TFC_DB_PASS:-}"
DB_HOST_PATTERN="${TFC_DB_HOST_PATTERN:-localhost}"
MYSQL_ROOT_ARGS="${MYSQL_ROOT_ARGS:-}"

if [ -z "$DB_PASS" ]; then
  echo "FEHLER: TFC_DB_PASS ist nicht gesetzt."
  echo "Beispiel: TFC_DB_PASS='...' bash setup_db.sh"
  exit 2
fi

echo "==> Datenbank und User anlegen..."
mysql $MYSQL_ROOT_ARGS -e "
  CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS '${DB_USER}'@'${DB_HOST_PATTERN}' IDENTIFIED BY '${DB_PASS}';
  GRANT ALL PRIVILEGES ON \`${DB_NAME}\`.* TO '${DB_USER}'@'${DB_HOST_PATTERN}';
  FLUSH PRIVILEGES;
"

echo "==> Schema einspielen..."
mysql $MYSQL_ROOT_ARGS "$DB_NAME" < "$(dirname "$0")/install.sql"

echo ""
echo "Fertig: Datenbank '${DB_NAME}' und User '${DB_USER}' sind vorbereitet."
echo "Passwort wird nicht ausgegeben."
