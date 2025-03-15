<?php
// /backend/api/updateCryptoPrices.php
//
// Dieses Skript ruft aktuelle BTC-Preise (von Binance, Kraken und Coinbase) ab,
// berechnet den VWAP und speichert die Daten in der Tabelle crypto_prices.
// Es gibt keinen eigenen Loop – das Skript wird bei jedem Aufruf einmal ausgeführt.

require_once '../config.php';
require_once '../functions.php';

/**
 * Dieses Skript ruft aktuelle BTC-Preise ab, berechnet den VWAP
 * und speichert die Daten in der Tabelle crypto_prices.
 */

// HTTP-Kontext für file_get_contents
$ctx = stream_context_create([
    'http' => [
        'timeout' => 5,
        'user_agent' => 'Mozilla/5.0 (compatible; BTCTracker/1.0;)'
    ]
]);

function fetchAllPrices() {
    global $ctx;
    $prices = [];
    $volumes = [];
    
    // Binance
    try {
        $response = file_get_contents('https://api.binance.com/api/v3/ticker/24hr?symbol=BTCUSDT', false, $ctx);
        if ($response === false) throw new Exception('Binance API nicht erreichbar');
        $data = json_decode($response, true);
        $prices['binance'] = floatval($data['lastPrice']);
        $volumes['binance'] = floatval($data['volume']);
    } catch (Exception $e) {
        $prices['binance'] = null;
        $volumes['binance'] = 0;
    }
    
    // Kraken
    try {
        $response = file_get_contents('https://api.kraken.com/0/public/Ticker?pair=XBTUSD', false, $ctx);
        if ($response === false) throw new Exception('Kraken API nicht erreichbar');
        $data = json_decode($response, true);
        $prices['kraken'] = floatval($data['result']['XXBTZUSD']['c'][0]);
        $volumes['kraken'] = floatval($data['result']['XXBTZUSD']['v'][1]);
    } catch (Exception $e) {
        $prices['kraken'] = null;
        $volumes['kraken'] = 0;
    }
    
    // Coinbase
    try {
        $response = file_get_contents('https://api.coinbase.com/v2/exchange-rates?currency=BTC', false, $ctx);
        if ($response === false) throw new Exception('Coinbase API nicht erreichbar');
        $data = json_decode($response, true);
        $prices['coinbase'] = floatval($data['data']['rates']['USD']);
        
        $volumeResponse = file_get_contents('https://api.exchange.coinbase.com/products/BTC-USD/stats', false, $ctx);
        if ($volumeResponse === false) throw new Exception('Coinbase Volume API nicht erreichbar');
        $volumeData = json_decode($volumeResponse, true);
        $volumes['coinbase'] = floatval($volumeData['volume']);
    } catch (Exception $e) {
        $prices['coinbase'] = null;
        $volumes['coinbase'] = 0;
    }
    
    // Berechnung des VWAP
    $totalVolume = array_sum($volumes);
    $vwap = 0;
    if ($totalVolume > 0) {
        $weightedSum = 0;
        foreach ($prices as $exchange => $price) {
            if ($price !== null && $volumes[$exchange] > 0) {
                $weightedSum += $price * $volumes[$exchange];
            }
        }
        $vwap = $weightedSum / $totalVolume;
    }
    
    return [
        'prices'  => $prices,
        'volumes' => $volumes,
        'vwap'    => $vwap
    ];
}

try {
    $priceData = fetchAllPrices();
    
    if (!empty($priceData['prices'])) {
        // Daten für die DB-Zeile zusammenstellen
        $now = new DateTime();
        $newDataPoint = [
            'date'    => $now->format('d.m.Y'),
            'time'    => $now->format('H:i:s'),
            'prices'  => $priceData['prices'],
            'volumes' => $priceData['volumes'],
            'vwap'    => $priceData['vwap']
        ];
        
        // Aktualisierung in der Datenbank (updateCryptoPrices() aus functions.php)
        $id = updateCryptoPrices($newDataPoint);
        echo "Kursdaten aktualisiert (ID: $id) am " . $now->format('Y-m-d H:i:s') . "\n";
    } else {
        echo "Fehler: Keine Daten von den APIs abrufbar.\n";
    }
} catch (Exception $e) {
    echo "Fehler bei updateCryptoPrices.php: " . $e->getMessage() . "\n";
}
?>
