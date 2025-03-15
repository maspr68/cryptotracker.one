#!/bin/bash
# updateFGp.sh: Ruft in einer Schleife die Update-Endpunkte auf.
# Fear & Greed werden alle 3 Sekunden abgerufen.


BASE_URL="https://www.cryptotracker.one/backend/api"

# Schleife für FG (alle 3 Sekunden)
while true; do
    curl --silent "$BASE_URL/updateFG.php" > /dev/null
    sleep 3
done
