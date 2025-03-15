<?php
// generateVapidConfig.php

// Pfad zur Konfigurationsdatei
$configFile = __DIR__ . '/vapid_config.php';

// Prüfen, ob die Konfigurationsdatei bereits existiert
if (file_exists($configFile)) {
    // Wenn sie existiert, kann sie eingebunden und die Schlüssel genutzt werden
    include $configFile;
    echo "VAPID-Schlüssel bereits vorhanden.<br>";
    echo "Public Key: " . VAPID_PUBLIC_KEY . "<br>";
    echo "Private Key: " . VAPID_PRIVATE_KEY . "<br>";
    exit;
}

// Falls die Datei nicht existiert, Schlüssel generieren
require __DIR__ . '/vendor/autoload.php';
use Minishlink\WebPush\VAPID;

// Generiere die VAPID-Schlüssel
$keys = VAPID::createVapidKeys();

// Erstelle den Inhalt der Konfigurationsdatei
$configContent = "<?php\n";
$configContent .= "define('VAPID_PUBLIC_KEY', '" . $keys['publicKey'] . "');\n";
$configContent .= "define('VAPID_PRIVATE_KEY', '" . $keys['privateKey'] . "');\n";
$configContent .= "?>\n";

// Speichere die Konfigurationsdatei
if (file_put_contents($configFile, $configContent)) {
    echo "VAPID-Schlüssel erfolgreich generiert und in vapid_config.php abgelegt.<br>";
    echo "Public Key: " . $keys['publicKey'] . "<br>";
    echo "Private Key: " . $keys['privateKey'] . "<br>";
} else {
    echo "Fehler beim Schreiben der Konfigurationsdatei.";
}
