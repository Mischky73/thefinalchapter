#!/bin/bash
# Einmalig als root ausführen: sudo bash setup_db.sh
# Legt Datenbank, User und Schema an

set -e

echo "==> Datenbank anlegen..."
mysql -e "
  CREATE DATABASE IF NOT EXISTS thefinalchapter CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
  CREATE USER IF NOT EXISTS 'tlcuser'@'localhost' IDENTIFIED BY 'TLC2026secure!';
  GRANT ALL PRIVILEGES ON thefinalchapter.* TO 'tlcuser'@'localhost';
  FLUSH PRIVILEGES;
"

echo "==> Schema einspielen..."
mysql thefinalchapter < "$(dirname "$0")/install.sql"

echo ""
echo "✅ Fertig! Datenbank 'thefinalchapter' mit User 'tlcuser' angelegt."
echo "   Passwort: TLC2026secure!"
echo ""
echo "Nächster Schritt: php import/wp_import.php"
