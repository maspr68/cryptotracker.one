<?php
// saveSubscription.php

// Lese den rohen POST-Body aus
$rawData = file_get_contents('php://input');
if (!$rawData) {
    http_response_code(400);
    echo 'Keine Daten empfangen.';
    exit;
}

// JSON-Daten in ein Array umwandeln
$subscription = json_decode($rawData, true);
if (!$subscription) {
    http_response_code(400);
    echo 'Ungültige JSON-Daten.';
    exit;
}

// Beispiel: Speichere das Abonnement in einer Datei "subscriptions.json"
$file = __DIR__ . '/subscriptions.json';
$subscriptions = [];
if (file_exists($file)) {
    $subscriptions = json_decode(file_get_contents($file), true) ?: [];
}

// Prüfe, ob das Abonnement bereits existiert
foreach ($subscriptions as $sub) {
    if ($sub['endpoint'] === $subscription['endpoint']) {
        echo 'Abonnement bereits vorhanden.';
        exit;
    }
}

$subscriptions[] = $subscription;
file_put_contents($file, json_encode($subscriptions));
echo 'Abonnement gespeichert.';
?>
