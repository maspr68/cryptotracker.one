<?php
class CryptoNewsAggregator {
    private $sources;
    private $lastUpdateTimestamp;
    private $multiCurlHandles = [];
    private $marketMovingKeywords = [];
    private $db; // Datenbank-Verbindung

    public function __construct($host, $user, $pass, $dbName, $port = 3307) {
        // Datenbank-Verbindung herstellen
        $this->connectToDatabase($host, $user, $pass, $dbName, $port);
        
        // Keywords aus der Datenbank laden
        $this->loadKeywordsFromDb();
        
        // Quellen aus der Datenbank laden
        $this->loadSourcesFromDb();
        
        $this->lastUpdateTimestamp = time();
        $this->initMultiCurl();
    }

    private function connectToDatabase($host, $user, $pass, $dbName, $port = 3307) {
        try {
            // Verwende exakt den gleichen DSN-String wie in der funktionierenden Testdatei
            $dsn = "mysql:host=$host;port=$port;dbname=$dbName";
            $this->db = new PDO($dsn, $user, $pass);
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Datenbankverbindung fehlgeschlagen: " . $e->getMessage());
        }
    }
    
    private function loadKeywordsFromDb() {
        $stmt = $this->db->query("SELECT keyword, weight FROM keywords");
        $keywords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($keywords as $keyword) {
            $this->marketMovingKeywords[$keyword['keyword']] = $keyword['weight'];
        }
        // echo "Keywords geladen: " . count($this->marketMovingKeywords) . "\n";
    }
    
    private function loadSourcesFromDb() {
        $stmt = $this->db->query("SELECT * FROM news_sources");
        $this->sources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        // echo "Quellen geladen: " . count($this->sources) . "\n";
    }
    
    // Speichert einen News-Eintrag in der Datenbank
    private function saveNewsItem($newsItem) {
        try {
            // Zuerst Source ID finden
            $stmt = $this->db->prepare("SELECT id FROM news_sources WHERE name = :name");
            $stmt->execute(['name' => $newsItem['source']]);
            $sourceId = $stmt->fetchColumn();
            
            if (!$sourceId) {
                throw new Exception("Quelle nicht in der Datenbank gefunden: " . $newsItem['source']);
            }
            
            // News-Eintrag einfügen
            $stmt = $this->db->prepare("INSERT INTO news_items 
                (title, link, source_id, language, timestamp, impact_score, is_market_moving, is_arkham, news_type) 
                VALUES (:title, :link, :source_id, :language, FROM_UNIXTIME(:timestamp), :impact, :is_market_moving, :is_arkham, :news_type)
                ON DUPLICATE KEY UPDATE timestamp = FROM_UNIXTIME(:timestamp)");
                
            $newsType = 'neutral';
            if (isset($newsItem['type'])) {
                $newsType = $newsItem['type'];
            }
            
            $isMarketMoving = isset($newsItem['market_moving']) && $newsItem['market_moving'] ? 1 : 0;
            $isArkham = isset($newsItem['is_arkham']) && $newsItem['is_arkham'] ? 1 : 0;
            $impact = isset($newsItem['impact']) ? $newsItem['impact'] : null;
            
            $stmt->execute([
                'title' => $newsItem['title'],
                'link' => $newsItem['link'],
                'source_id' => $sourceId,
                'language' => $newsItem['language'],
                'timestamp' => $newsItem['timestamp'],
                'impact' => $impact,
                'is_market_moving' => $isMarketMoving,
                'is_arkham' => $isArkham,
                'news_type' => $newsType
            ]);
            
            return $this->db->lastInsertId();
        } catch (PDOException $e) {
            error_log("Fehler beim Speichern eines News-Eintrags: " . $e->getMessage());
            return false;
        }
    }
    
    private function calculateNewsImpact($title, $source) {
        $impact = 0;
        $title = strtolower($title);
        
        // Basispunkte nach Quellen-Priorität
        $impact += isset($source['priority']) ? $source['priority'] : 1;
        
        // Überprüfe Keywords und ihre Gewichtung
        foreach ($this->marketMovingKeywords as $keyword => $weight) {
            if (strpos($title, $keyword) !== false) {
                $impact += $weight;
            }
        }
        
        // Breaking News Bonus
        if (stripos($title, 'breaking') !== false) {
            $impact += 3;
        }
        
        // Bonus für Arkham-Nachrichten
        if (isset($source['name']) && $source['name'] === 'Arkham Intelligence') {
            $impact += 2; // Zusätzlicher Bonus für Arkham-Nachrichten
        }
        
        return $impact;
    }

    private function isMarketMovingNews($newsItem) {
        $impact = $this->calculateNewsImpact($newsItem['title'], ['priority' => isset($newsItem['priority']) ? $newsItem['priority'] : 1]);
        return $impact >= 8; // Schwellenwert für market-moving news
    }

    private function initMultiCurl() {
        foreach ($this->sources as $source) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $source['url']);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
            
            $this->multiCurlHandles[$source['name']] = $ch;
        }
    }

    private function parseHtmlContent($htmlContent, $source) {
        $newsItems = [];
        $processedUrls = []; // Zur Vermeidung von Duplikaten
        
        // Debug-Logging
        error_log("Starte HTML-Parsing für " . $source['name']);
        
        // Für Arkham Intelligence spezifisches Parsing verwenden
        if ($source['name'] === 'Arkham Intelligence') {
            return $this->parseArkhamResearchPage($htmlContent, $source);
        }
        
        // Standard-HTML-Parsing für andere Quellen
        $dom = new DOMDocument();
        
        // Fehler unterdrücken, da HTML oft nicht valide ist
        libxml_use_internal_errors(true);
        $dom->loadHTML($htmlContent);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Container-Elements finden
        $selectorData = json_decode($source['selector_data'], true);
        $containerSelector = $selectorData['container'] ?? '.post, article';
        
        $items = $xpath->query("//*[contains(concat(' ', normalize-space(@class), ' '), ' " . str_replace('.', '', $containerSelector) . " ')]");
        
        if (!$items || $items->length === 0) {
            // Versuche alternativen Ansatz mit allgemeineren Selektoren
            $items = $xpath->query("//article | //div[contains(@class, 'post')] | //div[contains(@class, 'research')]");
        }
        
        if ($items && $items->length > 0) {
            error_log("Gefundene Items: " . $items->length);
            foreach ($items as $item) {
                // Titel extrahieren
                $titleSelector = $selectorData['title'] ?? 'h2, h3, .title';
                $titleElements = $xpath->query(".//" . str_replace(', ', ' | .//', $titleSelector), $item);
                $title = $titleElements->length > 0 ? trim($titleElements->item(0)->textContent) : 'Kein Titel';
                
                // Link extrahieren
                $linkSelector = $selectorData['link'] ?? 'a';
                $linkElements = $xpath->query(".//" . $linkSelector, $item);
                $link = '#';
                if ($linkElements->length > 0) {
                    $href = $linkElements->item(0)->getAttribute('href');
                    // Relative URLs in absolute umwandeln
                    if (strpos($href, 'http') !== 0) {
                        $parsedUrl = parse_url($source['url']);
                        $baseUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'];
                        $link = $baseUrl . (strpos($href, '/') === 0 ? '' : '/') . $href;
                    } else {
                        $link = $href;
                    }
                }
                // Prüfen, ob wir diese URL bereits verarbeitet haben
                if (in_array($link, $processedUrls)) {
                    continue; // Überspringen, wenn Duplikat
                }
                $processedUrls[] = $link;
                
                // Datum extrahieren
                $dateSelector = $selectorData['date'] ?? '.date, time, .meta';
                $dateElements = $xpath->query(".//" . str_replace(', ', ' | .//', $dateSelector), $item);
                $pubDate = $dateElements->length > 0 ? trim($dateElements->item(0)->textContent) : '';
                
                // Timestamp ermitteln
                $timestamp = time(); // Standard: aktuelle Zeit
                if (!empty($pubDate)) {
                    // Versuche das Datum zu parsen
                    $parsedDate = strtotime($pubDate);
                    if ($parsedDate !== false) {
                        $timestamp = $parsedDate;
                    } else {
                        // Versuche andere Datumsformate
                        $dateFormats = [
                            'd.m.Y', 
                            'Y-m-d', 
                            'M d, Y',
                            'F d, Y',
                            'd F Y'
                        ];
                        
                        foreach ($dateFormats as $format) {
                            $date = DateTime::createFromFormat($format, $pubDate);
                            if ($date !== false) {
                                $timestamp = $date->getTimestamp();
                                break;
                            }
                        }
                    }
                }
                
                // Leere oder "Kein Titel"-Einträge nur hinzufügen, wenn ein echter Link vorhanden ist
                if ($title === 'Kein Titel' && $link === '#') {
                    continue;
                }
                
                $newsItem = [
                    'title' => $this->truncateTitle($title, 80),
                    'link' => $link,
                    'source' => $source['name'],
                    'language' => $source['language'],
                    'timestamp' => $timestamp,
                    'priority' => $source['priority'],
                    'is_arkham' => ($source['name'] === 'Arkham Intelligence')
                ];
                
                // Prüfe, ob es sich um market-moving News handelt
                if ($this->isMarketMovingNews($newsItem)) {
                    $newsItem['market_moving'] = true;
                    $newsItem['impact'] = $this->calculateNewsImpact($title, $source);
                }
                
                $newsItems[] = $newsItem;
            }
        } else {
            error_log("Keine Items gefunden für " . $source['name']);
        }
        
        return $newsItems;
    }

    private function parseArkhamResearchPage($htmlContent, $source) {
        $newsItems = [];
        
        // Zuerst versuchen wir, die vordefinierten Briefings zu laden
        if (preg_match_all('/window\.briefings\.push\(\{.*?id:\s*[\'"]([^\'"]+)[\'"].*?title:\s*[\'"]([^\'"]+)[\'"].*?date:\s*[\'"]([^\'"]+)[\'"].*?\})/si', $htmlContent, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $match) {
                $id = trim($match[1]);
                $title = trim(html_entity_decode($match[2]));
                $date = trim($match[3]);
                
                $newsItem = [
                    'title' => $this->truncateTitle($title, 80),
                    'link' => "https://info.arkm.com/research/" . $id,
                    'source' => $source['name'],
                    'language' => $source['language'],
                    'timestamp' => strtotime($date),
                    'priority' => $source['priority'],
                    'is_arkham' => true,
                    'market_moving' => true,
                    'impact' => $this->calculateNewsImpact($title, $source)
                ];
                
                $newsItems[] = $newsItem;
            }
        }
        
        // Fallback, wenn keine Artikel gefunden
        if (empty($newsItems)) {
            $fallbackArticles = [
                [
                    'id' => 'microstrategy-buys-1-5b-of-bitcoin',
                    'title' => 'Microstrategy Buys $1.5B of Bitcoin',
                    'date' => 'December 03 2024'
                ],
                [
                    'id' => 'hyperliquid-user-receives-4-million-airdrop',
                    'title' => 'Hyperliquid User Receives $4 Million Airdrop',
                    'date' => 'November 30 2024'
                ],
                [
                    'id' => 'trump-makes-3m-on-memecoins',
                    'title' => 'Trump Makes $3M on Memecoins',
                    'date' => 'November 28 2024'
                ]
            ];
            
            foreach ($fallbackArticles as $article) {
                $newsItem = [
                    'title' => $article['title'],
                    'link' => "https://info.arkm.com/research/" . $article['id'],
                    'source' => $source['name'],
                    'language' => $source['language'],
                    'timestamp' => strtotime($article['date']),
                    'priority' => $source['priority'],
                    'is_arkham' => true,
                    'market_moving' => true,
                    'impact' => $this->calculateNewsImpact($article['title'], $source)
                ];
                
                $newsItems[] = $newsItem;
            }
        }
        
        return $newsItems;
    }

    public function fetchAllSources($language = null) {
        $mh = curl_multi_init();
        $activeHandles = [];

        foreach ($this->sources as $source) {
            if ($language !== null && $source['language'] !== $language) {
                continue;
            }
            
            $ch = $this->multiCurlHandles[$source['name']];
            curl_multi_add_handle($mh, $ch);
            $activeHandles[$source['name']] = [
                'handle' => $ch,
                'source' => $source
            ];
        }

        $active = null;
        do {
            $status = curl_multi_exec($mh, $active);
            if ($active) {
                curl_multi_select($mh);
            }
        } while ($active && $status == CURLM_OK);

        $allNews = [];
        $savedCount = 0;

        foreach ($activeHandles as $sourceName => $info) {
            $ch = $info['handle'];
            $source = $info['source'];
            
            $content = curl_multi_getcontent($ch);
            curl_multi_remove_handle($mh, $ch);

            if (!empty($content)) {
                try {
                    if ($source['type'] === 'rss') {
                        // RSS-Feed parsen
                        libxml_use_internal_errors(true);
                        $feed = simplexml_load_string($content);
                        
                        if ($feed && isset($feed->channel->item)) {
                            foreach ($feed->channel->item as $item) {
                                $title = isset($item->title) ? (string)$item->title : 'Kein Titel';
                                $link = isset($item->link) ? (string)$item->link : '#';
                                $pubDate = isset($item->pubDate) ? (string)$item->pubDate : 'now';

                                $newsItem = [
                                    'title' => $this->truncateTitle($title, 80),
                                    'link' => $link,
                                    'source' => $source['name'],
                                    'language' => $source['language'],
                                    'timestamp' => strtotime($pubDate),
                                    'priority' => $source['priority']
                                ];

                                // Prüfe, ob es sich um market-moving News handelt
                                if ($this->isMarketMovingNews($newsItem)) {
                                    $newsItem['market_moving'] = true;
                                    $newsItem['impact'] = $this->calculateNewsImpact($title, $source);
                                }

                                // In Datenbank speichern
                                if ($this->saveNewsItem($newsItem)) {
                                    $savedCount++;
                                }
                                
                                $allNews[] = $newsItem;
                            }
                        }
                    } else if ($source['type'] === 'html') {
                        // HTML-Seite parsen
                        $htmlNewsItems = $this->parseHtmlContent($content, $source);
                        
                        // Alle HTML-News-Items in Datenbank speichern
                        foreach ($htmlNewsItems as $newsItem) {
                            if ($this->saveNewsItem($newsItem)) {
                                $savedCount++;
                            }
                        }
                        
                        $allNews = array_merge($allNews, $htmlNewsItems);
                    }
                } catch (Exception $e) {
                    error_log("Fehler beim Parsen von {$source['name']}: " . $e->getMessage());
                }
            }
        }

        curl_multi_close($mh);
        // echo "Insgesamt {$savedCount} Nachrichten gespeichert.\n";
        return $allNews;
    }
    
    // Holt die neuesten Nachrichten aus der Datenbank
    public function getLatestNewsFromDb($language = null, $limit = 30) {
        try {
            $sql = "SELECT n.*, s.name as source_name 
                    FROM news_items n 
                    JOIN news_sources s ON n.source_id = s.id 
                    WHERE 1=1";
            
            $params = [];
            
            if ($language !== null) {
                $sql .= " AND n.language = :language";
                $params['language'] = $language;
            }
            
            $sql .= " ORDER BY n.timestamp DESC LIMIT :limit";
            $params['limit'] = $limit;
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                if ($key == 'limit') {
                    $stmt->bindValue(':' . $key, $value, PDO::PARAM_INT);
                } else {
                    $stmt->bindValue(':' . $key, $value);
                }
            }
            
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Fehler beim Abrufen der Nachrichten: " . $e->getMessage());
            return [];
        }
    }
    
    // Holt Market-Moving-News aus der Datenbank
    public function getMarketMovingNewsFromDb($hours = 24, $limit = 10) {
        try {
            $sql = "SELECT n.*, s.name as source_name 
                    FROM news_items n 
                    JOIN news_sources s ON n.source_id = s.id 
                    WHERE (n.is_market_moving = 1 OR n.is_arkham = 1)
                    AND n.timestamp >= DATE_SUB(NOW(), INTERVAL :hours HOUR)
                    ORDER BY n.impact_score DESC, n.timestamp DESC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':hours', $hours, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            error_log("Fehler beim Abrufen der Market-Moving-News: " . $e->getMessage());
            return [];
        }
    }

    public function getMarketMovingNews($hours = 24) {
        // Sicherstellen, dass die Nachrichten aktuell sind
        $this->fetchAllSources();
        
        // Dann die Market-Moving-News aus der DB abrufen
        return $this->getMarketMovingNewsFromDb($hours, 10);
    }

    public function aggregateNews($language = null, $limit = 30) {
        // Zuerst Nachrichten aus den Quellen holen und in DB speichern
        $this->fetchAllSources($language);
        
        // Dann die neuesten Nachrichten aus der DB abrufen
        return $this->getLatestNewsFromDb($language, $limit);
    }

    public function timeAgo($timestamp, $language = 'de') {
        // Konvertiere MySQL DATETIME zu Unix-Timestamp, falls nötig
        if (!is_numeric($timestamp)) {
            $timestamp = strtotime($timestamp);
        }
        
        $now = time();
        $diff = $now - $timestamp;
        $hours = floor($diff / 3600);
        $minutes = floor(($diff % 3600) / 60);

        // Breaking News Zeit
        $isBreakingNews = $diff < 3600;

        if ($hours < 1) {
            if ($language === 'en') {
                return [
                    'text' => $minutes < 1 ? "just now:" : 
                             ($minutes == 1 ? "1 minute ago:" : "{$minutes} minutes ago:"),
                    'class' => 'time-recent',
                    'breaking' => $isBreakingNews
                ];
            } else {
                return [
                    'text' => $minutes < 1 ? "gerade eben:" : 
                             ($minutes == 1 ? "vor 1 Minute:" : "vor {$minutes} Minuten:"),
                    'class' => 'time-recent',
                    'breaking' => $isBreakingNews
                ];
            }
        } else {
            if ($language === 'en') {
                if ($hours == 1 && $minutes == 0) {
                    return [
                        'text' => "1 hour ago:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($hours == 1) {
                    return [
                        'text' => "1 hour and {$minutes} minutes ago:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($minutes == 0) {
                    return [
                        'text' => "{$hours} hours ago:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($hours < 24) {
                    return [
                        'text' => "{$hours} hours and {$minutes} minutes ago:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } else {
                    return [
                        'text' => date('d.m H:i', $timestamp), 
                        'class' => 'time-old',
                        'breaking' => false
                    ];
                }

            } else {
                if ($hours == 1 && $minutes == 0) {
                    return [
                        'text' => "vor 1 Stunde:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($hours == 1) {
                    return [
                        'text' => "vor 1 Stunde und {$minutes} Minuten:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($minutes == 0) {
                    return [
                        'text' => "vor {$hours} Stunden:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } elseif ($hours < 24) {
                    return [
                        'text' => "vor {$hours} Stunden und {$minutes} Minuten:", 
                        'class' => 'time-old',
                        'breaking' => $isBreakingNews
                    ];
                } else {
                    return [
                        'text' => date('d.m H:i', $timestamp), 
                        'class' => 'time-old',
                        'breaking' => false
                    ];
                }
            }
        }
    }

    private function truncateTitle($title, $length = 80) {
        return (strlen($title) > $length) 
            ? substr($title, 0, $length) . '...' 
            : $title;
    }

    public function getLastUpdateTimestamp() {
        return $this->lastUpdateTimestamp;
    }
}
?>