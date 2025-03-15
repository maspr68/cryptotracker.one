<?php
// /backend/functions.php

require_once 'config.php';

/**
 * Stellt eine PDO-Datenbankverbindung her.
 *
 * @return PDO
 */
function getDatabaseConnection() {
    global $dbConfig;
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    
    try {
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    } catch (PDOException $e) {
        die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
    }
    
    return $pdo;
}

/**
 * Ruft den neuesten Datensatz aus der Tabelle crypto_prices ab.
 *
 * @return array|false Assoziatives Array mit den Kursdaten oder false, falls keine Daten gefunden wurden.
 */
function getLatestCryptoPrices() {
    $pdo = getDatabaseConnection();
    // Sortiert nach der höchsten ID
    $sql = "SELECT * FROM crypto_prices ORDER BY id DESC LIMIT 1";
    $stmt = $pdo->query($sql);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

/**
 * Ruft die neuesten News-Einträge aus der Tabelle news_items ab.
 * Dabei wird ein Join mit der Tabelle news_sources durchgeführt, sodass der Quellenname als "source_name" geliefert wird.
 *
 * @param int $limit Anzahl der abzurufenden News-Einträge (Standard: 10).
 * @return array Array mit den News-Datensätzen.
 */
function getLatestNews($limit = 20) {
    $pdo = getDatabaseConnection();
    $sql = "SELECT n.*, s.name as source_name 
            FROM news_items n 
            JOIN news_sources s ON n.source_id = s.id 
            ORDER BY n.timestamp DESC 
            LIMIT :limit";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Fügt eine neue Newsquelle in die Tabelle news_sources ein.
 *
 * @param string $name Name der Newsquelle.
 * @param string $type Typ der Newsquelle (z.B. RSS, API).
 * @param string $url URL der Newsquelle.
 * @param string $language Sprache der Newsquelle (z.B. 'de' oder 'en').
 * @param int $priority Priorität der Newsquelle.
 * @param string|null $selector_data JSON-Daten für Selektoren, falls erforderlich.
 * @return int ID der neu eingefügten Newsquelle.
 */
function addNewsSource($name, $type, $url, $language, $priority, $selector_data = null) {
    $pdo = getDatabaseConnection();
    $sql = "INSERT INTO news_sources (name, type, url, language, priority, selector_data)
            VALUES (:name, :type, :url, :language, :priority, :selector_data)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':name' => $name,
        ':type' => $type,
        ':url' => $url,
        ':language' => $language,
        ':priority' => $priority,
        ':selector_data' => $selector_data,
    ]);
    return $pdo->lastInsertId();
}

/**
 * Fügt einen neuen Datensatz in der Tabelle crypto_prices ein.
 * Diese Funktion wird von updateCryptoPrices.php genutzt.
 *
 * Erwartet ein Array $data mit folgenden Schlüsseln:
 * - date: Datum im Format 'd.m.Y'
 * - time: Uhrzeit im Format 'H:i:s'
 * - prices: Array mit den Preisen (binance, kraken, coinbase)
 * - volumes: Array mit den Volumina (binance, kraken, coinbase)
 * - vwap: Berechneter VWAP
 * - fear_and_greed_index: Der aktuell abgerufene Fear & Greed Index (Integer oder null)
 *
 * @param array $data
 * @return int ID des eingefügten Datensatzes.
 */
function updateCryptoPrices($data) {
    $pdo = getDatabaseConnection();
    // Achtung: Das Feld fear_and_greed_index wurde entfernt
    $sql = "INSERT INTO crypto_prices 
            (date_recorded, time_recorded, vwap, binance_price, kraken_price, coinbase_price, 
             binance_volume, kraken_volume, coinbase_volume)
            VALUES 
            (STR_TO_DATE(:date, '%d.%m.%Y'), :time, :vwap, :binance_price, :kraken_price, :coinbase_price, 
             :binance_volume, :kraken_volume, :coinbase_volume)";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        ':date' => $data['date'],
        ':time' => $data['time'],
        ':vwap' => $data['vwap'],
        ':binance_price' => $data['prices']['binance'] ?? null,
        ':kraken_price' => $data['prices']['kraken'] ?? null,
        ':coinbase_price' => $data['prices']['coinbase'] ?? null,
        ':binance_volume' => $data['volumes']['binance'] ?? 0,
        ':kraken_volume' => $data['volumes']['kraken'] ?? 0,
        ':coinbase_volume' => $data['volumes']['coinbase'] ?? 0
    ]);
    return $pdo->lastInsertId();
}


/**
 * Fügt einen neuen Datensatz in der Tabelle fear_and_greed ein.
 *
 * Erwartet ein Array $data mit folgenden Schlüsseln:
 * - date: Datum im Format 'Y-m-d' (z. B. '2025-03-02')
 * - time: Uhrzeit im Format 'H:i:s' (z. B. '12:00:00')
 * - value: Der aktuelle Fear & Greed Index (Integer)
 *
 * @param array $data
 * @return int ID des eingefügten Datensatzes.
 */
function updateFearAndGreed($data) {
    // Debug-Ausgabe, damit du im Browser (oder cURL) siehst, was gespeichert wird.
    // Du kannst hier bei Bedarf weitere Infos ausgeben.
    echo "Speichere neuen Fear & Greed-Wert:\n";
    echo "Date: " . $data['date'] . "\n";
    echo "Time: " . $data['time'] . "\n";
    echo "Value: " . $data['value'] . "\n\n";

    $pdo = getDatabaseConnection();
    $sql = "INSERT INTO fear_and_greed (date_recorded, time_recorded, value)
            VALUES (:date, :time, :value)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
       ':date'  => $data['date'],
       ':time'  => $data['time'],
       ':value' => $data['value']
    ]);
    $newId = $pdo->lastInsertId();

    echo "Datensatz gespeichert mit ID: $newId\n";
    return $newId;
}

?>
