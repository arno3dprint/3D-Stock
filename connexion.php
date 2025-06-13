<?php
session_start();

class UtilisateurCSV {
    private $utilisateurs = [];

    public function __construct($fichier) {
        if (!file_exists($fichier)) {
            die("⚠️ Fichier CSV introuvable à l'emplacement : $fichier");
        }

        if (($handle = fopen($fichier, "r")) !== false) {
            while (($ligne = fgetcsv($handle, 1000, ",")) !== false) {
                if (count($ligne) === 2) {
                    $this->utilisateurs[$ligne[0]] = $ligne[1];
                }
            }
            fclose($handle);
        }
    }

    public function verifier($username, $password) {
        return isset($this->utilisateurs[$username]) &&
               password_verify($password, $this->utilisateurs[$username]);
    }
}

$messageErreur = '';

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Chemin complet vers le fichier CSV, basé sur le dossier du script
    $csv = new UtilisateurCSV(__DIR__ . '/utilisateur.csv');

    if ($csv->verifier($username, $password)) {
        $_SESSION['username'] = $username;
        header("Location: index.php");
        exit();
    } else {
        $messageErreur = "Nom d'utilisateur ou mot de passe incorrect.";
    }
}
?>

<h2>Connexion</h2>
<?php if ($messageErreur): ?>
<p style="color:red"><?= htmlspecialchars($messageErreur) ?></p>
<?php endif; ?>
<form method="post">
    <label>Nom d'utilisateur :</label><br>
    <input type="text" name="username" required><br>
    <label>Mot de passe :</label><br>
    <input type="password" name="password" required><br><br>
    <button type="submit">Se connecter</button>
</form>
