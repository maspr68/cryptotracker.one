<?php
// /backend/api/getlatestfearandgreed.php

require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    // Sortiert nach ID (oder Timestamp), um den neuesten Datensatz zu bekommen
    $stmt = $pdo->query("SELECT * FROM fear_and_greed ORDER BY id DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($data) {
        // Beispiel: Du könntest nur 'value' zurückgeben oder das ganze Array
        echo json_encode([
            'fear_and_greed_index' => $data['value'] ?? null,
            'date_recorded'        => $data['date_recorded'],
            'time_recorded'        => $data['time_recorded']
        ]);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Keine Fear & Greed Daten gefunden']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
