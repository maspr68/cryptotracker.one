<?php
session_start();

// Simulierter Bitcoin-Startkurs
if (!isset($_SESSION['bitcoin_kurs'])) {
    $_SESSION['bitcoin_kurs'] = 50000;
    $_SESSION['last_kurs'] = 50000;
    $_SESSION['bet_streak'] = 0;
    $_SESSION['total_wins'] = 0;
}

// Zufällige Kursbewegung simulieren
function getNewBitcoinKurs($currentKurs) {
    $change = rand(-500, 500); // ±500 USD Bewegung
    return $currentKurs + $change;
}

// Live-Wette: Nutzer schätzt, ob der Kurs steigt oder fällt
if (isset($_GET['bet'])) {
    $_SESSION['user_bet'] = $_GET['bet'];
    echo json_encode(["status" => "bet placed"]);
    exit;
}

// API-Update
if (isset($_GET['update'])) {
    $_SESSION['last_kurs'] = $_SESSION['bitcoin_kurs'];
    $_SESSION['bitcoin_kurs'] = getNewBitcoinKurs($_SESSION['bitcoin_kurs']);
    
    // Wette auswerten
    $correct = null;
    if (isset($_SESSION['user_bet'])) {
        $correct = ($_SESSION['bitcoin_kurs'] > $_SESSION['last_kurs'] && $_SESSION['user_bet'] === "up") ||
                   ($_SESSION['bitcoin_kurs'] < $_SESSION['last_kurs'] && $_SESSION['user_bet'] === "down");
        
        if ($correct) {
            $_SESSION['bet_streak']++;
            $_SESSION['total_wins']++;
        } else {
            $_SESSION['bet_streak'] = 0;
        }
        unset($_SESSION['user_bet']);
    }
    
    echo json_encode([
        "kurs" => $_SESSION['bitcoin_kurs'],
        "correct" => $correct,
        "streak" => $_SESSION['bet_streak'],
        "wins" => $_SESSION['total_wins']
    ]);
    exit;
}
$bitcoin_kurs = $_SESSION['bitcoin_kurs'];
?>

<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bitcoin Gamification</title>
    <style>
        body { font-family: Arial, sans-serif; background-color: #000; color: white; display: flex; flex-direction: column; align-items: center; justify-content: center; height: 100vh; }
        .kurs-container { font-size: 48px; font-weight: bold; padding: 20px; border-radius: 10px; background-color: #222; transition: background 0.5s; }
        .kurs-up { background-color: green; }
        .kurs-down { background-color: red; }
        .bet-container { margin-top: 20px; }
        .bet-button { font-size: 24px; padding: 10px 20px; cursor: pointer; margin: 5px; }
        #streak-container { font-size: 24px; margin-top: 10px; }
    </style>
</head>
<body>
    <div id="kurs" class="kurs-container">
        $<?php echo number_format($bitcoin_kurs, 2, ',', '.'); ?>
    </div>

    <!-- Live-Wette -->
    <div class="bet-container">
        <button class="bet-button" onclick="placeBet('up')">⬆ Steigt</button>
        <button class="bet-button" onclick="placeBet('down')">⬇ Fällt</button>
        <div id="bet-result"></div>
        <div id="streak-container">🏆 Streak: 0 | Gesamtgewinne: 0</div>
    </div>

    <script>
        function updateKurs() {
            fetch('?update=1')
                .then(response => response.json())
                .then(data => {
                    let kursElement = document.getElementById('kurs');
                    kursElement.innerText = '$' + data.kurs.toLocaleString('de-DE', { minimumFractionDigits: 2 });
                    kursElement.className = 'kurs-container ' + (data.kurs > <?php echo $_SESSION['last_kurs']; ?> ? 'kurs-up' : 'kurs-down');
                    
                    // Wetten auswerten
                    if (data.correct !== null) {
                        document.getElementById('bet-result').innerText = data.correct ? '✅ Richtig!' : '❌ Falsch!';
                        document.getElementById('streak-container').innerText = `🏆 Streak: ${data.streak} | Gesamtgewinne: ${data.wins}`;
                    }
                });
        }

        function placeBet(direction) {
            fetch('?bet=' + direction)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('bet-result').innerText = 'Wette platziert!';
                });
        }

        setInterval(updateKurs, 2000);
    </script>
</body>
</html>
