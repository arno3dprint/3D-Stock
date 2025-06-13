<?php
session_start();
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Accueil</title>

  <!-- PWA -->
  <link rel="manifest" href="/manifest.json">
  <meta name="theme-color" content="#0d47a1">
  <link rel="apple-touch-icon" href="/icons/icon-192x192.png">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

  <link rel="stylesheet" href="style.css" />
</head>
<body>
  <header>
    <div class="menu">
      <h1>Arno-3d_print</h1>
      <nav>
        <ul>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="filament.php">Filament</a></li>
          <li><a href="materiaux.php">Matériaux</a></li>
          <li><a href="projet.php">Projet</a></li>
          <li><a href="deconnexion.php">Déconnexion</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Bienvenue sur notre site</h2>
      <p>Nous proposons des solutions professionnelles adaptées à vos besoins. Découvrez nos services et contactez-nous pour en savoir plus.</p>
    </section>

    <!-- Bouton PWA -->
    <div style="margin-top: 20px;">
      <button id="installBtn" style="display: none;">📲 Installer l’application</button>
      <p id="iosHint" style="display: none;">
        Pour installer sur iPhone : appuie sur <strong>Partager</strong> puis <strong>“Sur l’écran d’accueil”</strong>.
      </p>
    </div>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Nom de l'entreprise — Tous droits réservés</p>
    </div>
  </footer>

  <!-- Scripts -->
  <script>
    // Enregistrement du service worker
    if ('serviceWorker' in navigator) {
      navigator.serviceWorker.register('/service-worker.js')
        .then(reg => console.log('✅ Service Worker enregistré'))
        .catch(err => console.error('❌ Erreur Service Worker', err));
    }

    // Détection iOS
    const isIOS = /iphone|ipad|ipod/i.test(navigator.userAgent);
    const isStandalone = window.navigator.standalone === true;
    if (isIOS && !isStandalone) {
      document.getElementById('iosHint').style.display = 'block';
    }

    // Bouton installation
    let deferredPrompt;
    const installBtn = document.getElementById('installBtn');

    window.addEventListener('beforeinstallprompt', (e) => {
      e.preventDefault();
      deferredPrompt = e;
      installBtn.style.display = 'block';
    });

    installBtn.addEventListener('click', async () => {
      if (deferredPrompt) {
        deferredPrompt.prompt();
        const choix = await deferredPrompt.userChoice;
        if (choix.outcome === 'accepted') {
          console.log("✅ Appli installée !");
        } else {
          console.log("❌ Installation refusée");
        }
        deferredPrompt = null;
        installBtn.style.display = 'none';
      }
    });
  </script>
</body>
</html>
