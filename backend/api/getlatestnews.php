<?php
// /backend/api/getlatestnews.php

require_once '../functions.php';
header('Content-Type: application/json');

try {
    $news = getLatestNews(20);
    if ($news) {
        echo json_encode($news);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Keine News gefunden']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
?>
