<!-- VolumeChart.php – Keine header() Aufrufe und HTML-Struktur, nur der relevante Inhalt -->
<div class="volume-container">
  <div class="volume-box" id="binanceVolumeBox" style="background: #3498db; color: #fff;">
    Binance Volumen: --
  </div>
  <div class="volume-box" id="coinbaseVolumeBox" style="background: #e74c3c; color: #fff;">
    Coinbase Volumen: --
  </div>
  <div class="volume-box" id="krakenVolumeBox" style="background: #2ecc71; color: #fff;">
    Kraken Volumen: --
  </div>
</div>
<div id="volumeChartContainer"></div>

<script>
  // Arrays für die 24h-Volumendaten (alle 15 Minuten)
  let timeLabels24 = [];
  let binanceVolumes24 = [];
  let coinbaseVolumes24 = [];
  let krakenVolumes24 = [];

  // Initialisiere den Chart – ein Liniendiagramm
  let volumeChartOptions = {
    series: [
      { name: 'Binance', data: [] },
      { name: 'Coinbase', data: [] },
      { name: 'Kraken', data: [] }
    ],
    chart: {
      type: 'line',
      height: 300,
      animations: { enabled: true }
    },
    colors: ['#3498db', '#e74c3c', '#2ecc71'],
    xaxis: { categories: [], labels: { rotate: 0 } },
    yaxis: { title: { text: 'BTC Volumen' } },
    tooltip: { y: { formatter: function(val) { return val; } } }
  };

  let volumeChart = new ApexCharts(document.querySelector("#volumeChartContainer"), volumeChartOptions);
  volumeChart.render();

  // Funktion: Abrufen der 24h-Volumendaten (alle 15 Minuten)
  function fetch24hVolumeData() {
    fetch('/backend/api/get24hVolumeData.php')
      .then(response => {
        if (!response.ok) throw new Error('Fehler beim Abrufen der 24h Volumendaten');
        return response.json();
      })
      .then(data => {
        // Erwartete Datenstruktur: { times: [...], binanceVolumes: [...], coinbaseVolumes: [...], krakenVolumes: [...] }
        timeLabels24 = data.times || [];
        binanceVolumes24 = data.binanceVolumes || [];
        coinbaseVolumes24 = data.coinbaseVolumes || [];
        krakenVolumes24 = data.krakenVolumes || [];

        // Aktualisiere den Chart
        volumeChart.updateOptions({
          xaxis: { categories: timeLabels24 }
        });
        volumeChart.updateSeries([
          { name: 'Binance', data: binanceVolumes24 },
          { name: 'Coinbase', data: coinbaseVolumes24 },
          { name: 'Kraken', data: krakenVolumes24 }
        ]);

        // Aktualisiere die Volumenboxen mit dem letzten (aktuellsten) Wert
        let latestBinance = binanceVolumes24[binanceVolumes24.length - 1] || '--';
        let latestCoinbase = coinbaseVolumes24[coinbaseVolumes24.length - 1] || '--';
        let latestKraken = krakenVolumes24[krakenVolumes24.length - 1] || '--';

        document.getElementById('binanceVolumeBox').textContent = "Binance Volumen: " + (latestBinance !== '--' ? latestBinance.toLocaleString('de-DE') : '--');
        document.getElementById('coinbaseVolumeBox').textContent = "Coinbase Volumen: " + (latestCoinbase !== '--' ? latestCoinbase.toLocaleString('de-DE') : '--');
        document.getElementById('krakenVolumeBox').textContent = "Kraken Volumen: " + (latestKraken !== '--' ? latestKraken.toLocaleString('de-DE') : '--');
      })
      .catch(err => {
        console.error(err);
      });
  }

  // Initialer Abruf und danach alle 15 Minuten (900000 ms) aktualisieren
  fetch24hVolumeData();
  setInterval(fetch24hVolumeData, 900000);
</script>