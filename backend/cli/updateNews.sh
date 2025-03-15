#!/bin/bash
# update_loop.sh: Ruft in zwei separaten Schleifen die Update-Endpunkte auf.
# Kurse und Fear & Greed werden alle 3 Sekunden abgerufen.
# News werden alle 20 Sekunden abgerufen.

BASE_URL="https://www.cryptotracker.one/backend/api"

# Schleife für News (alle 20 Sekunden)
while true; do
    curl --silent "$BASE_URL/updateNews.php" > /dev/null
    sleep 20
done
