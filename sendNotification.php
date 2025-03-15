<?php
// sendNotification.php

// Autoload der Composer-Bibliotheken
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Laden der Abonnement-Daten (als JSON) aus einer Datei oder Datenbank
// Hier wird ein statisches Beispiel aus der Datei "subscription.json" verwendet
$subscriptionJson = file_get_contents('subscription.json');
$subscriptionData = json_decode($subscriptionJson, true);
if (!$subscriptionData) {
    die("Fehler: Kein gültiges Subscription-Objekt gefunden.");
}
$subscription = Subscription::create($subscriptionData);

// VAPID-Konfiguration (ersetze die Platzhalter durch deine echten Schlüssel)
$auth = [
    'VAPID' => [
        'subject' => 'mailto:dein-email@example.com',
        'publicKey' => 'DEIN_PUBLIC_KEY_HIER',     // Ersetze diesen Platzhalter
        'privateKey' => 'DEIN_PRIVATE_KEY_HIER'      // Ersetze diesen Platzhalter
    ],
];

$webPush = new WebPush($auth);

// Definiere die Nachricht, die versendet werden soll
$message = "Hallo! Das ist eine Push-Benachrichtigung aus dem Cryptotracker.";

// Sende die Benachrichtigung an das Abonnement
$webPush->sendNotification(
    $subscription,
    $message
);

// Flush alle ausstehenden Benachrichtigungen und verarbeite die Ergebnisse
$reports = $webPush->flush();

foreach ($reports as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();
    if ($report->isSuccess()) {
        echo "Benachrichtigung an $endpoint erfolgreich gesendet.<br>";
    } else {
        echo "Fehler beim Senden an $endpoint: " . $report->getReason() . "<br>";
    }
}
