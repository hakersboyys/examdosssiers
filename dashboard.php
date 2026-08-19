<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] === 'admin') {
    header("Location: login.php");
    exit();
}

$host = "localhost";
$db_name = "examdossiers";
$username = "root";
$password = "";

try {
    $conn = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
$stmt_hist = $conn->prepare("
    SELECT d.id AS dossier_id, d.statut, d.motif_rejet, d.date_depot, d.numero_table,
           e.libelle, e.annee
    FROM dossiers d
    JOIN examens e ON d.examen_id = e.id
    WHERE d.user_id = :uid
    ORDER BY d.date_depot DESC
");

$stmt_hist->execute([
    ':uid' => $_SESSION['user_id']
]);

$dossiers = $stmt_hist->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Mon Espace — ExamDossiers</title>

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
    background: linear-gradient(135deg, rgba(11, 36, 19, 0.85), rgba(0, 0, 0, 0.6));
    min-height: 100vh;
}

header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px 80px;
    background: rgba(255,255,255,0.03);
    backdrop-filter: blur(10px);
    border-bottom: 1px solid rgba(255,255,255,0.1);
}

.logo-container h2 {
    margin: 0;
    font-size: 26px;
    font-weight: 800;
    background: linear-gradient(to right, #fff, #00ff88);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.logout {
    background: #ff4d4d;
    padding: 10px 20px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
}

.container {
    max-width: 1000px;
    margin: 50px auto;
    padding: 0 20px;
}

.welcome-box, .history-box {
    background: rgba(255,255,255,0.06);
    padding: 30px;
    border-radius: 20px;
    margin-bottom: 30px;
}

.btn-action {
    background: #00a859;
    padding: 12px 24px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
}

.dossier-card {
    background: rgba(0,0,0,0.25);
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 12px;
    border-left: 5px solid #ff9f43;
}

.status-badge {
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 12px;
}

.en_attente { background:#ff9f43; }
.valide { background:#10ac84; }
.rejete { background:#ee5253; }

.table-num-section {
    background: rgba(16,172,132,0.1);
    padding: 15px;
    border-radius: 10px;
    margin-top: 10px;
}

.table-num-section strong {
    font-size: 22px;
    color: #00ff88;
}

.btn-download-recu, .btn-correct {
    display: inline-block;
    margin-top: 10px;
    padding: 10px 18px;
    border-radius: 8px;
    color: white;
    text-decoration: none;
    font-weight: 700;
}

.btn-download-recu { background:#10ac84; }
.btn-correct { background:#ff9f43; }

.no-data {
    text-align: center;
    opacity: 0.6;
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
        <span>
            Candidat :
            <b>
                <?php echo $_SESSION['user_prenom'] . " " . $_SESSION['user_nom']; ?>
            </b>
        </span>

        <a href="logout.php" class="logout">Déconnexion</a>
    </nav>
</header>

<div class="container">

<div class="welcome-box">
    <div>
        <h1>Bonjour, <?php echo $_SESSION['user_prenom']; ?></h1>
        <p>Suivi de vos dossiers d'examens nationaux</p>
    </div>

    <a href="depot.php" class="btn-action">Nouveau dépôt</a>
</div>

<div class="history-box">
    <h3>Mes dossiers</h3>

    <?php if (empty($dossiers)): ?>
        <p class="no-data">Aucun dossier soumis.</p>
    <?php else: ?>

        <?php foreach ($dossiers as $d): ?>
        <div class="dossier-card">

            <div style="display:flex;justify-content:space-between;">
                <b><?php echo $d['libelle'] . " " . $d['annee']; ?></b>

                <span class="status-badge <?php echo $d['statut']; ?>">
                    <?php echo $d['statut']; ?>
                </span>
            </div>

            <p>Déposé le : <?php echo $d['date_depot']; ?></p>
<?php if($d['statut'] === 'rejete'): ?>
    <div class="motif-rejet">
        <strong>Motif du rejet (DEC) :</strong> <?php echo $d['motif_rejet']; ?>
    </div>

    <a href="corriger.php" class="btn-correct" style="text-decoration: none; display: inline-block;">✏️ Corriger et remplacer mes pièces</a>
<?php endif; ?>

            <?php if ($d['statut'] == "valide"): ?>

                <?php if (!empty($d['numero_table'])): ?>
                    <div class="table-num-section">
                        <p>Numéro de table :</p>
                        <strong><?php echo $d['numero_table']; ?></strong>
                    </div>
                <?php else: ?>
                    <p>Numéro en cours de génération...</p>
                <?php endif; ?>

            <?php endif; ?>

        </div>
        <?php endforeach; ?>

    <?php endif; ?>

</div>

</div>
</div>
</body>
</html>