<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>Modal Overlay</title>
  <style>
    /* Overlay, das den Hintergrund unscharf macht */
    #overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(232, 34, 34, 0.8);
      backdrop-filter: blur(5px);
      display: none;
      z-index: 9999;
    }
    /* Das Modal, das zentriert im Overlay erscheint */
    #modal {
      position: absolute;
      top: 50%;
      left: 50%;
      width: 90%;
      max-width: 400px;
      background: #28a745;
      color: #fff;
      border-radius: 8px;
      transform: translate(-50%, -50%);
      padding: 20px;
      box-shadow: 8px 8px 15px rgba(0, 0, 0, 0.7);
      text-align: center;
    }
    /* Styling für Inputs im Modal – große Schrift, fett */
    #modal input {
      padding: 10px;
      font-size: 32px;
      font-weight: 900;
      width: 80%;
      border-radius: 4px;
      border: none;
      margin-bottom: 1rem;
      text-align: center;
    }
    /* Speziell für das E-Mail-Feld in der Registrierung (kleinere Schrift) */
    #modal input[type="email"] {
      font-size: 20px;
      font-weight: normal;
    }
    #modal button {
      padding: 10px 20px;
      font-size: 1rem;
      border: none;
      border-radius: 4px;
      background: #fff;
      color: #28a745;
      cursor: pointer;
      margin: 5px;
    }
    .toggle-btn {
      background: transparent;
      color: #fff;
      border: 1px solid #fff;
      padding: 5px 10px;
      border-radius: 4px;
      cursor: pointer;
      margin-top: 10px;
    }
  </style>
</head>
<body>
  <!-- Overlay mit Modal (versteckt) -->
  <div id="overlay">
    <div id="modal">
      <!-- CWX-ID-Prüfung -->
      <div id="cwxCheckSection">
        <h2>Bitte gib deine CWX-ID ein</h2>
        <p>Falls du bereits eine CWX-ID hast, gib sie hier ein:</p>
        <input type="text" id="cwxInput" placeholder="8-stellige CWX-ID">
        <div id="checkResult" style="margin-top:10px;"></div>
        <button class="toggle-btn" id="showRegister">Keine CWX-ID? Registriere dich</button>
      </div>
      <!-- Registrierung -->
      <div id="registerSection" style="display:none;">
        <h2>Registrierung</h2>
        <p>Du hast noch keine CWX-ID?<br>
           Registriere dich jetzt – im Hintergrund setzen wir:<br>
           Vorname: <strong>Cryptotracker</strong>, Nachname: <strong>Anmeldung</strong>, und xname: <strong>42</strong>.
        </p>
        <input type="email" id="regEmail" placeholder="E-Mail-Adresse">
        <button id="regBtn">Registrieren</button>
        <button class="toggle-btn" id="showCheck">Ich habe bereits eine CWX-ID</button>
      </div>
    </div>
  </div>
  
  <script>
    document.addEventListener("DOMContentLoaded", function() {
      // Falls noch keine CWX-ID vorhanden, Overlay nach 10 Sekunden anzeigen
      if (!localStorage.getItem('cwxId') || localStorage.getItem('cwxId').trim() === "") {
        setTimeout(function() {
          document.getElementById('overlay').style.display = 'block';
        }, 10000);
      } else {
        document.getElementById('overlay').style.display = 'none';
      }
    });
    
    // CWX-ID prüfen: Ruft checkcwx.php (Server-Proxy) auf
    function checkCWXID() {
      var cwxId = document.getElementById('cwxInput').value.trim();
      if (cwxId.length !== 8 || !/^\d{8}$/.test(cwxId)) {
        document.getElementById('checkResult').textContent = "Bitte gib eine gültige 8-stellige CWX-ID ein.";
        document.getElementById('checkResult').style.color = "red";
        return;
      }
      fetch('checkcwx.php?id=' + encodeURIComponent(cwxId))
        .then(response => response.json())
        .then(data => {
          if (data.error) {
            document.getElementById('checkResult').textContent = "Fehler: " + data.error;
            document.getElementById('checkResult').style.color = "red";
          } else {
            document.getElementById('checkResult').textContent = data.message;
            document.getElementById('checkResult').style.color = "green";
            // CWX-ID speichern
            localStorage.setItem('cwxId', cwxId);
            // Direkt den Header aktualisieren:
            var headerCwxEl = document.getElementById('headerCwxId');
            if (headerCwxEl) { 
              headerCwxEl.textContent = "Eingeloggt als CWX-ID: " + cwxId;
            }
            // Auch den Container für CWX-ID und Logout-Button anzeigen:
            var headerCwxLogout = document.querySelector('.header-cwx-logout');
            if (headerCwxLogout) {
              headerCwxLogout.style.display = 'inline-flex';
            }
            // Ton abspielen
            var audio = new Audio('/assets/jumpy.wav');
            audio.play();
            // Overlay nach kurzer Verzögerung ausblenden
            setTimeout(function() {
              document.getElementById('overlay').style.display = 'none';
            }, 2000);
          }
        })
        .catch(err => {
          document.getElementById('checkResult').textContent = "Fehler beim Prüfen: " + err;
          document.getElementById('checkResult').style.color = "red";
        });
    }
    
    // Registrierung via API (wie bisher)
    function registerUser() {
      var email = document.getElementById('regEmail').value.trim();
      if (!validateEmail(email)) {
        alert("Bitte gib eine gültige E-Mail-Adresse ein.");
        return;
      }
      const params = new URLSearchParams();
      params.append('xname', '42');
      params.append('first_name', 'Cryptotracker');
      params.append('last_name', 'Anmeldung');
      params.append('email', email);
      
      const username = "98147157";
      const apiKey   = "7ksycnkYAvTUDZpivUyt4f3tz2tpd1AKIsJL3WnaUr9Mapf0bbPxbdgWY1pGs0ZI";
      const basicAuthHeader = 'Basic ' + btoa(username + ':' + apiKey);
      
      fetch('https://my.cwx.one/api/partner/register-crossworker', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
          'Authorization': basicAuthHeader,
          'Accept': 'application/json'
        },
        body: params.toString()
      })
      .then(response => response.json())
      .then(data => {
        if (data.error) {
          alert("Registrierungsfehler: " + data.error);
        } else {
          alert("Danke für deine Registrierung! Bitte überprüfe deine E-Mail für weitere Informationen.");
          document.getElementById('overlay').style.display = 'none';
        }
      })
      .catch(err => {
        alert("Fehler beim Registrieren: " + err);
      });
    }
    
    function validateEmail(email) {
      const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
      return re.test(email);
    }
    
    // Automatische Prüfung, sobald 8 Ziffern eingegeben wurden
    document.getElementById('cwxInput').addEventListener('input', function() {
      var value = this.value.trim();
      if (value.length === 8 && /^\d{8}$/.test(value)) {
        checkCWXID();
      }
    });
    
    // Umschalten zwischen CWX-ID-Prüfung und Registrierung
    document.getElementById('showRegister').addEventListener('click', function() {
      document.getElementById('cwxCheckSection').style.display = 'none';
      document.getElementById('registerSection').style.display = 'block';
    });
    document.getElementById('showCheck').addEventListener('click', function() {
      document.getElementById('registerSection').style.display = 'none';
      document.getElementById('cwxCheckSection').style.display = 'block';
    });
    
    document.getElementById('regBtn').addEventListener('click', registerUser);
  </script>
</body>
</html>
