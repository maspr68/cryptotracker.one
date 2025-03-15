<?php
// checkcwx.php

// Wenn ein GET-Parameter "id" übergeben wurde, führen wir den Check durch.
if (isset($_GET['id'])) {
    header('Content-Type: application/json; charset=utf-8');

    $inputId = $_GET['id'];

    // Validierung: CWX-ID muss exakt 8 Ziffern enthalten
    if (!preg_match('/^\d{8}$/', $inputId)) {
        echo json_encode(["error" => "Die zu prüfende CWX-ID muss genau 8 Ziffern enthalten."]);
        exit;
    }

    // API-Owner-Daten (fix) – Diese CWX-ID und der zugehörige API-Key werden für die Basic Auth genutzt
    $apiOwnerId = "98147157";  // API-Owner CWX-ID
    $apiKey     = "7ksycnkYAvTUDZpivUyt4f3tz2tpd1AKIsJL3WnaUr9Mapf0bbPxbdgWY1pGs0ZI";

    // Crossworx-Endpunkt: die zu prüfende ID wird als GET-Parameter angehängt
    $endpoint = "https://my.cwx.one/api/partner/check-cwx-id?id=" . urlencode($inputId);

    // cURL-Aufruf zur Validierung
    $ch = curl_init($endpoint);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    // Basic Authentication: Benutzername = API-Owner CWX-ID, Passwort = API-Key
    curl_setopt($ch, CURLOPT_USERPWD, "$apiOwnerId:$apiKey");

    // Zum Testen: SSL-Überprüfung deaktivieren (in Produktion bitte aktivieren!)
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $apiResponseRaw = curl_exec($ch);
    $curlError      = curl_error($ch);
    curl_close($ch);

    // Wenn ein cURL-Fehler aufgetreten ist
    if ($curlError) {
        echo json_encode(["error" => "Verbindungsfehler: $curlError"]);
        exit;
    }

    // DEBUG-BLOCK (optional)
    if (isset($_GET['debug'])) {
        header('Content-Type: text/plain; charset=utf-8');
        echo "==== DEBUG: Rohantwort von Crossworx ====\n\n";
        echo $apiResponseRaw;
        exit;
    }

    // API-Antwort parsen
    $apiResponse = json_decode($apiResponseRaw, true);
    if (!$apiResponse) {
        echo json_encode(["error" => "Ungültige API-Antwort."]);
        exit;
    }

    // Beispiel-Antwortstruktur:
    // {
    //   "data": {
    //     "success": true,
    //     "is_valid": true,
    //     "is_active": true,
    //     ...
    //   }
    // }

    // 1) "success" prüfen
    if (!isset($apiResponse['data']['success']) || $apiResponse['data']['success'] !== true) {
        echo json_encode(["error" => "Die CWX-ID konnte nicht validiert werden (kein success)."]);
        exit;
    }

    // 2) "is_valid" prüfen
    if (!isset($apiResponse['data']['is_valid']) || $apiResponse['data']['is_valid'] !== true) {
        echo json_encode(["error" => "Die CWX-ID konnte nicht validiert werden (is_valid=false)."]);
        exit;
    }

    // 3) "is_active" prüfen
    if (!isset($apiResponse['data']['is_active']) || $apiResponse['data']['is_active'] !== true) {
        echo json_encode(["error" => "Die CWX-ID ist gültig, aber nicht aktiv."]);
        exit;
    }

    // Alles okay, also Erfolg melden
    echo json_encode([
        "success" => true,
        "message" => "Deine CWX-ID ist gültig und aktiv!"
    ]);
    exit;
}
?>
<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Cryptotracker Anmeldung</title>
  <style>
    :root {
      --input-width: 200px;
      --input-height: 40px;
      --input-border: 2px solid #3498db;
      --input-border-radius: 4px;
      --input-font-size: 1rem;
      --input-padding: 0.5rem;
      --input-color: #333;
      --input-bg: #fff;
      --label-font-size: 1rem;
      --label-color: #333;
      --container-bg: #fff;
      --container-padding: 20px;
      --container-radius: 8px;
      --container-shadow: 0 2px 6px rgba(0,0,0,0.1);
      --button-bg: #28a745;
      --button-color: #fff;
      --button-border: none;
      --button-padding: 10px 20px;
      --button-radius: 4px;
    }
    body {
      font-family: Arial, sans-serif;
      background: #f5f5f5;
      padding: 20px;
    }
    .cwx-container {
      max-width: 400px;
      margin: 0 auto;
      background: var(--container-bg);
      padding: var(--container-padding);
      border-radius: var(--container-radius);
      box-shadow: var(--container-shadow);
      text-align: center;
    }
    .cwx-container label {
      font-size: var(--label-font-size);
      color: var(--label-color);
      display: block;
      margin-bottom: 0.5rem;
    }
    .cwx-container input.cwx-input {
      width: var(--input-width);
      height: var(--input-height);
      padding: var(--input-padding);
      border: var(--input-border);
      border-radius: var(--input-border-radius);
      font-size: var(--input-font-size);
      color: var(--input-color);
      background: var(--input-bg);
      box-sizing: border-box;
      margin-bottom: 1rem;
    }
    .cwx-container #result {
      margin-top: 1rem;
      font-weight: bold;
    }
  </style>
</head>
<body>
  <div class="cwx-container">
    <h2>Cryptotracker Anmeldung</h2>
    <label for="cwxInput">Gib deine 8-stellige CWX-ID ein:</label>
    <input type="text" id="cwxInput" maxlength="8" placeholder="z.B. 12345678" class="cwx-input">
    <div id="result"></div>
  </div>
  
  <script>
    const input = document.getElementById('cwxInput');
    const resultDiv = document.getElementById('result');

    // Wenn genau 8 Ziffern eingegeben wurden, CWX-ID überprüfen
    input.addEventListener('input', function() {
      const userInput = input.value.trim();
      if (userInput.length === 8 && /^\d+$/.test(userInput)) {
        fetch('checkcwx.php?id=' + encodeURIComponent(userInput))
          .then(response => response.json())
          .then(data => {
            if (data.error) {
              resultDiv.textContent = 'Fehler: ' + data.error;
              resultDiv.style.color = 'red';
            } else {
              resultDiv.textContent = data.message;
              resultDiv.style.color = 'green';
            }
          })
          .catch(err => {
            resultDiv.textContent = 'Fehler beim Abrufen der API: ' + err;
            resultDiv.style.color = 'red';
          });
      } else {
        // Noch nicht 8 Ziffern oder ungültige Eingabe
        resultDiv.textContent = '';
      }
    });
  </script>
</body>
</html>
