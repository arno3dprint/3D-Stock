<?php
session_start();if (!isset($_SESSION['username'])) header('Location: connexion.php');


if (isset($_POST['color'])) {
    $_SESSION['backgroundColor'] = $_POST['color'];
}

$backgroundColor = isset($_SESSION['backgroundColor']) ? $_SESSION['backgroundColor'] : 'white';

include_once __DIR__ . '/phpqrcode/qrlib.php';

// Configuration
define('MAX_POIDS_DISPLAY', 1000);
define('QR_SIZE', 3);
define('DATA_FILE', 'filament.json');

/**
 * Lit les filaments depuis le fichier JSON
 */
function lireFilaments() {
    if (!file_exists(DATA_FILE)) {
        return [];
    }
    $json = file_get_contents(DATA_FILE);
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

/**
 * Sauvegarde les filaments dans le fichier JSON
 */
function sauvegarderFilaments($filaments) {
    $result = file_put_contents(DATA_FILE, json_encode($filaments, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    if ($result === false) {
        error_log("Erreur lors de la sauvegarde des filaments dans " . DATA_FILE);
        return false;
    }
    return true;
}

/**
 * Initialise les filaments : charge depuis JSON ou utilise la session
 */
function initialiserFilaments() {
    // Charger depuis le fichier JSON
    $filamentsJson = lireFilaments();
    
    // Si pas de session, utiliser les données JSON
    if (!isset($_SESSION['filaments'])) {
        $_SESSION['filaments'] = $filamentsJson;
    }
    // Si session vide mais JSON existe, charger depuis JSON
    elseif (empty($_SESSION['filaments']) && !empty($filamentsJson)) {
        $_SESSION['filaments'] = $filamentsJson;
    }
    
    return $_SESSION['filaments'];
}

/**
 * Synchronise la session avec le fichier JSON
 */
function synchroniserDonnees() {
    return sauvegarderFilaments($_SESSION['filaments']);
}

// Initialisation
$filaments = initialiserFilaments();

/**
 * Génère un QR code en base64
 * @param string $text Texte à encoder
 * @param int $size Taille du QR code
 * @return string QR code en base64
 */
function generateQRCodeBase64($text, $size = QR_SIZE) {
    try {
        ob_start();
        QRcode::png($text, null, QR_ECLEVEL_L, $size);
        $imageString = ob_get_contents();
        ob_end_clean();
        return 'data:image/png;base64,' . base64_encode($imageString);
    } catch (Exception $e) {
        error_log("Erreur génération QR code: " . $e->getMessage());
        return '';
    }
}

/**
 * Valide les données d'un filament
 * @param array $data Données à valider
 * @return array Erreurs de validation
 */
function validerFilament($data) {
    $erreurs = [];
    
    if (empty(trim($data['type'] ?? ''))) {
        $erreurs[] = "Le type de filament est requis";
    }
    
    if (empty(trim($data['marque'] ?? ''))) {
        $erreurs[] = "La marque est requise";
    }
    
    $poids = floatval($data['poids'] ?? 0);
    if ($poids <= 0) {
        $erreurs[] = "Le poids doit être supérieur à 0";
    }
    
    $prix = floatval($data['prix'] ?? 0);
    if ($prix <= 0) {
        $erreurs[] = "Le prix doit être supérieur à 0";
    }
    
    return $erreurs;
}

/**
 * Filtre les filaments selon les critères de recherche
 */
function filtrerFilaments($filaments, $criteres) {
    $result = [];
    foreach ($filaments as $id => $filament) {
        // Filtres texte (insensible à la casse)
        if (!empty($criteres['type']) && stripos($filament['type'], $criteres['type']) === false) continue;
        if (!empty($criteres['marque']) && stripos($filament['marque'], $criteres['marque']) === false) continue;
        if (!empty($criteres['couleur']) && stripos($filament['couleur'] ?? '', $criteres['couleur']) === false) continue;
        
        // Filtres numériques
        if ($criteres['poidsMin'] !== null && $filament['poids'] < $criteres['poidsMin']) continue;
        if ($criteres['poidsMax'] !== null && $filament['poids'] > $criteres['poidsMax']) continue;
        if ($criteres['prixMin'] !== null && $filament['prix'] < $criteres['prixMin']) continue;
        if ($criteres['prixMax'] !== null && $filament['prix'] > $criteres['prixMax']) continue;

        $result[$id] = $filament;
    }
    return $result;
}

// Variables pour les messages
$message = '';
$messageType = '';

// Traitement suppression
if (isset($_POST['supprimer_id'])) {
    $supprimerId = $_POST['supprimer_id'];
    if (isset($_SESSION['filaments'][$supprimerId])) {
        unset($_SESSION['filaments'][$supprimerId]);
        
        // Sauvegarder dans le fichier JSON
        if (synchroniserDonnees()) {
            $message = "Filament supprimé avec succès";
            $messageType = "success";
        } else {
            $message = "Filament supprimé mais erreur de sauvegarde";
            $messageType = "warning";
        }
    } else {
        $message = "Filament non trouvé";
        $messageType = "error";
    }
    header("Location: filament.php" . ($message ? "?msg=" . urlencode($message) . "&type=" . $messageType : ""));
    exit;
}

// Traitement modification
if (isset($_POST['modifier_id'])) {
    $id = $_POST['modifier_id'];
    if (isset($_SESSION['filaments'][$id])) {
        $erreurs = validerFilament($_POST);
        if (empty($erreurs)) {
            $_SESSION['filaments'][$id] = [
                'type' => htmlspecialchars(trim($_POST['type'])),
                'marque' => htmlspecialchars(trim($_POST['marque'])),
                'poids' => floatval($_POST['poids']),
                'prix' => floatval($_POST['prix']),
                'diametre' => 1.75, // Diamètre fixe
                'couleur' => htmlspecialchars(trim($_POST['couleur'] ?? '')),
                'date_modification' => date('Y-m-d H:i:s'),
                // Conserver la date d'ajout si elle existe
                'date_ajout' => $_SESSION['filaments'][$id]['date_ajout'] ?? date('Y-m-d H:i:s')
            ];
            
            // Sauvegarder dans le fichier JSON
            if (synchroniserDonnees()) {
                $message = "Filament modifié avec succès";
                $messageType = "success";
            } else {
                $message = "Filament modifié mais erreur de sauvegarde";
                $messageType = "warning";
            }
        } else {
            $message = implode(", ", $erreurs);
            $messageType = "error";
        }
    } else {
        $message = "Filament non trouvé";
        $messageType = "error";
    }
    header("Location: filament.php" . ($message ? "?msg=" . urlencode($message) . "&type=" . $messageType : ""));
    exit;
}

// Traitement ajout
if (isset($_POST['ajouter'])) {
    $erreurs = validerFilament($_POST);
    if (empty($erreurs)) {
        $id = uniqid('filament_', true);
        $_SESSION['filaments'][$id] = [
            'type' => htmlspecialchars(trim($_POST['type'])),
            'marque' => htmlspecialchars(trim($_POST['marque'])),
            'poids' => floatval($_POST['poids']),
            'prix' => floatval($_POST['prix']),
            'diametre' => 1.75, // Diamètre fixe
            'couleur' => htmlspecialchars(trim($_POST['couleur'] ?? '')),
            'date_ajout' => date('Y-m-d H:i:s')
        ];
        
        // Sauvegarder dans le fichier JSON
        if (synchroniserDonnees()) {
            $message = "Filament ajouté avec succès";
            $messageType = "success";
        } else {
            $message = "Filament ajouté mais erreur de sauvegarde";
            $messageType = "warning";
        }
    } else {
        $message = implode(", ", $erreurs);
        $messageType = "error";
    }
    header("Location: filament.php" . ($message ? "?msg=" . urlencode($message) . "&type=" . $messageType : ""));
    exit;
}

// Récupération des messages
if (isset($_GET['msg'])) {
    $message = $_GET['msg'];
    $messageType = $_GET['type'] ?? 'info';
}

// Traitement recherche avec nettoyage des données
$criteres = [
    'type' => trim($_GET['search_type'] ?? ''),
    'marque' => trim($_GET['search_marque'] ?? ''),
    'poidsMin' => !empty($_GET['search_poids_min']) ? floatval($_GET['search_poids_min']) : null,
    'poidsMax' => !empty($_GET['search_poids_max']) ? floatval($_GET['search_poids_max']) : null,
    'prixMin' => !empty($_GET['search_prix_min']) ? floatval($_GET['search_prix_min']) : null,
    'prixMax' => !empty($_GET['search_prix_max']) ? floatval($_GET['search_prix_max']) : null,
    'couleur' => trim($_GET['search_couleur'] ?? '')
];

// Filtrage des filaments
$filamentsAffiches = filtrerFilaments($_SESSION['filaments'], $criteres);

// Statistiques
$nbTotal = count($_SESSION['filaments']);
$nbAffiches = count($filamentsAffiches);
$poidsTotal = array_sum(array_column($_SESSION['filaments'], 'poids'));
$valeurTotale = array_sum(array_map(function($f) { return $f['poids'] * $f['prix'] / 1000; }, $_SESSION['filaments']));
?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Gestion Filament - Arno-3d_print</title>
  <link rel="stylesheet" href="style.css" />
</head>
<style>
    /* Messages d'alerte */
    .alert {
      padding: 10px 15px;
      margin: 10px 0;
      border-radius: 4px;
      position: relative;
    }
    .alert.success {
      background-color: #d4edda;
      color: #155724;
      border: 1px solid #c3e6cb;
    }
    .alert.error {
      background-color: #f8d7da;
      color: #721c24;
      border: 1px solid #f5c6cb;
    }
    .alert.info {
      background-color: #d1ecf1;
      color: #0c5460;
      border: 1px solid #bee5eb;
    }
    
    /* Statistiques */
    .stats {
      display: flex;
      gap: 20px;
      margin-bottom: 20px;
      flex-wrap: wrap;
    }
    .stat-item {
      background: #f8f9fa;
      padding: 15px;
      border-radius: 6px;
      text-align: center;
      flex: 1;
      min-width: 150px;
    }
    .stat-number {
      font-size: 1.5em;
      font-weight: bold;
      color: #007bff;
    }
    .stat-label {
      font-size: 0.9em;
      color: #666;
    }

    /* Styles existants améliorés */
    .modal {
      display: none;
      position: fixed;
      z-index: 1000;
      padding-top: 60px;
      left: 0; top: 0; width: 100%; height: 100%;
      overflow: auto;
      background-color: rgba(0,0,0,0.6);
    }
    .modal-content {
      background-color: #fefefe;
      margin: auto;
      padding: 20px;
      border-radius: 8px;
      width: 90%;
      max-width: 500px;
      position: relative;
      box-shadow: 0 4px 6px rgba(0,0,0,0.1);
    }
    .close-btn {
      color: #aaa;
      position: absolute;
      top: 10px;
      right: 15px;
      font-size: 28px;
      font-weight: bold;
      cursor: pointer;
      transition: color 0.3s;
    }
    .close-btn:hover {
      color: #000;
    }

    /* Formulaires améliorés */
    .form-group {
      margin-bottom: 15px;
    }
    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: bold;
    }
    .form-group input, .form-group select {
      width: 100%;
      padding: 8px;
      border: 1px solid #ddd;
      border-radius: 4px;
      font-size: 14px;
    }
    .form-group input:focus {
      outline: none;
      border-color: #007bff;
      box-shadow: 0 0 0 2px rgba(0,123,255,0.25);
    }

    /* Styles recherche améliorés */
    .form-recherche {
      background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
      border: none;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Cartes améliorées */
    .card {
      transition: transform 0.2s, box-shadow 0.2s;
    }
    .card:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }

    /* QR Code */
    .qr-code {
      cursor: pointer;
      transition: transform 0.2s;
    }
    .qr-code:hover {
      transform: scale(1.05);
    }

    /* Boutons améliorés */
    .btn {
      margin-top: 0.5rem;
      padding: 0.4rem 0.8rem;
      cursor: pointer;
      border: none;
      border-radius: 4px;
      font-size: 14px;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
    }
    .btn:hover {
      transform: translateY(-1px);
      box-shadow: 0 2px 4px rgba(0,0,0,0.2);
    }
    .edit-btn {
      background-color: #28a745;
      color: white;
      margin-left: 5px;
    }
    .edit-btn:hover {
      background-color: #218838;
    }
    .delete-btn {
      background-color: #dc3545;
      color: white;
    }
    .delete-btn:hover {
      background-color: #c82333;
    }

    /* Progress bar améliorée */
    .progress-bar {
      width: 100%;
      height: 20px;
      background-color: #e9ecef;
      border-radius: 10px;
      overflow: hidden;
      margin: 10px 0;
    }
    .progress {
      height: 100%;
      background: linear-gradient(90deg, #28a745 0%, #ffc107 70%, #dc3545 100%);
      transition: width 0.3s ease;
      border-radius: 10px;
    }

    /* Responsive */
    @media (max-width: 768px) {
      .stats {
        flex-direction: column;
      }
      .form-recherche {
        flex-direction: column;
        align-items: stretch;
      }
      .form-recherche input {
        width: 100%;
        margin-bottom: 10px;
      }
    }
  </style>
<body>
  <header>
    <div class="menu">
      <h1>Arno-3d_print</h1>
      <nav>
        <ul>
          <li><a href="index.php">Accueil</a></li>
          <li><a href="filament.php" class="active">Filament</a></li>
          <li><a href="materiaux.php">Matériaux</a></li>
          <li><a href="projet.php">Projet</a></li>

        </ul>
      </nav>
    </div>
  </header>

  <main class="container">
    <h2>Gestion des filaments</h2>

    <!-- Messages -->
    <?php if ($message): ?>
      <div class="alert <?= $messageType ?>">
        <?= htmlspecialchars($message) ?>
      </div>
    <?php endif; ?>

    <!-- Statistiques -->
    <div class="stats">
      <div class="stat-item">
        <div class="stat-number"><?= $nbTotal ?></div>
        <div class="stat-label">Filaments total</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= $nbAffiches ?></div>
        <div class="stat-label">Affichés</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= number_format($poidsTotal, 2) ?>g</div>
        <div class="stat-label">Poids total</div>
      </div>
      <div class="stat-item">
        <div class="stat-number"><?= number_format($valeurTotale, 2) ?>€</div>
        <div class="stat-label">Valeur totale</div>
      </div>
    </div>

    <!-- Formulaire recherche -->
    <form method="GET" class="form-recherche">
      <input type="text" name="search_type" placeholder="Type" value="<?= htmlspecialchars($criteres['type']) ?>" />
      <input type="text" name="search_marque" placeholder="Marque" value="<?= htmlspecialchars($criteres['marque']) ?>" />
      <input type="text" name="search_couleur" placeholder="Couleur" value="<?= htmlspecialchars($criteres['couleur']) ?>" />
      <input type="number" step="0.01" name="search_poids_min" placeholder="Poids min (g)" value="<?= $criteres['poidsMin'] !== null ? $criteres['poidsMin'] : '' ?>" />
      <input type="number" step="0.01" name="search_poids_max" placeholder="Poids max (g)" value="<?= $criteres['poidsMax'] !== null ? $criteres['poidsMax'] : '' ?>" />
      <input type="number" step="0.01" name="search_prix_min" placeholder="Prix min (€)" value="<?= $criteres['prixMin'] !== null ? $criteres['prixMin'] : '' ?>" />
      <input type="number" step="0.01" name="search_prix_max" placeholder="Prix max (€)" value="<?= $criteres['prixMax'] !== null ? $criteres['prixMax'] : '' ?>" />
      <button type="submit">Rechercher</button>
      <a href="filament.php" class="btn" style="background-color: #6c757d; color: white;">Réinitialiser</a>
    </form>

    <!-- Formulaire ajout -->
    <button onclick="openAddModal()" class="btn" style="background-color: #007bff; color: white; margin-bottom: 20px;">Ajouter un filament</button>

    <!-- Liste des filaments -->
    <div class="card-container">
      <?php if (empty($filamentsAffiches)): ?>
        <p>Aucun filament trouvé avec ces critères.</p>
      <?php else: ?>
        <?php foreach ($filamentsAffiches as $id => $filament): ?>
          <?php
            $qrText = "Filament: {$filament['type']} - {$filament['marque']}\n" .
                      "Poids: {$filament['poids']}g\n" .
                      "Prix: {$filament['prix']}€\n" .
                      "Diamètre: 1.75mm";
            if (!empty($filament['couleur'])) {
              $qrText .= "\nCouleur: {$filament['couleur']}";
            }
            $qrCodeBase64 = generateQRCodeBase64($qrText);

            $poidsPercent = min(100, ($filament['poids'] / MAX_POIDS_DISPLAY) * 100);
          ?>
          <div class="card">
            <div class="card-header">
              <?= htmlspecialchars($filament['type'] . " - " . $filament['marque']) ?>
              <?php if (!empty($filament['couleur'])): ?>
                <span style="color: #666; font-size: 0.9em;">(<?= htmlspecialchars($filament['couleur']) ?>)</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <p><strong>Poids :</strong> <?= $filament['poids'] ?>g</p>
              <p><strong>Prix :</strong> <?= $filament['prix'] ?>€</p>
              <p><strong>Diamètre :</strong> 1.75mm</p>
              
              <!-- Progress bar poids -->
              <div class="progress-bar" title="<?= $poidsPercent ?>% de <?= MAX_POIDS_DISPLAY ?>g">
                <div class="progress" style="width: <?= $poidsPercent ?>%;"></div>
              </div>

              <!-- QR Code cliquable -->
              <?php if ($qrCodeBase64): ?>
                <img src="<?= $qrCodeBase64 ?>" alt="QR Code filament" 
                     class="qr-code"
                     style="margin-top: 10px; width: 120px; height: 120px;"
                     onclick="openQRModal('<?= addslashes($qrCodeBase64) ?>')" />
              <?php endif; ?>

              <div style="margin-top: 10px;">
                <button class="edit-btn btn" onclick="openEditModal('<?= $id ?>')">Modifier</button>
                <button class="delete-btn btn" onclick="confirmDelete('<?= $id ?>', '<?= addslashes($filament['type'] . ' - ' . $filament['marque']) ?>')">Supprimer</button>
              </div>
            </div>
          </div>

          <!-- Modale Edit -->
          <div id="editModal-<?= $id ?>" class="modal">
            <div class="modal-content">
              <span class="close-btn" onclick="closeEditModal('<?= $id ?>')">&times;</span>
              <h3>Modifier le filament</h3>
              <form method="POST">
                <input type="hidden" name="modifier_id" value="<?= $id ?>" />
                
                <div class="form-group">
                  <label>Type de filament :</label>
                  <input type="text" name="type" required value="<?= htmlspecialchars($filament['type']) ?>" />
                </div>
                
                <div class="form-group">
                  <label>Marque :</label>
                  <input type="text" name="marque" required value="<?= htmlspecialchars($filament['marque']) ?>" />
                </div>
                
                <div class="form-group">
                  <label>Couleur :</label>
                  <input type="text" name="couleur" value="<?= htmlspecialchars($filament['couleur'] ?? '') ?>" />
                </div>
                
                <div class="form-group">
                  <label>Poids (g) :</label>
                  <input type="number" step="0.01" name="poids" required value="<?= $filament['poids'] ?>" />
                </div>
                
                <div class="form-group">
                  <label>Prix (€) :</label>
                  <input type="number" step="0.01" name="prix" required value="<?= $filament['prix'] ?>" />
                </div>
                
                <button type="submit" class="btn" style="background-color: #28a745; color: white;">Enregistrer</button>
                <button type="button" onclick="closeEditModal('<?= $id ?>')" class="btn" style="background-color: #6c757d; color: white;">Annuler</button>
              </form>
            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>

  <!-- Modale Ajout -->
  <div id="addModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeAddModal()">&times;</span>
      <h3>Ajouter un nouveau filament</h3>
      <form method="POST">
        <div class="form-group">
          <label>Type de filament :</label>
          <select name="type" required>
            <option value="">-- Sélectionner un type --</option>
            <option value="PLA" selected>PLA</option>
            <option value="ABS">ABS</option>
            <option value="PETG">PETG</option>
            <option value="TPU">TPU</option>
            <option value="PC">PC (Polycarbonate)</option>
            <option value="Nylon">Nylon</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Marque :</label>
          <select name="marque" required>
            <option value="">-- Sélectionner une marque --</option>
            <option value="GST 3D" selected>GST 3D</option>
            <option value="Sunlu">Sunlu</option>
            <option value="Autre">Autre</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Couleur :</label>
          <select name="couleur">
            <option value="">-- Sélectionner une couleur --</option>
            <option value="Blanc" selected>Blanc</option>
            <option value="Noir">Noir</option>
            <option value="Rouge">Rouge</option>
            <option value="Bleu">Bleu</option>
            <option value="Vert">Vert</option>
            <option value="Jaune">Jaune</option>
            <option value="Orange">Orange</option>
            <option value="Violet">Violet</option>
            <option value="Rose">Rose</option>
            <option value="Gris">Gris</option>
            <option value="Marron">Marron</option>
            <option value="Transparent">Transparent</option>
            <option value="Or">Or</option>
            <option value="Argent">Argent</option>
            <option value="Bronze">Bronze</option>
            <option value="Naturel">Naturel</option>
          </select>
        </div>
        
        <div class="form-group">
          <label>Poids (g) :</label>
          <input type="number" step="0.01" name="poids" required value="1000" />
        </div>
        
        <div class="form-group">
          <label>Prix (€) :</label>
          <input type="number" step="0.01" name="prix" required value="25.00" />
        </div>
        
        <button type="submit" name="ajouter" class="btn" style="background-color: #007bff; color: white;">Ajouter</button>
        <button type="button" onclick="closeAddModal()" class="btn" style="background-color: #6c757d; color: white;">Annuler</button>
      </form>
    </div>
  </div>

  <!-- Modale QR Code -->
  <div id="qrModal" class="modal">
    <div class="modal-content" style="max-width: 350px; text-align: center;">
      <span class="close-btn" onclick="closeQRModal()">&times;</span>
      <h3>QR Code du filament</h3>
      <img id="qrModalImg" src="" alt="QR Code agrandi" style="width: 300px; height: 300px;" />
    </div>
  </div>

  <script>
    // Gestion des modales
    function openAddModal() {
      document.getElementById('addModal').style.display = 'block';
    }
    
    function closeAddModal() {
      document.getElementById('addModal').style.display = 'none';
    }

    function openQRModal(src) {
      const modal = document.getElementById('qrModal');
      const img = document.getElementById('qrModalImg');
      img.src = src;
      modal.style.display = 'block';
    }
    
    function closeQRModal() {
      document.getElementById('qrModal').style.display = 'none';
    }

    function openEditModal(id) {
      document.getElementById('editModal-' + id).style.display = 'block';
    }
    
    function closeEditModal(id) {
      document.getElementById('editModal-' + id).style.display = 'none';
    }

    function confirmDelete(id, name) {
      if (confirm('Êtes-vous sûr de vouloir supprimer le filament "' + name + '" ?')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="supprimer_id" value="' + id + '">';
        document.body.appendChild(form);
        form.submit();
      }
    }

    // Fermeture des modales au clic extérieur
    window.onclick = function(event) {
      const modals = document.querySelectorAll('.modal');
      modals.forEach(modal => {
        if (event.target === modal) {
          modal.style.display = 'none';
        }
      });
    };

    // Masquer les messages après 5 secondes
    document.addEventListener('DOMContentLoaded', function() {
      const alerts = document.querySelectorAll('.alert');
      alerts.forEach(alert => {
        setTimeout(() => {
          alert.style.opacity = '0';
          setTimeout(() => alert.remove(), 300);
        }, 5000);
      });
    });
  </script>
</body>
</html>