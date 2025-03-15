<?php
// pull-to-refresh.php
?>
<!-- PullToRefresh Bibliothek einbinden -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pulltorefreshjs/0.1.22/pulltorefresh.min.js"></script>

<!-- Pull-to-Refresh Initialisierung -->
<script>
  document.addEventListener('DOMContentLoaded', function() {
    // Initialisieren Sie PullToRefresh erst nach dem Laden der Seite
    PullToRefresh.init({
      mainElement: 'body',
      onRefresh() {
        // Neu laden der Seite
        window.location.reload();
      },
      distThreshold: 60, // Minimum Distanz, bevor Refresh ausgelöst wird
      iconArrow: '&#8595;', // Pfeil nach unten
      iconRefreshing: '&#8635;', // Kreissymbol
      instructionsPullToRefresh: 'Ziehen zum Aktualisieren',
      instructionsReleaseToRefresh: 'Loslassen zum Aktualisieren',
      instructionsRefreshing: 'Aktualisiere...'
    });
  });
</script>

<!-- Styles für Pull-to-Refresh -->
<style>
  .ptr-element {
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    color: #fff;
    z-index: 10;
    text-align: center;
    height: 50px;
    transition: all .25s ease;
  }
  .ptr-refresh {
    background-color: #28a745;
  }
  .ptr-pull {
    background-color: #007bff;
  }
  .ptr-release {
    background-color: #17a2b8;
  }
</style>