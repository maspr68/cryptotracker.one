<?php
// /backend/api/getLatest24hPoint.php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    
    // Wähle den neuesten Datensatz der letzten 24 Stunden.
    // Das Zeitlabel wird jetzt mit Minuten (HH:mm) formatiert.
    $sql = "
      SELECT 
        DATE_FORMAT(timestamp, '%H:%i') AS time_label,
        vwap
      FROM crypto_prices
      WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
      ORDER BY timestamp DESC
      LIMIT 1
    ";
    
    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($row) {
        echo json_encode([
            'time' => $row['time_label'],
            'vwap' => $row['vwap']
        ]);
    } else {
        echo json_encode(['error' => 'Keine Daten gefunden']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
