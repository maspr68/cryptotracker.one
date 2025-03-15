<?php
/**
 * updateNews.php
 * 
 * Dieses Skript lädt alle konfigurierten News-Quellen aus der Datenbank,
 * ruft deren Inhalte per cURL ab, parst die News (RSS oder HTML) und speichert 
 * jeden News-Eintrag in der Datenbank.
 */

// Fehlerbericht-Einstellungen
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', 'error.log');

// Konfigurationsdatei einbinden
require_once '../config.php';

// --- Datenbank-Verbindung herstellen ---
try {
    $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};dbname={$dbConfig['name']};charset=utf8mb4";
    $db = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
       PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
       PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
}

// --- Hilfsfunktionen ---

// Lädt alle News-Quellen aus der Tabelle news_sources
function loadSources($db) {
    $stmt = $db->query("SELECT * FROM news_sources");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Speichert einen News-Eintrag in der Tabelle news_items
function saveNewsItem($db, $newsItem) {
    // Zuerst die Source-ID anhand des Namens ermitteln
    $stmt = $db->prepare("SELECT id FROM news_sources WHERE name = :name");
    $stmt->execute(['name' => $newsItem['source']]);
    $sourceId = $stmt->fetchColumn();
    
    if (!$sourceId) {
        error_log("Quelle nicht in der Datenbank gefunden: " . $newsItem['source']);
        return false;
    }
    
    // Einfügen des News-Eintrags; bei doppeltem Key wird nur der Zeitstempel aktualisiert
    $stmt = $db->prepare("INSERT INTO news_items 
        (title, link, source_id, language, timestamp, impact_score, is_market_moving, is_arkham, news_type)
        VALUES (:title, :link, :source_id, :language, FROM_UNIXTIME(:timestamp), :impact, :is_market_moving, :is_arkham, :news_type)
        ON DUPLICATE KEY UPDATE timestamp = FROM_UNIXTIME(:timestamp)");
    
    $newsType = isset($newsItem['type']) ? $newsItem['type'] : 'neutral';
    $isMarketMoving = (isset($newsItem['market_moving']) && $newsItem['market_moving']) ? 1 : 0;
    $isArkham = (isset($newsItem['is_arkham']) && $newsItem['is_arkham']) ? 1 : 0;
    $impact = isset($newsItem['impact']) ? $newsItem['impact'] : null;
    
    $stmt->execute([
        'title'         => $newsItem['title'],
        'link'          => $newsItem['link'],
        'source_id'     => $sourceId,
        'language'      => $newsItem['language'],
        'timestamp'     => $newsItem['timestamp'],
        'impact'        => $impact,
        'is_market_moving' => $isMarketMoving,
        'is_arkham'     => $isArkham,
        'news_type'     => $newsType
    ]);
    return $db->lastInsertId();
}

// Berechnet einen Impact-Wert für eine News basierend auf Titel und Quellenpriorität
function calculateNewsImpact($title, $source) {
    $impact = isset($source['priority']) ? $source['priority'] : 1;
    $titleLower = strtolower($title);
    // Beispielhaft einige Keywords mit Gewichtungen:
    $keywords = [
        'bitcoin' => 2,
        'btc' => 1,
        'crypto' => 1,
        'breaking' => 3,
    ];
    foreach ($keywords as $keyword => $weight) {
        if (strpos($titleLower, $keyword) !== false) {
            $impact += $weight;
        }
    }
    if (stripos($title, 'breaking') !== false) {
        $impact += 3;
    }
    if (isset($source['name']) && $source['name'] === 'Arkham Intelligence') {
        $impact += 2;
    }
    return $impact;
}

// Kürzt den Titel auf eine maximale Länge
function truncateTitle($title, $length = 80) {
    return (strlen($title) > $length) ? substr($title, 0, $length) . '...' : $title;
}

// Parst HTML-Inhalt, um News-Items zu extrahieren
function parseHtmlContent($htmlContent, $source) {
    $newsItems = [];
    $processedUrls = [];
    
    libxml_use_internal_errors(true);
    $dom = new DOMDocument();
    $dom->loadHTML($htmlContent);
    libxml_clear_errors();
    $xpath = new DOMXPath($dom);
    
    // Versuche, den Container mit einem CSS-Klassen-Selektor zu finden, der in selector_data definiert ist
    $selectorData = json_decode($source['selector_data'], true);
    $containerSelector = isset($selectorData['container']) ? $selectorData['container'] : 'article';
    // Hier nutzen wir einen einfachen XPath-Ausdruck, um Elemente mit der entsprechenden Klasse zu finden
    $items = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' " . str_replace('.', '', $containerSelector) . " ')]");
    if (!$items || $items->length === 0) {
        $items = $xpath->query("//article");
    }
    
    if ($items && $items->length > 0) {
        foreach ($items as $item) {
            // Titel extrahieren (Fallback: h2)
            $titleSelector = isset($selectorData['title']) ? $selectorData['title'] : 'h2';
            $titleNodes = $xpath->query(".//" . str_replace(', ', ' | .//', $titleSelector), $item);
            $title = $titleNodes->length > 0 ? trim($titleNodes->item(0)->textContent) : 'Kein Titel';
            
            // Link extrahieren (Fallback: a-Tag)
            $linkSelector = isset($selectorData['link']) ? $selectorData['link'] : 'a';
            $linkNodes = $xpath->query(".//" . $linkSelector, $item);
            $link = '#';
            if ($linkNodes->length > 0) {
                $href = $linkNodes->item(0)->getAttribute('href');
                if (strpos($href, 'http') !== 0) {
                    $parsedUrl = parse_url($source['url']);
                    $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                    $link = $baseUrl . (strpos($href, '/') === 0 ? '' : '/') . $href;
                } else {
                    $link = $href;
                }
            }
            if (in_array($link, $processedUrls)) {
                continue;
            }
            $processedUrls[] = $link;
            
            // Datum extrahieren
            $dateSelector = isset($selectorData['date']) ? $selectorData['date'] : 'time';
            $dateNodes = $xpath->query(".//" . str_replace(', ', ' | .//', $dateSelector), $item);
            $pubDate = $dateNodes->length > 0 ? trim($dateNodes->item(0)->textContent) : '';
            $timestamp = time();
            if (!empty($pubDate)) {
                $parsed = strtotime($pubDate);
                if ($parsed !== false) {
                    $timestamp = $parsed;
                }
            }
            
            $newsItem = [
                'title' => truncateTitle($title, 80),
                'link' => $link,
                'source' => $source['name'],
                'language' => $source['language'],
                'timestamp' => $timestamp,
                'priority' => $source['priority']
            ];
            // Falls Impact berechnet werden soll:
            $impact = calculateNewsImpact($title, $source);
            if ($impact >= 8) {
                $newsItem['market_moving'] = true;
                $newsItem['impact'] = $impact;
            }
            $newsItems[] = $newsItem;
        }
    }
    return $newsItems;
}

// Ruft News von einer Quelle per cURL ab (RSS oder HTML)
function fetchNewsFromSource($source) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $source['url']);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (compatible; CryptoNewsAggregator/1.0;)');
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    $content = curl_exec($ch);
    curl_close($ch);
    
    if (!$content) {
        return [];
    }
    
    $newsItems = [];
    if ($source['type'] === 'rss') {
        // RSS-Feed parsen
        libxml_use_internal_errors(true);
        $feed = simplexml_load_string($content);
        libxml_clear_errors();
        if ($feed && isset($feed->channel->item)) {
            foreach ($feed->channel->item as $item) {
                $title = isset($item->title) ? (string)$item->title : 'Kein Titel';
                $link = isset($item->link) ? (string)$item->link : '#';
                $pubDate = isset($item->pubDate) ? (string)$item->pubDate : '';
                $timestamp = !empty($pubDate) ? strtotime($pubDate) : time();
                
                $newsItem = [
                    'title' => truncateTitle($title, 80),
                    'link' => $link,
                    'source' => $source['name'],
                    'language' => $source['language'],
                    'timestamp' => $timestamp,
                    'priority' => $source['priority']
                ];
                $impact = calculateNewsImpact($title, $source);
                if ($impact >= 8) {
                    $newsItem['market_moving'] = true;
                    $newsItem['impact'] = $impact;
                }
                $newsItems[] = $newsItem;
            }
        }
    } else if ($source['type'] === 'html') {
        // HTML-Inhalt parsen
        $newsItems = parseHtmlContent($content, $source);
    }
    return $newsItems;
}

// --- Hauptablauf ---

$sources = loadSources($db);
$totalSaved = 0;

foreach ($sources as $source) {
    // Für jede Quelle werden die News abgerufen
    $newsFromSource = fetchNewsFromSource($source);
    
    // Jeden gefundenen News-Eintrag in die Datenbank speichern
    foreach ($newsFromSource as $newsItem) {
        $result = saveNewsItem($db, $newsItem);
        if ($result !== false) {
            $totalSaved++;
        }
    }
}

echo "Es wurden insgesamt {$totalSaved} Nachrichten abgerufen und in der DB gespeichert.\n";
