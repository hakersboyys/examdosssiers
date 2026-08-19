<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

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
    die("<div style='background:#ee5253;padding:20px;text-align:center;color:white;'>Erreur : " . $e->getMessage() . "</div>");
}

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: admin.php");
    exit();
}

$dossier_id = intval($_GET['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_decision'])) {
    $statut_decision = $_POST['statut_decision']; 
    $motif_rejet = htmlspecialchars(strip_tags($_POST['motif_rejet']));

    if ($statut_decision === 'valide') {
        $stmt_up = $conn->prepare("UPDATE dossiers SET statut = 'valide', motif_rejet = NULL WHERE id = :id");
        $stmt_up->execute([':id' => $dossier_id]);
        $message = "<p class='success'>Dossier N° $dossier_id validé officiellement avec succès !</p>";
    } elseif ($statut_decision === 'rejete') {
        if (!empty($motif_rejet)) {
            $stmt_up = $conn->prepare("UPDATE dossiers SET statut = 'rejete', motif_rejet = :motif WHERE id = :id");
            $stmt_up->execute([':motif' => $motif_rejet, ':id' => $dossier_id]);
            $message = "<p class='error'>Dossier N° $dossier_id rejeté. Motif envoyé au candidat.</p>";
        } else {
            $message = "<p class='error'>Erreur : Saisie d'un motif obligatoire en cas de rejet.</p>";
        }
    }
}
$stmt_get = $conn->prepare("
    SELECT d.id AS dossier_id, d.statut, d.motif_rejet, d.date_depot,
           u.nom, u.prenom, u.email, u.telephone, u.departement, u.type_candidat, u.etablissement,
           e.libelle AS examen_nom
    FROM dossiers d
    JOIN users u ON d.user_id = u.id
    JOIN examens e ON d.examen_id = e.id
    WHERE d.id = :did LIMIT 1
");
$stmt_get->execute([':did' => $dossier_id]);
$dossier = $stmt_get->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    header("Location: admin.php");
    exit();
}

// RÉCUPÉRATION DES PIÈCES PDF LINKÉES EN BDD
$stmt_docs = $conn->prepare("SELECT type_document, fichier_url FROM documents WHERE dossier_id = :did");
$stmt_docs->execute([':did' => $dossier_id]);
$pieces_candidat = $stmt_docs->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Instruction Dossier #<?php echo $dossier_id; ?></title>
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
    background: linear-gradient(135deg, rgba(11, 36, 19, 0.9) 0%, rgba(0, 0, 0, 0.85) 100%);
    min-height: 100vh;
    padding-bottom: 60px;
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
    letter-spacing: 0.5px;
    background: linear-gradient(to right, #ffffff, #00ff88);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

.btn-back {
    background: rgba(255,255,255,0.1);
    padding: 10px 20px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-size: 14px;
    font-weight: 700;
}

.container {
    max-width: 900px;
    margin: 40px auto;
    padding: 0 20px;
}

.panel {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    padding: 35px;
    border-radius: 24px;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
    margin-bottom: 30px;
}

h2 {
    margin: 0 0 20px 0;
    font-size: 22px;
    border-bottom: 1px solid rgba(255,255,255,0.1);
    padding-bottom: 10px;
    color: #00ff88;
}

.info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
    margin-bottom: 10px;
}

.info-item {
    font-size: 15px;
}

.info-item span {
    color: rgba(255,255,255,0.6);
}

.piece-item {
    background: rgba(0,0,0,0.25);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 12px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-left: 4px solid #00a859;
}

.btn-view {
    color: #00ff88;
    text-decoration: none;
    font-weight: 700;
}

.textarea-motif {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border-radius: 10px;
    border: 1px solid rgba(255,255,255,0.2);
    background: rgba(255,255,255,0.9);
    color: #111;
    font-family: Arial, sans-serif;
    font-size: 14px;
    outline: none;
    resize: none;
}

.actions {
    display: flex;
    gap: 15px;
    margin-top: 15px;
}

.btn-decision {
    padding: 14px 25px;
    border: none;
    border-radius: 10px;
    color: white;
    font-weight: 700;
    cursor: pointer;
    font-size: 15px;
    flex: 1;
    transition: 0.3s;
}

.btn-val { background: #10ac84; }
.btn-rej { background: #ee5253; }

.error { color: #ff4d4d; font-weight: 700; background: rgba(255,77,77,0.1); padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
.success { color: #00ff88; font-weight: 700; background: rgba(0,255,136,0.1); padding: 12px; border-radius: 8px; text-align: center; margin-bottom: 20px; }
</style>
</head>
<body>
<div class="overlay">
<header>
    <div class="logo-container">
        <h2>ExamDossiers BJ — Bureau d'Instruction</h2>
    </div>
    <nav>
        <a href="admin.php" class="btn-back">← Retour au Registre</a>
    </nav>
</header>

<div class="container">
    <?php if (!empty($message)) echo $message; ?>

    <div class="panel">
        <h2>Fiche du Candidat</h2>
        <div class="info-grid">
            <div class="info-item"><span>Nom Complet :</span> <b><?php echo $dossier['nom'] . " " . $dossier['prenom']; ?></b></div>
            <div class="info-item"><span>Examen Visé :</span> <b><?php echo $dossier['examen_nom']; ?></b></div>
            <div class="info-item"><span>Statut Inscription :</span> <span style="text-transform: capitalize; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px;"><b><?php echo $dossier['type_candidat']; ?></b></span></div>
            <div class="info-item"><span>Département :</span> <b><?php echo $dossier['departement']; ?></b></div>
            <div class="info-item"><span>Établissement / CEG :</span> <b><?php echo $dossier['etablissement']; ?></b></div>
            <div class="info-item"><span>Statut du Dossier :</span> <b style="color: #ff9f43;"><?php echo ucfirst($dossier['statut']); ?></b></div>
        </div>
    </div>

    <div class="panel">
        <h2>Pièces Justificatives Transmises</h2>
        <?php if(empty($pieces_candidat)): ?>
            <p style="color: #ff4d4d; text-align: center;">Aucun document PDF lié à cette candidature.</p>
        <?php else: ?>
            <?php foreach($pieces_candidat as $p): ?>
                <div class="piece-item">
                    <span>Doc : <b><?php echo $p['type_document']; ?></b></span>
                    <a href="<?php echo $p['fichier_url']; ?>" target="_blank" class="btn-view">👁 Ouvrir le PDF</a>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <div class="panel">
        <h2>Verdict Administratif (DEC)</h2>
        <form action="instruction.php?id=<?php echo $dossier_id; ?>" method="POST">
            <input type="hidden" name="statut_decision" id="statut_decision" value="">
            
            <label style="font-size: 14px; color: rgba(255,255,255,0.7); font-weight: 600;">Saisir un motif d'explication (Obligatoire uniquement en cas de REJET) :</label>
            <textarea name="motif_rejet" class="textarea-motif" rows="3" placeholder="Ex: La quittance du Trésor Public n'est pas conforme à la session 2026..."></textarea>
            
            <div class="actions">
                <button type="submit" name="action_decision" class="btn-decision btn-val" onclick="document.getElementById('statut_decision').value='valide';">✔️ Valider le dossier</button>
                <button type="submit" name="action_decision" class="btn-decision btn-rej" onclick="document.getElementById('statut_decision').value='rejete';">❌ Rejeter le dossier</button>
            </div>
        </form>
    </div>
</div>
</div>
</body>
</html>
