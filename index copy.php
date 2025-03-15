<!DOCTYPE html>
<html lang="de">
<head>
  <meta charset="UTF-8">
  <title>24h Crypto Dashboard - Live Update</title>
  <!-- Google tag (gtag.js) --> 
  <script async src="https://www.googletagmanager.com/gtag/js?id=G-29PQ8EGV78"></script> 
  <script> 
    window.dataLayer = window.dataLayer || []; 
    function gtag(){dataLayer.push(arguments);} 
    gtag('js', new Date()); 
    gtag('config', 'G-29PQ8EGV78'); 
  </script>
  <!-- Mobile Web App Meta Tags -->
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
  <meta name="theme-color" content="#333333">
  <meta name="description" content="Echtzeit-Crypto-Dashboard mit VWAP, Fear &amp; Greed Index und News">

  <!-- Vereinfachter iOS Splash Screen -->
  <link rel="apple-touch-startup-image" href="splash/splash.png">

  <link rel="manifest" href="manifest.json">
  <link rel="icon" href="favicon.ico" type="image/x-icon">
  <link rel="apple-touch-icon" href="assets/images/apple-touch-icon.png">
  <!-- ApexCharts (CDN) für Diagramme -->
  <script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
  
  <!-- Style für Pull-to-Refresh -->
  <style>
    #pullToRefreshIndicator {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      background-color: #28a745;
      color: white;
      text-align: center;
      padding: 10px;
      transform: translateY(-100%);
      transition: transform 0.3s ease;
      z-index: 1000;
    }
  </style>
  
  <!-- Eigene Styles für das Logo im Header -->
  <style>
    :root {
      --logo-bg: white;      /* Hintergrund des Logo-Containers */
      --logo-align: center;         /* Mögliche Werte: left, center, right */
      --logo-width: 200px;          /* Breite des Logos */
    }
    .header-logo-container {
      background-color: var(--logo-bg);
      text-align: var(--logo-align);
      padding: 10px;
    }
    .header-logo {
      width: var(--logo-width);
      height: auto;
    }
  </style>
  
  <style>
    /* Volume Chart Styles */
    .volume-container {
      display: flex;
      flex-wrap: nowrap;
      flex: 1 1 auto;
      justify-content: space-between;
      margin-bottom: 1rem;
    }
    .volume-box {
      background-color: #f8f9fa;
      padding: 8px;
      border-radius: 4px;
      flex: 1 1 30%;
      min-width: 90px;
      margin: 0.25rem;
      text-align: center;
      font-weight: 600;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }

    /* Moderne, Apple-like Schriftart und responsives Layout */
    body {
      margin: 0;
      padding: 0;
      font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
      background: #f5f5f5;
      font-size: 14px;
      max-width: 100vw;
      overflow-x: hidden;
    }
    
    /* Hero Überschrift */
    header {
      background: #ffffff;
      color:rgb(70, 68, 68);
      font-weight: 900;
      padding: 0.75rem;
      text-align: center;
    }

     /* Sub-Hero Überschrift */
    .hero-subtext {
      font-size: 0.6rem;
      background-color: #ffffff;
      color: #rgb(70, 68, 68);
      padding: 4px 8px;
      margin-bottom: 8px;
      border-radius: 4px;
      text-align: center;
    }
    
    /* Mobile-optimiertes Layout mit Flex-Container */
    main {
      display: flex;
      flex-direction: column;
      gap: 1rem;
      padding: 1rem;
      max-width: 100%;
      margin: 0 auto;
      min-height: calc(100vh - 100px);
    }
    
    /* Karten-Reihenfolge definieren */
    .card-fear-greed { order: 4; }
    .card-vwap { order: 1; }
    .card-chart { order: 2; }
    .card-news { order: 3; }
    .card-volume { order: 5; } 
    
    .card {
      background: #fff;
      border-radius: 4px;
      box-shadow: 0 1px 2px rgba(0,0,0,0.15);
      padding: 0.75rem;
      width: auto;
      margin-bottom: 0.5rem;
    }
    .card h2 {
      margin-top: 0;
      font-size: 1.2rem;
      margin-bottom: 0.75rem;
    }
    
    /* Eigene Klasse für Karten-Überschriften */
    .card-title {
      margin-top: 0;
      font-size: 1.1rem;
      margin-bottom: 0.75rem;
      font-weight: 1000;
      color: #333;
      text-align: center;
    }
    
    .chart-container, .gauge-container {
      min-height: 250px;
    }
    /* Container für den aktuellen VWAP-Wert (kleiner gemacht) */
    .vwap-container {
      background-color: #f0f0f0;
      color: #000;
      font-weight: 800;
      font-size: 1.5rem;
      padding: 10px;
      border-radius: 4px;
      text-align: center;
      flex: 2;
    }
    /* Container für prozentuale Veränderung */
    .vwap-change-container {
      background-color: #f0f0f0;
      color: #000;
      font-weight: 800;
      font-size: 1.3rem;
      padding: 10px;
      border-radius: 4px;
      text-align: center;
      flex: 1;
    }
    /* Flex-Container für den VWAP-Bereich */
    .vwap-row {
      display: flex;
      gap: 10px;
      align-items: center;
      margin-top: 1rem;
      flex-direction: row;
      flex-wrap: wrap;
    }
    /* Container für die letzte Aktualisierung in der VWAP-Karte */
    .last-update {
      font-size: 1rem;
      background-color: #f0f0f0;
      color: #333;
      padding: 6px 10px;
      margin-bottom: 12px;
      border-radius: 4px;
      text-align: center;
    }
    /* Container für die Gauge-Kategorie */
    .gauge-category {
      font-size: 1.5rem;
      font-weight: 900;
      background-color: #f0f0f0;
      color: #333;
      padding: 4px 8px;
      margin-top: 8px;
      border-radius: 4px;
      text-align: center;
    }
    
    /* Exchange-Infos */
    .exchange-info {
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      margin-top: 1rem;
      font-size: 0.9rem;
    }
    .exchange-line {
      display: flex;
      justify-content: space-between;
    }
    .exchange-label {
      font-weight: 600;
    }
    
    /* Volume Chart Container */
    .volume-chart-container {
      min-height: 250px;
      margin-top: 1rem;
    }
    
    /* Exchange Prices Container */
    .exchange-prices {
      display: flex;
      justify-content: space-between;
      flex-wrap: wrap;
      width: 100%;
    }
    .exchange-price-card {
      background-color: #ff0 !important;
      border-radius: 4px;
      padding: 10px;
      flex: 1 1 30%;
      min-width: 90px;
      margin: 0.25rem;
      text-align: center;
      box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    }
    .exchange-name {
      font-weight: 600;
      font-size: 1rem;
      margin-bottom: 5px;
      color: #333;
    }
    .exchange-price-value {
      font-weight: 800;
      font-size: 1.3rem;
    }
    
    /* Kompaktere News-Container */
    .news-container {
      display: block;
      background: #fff;
      border-radius: 4px;
      margin-bottom: 0.4rem;
      padding: 0.5rem;
      text-decoration: none;
      color: #000;
      box-shadow: 0 1px 2px rgba(0,0,0,0.1);
      transition: background 0.2s ease;
      overflow: hidden;
    }
    .news-container:hover {
      background: rgb(245, 184, 184);
    }
    .news-title {
      font-size: 0.75rem;
      font-weight: 600;
      margin-bottom: 2px;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    .news-meta {
      font-size: 0.5rem;
      color: #666;
      line-height: 1.2;
    }
    #newsSection {
      height: auto;
    }
    .breaking-tag {
      display: inline;
      background: #dc3545;
      color: #fff;
      font-weight: 700;
      padding: 1px 4px;
      border-radius: 3px;
      font-size: 0.5rem;
      margin-right: 3px;
    }
    /* Breaking News Border */
    .news-container.breaking-news-border {
      border: 1px solid #999 !important;
      box-shadow: none !important;
    }
  </style>

  <script type="text/javascript" src="https://cdn.weglot.com/weglot.min.js"></script>
  <script>
    Weglot.initialize({
        api_key: 'wg_1dbbd650fe2988cc9715b1549549ad501'
    });
  </script>
</head>
<body>
  <!-- Pull-to-Refresh Indikator hinzufügen -->
  <div id="pullToRefreshIndicator">Loslassen zum Aktualisieren</div>
  <header>
    <!-- Neuer Logo-Container -->
    <div class="header-logo-container">
      <img src="/assets/images/cwxlogo.png" alt="Logo" class="header-logo">
    </div>
    <div class="header-container">
      <div class="logo">24h Cryptotracker®</div>
      <div class="hero-subtext">Die Veränderungen in % beziehen sich exakt auf den gleichen Zeitpunkt von gestern.</div>
    </div>
  </header>

  <main>
    <!-- Fear & Greed Card -->
    <div class="card card-fear-greed">
      <h2 class="card-title">Live Fear &amp; Greed Index</h2>
      <div id="gaugeChart" class="gauge-container" title="Tooltip wird aktualisiert"></div>
      <div id="gaugeCategory" class="gauge-category">--</div>
    </div>
    
    <!-- VWAP Card -->
    <div class="card card-vwap">
      <h2 class="card-title">Live Volume Weighted Average Price</h2>
      <div id="vwapUpdateTime" class="last-update">Letzte Aktualisierung: --</div>
      <div class="vwap-row">
        <div id="currentVWAP" class="vwap-container">Lade Daten...</div>
        <div id="vwapChange" class="vwap-change-container">--%</div>
      </div>
    </div>
    
    <!-- Chart Card -->
    <div class="card card-chart">
      <h2 class="card-title">Last 24h from now</h2>
      <div id="lineChart" class="chart-container"></div>
    </div>
    
    <!-- News Card -->
    <div class="card card-news">
      <h2 class="card-title">Live Crypto News</h2>
      <div id="newsSection">
        <p>Daten werden geladen...</p>
      </div>
    </div>

    <!-- Volume Chart Card - NEUE VARIANTE mit include -->
    <div class="card card-volume">
      <h2 class="card-title">Live Volumen Kryptobörsen</h2>
      <?php include 'volumeChart.php'; ?>
    </div>
  </main>

  <!-- ApexCharts bereits im head geladen -->
  <script>
    // ------------------------- APEXCHARTS INITIALISIERUNG: Fear & Greed -------------------------
    let gaugeOptions = {
      series: [50],
      chart: { type: 'radialBar', height: 300 },
      plotOptions: {
        radialBar: {
          hollow: { size: '50%' },
          dataLabels: { 
            name: { show: false }, 
            value: { 
              fontSize: '44px',
              fontWeight: '1000',
              color: '#333333'
            } 
          }
        }
      },
      fill: {
        gradient: {
          enabled: true,
          shade: 'light',
          type: "vertical",
          shadeIntensity: 0.5,
          gradientToColors: ["#2ecc71"],
          inverseColors: true,
          stops: [0, 100]
        },
        colors: ["#e74c3c"]
      },
      labels: ['Fear & Greed'],
      tooltip: {
        enabled: true,
        y: {
          formatter: function(val, opts) {
            let catObj = getFNGCategory(val);
            return catObj.tooltip;
          }
        }
      }
    };
    let gaugeChart = new ApexCharts(document.querySelector("#gaugeChart"), gaugeOptions);
    gaugeChart.render();

    // ------------------------- APEXCHARTS INITIALISIERUNG: VWAP -------------------------
    let lineOptions = {
      series: [{ name: 'VWAP', data: [] }],
      chart: { type: 'line', height: 300, animations: { enabled: true } },
      stroke: { curve: 'smooth', width: 3 },
      markers: { size: 0 },
      xaxis: { categories: [], labels: { rotate: 0 } },
      yaxis: { decimalsInFloat: 2 },
      tooltip: { theme: 'light' }
    };
    let lineChart = new ApexCharts(document.querySelector("#lineChart"), lineOptions);
    lineChart.render();

    // ------------------------- GLOBALE VARIABLEN -------------------------
    let currentCategories = [];
    let currentVwapData = [];
    let lastVWAPValue = null;

    // ------------------------- FNG-KATEGORIE-FUNKTION -------------------------
    function getFNGCategory(value) {
      if (value >= 0 && value <= 20) {
        return { category: "Extreme Fear", tooltip: "Extreme Angst am Markt, starke Verkaufstendenz, Panikstimmung an den Märkten" };
      } else if (value >= 21 && value <= 40) {
        return { category: "Fear", tooltip: "Angst am Markt, pessimistische Marktstimmung, vorsichtige Anleger" };
      } else if (value >= 41 && value <= 59) {
        return { category: "Neutral", tooltip: "Neutrale Markteinstellung, ausgeglichene Marktstimmung, weder extreme Angst noch extreme Gier" };
      } else if (value >= 60 && value <= 80) {
        return { category: "Greed", tooltip: "Gierige Grundstimmung, optimistische Marktstimmung, Anleger sind risikofreudiger" };
      } else if (value >= 81 && value <= 100) {
        return { category: "Extreme Greed", tooltip: "Extreme Gier der Anleger, übermäßiger Optimismus, Kaufrausch, mögliche Überbewertung" };
      } else {
        return { category: "Unbekannt", tooltip: "" };
      }
    }

    function getFNGStyle(category) {
      switch (category) {
        case "Extreme Fear":
        case "Extreme Greed":
          return { background: "linear-gradient(to right, #dc3545, #ff6b6b)", color: "#fff" };
        case "Fear":
        case "Greed":
          return { background: "linear-gradient(to right, #fd7e14, #ffa64d)", color: "#fff" };
        case "Neutral":
          return { background: "linear-gradient(to right, #90ee90, #68c470)", color: "#000" };
        default:
          return { background: "#f0f0f0", color: "#000" };
      }
    }

    function getFNGGradientColors(category) {
      switch (category) {
        case "Extreme Fear":
        case "Extreme Greed":
          return { color1: "#dc3545", color2: "#ff6b6b" };
        case "Fear":
        case "Greed":
          return { color1: "#fd7e14", color2: "#ffa64d" };
        case "Neutral":
          return { color1: "#90ee90", color2: "#68c470" };
        default:
          return { color1: "#f0f0f0", color2: "#d3d3d3" };
      }
    }

    // ------------------------- GAUGE UPDATE -------------------------
    function updateGauge(fgValue) {
      let val = Math.max(0, Math.min(100, fgValue));
      gaugeChart.updateSeries([val]);
      let catObj = getFNGCategory(val);
      document.querySelector("#gaugeChart").setAttribute("title", catObj.tooltip);

      const gaugeCategoryElem = document.getElementById("gaugeCategory");
      gaugeCategoryElem.textContent = catObj.category;

      const styleObj = getFNGStyle(catObj.category);
      gaugeCategoryElem.style.background = styleObj.background;
      gaugeCategoryElem.style.color = styleObj.color;

      let gradientColors = getFNGGradientColors(catObj.category);
      gaugeChart.updateOptions({
        fill: {
          colors: [gradientColors.color1],
          gradient: {
            enabled: true,
            shade: 'light',
            type: "vertical",
            shadeIntensity: 0.5,
            gradientToColors: [gradientColors.color2],
            inverseColors: true,
            stops: [0, 100]
          }
        }
      });
    }

    // ------------------------- ANIMATION: Marker (schnelles Ein-/Ausblenden) -------------------------
    function animateMarker(markerColor, shape = 'circle') {
      lineChart.updateOptions({
        markers: {
          discrete: [{
            seriesIndex: 0,
            dataPointIndex: currentVwapData.length - 1,
            size: 10,
            fillColor: markerColor,
            strokeColor: markerColor,
            shape: shape
          }]
        }
      });
      setTimeout(() => {
        lineChart.updateOptions({ markers: { discrete: [] } });
      }, 200);
    }

    // ------------------------- UPDATE-FUNKTIONEN (VWAP) -------------------------
    function updateCurrentVWAP(vwap) {
      let formatted = vwap.toLocaleString('de-DE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
      let container = document.getElementById('currentVWAP');
      container.textContent = formatted + " USD";
      
      let updateTimeContainer = document.getElementById('vwapUpdateTime');
      let now = new Date();
      let formattedTime = now.toLocaleDateString('de-DE') + " " + now.toLocaleTimeString('de-DE');
      updateTimeContainer.textContent = "Letzte Aktualisierung: " + formattedTime;
      
      if (lastVWAPValue !== null) {
        if (vwap > lastVWAPValue) {
          container.style.backgroundColor = "#28a745";
          container.style.color = "#fff";
          animateMarker("#28a745");
        } else if (vwap < lastVWAPValue) {
          container.style.backgroundColor = "#dc3545";
          container.style.color = "#fff";
          animateMarker("#dc3545");
        } else {
          container.style.backgroundColor = "#f0f0f0";
          container.style.color = "#000";
        }
      }
      lastVWAPValue = vwap;
      
      if (currentVwapData.length > 0) {
        let vwap24h = currentVwapData[0];
        if (vwap24h > 0) {
          let change = ((vwap - vwap24h) / vwap24h) * 100;
          updateVwapChange(change);
        }
      }
    }

    function updateVwapChange(change) {
      let container = document.getElementById('vwapChange');
      let formattedChange = change.toFixed(2) + "%";
      container.textContent = formattedChange;
      if (change > 0) {
        container.style.backgroundColor = "#28a745";
        container.style.color = "#fff";
      } else if (change < 0) {
        container.style.backgroundColor = "#dc3545";
        container.style.color = "#fff";
      } else {
        container.style.backgroundColor = "#f0f0f0";
        container.style.color = "#000";
      }
    }

    function updateLineChart(vwapData, timeLabels) {
      lineChart.updateOptions({ xaxis: { categories: timeLabels } });
      lineChart.updateSeries([{ name: 'VWAP', data: vwapData }]);
    }

    // ------------------------- FETCH-FUNKTIONEN -------------------------
    function fetchFearAndGreed() {
      fetch('../backend/api/getlatestfearandgreed.php')
        .then(response => {
          if (!response.ok) throw new Error('Fehler beim Abrufen des Fear & Greed Index');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            console.error(data.error);
          } else {
            let fgValue = parseInt(data.fear_and_greed_index || 0);
            updateGauge(fgValue);
          }
        })
        .catch(err => {
          console.error(err);
        });
    }

    function fetchCurrentCryptoData() {
      fetch('../backend/api/getlatestcryptoprices.php')
        .then(response => {
          if (!response.ok) throw new Error('Fehler beim Abrufen der aktuellen Kryptodaten');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            console.error(data.error);
          } else {
            updateCurrentVWAP(data.vwap || 0);
            document.getElementById('binancePrice').textContent = data.binance_price ?? 'n/a';
            document.getElementById('krakenPrice').textContent = data.kraken_price ?? 'n/a';
            document.getElementById('coinbasePrice').textContent = data.coinbase_price ?? 'n/a';
            document.getElementById('binanceVolume').textContent = data.binance_volume ?? 'n/a';
            document.getElementById('krakenVolume').textContent = data.kraken_volume ?? 'n/a';
            document.getElementById('coinbaseVolume').textContent = data.coinbase_volume ?? 'n/a';
          }
        })
        .catch(err => {
          console.error(err);
        });
    }

    function fetchInitial24hData() {
      fetch('../backend/api/get24hData.php')
        .then(response => {
          if (!response.ok) throw new Error('Fehler beim Abrufen der 24h-Daten');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            console.error(data.error);
          } else {
            currentCategories = data.times || [];
            currentVwapData = data.vwapData || [];
            updateLineChart(currentVwapData, currentCategories);
          }
        })
        .catch(err => {
          console.error(err);
        });
    }

    function fetchNewest24hPoint() {
      fetch('../backend/api/getLatest24hPoint.php')
        .then(response => {
          if (!response.ok) throw new Error('Fehler beim Abrufen des neuesten 24h-Datenpunkts');
          return response.json();
        })
        .then(data => {
          if (data.error) {
            console.error(data.error);
          } else {
            let newTime = data.time;
            let newVWAP = parseFloat(data.vwap);
            if (currentCategories.length > 0) {
              if (newTime === currentCategories[currentCategories.length - 1]) {
                currentVwapData[currentVwapData.length - 1] = newVWAP;
              } else {
                currentCategories.push(newTime);
                currentVwapData.push(newVWAP);
                if (currentCategories.length > 24) {
                  currentCategories.shift();
                  currentVwapData.shift();
                }
              }
            } else {
              currentCategories.push(newTime);
              currentVwapData.push(newVWAP);
            }
            updateLineChart(currentVwapData, currentCategories);
            updateCurrentVWAP(newVWAP);
          }
        })
        .catch(err => {
          console.error(err);
        });
    }

    function fetchNews() {
      fetch('../backend/api/getlatestnews.php?limit=20')
        .then(response => {
          if (!response.ok) throw new Error('Fehler beim Abrufen der News');
          return response.json();
        })
        .then(news => {
          let html = '';
          if (news.error) {
            html = `<p>${news.error}</p>`;
          } else {
            let now = new Date(); // Aktuelle Zeit
            news.forEach(item => {
              let newsDate = null;
              let isBreaking = false;
              if (item.timestamp && typeof item.timestamp === 'string') {
                newsDate = new Date(item.timestamp);
                const diffMs = now - newsDate;
                isBreaking = (diffMs < 1800000);
              }
              let pubDate = 'n/a';
              if (newsDate && !isNaN(newsDate.getTime())) {
                pubDate = newsDate.toLocaleDateString('de-DE') + " " + newsDate.toLocaleTimeString('de-DE');
              }
              let source = item.source_name || item.source || 'Unbekannt';
              html += `
                <a class="news-container${isBreaking ? ' breaking-news-border' : ''}" 
                   href="${item.link}" target="_blank">
                  ${isBreaking ? '<span class="breaking-tag">BREAKING NEWS</span> ' : ''}
                  <div class="news-title">${item.title}</div>
                  <div class="news-meta">${pubDate} | ${source}</div>
                </a>
              `;
            });
          }
          document.getElementById('newsSection').innerHTML = html;
        })
        .catch(err => {
          document.getElementById('newsSection').innerHTML = `<p>${err.message}</p>`;
        });
    }

    // ------------------------- INITIAL AUFRUF -------------------------
    fetchFearAndGreed();
    fetchCurrentCryptoData();
    fetchInitial24hData();
    fetchNews();
    fetchNewest24hPoint();

    // ------------------------- INTERVALLE -------------------------
    setInterval(fetchFearAndGreed, 5000);
    setInterval(fetchCurrentCryptoData, 5000);
    setInterval(fetchNewest24hPoint, 30000);
    setInterval(fetchNews, 60000);
  </script>

  <script>
    let deferredPrompt; 
    const installBanner = document.getElementById('installBanner');
    const installBtn = document.getElementById('installBtn');
    const dismissBtn = document.getElementById('dismissBtn');
    if (installBanner && installBtn && dismissBtn) {
      window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        installBanner.style.display = 'block';
      });

      installBtn.addEventListener('click', () => {
        installBanner.style.display = 'none';
        deferredPrompt.prompt();
        deferredPrompt.userChoice.then((choiceResult) => {
          deferredPrompt = null;
        });
      });

      dismissBtn.addEventListener('click', () => {
        installBanner.style.display = 'none';
      });

      window.addEventListener('appinstalled', (evt) => {
        installBanner.style.display = 'none';
      });
    }
  </script>

  <script>
    if ('serviceWorker' in navigator) {
      window.addEventListener('load', function() {
        navigator.serviceWorker.register('/service-worker.js')
          .then(reg => console.log('Service Worker registriert'))
          .catch(err => console.log('Service Worker Fehler:', err));
      });
    }
  </script>

  <div id="installBanner" style="display: none; position: fixed; bottom: 0; width: 100%; background: #333; color: white; padding: 10px; text-align: center; z-index: 1000;">
    <p>Installieren Sie diese App auf Ihrem Startbildschirm für schnelleren Zugriff!</p>
    <button id="installBtn" style="background: #28a745; color: white; border: none; padding: 5px 15px; border-radius: 4px; margin-right: 10px;">Installieren</button>
    <button id="dismissBtn" style="background: #dc3545; color: white; border: none; padding: 5px 15px; border-radius: 4px;">Nicht jetzt</button>
  </div>

  <?php include 'pull-to-refresh.php'; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const indicator = document.getElementById('pullToRefreshIndicator');
      
      let touchStartY = 0;
      let touchEndY = 0;
      const minSwipeDistance = 80;
      let isRefreshing = false;
      
      document.addEventListener('touchstart', function(e) {
        touchStartY = e.touches[0].clientY;
        if (window.scrollY === 0) {
          indicator.style.transform = 'translateY(-100%)';
        }
      }, false);
      
      document.addEventListener('touchmove', function(e) {
        if (window.scrollY > 0 || isRefreshing) return;
        
        const currentY = e.touches[0].clientY;
        const diff = currentY - touchStartY;
        
        if (diff > 0) {
          e.preventDefault();
          const showAmount = Math.min(diff * 0.5, 60);
          indicator.style.transform = `translateY(-${100 - showAmount}%)`;
        }
      }, { passive: false });
      
      document.addEventListener('touchend', function(e) {
        if (window.scrollY > 0 || isRefreshing) return;
        
        touchEndY = e.changedTouches[0].clientY;
        const diff = touchEndY - touchStartY;
        
        if (diff > minSwipeDistance) {
          isRefreshing = true;
          indicator.textContent = 'Aktualisiere...';
          indicator.style.transform = 'translateY(0)';
          setTimeout(() => {
            window.location.reload();
          }, 1000);
        } else {
          indicator.style.transform = 'translateY(-100%)';
        }
      }, false);
    });
  </script>


<?php include 'modalOverlay.php'; ?>
</body>
</html>
