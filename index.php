<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ExamDossiers — Portail National</title>
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
    -webkit-backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
}

.logo-container h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    letter-spacing: 0.5px;
    background: linear-gradient(to right, #ffffff, #00ff88);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

nav {
    display: flex;
    align-items: center;
    gap: 10px;
}

nav a {
    color: rgba(255, 255, 255, 0.8);
    margin: 0 10px;
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    padding: 10px 18px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

nav a:hover {
    color: white;
    background: rgba(255, 255, 255, 0.08);
}

.btn-nav-login {
    border: 1px solid rgba(255, 255, 255, 0.4);
}

.btn-nav-login:hover {
    background: white;
    color: #113d1c;
}

.hero {
    text-align: center;
    margin: auto 0;
    padding: 0 20px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
}

.hero-box {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(16px);
    -webkit-backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 50px 70px;
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    max-width: 800px;
}

.hero h1 {
    font-size: 46px;
    font-weight: 800;
    margin: 0 0 15px 0;
    line-height: 1.2;
    letter-spacing: -0.5px;
}

.hero p {
    font-size: 18px;
    color: rgba(255, 255, 255, 0.75);
    margin: 0 0 35px 0;
    line-height: 1.6;
}

.btn-main {
    background: #00a859;
    padding: 16px 38px;
    color: white;
    text-decoration: none;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    letter-spacing: 0.5px;
    display: inline-block;
    box-shadow: 0 6px 20px rgba(0, 168, 89, 0.4);
    transition: all 0.3s ease;
}

.btn-main:hover {
    background: #00bf63;
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0, 191, 99, 0.5);
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
        <a href="login.php" class="btn-nav-login">Connexion</a>
        <a href="register.php">Inscription</a>
    </nav>
</header>
<div class="hero">
    <div class="hero-box">
        <h1>Dématérialisation des Examens Nationaux</h1>
        <p>Soumettez vos pièces justificatives réglementaires en ligne de manière simple, rapide et sécurisée pour le BAC, BEPC, CAP et CEP au Bénin.</p>
        <a class="btn-main" href="login.php">Commencer mon dépôt</a>
    </div>
</div>
</div>
</body>
</html>
