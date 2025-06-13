<?php 
session_start(); if (!isset($_SESSION['username'])) header('Location: connexion.php');

session_start();

define('DATA_FILE', 'projets.json');

function lireProjets() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function sauvegarderProjets($projets) {
    file_put_contents(DATA_FILE, json_encode($projets, JSON_PRETTY_PRINT));
}

$message = '';
$projets = lireProjets();

// Gestion suppression (GET avec action=supprimer&id=...)
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'supprimer') {
    $id = (int)$_GET['id'];
    if (isset($projets[$id])) {
        unset($projets[$id]);
        // Re-indexer le tableau
        $projets = array_values($projets);
        sauvegarderProjets($projets);
        $message = "Projet supprimé avec succès.";
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?message=" . urlencode($message));
        exit;
    }
}

// Variables pour edition
$editMode = false;
$editId = null;
$editProjet = null;

// Chargement projet à éditer (GET avec action=edit&id=...)
if (isset($_GET['action'], $_GET['id']) && $_GET['action'] === 'edit') {
    $editId = (int)$_GET['id'];
    if (isset($projets[$editId])) {
        $editProjet = $projets[$editId];
        $editMode = true;
    }
}

// Gestion ajout ou modification (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Récupérer données
    $nom = trim($_POST['nom'] ?? '');
    $client = trim($_POST['client'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $type = trim($_POST['type'] ?? '');
    $statut = trim($_POST['statut'] ?? '');
    $filament = trim($_POST['filament'] ?? '');
    $poids = floatval($_POST['poids'] ?? 0);
    $imprimante = trim($_POST['imprimante'] ?? '');
    $date_debut = trim($_POST['date_debut'] ?? '');
    $date_fin = trim($_POST['date_fin'] ?? '');
    $fichier = trim($_POST['fichier'] ?? '');

    $id = isset($_POST['id']) ? (int)$_POST['id'] : null;

    if ($nom === '') {
        $message = "Le nom du projet est obligatoire.";
    } else {
        if ($id !== null && isset($projets[$id])) {
            // Modification
            $projets[$id] = [
                'nom' => $nom,
                'client' => $client,
                'telephone' => $telephone,
                'type' => $type,
                'statut' => $statut,
                'filament' => $filament,
                'poids' => $poids,
                'imprimante' => $imprimante,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'fichier' => $fichier,
            ];
            $message = "Projet modifié avec succès.";
        } else {
            // Ajout
            $projets[] = [
                'nom' => $nom,
                'client' => $client,
                'telephone' => $telephone,
                'type' => $type,
                'statut' => $statut,
                'filament' => $filament,
                'poids' => $poids,
                'imprimante' => $imprimante,
                'date_debut' => $date_debut,
                'date_fin' => $date_fin,
                'fichier' => $fichier,
            ];
            $message = "Projet ajouté avec succès.";
        }
        sauvegarderProjets($projets);
        header("Location: " . strtok($_SERVER['REQUEST_URI'], '?') . "?message=" . urlencode($message));
        exit;
    }
}

// Message via URL après redirection
if (isset($_GET['message'])) {
    $message = $_GET['message'];
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Projet - Arno-3d_print</title>
  <link rel="stylesheet" href="style.css" />
  <style>
    .header-user {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 10px 20px;
      background: #007bff;
      color: white;
    }
    .user-info {
      display: flex;
      align-items: center;
      gap: 15px;
    }
    .logout-btn {
      background: #dc3545;
      color: white;
      padding: 8px 15px;
      text-decoration: none;
      border-radius: 4px;
      font-size: 14px;
    }
    .logout-btn:hover {
      background: #c82333;
    }
    form {
      background: #fff;
      padding: 15px;
      border-radius: 5px;
      box-shadow: 0 0 5px #ccc;
      margin-bottom: 30px;
    }
    label {
      display: block;
      margin-top: 10px;
      font-weight: bold;
    }
    input[type=text], input[type=date], input[type=number], select {
      width: 100%;
      padding: 7px;
      margin-top: 5px;
      box-sizing: border-box;
      border: 1px solid #ccc;
      border-radius: 3px;
    }
    input[type=submit] {
      margin-top: 15px;
      padding: 10px 20px;
      background: #007bff;
      color: white;
      border: none;
      border-radius: 3px;
      cursor: pointer;
    }
    input[type=submit]:hover {
      background: #0056b3;
    }
    table {
      border-collapse: collapse;
      width: 100%;
      background: white;
    }
    th, td {
      border: 1px solid #ccc;
      padding: 8px;
      text-align: left;
    }
    th {
      background: #007bff;
      color: white;
    }
    .message {
      margin-bottom: 20px;
      padding: 10px;
      background: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
      border-radius: 4px;
    }
    .actions a {
      margin-right: 10px;
      color: #007bff;
      text-decoration: none;
    }
    .actions a:hover {
      text-decoration: underline;
    }
  </style>
  <script>
    function confirmDelete(nomProjet) {
      return confirm('Voulez-vous vraiment supprimer le projet "' + nomProjet + '" ?');
    }
  </script>
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
          <li><a href="projet.php" class="active">Projet</a></li>
        </ul>
      </nav>
    </div>
  </header>

  <main class="container">
    <section>
      <h2><?= $editMode ? 'Modifier le projet' : 'Ajouter un nouveau projet' ?></h2>

      <?php if ($message): ?>
        <div class="message"><?= htmlspecialchars($message) ?></div>
      <?php endif; ?>

      <form method="POST" action="">
        <input type="hidden" name="id" value="<?= $editMode ? (int)$editId : '' ?>" />

        <label for="nom">Nom du projet *</label>
        <input type="text" name="nom" id="nom" required value="<?= htmlspecialchars($editProjet['nom'] ?? '') ?>" />

        <label for="client">Client (optionnel)</label>
        <input type="text" name="client" id="client" value="<?= htmlspecialchars($editProjet['client'] ?? '') ?>" />

        <label for="telephone">Numéro de téléphone du client</label>
        <input type="text" name="telephone" id="telephone" placeholder="ex : 06 12 34 56 78" value="<?= htmlspecialchars($editProjet['telephone'] ?? '') ?>" />

        <label for="type">Type d'impression</label>
        <input type="text" name="type" id="type" placeholder="ex : prototype, pièce, déco…" value="<?= htmlspecialchars($editProjet['type'] ?? '') ?>" />

        <label for="statut">Statut</label>
        <select name="statut" id="statut">
          <?php
          $statuts = ['en attente', 'en cours', 'terminé'];
          $currentStatut = $editProjet['statut'] ?? '';
          foreach ($statuts as $s) {
              $sel = ($s === $currentStatut) ? 'selected' : '';
              echo "<option value=\"$s\" $sel>" . ucfirst($s) . "</option>";
          }
          ?>
        </select>

        <label for="filament">Filament utilisé</label>
        <input type="text" name="filament" id="filament" placeholder="ex : PLA Blanc 1.75mm" value="<?= htmlspecialchars($editProjet['filament'] ?? '') ?>" />

        <label for="poids">Poids estimé (grammes)</label>
        <input type="number" name="poids" id="poids" step="0.1" value="<?= htmlspecialchars($editProjet['poids'] ?? '') ?>" />

        <label for="imprimante">Imprimante utilisée</label>
        <input type="text" name="imprimante" id="imprimante" placeholder="ex : Creality Ender 3" value="<?= htmlspecialchars($editProjet['imprimante'] ?? '') ?>" />

        <label for="date_debut">Date de début</label>
        <input type="date" name="date_debut" id="date_debut" value="<?= htmlspecialchars($editProjet['date_debut'] ?? '') ?>" />

        <label for="date_fin">Date de fin</label>
        <input type="date" name="date_fin" id="date_fin" value="<?= htmlspecialchars($editProjet['date_fin'] ?? '') ?>" />

        <label for="fichier">Nom du fichier STL</label>
        <input type="text" name="fichier" id="fichier" placeholder="ex : projet1.stl" value="<?= htmlspecialchars($editProjet['fichier'] ?? '') ?>" />

        <input type="submit" value="<?= $editMode ? 'Modifier le projet' : 'Ajouter le projet' ?>" />
        <?php if ($editMode): ?>
          <a href="projet.php" style="margin-left: 10px;">Annuler</a>
        <?php endif; ?>
      </form>
    </section>

    <section>
      <h2>Liste des projets</h2>
      <?php if (count($projets) === 0): ?>
        <p>Aucun projet enregistré pour le moment.</p>
      <?php else: ?>
        <table>
          <thead>
            <tr>
              <th>Nom</th>
              <th>Client</th>
              <th>Téléphone</th>
              <th>Type</th>
              <th>Statut</th>
              <th>Filament</th>
              <th>Poids (g)</th>
              <th>Imprimante</th>
              <th>Début</th>
              <th>Fin</th>
              <th>Fichier STL</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($projets as $index => $p): ?>
              <tr>
                <td><?= htmlspecialchars($p['nom']) ?></td>
                <td><?= htmlspecialchars($p['client']) ?></td>
                <td><?= htmlspecialchars($p['telephone']) ?></td>
                <td><?= htmlspecialchars($p['type']) ?></td>
                <td><?= htmlspecialchars($p['statut']) ?></td>
                <td><?= htmlspecialchars($p['filament']) ?></td>
                <td><?= htmlspecialchars($p['poids']) ?></td>
                <td><?= htmlspecialchars($p['imprimante']) ?></td>
                <td><?= htmlspecialchars($p['date_debut']) ?></td>
                <td><?= htmlspecialchars($p['date_fin']) ?></td>
                <td><?= htmlspecialchars($p['fichier']) ?></td>
                <td class="actions">
                  <a href="?action=edit&id=<?= $index ?>">Modifier</a>
                  <a href="?action=supprimer&id=<?= $index ?>" onclick="return confirmDelete('<?= htmlspecialchars(addslashes($p['nom'])) ?>')">Supprimer</a>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>