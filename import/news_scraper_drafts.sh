#!/bin/sh
# Server-Cron: neue Feed-Meldungen als Entwürfe anlegen, nicht veröffentlichen.
# Beispiel Cron: */30 * * * * cd /var/www/html && sh import/news_scraper_drafts.sh >> storage/logs/news_scraper_drafts.log 2>&1
cd "$(dirname "$0")/.." || exit 1
php import/news_scraper.php --status=draft
