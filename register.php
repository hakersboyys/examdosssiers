<?php
session_start();
$host = "localhost";
$db_name = "examdossiers";
$username = "root";
$password = ""; 
$conn = null;
$message = "";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = htmlspecialchars(strip_tags($_POST['nom']));
    $prenom = htmlspecialchars(strip_tags($_POST['prenom']));
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $telephone = htmlspecialchars(strip_tags($_POST['telephone']));
    $departement = htmlspecialchars(strip_tags($_POST['departement']));
    $type_candidat = htmlspecialchars(strip_tags($_POST['type_candidat']));
    $etablissement = htmlspecialchars(strip_tags($_POST['etablissement']));
    $password = $_POST['password'];

    if (!empty($nom) && !empty($prenom) && !empty($email) && !empty($telephone) && !empty($password)) {
        $check = $conn->prepare("SELECT id FROM users WHERE email = :email");
        $check->execute([':email' => $email]);

        if ($check->rowCount() > 0) {
            $message = "<p class='error'>Cet email est déjà utilisé.</p>";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            $ins = $conn->prepare("INSERT INTO users (nom, prenom, email, telephone, password, role, departement, type_candidat, etablissement) VALUES (:nom, :prenom, :email, :tel, :pass, 'candidat', :dept, :type_c, :etab)");
            
            if ($ins->execute([
                ':nom' => $nom, 
                ':prenom' => $prenom, 
                ':email' => $email, 
                ':tel' => $telephone, 
                ':pass' => $hashed_password,
                ':dept' => $departement,
                ':type_c' => $type_candidat,
                ':etab' => $etablissement
            ])) {
                $message = "<p class='success'>Inscription réussie ! <a href='login.php'>Se connecter</a></p>";
            }
        }
    } else {
        $message = "<p class='error'>Veuillez remplir tous les champs.</p>";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Inscription — ExamDossiers</title>
<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: url('images/educ.png') center/cover no-repeat fixed;
    color: white;
    min-height: 100vh;
}

.overlay {
    background: linear-gradient(135deg, rgba(11, 36, 19, 0.85) 0%, rgba(0, 0, 0, 0.6) 100%);
    min-height: 100vh;
    display: flex;
    flex-direction: column;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 80px;
    background: rgba(255, 255, 255, 0.03);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-container h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    background: linear-gradient(to right, #ffffff, #00ff88);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

nav a {
    color: rgba(255, 255, 255, 0.8);
    margin: 0 10px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 8px;
}

.form-container {
    margin: auto;
    padding: 40px 20px;
    width: 100%;
    max-width: 500px;
    box-sizing: border-box;
}

.form-box {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 35px;
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

h1 {
    text-align: center;
    margin: 0 0 5px 0;
    font-size: 30px;
    font-weight: 800;
}

.subtitle {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 13px;
    margin: 0 0 25px 0;
}

.input-group {
    margin-bottom: 15px;
}

.input-group label {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
    color: rgba(255,255,255,0.7);
    font-weight: 600;
}

.input-group input, .input-group select {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.95);
    color: #111;
    outline: none;
}

.btn {
    background: #00a859;
    padding: 14px;
    color: white;
    border: none;
    border-radius: 11px;
    width: 100%;
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
    box-shadow: 0 6px 20px rgba(0, 168, 89, 0.3);
    margin-top: 10px;
}

.btn:hover {
    background: #00bf63;
}

.error {
    color: #ff4d4d;
    text-align: center;
    font-weight: 700;
    background: rgba(255, 77, 77, 0.1);
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.success {
    color: #00ff88;
    text-align: center;
    font-weight: 700;
    background: rgba(0, 255, 136, 0.1);
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 15px;
}

.link {
    text-align: center;
    margin-top: 20px;
    font-size: 14px;
}

.link a {
    color: #00ff88;
    text-decoration: none;
}
</style>
</head>
<body>
<div class="overlay">
<header>
    <div class="logo-container">
        <h2>ExamDossiers BJ</h2>
    </div>
    <nav>
        <a href="index.php">Accueil</a>
        <a href="login.php">Connexion</a>
    </nav>
</header>
<div class="form-container">
    <div class="form-box">
        <h1>Inscription</h1>
        <p class="subtitle">Créez votre profil candidat béninois</p>
        <?php if (!empty($message)) echo $message; ?>
        <form action="register.php" method="POST">
            <div class="input-group"><input type="text" name="nom" placeholder="Votre Nom" required></div>
            <div class="input-group"><input type="text" name="prenom" placeholder="Votre Prénom" required></div>
            <div class="input-group"><input type="email" name="email" placeholder="Adresse email" required></div>
            <div class="input-group"><input type="text" name="telephone" placeholder="Numéro de téléphone" required></div>

            <div class="input-group">
                <label>Département de résidence :</label>
                <select name="departement" required>
                    <option value="Alibori">Alibori</option>
                    <option value="Atacora">Atacora</option>
                    <option value="Atlantique">Atlantique</option>
                    <option value="Borgou">Borgou</option>
                    <option value="Collines">Collines</option>
                    <option value="Donga">Donga</option>
                    <option value="Kouffo">Kouffo</option>
                    <option value="Littoral" selected>Littoral</option>
                    <option value="Mono">Mono</option>
                    <option value="Ouémé">Ouémé</option>
                    <option value="Plateau">Plateau</option>
                    <option value="Zou">Zou</option>
                </select>
            </div>
            
            <div class="input-group">
                <label>Type de Candidature :</label>
                <select name="type_candidat" onchange="if(this.value=='libre'){document.getElementById('etab_field').style.display='none';}else{document.getElementById('etab_field').style.display='block';}" required>
                    <option value="officiel">Officiel (Scolarisé)</option>
                    <option value="libre">Libre (Candidat Libre)</option>
                </select>
            </div>
            
            <div class="input-group" id="etab_field">
                <label>Nom de l'établissement (CEG / École) :</label>
                <input type="text" name="etablissement" placeholder="Ex: CEG Gbégamey" value="Candidat Libre">
            </div>

            <div class="input-group"><input type="password" name="password" placeholder="Mot de passe" required></div>
            <button type="submit" class="btn">Créer mon compte</button>
        </form>
        <div class="link">Déjà inscrit ? <a href="login.php">Se connecter</a></div>
    </div>
</div>
</div>
</body>
</html>
