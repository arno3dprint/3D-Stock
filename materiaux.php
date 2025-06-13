<?php session_start(); if (!isset($_SESSION['username'])) header('Location: connexion.php'); ?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Acceuil</title>
  <link rel="stylesheet" href="style.css">
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
        </ul>
      </nav>
    </div>
  </header>

  <main class="container">
    <section>
      <h2>Bienvenue sur notre site</h2>
      <p>Nous proposons des solutions professionnelles adaptées à vos besoins. Découvrez nos services et contactez-nous pour en savoir plus.</p>
    </section>
  </main>

  <footer>
    <div class="container">
      <p>&copy; 2025 Nom de l'entreprise — Tous droits réservés</p>
    </div>
  </footer>
</body>
</html>
