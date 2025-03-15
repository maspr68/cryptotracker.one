<?php
/**
 * get24hVolumeData.php
 * 
 * Gibt für die letzten 24 Stunden (in 15-Minuten-Intervallen) fiktive Volumendaten für
 * Binance, Coinbase und Kraken zurück. Struktur:
 * {
 *   "times": ["00:00", "00:15", ...],
 *   "binanceVolumes": [Wert, Wert, ...],
 *   "coinbaseVolumes": [...],
 *   "krakenVolumes": [...]
 * }
 */

// Damit der Browser weiß, dass JSON gesendet wird
header('Content-Type: application/json; charset=utf-8');

// Normalerweise würdest du hier echte Daten aus deiner DB oder einer API holen.
// Dieses Beispiel generiert nur zufällige Werte, damit man das Prinzip versteht.

// Array für die Zeitlabels
$times = [];
// Arrays für die Volumenwerte
$binanceVolumes = [];
$coinbaseVolumes = [];
$krakenVolumes = [];

// Aktueller Zeitstempel
$currentTimestamp = time();
// 24 Stunden zurück
$oneDayAgo = $currentTimestamp - 86400; // 86400 Sekunden = 24 Stunden
// Intervall: 15 Minuten (in Sekunden)
$interval = 15 * 60; // 900 Sekunden

// Schleife von vor 24h bis jetzt in 15-Minuten-Schritten
for ($ts = $oneDayAgo; $ts <= $currentTimestamp; $ts += $interval) {
    // Format HH:MM für das Zeitlabel
    $times[] = date("H:i", $ts);

    // Beispielhafte zufällige Werte:
    // Ersetze diese Logik durch echte DB- oder API-Daten
    $binanceVolumes[]  = rand(1000, 5000);
    $coinbaseVolumes[] = rand(500, 3000);
    $krakenVolumes[]   = rand(300, 2000);
}

// Jetzt alles als JSON zurückgeben
echo json_encode([
    'times'            => $times,
    'binanceVolumes'   => $binanceVolumes,
    'coinbaseVolumes'  => $coinbaseVolumes,
    'krakenVolumes'    => $krakenVolumes
]);

exit; // Skript beenden