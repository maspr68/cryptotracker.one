<?php
// /backend/api/getlatestcryptoprices.php
//
// Dieser Endpunkt gibt den neuesten Datensatz aus der Tabelle crypto_prices
// als JSON zurück. Es werden die Felder für VWAP, Preise und Volumina der Börsen zurückgegeben.

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

$data = getLatestCryptoPrices();

if ($data) {
    // Erstelle ein Response-Array, das alle wichtigen Felder als Zahlen enthält
    $response = [
        'vwap'              => isset($data['vwap']) ? floatval($data['vwap']) : null,
        'binance_price'     => isset($data['binance_price']) ? floatval($data['binance_price']) : null,
        'kraken_price'      => isset($data['kraken_price']) ? floatval($data['kraken_price']) : null,
        'coinbase_price'    => isset($data['coinbase_price']) ? floatval($data['coinbase_price']) : null,
        'binance_volume'    => isset($data['binance_volume']) ? floatval($data['binance_volume']) : 0,
        'kraken_volume'     => isset($data['kraken_volume']) ? floatval($data['kraken_volume']) : 0,
        'coinbase_volume'   => isset($data['coinbase_volume']) ? floatval($data['coinbase_volume']) : 0,
        'date_recorded'     => $data['date_recorded'] ?? null,
        'time_recorded'     => $data['time_recorded'] ?? null
    ];
    
    echo json_encode($response);
} else {
    http_response_code(404);
    echo json_encode(['error' => 'Keine Kursdaten gefunden']);
}
?>
