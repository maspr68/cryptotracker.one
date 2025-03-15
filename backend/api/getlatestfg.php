<?php
// /backend/api/getlatestfg.php

require_once '../functions.php';
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    // Abfrage nur des Fear & Greed Index, sortiert nach der neuesten ID
    $stmt = $pdo->query("SELECT fear_and_greed_index FROM crypto_prices ORDER BY id DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data && isset($data['fear_and_greed_index'])) {
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Kein Fear & Greed Index gefunden']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
<?php
// /backend/api/getlatestfg.php

require_once '../functions.php';
header('Content-Type: application/json');

try {
    $pdo = getDatabaseConnection();
    // Abfrage nur des Fear & Greed Index, sortiert nach der neuesten ID
    $stmt = $pdo->query("SELECT fear_and_greed_index FROM crypto_prices ORDER BY id DESC LIMIT 1");
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($data && isset($data['fear_and_greed_index'])) {
        echo json_encode($data);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Kein Fear & Greed Index gefunden']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
