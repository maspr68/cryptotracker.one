<?php
// /backend/api/updatefearandgreed.php

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

// URL der API, die den Fear & Greed Index liefert
$apiUrl = 'https://api.alternative.me/fng/?limit=1&format=json';

// API-Aufruf durchführen
$response = @file_get_contents($apiUrl);
if ($response === false) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Abrufen des Fear & Greed Index von der API']);
    exit;
}

// JSON-Daten decodieren
$data = json_decode($response, true);
if (!isset($data['data'][0]['value'])) {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Parsen des Fear & Greed Index']);
    exit;
}

$fearAndGreed = intval($data['data'][0]['value']);

// Zeitzone einstellen und aktuellen Zeitpunkt ermitteln
date_default_timezone_set('Europe/Berlin');
$now = new DateTime();

// Neuen Datenpunkt erstellen: Wir speichern das Datum, die Uhrzeit und den Fear & Greed Index
$newDataPoint = [
    'date'  => $now->format('Y-m-d'),
    'time'  => $now->format('H:i:s'),
    'value' => $fearAndGreed
];

// Den neuen Datenpunkt in die Tabelle "fear_and_greed" speichern
$id = updateFearAndGreed($newDataPoint);

if ($id) {
    // Wir geben zusätzlich 'date_recorded' und 'time_recorded' zurück,
    // damit das Frontend sie anzeigen kann.
    echo json_encode([
        'success'             => true,
        'id'                  => $id,
        'fear_and_greed_index'=> $fearAndGreed,
        'date_recorded'       => $newDataPoint['date'],
        'time_recorded'       => $newDataPoint['time'],
        'message'             => "Fear & Greed Index aktualisiert am " . $now->format('Y-m-d H:i:s')
    ]);
} else {
    http_response_code(500);
    echo json_encode(['error' => 'Fehler beim Speichern des Fear & Greed Index in der Datenbank']);
}
