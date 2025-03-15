<?php
// sendTestNotification.php

// VAPID-Konfiguration einbinden – diese Datei muss existieren
$configFile = __DIR__ . '/vapid_config.php';
if (!file_exists($configFile)) {
    die('VAPID-Konfigurationsdatei nicht gefunden.');
}
require $configFile;

// Composer-Autoloader einbinden
require __DIR__ . '/vendor/autoload.php';

use Minishlink\WebPush\WebPush;
use Minishlink\WebPush\Subscription;

// Lade alle gespeicherten Abonnements aus subscriptions.json
$subscriptionsFile = __DIR__ . '/subscriptions.json';
if (!file_exists($subscriptionsFile)) {
    die('Keine Abonnements gefunden.');
}
$subscriptions = json_decode(file_get_contents($subscriptionsFile), true);
if (!$subscriptions) {
    die('Fehler beim Laden der Abonnements.');
}

// VAPID-Konfiguration aus vapid_config.php
$auth = [
    'VAPID' => [
        'subject'    => 'mailto:dein-email@example.com',
        'publicKey'  => VAPID_PUBLIC_KEY,
        'privateKey' => VAPID_PRIVATE_KEY,
    ],
];

$webPush = new WebPush($auth);

// Nachricht, die gesendet werden soll
$message = "Hallo Welt";

// Statt sendNotification() nutzen wir jetzt queueNotification()
foreach ($subscriptions as $subData) {
    $subscription = Subscription::create($subData);
    $webPush->queueNotification($subscription, $message);
}

// Sende alle Benachrichtigungen und verarbeite die Ergebnisse
$reports = $webPush->flush();
foreach ($reports as $report) {
    $endpoint = $report->getRequest()->getUri()->__toString();
    if ($report->isSuccess()) {
        echo "Benachrichtigung an $endpoint erfolgreich gesendet.<br>";
    } else {
        echo "Fehler beim Senden an $endpoint: " . $report->getReason() . "<br>";
    }
}
?>
