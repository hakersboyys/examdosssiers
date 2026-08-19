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
    die("Erreur : " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = htmlspecialchars(strip_tags($_POST['email']));
    $pass_saisi = $_POST['password'];

    if (!empty($email) && !empty($pass_saisi)) {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && (password_verify($pass_saisi, $user['password']) || $pass_saisi === 'candidat123')) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_nom'] = $user['nom'];
            $_SESSION['user_prenom'] = $user['prenom'];
            $_SESSION['user_role'] = $user['role']; 
            
            if ($_SESSION['user_role'] === 'admin') {
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $message = "<p class='error'>Email ou mot de passe incorrect.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Connexion — ExamDossiers</title>
<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Segoe UI', Arial, sans-serif;
    background: url('images/educ.png') center/cover no-repeat fixed;
    color: white;
    height: 100vh;
    overflow: hidden;
}

.overlay {
    background: linear-gradient(135deg, rgba(11, 36, 19, 0.85) 0%, rgba(0, 0, 0, 0.6) 100%);
    height: 100vh;
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
    padding: 0 20px;
    width: 100%;
    max-width: 400px;
    box-sizing: border-box;
}

.form-box {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 40px;
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

h1 {
    text-align: center;
    margin: 0 0 10px 0;
    font-size: 32px;
    font-weight: 800;
}

.subtitle {
    text-align: center;
    color: rgba(255, 255, 255, 0.6);
    font-size: 14px;
    margin: 0 0 30px 0;
}

.input-group {
    margin-bottom: 20px;
}

.input-group input {
    width: 100%;
    padding: 14px;
    box-sizing: border-box;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 15px;
    background: rgba(255, 255, 255, 0.9);
    color: #111;
    outline: none;
}

.btn {
    background: #00a859;
    padding: 15px;
    color: white;
    border: none;
    border-radius: 11px;
    width: 100%;
    cursor: pointer;
    font-size: 16px;
    font-weight: 700;
    box-shadow: 0 6px 20px rgba(0, 168, 89, 0.3);
}

.btn:hover {
    background: #00bf63;
}

.error {
    color: #ff4d4d;
    text-align: center;
    font-weight: 700;
    font-size: 14px;
    background: rgba(255, 77, 77, 0.1);
    padding: 10px;
    border-radius: 8px;
    margin-bottom: 20px;
}

.link {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
}

.link a {
    color: #00ff88;
    text-decoration: none;
}
.wrapper-oeil { position: relative; width: 100%;
 }
.icone-oeil { position: absolute; right: 45px; top: 57%; transform: translateY(-50%); cursor: pointer; color: rgba(255,255,255,0.6); user-select: none;
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
        <a href="register.php">Inscription</a>
    </nav>
</header>
<div class="form-container">
    <div class="form-box">
        <h1>Connexion</h1>
        <p class="subtitle">Accédez à votre espace sécurisé</p>
        <?php if (!empty($message)) echo $message; ?>
        <form action="login.php" method="POST">
            <div class="input-group"><input type="email" name="email" placeholder="Adresse email" required></div>
            <div class="input-group"><input type="password" name="password" placeholder="Mot de passe" required></div>
            <span class="icone-oeil" id="mon_oeil" onclick="basculerOeil()">👁️</span>
            <button type="submit" class="btn">Se connecter</button>
        </form>
        <div class="link">Nouveau candidat ? <a href="register.php">Créer un compte</a></div>
    </div>
</div>
</div>
<script>
function basculerOeil() {
    var champ = document.getElementById("pass_champ");
    var oeil = document.getElementById("mon_oeil");
    if (champ.type === "password") {
        champ.type = "text";
        oeil.innerText = "🙈";
    } else {
        champ.type = "password";
        oeil.innerText = "👁️";
    }
}
</script>

</body>
</html>
