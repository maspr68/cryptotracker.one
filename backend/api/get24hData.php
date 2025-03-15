<?php
// /backend/api/get24hData.php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    // Gruppiere die Daten in 1-Minuten-Blöcken (Format "HH:mm") der letzten 24 Stunden.
    // Wir verwenden MIN(timestamp) für die Sortierung, sodass die Daten chronologisch aufsteigen.
    $sql = "
      SELECT 
        DATE_FORMAT(timestamp, '%H:%i') AS time_label,
        AVG(vwap) AS vwap
      FROM crypto_prices
      WHERE timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
      GROUP BY DATE_FORMAT(timestamp, '%H:%i')
      ORDER BY MIN(timestamp) ASC
    ";
    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $times = [];
    $vwapData = [];
    foreach ($rows as $row) {
        $times[] = $row['time_label'];
        $vwapData[] = (float)$row['vwap'];
    }

    echo json_encode([
        'times' => $times,
        'vwapData' => $vwapData
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
