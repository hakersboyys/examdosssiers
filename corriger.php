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
$conn = null;
$message = "";

try {
    $conn = new PDO("mysql:host=" . $host . ";dbname=" . $db_name . ";charset=utf8", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}

$stmt_check = $conn->prepare("
    SELECT d.id, d.motif_rejet, e.libelle 
    FROM dossiers d
    JOIN examens e ON d.examen_id = e.id
    WHERE d.user_id = :uid AND d.statut = 'rejete'
    LIMIT 1
");
$stmt_check->execute([':uid' => $_SESSION['user_id']]);
$dossier = $stmt_check->fetch(PDO::FETCH_ASSOC);

if (!$dossier) {
    header("Location: dashboard.php");
    exit();
}

$dossier_id = $dossier['id'];
$examen_choisi = $dossier['libelle'];
$motif_rejet_dec = $dossier['motif_rejet'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['correction_submit'])) {
    $upload_success = true;
    $pieces_attendues = [];

    if ($examen_choisi === "BAC") {
        $pieces_attendues = ['bac_naissance', 'bac_quittance', 'bac_bepc', 'bac_photo'];
    } elseif ($examen_choisi === "BEPC") {
        $pieces_attendues = ['bepc_naissance', 'bepc_scolarite', 'bepc_photo'];
    } elseif ($examen_choisi === "CEP") {
        $pieces_attendues = ['cep_naissance', 'cep_photo'];
    } elseif ($examen_choisi === "CAP") {
        $pieces_attendues = ['cap_naissance', 'cap_quittance', 'cap_cep', 'cap_photo'];
    }

    $target_dir = "uploads/";
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }

    $files_to_update = [];
    $libelles_pieces = [
        'bac_naissance'  => 'Acte de Naissance / CIP (PDF)',
        'bac_quittance'  => 'Quittance de droit d inscription (PDF)',
        'bac_bepc'       => 'Attestation ou Relevé de notes du BEPC (PDF)',
        'bac_photo'      => 'Photo d identité numérisée (PDF)',
        'bepc_naissance' => 'Acte de Naissance / Jugement supplétif (PDF)',
        'bepc_scolarite' => 'Certificat de Scolarité 3ème (PDF)',
        'bepc_photo'     => 'Photo d identité numérique (PDF)',
        'cep_naissance'  => 'Acte de Naissance Écolier (PDF)',
        'cep_photo'      => 'Photo d identité Écolier (PDF)',
        'cap_naissance'  => 'Acte de Naissance / CIP (PDF)',
        'cap_quittance'  => 'Quittance des droits Trésor (PDF)',
        'cap_cep'        => 'Certificat d Études Primaires CEP (PDF)',
        'cap_photo'      => 'Photo d identité (PDF)'
    ];

    $au_moins_une_piece_chargee = false;

    foreach ($pieces_attendues as $key) {
        // L'élève n'est plus obligé de tout charger, on ne traite que les fichiers soumis
        if (!empty($_FILES[$key]['name'])) {
            $au_moins_une_piece_chargee = true;
            $file_name = time() . "_" . $key . "_" . basename($_FILES[$key]["name"]);
            $target_file = $target_dir . $file_name;
            $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

            if ($file_type === 'pdf') {
                if (move_uploaded_file($_FILES[$key]["tmp_name"], $target_file)) {
                    $label_bdd = $libelles_pieces[$key];
                    $files_to_update[$label_bdd] = $target_file;
                } else {
                    $upload_success = false;
                    $message = "<p class='error'>Erreur technique lors du transfert des fichiers.</p>";
                    break;
                }
            } else {
                $upload_success = false;
                $message = "<p class='error'>Toutes les pièces rectifiées doivent être au format strict PDF.</p>";
                break;
            }
        }
    }

    if ($upload_success) {
        if ($au_moins_une_piece_chargee) {
            foreach ($files_to_update as $type_document => $fichier_url) {
                $stmt_del = $conn->prepare("DELETE FROM documents WHERE dossier_id = :did AND type_document = :type_doc");
                $stmt_del->execute([':did' => $dossier_id, ':type_doc' => $type_document]);

                $stmt_ins = $conn->prepare("INSERT INTO documents (dossier_id, type_document, fichier_url) VALUES (:did, :type_doc, :url)");
                $stmt_ins->execute([':did' => $dossier_id, ':type_doc' => $type_document, ':url' => $fichier_url]);
            }

            $stmt_up_dos = $conn->prepare("UPDATE dossiers SET statut = 'en_attente', motif_rejet = NULL WHERE id = :id");
            $stmt_up_dos->execute([':id' => $dossier_id]);

            $message = "<p class='success'>Mise à jour réussie ! Les pièces modifiées ont été transmises à la DEC.</p>";
            $dossier = false; 
        } else {
            $message = "<p class='error'>Veuillez téléverser au moins le document demandé par la DEC avant de valider.</p>";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Correction Dossier — ExamDossiers</title>
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
    letter-spacing: 0.5px;
    background: linear-gradient(to right, #ffffff, #00ff88);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}
.form-container {
    margin: auto;
    padding: 40px 20px;
    width: 100%;
    max-width: 580px;
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
    font-size: 28px;
    font-weight: 800;
}
.motif-alert {
    background: rgba(238, 82, 83, 0.15);
    border: 1px solid rgba(238, 82, 83, 0.4);
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 25px;
    color: #ff8888;
    font-size: 14px;
}
.input-group {
    margin-bottom: 20px;
}
.input-group label {
    display: block;
    margin-bottom: 8px;
    font-size: 14px;
    color: rgba(255, 255, 255, 0.8);
    font-weight: 600;
}
.input-group input[type="file"] {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 14px;
    background: rgba(255, 255, 255, 0.1);
    color: white;
    cursor: pointer;
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
    transition: all 0.3s ease;
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
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 25px;
}
.success {
    color: # 00ff88;
    text-align: center;
    font-weight: 700;
    background: rgba(0, 255, 136, 0.1);
    padding: 12px;
    border-radius: 8px;
    margin-bottom: 25px;
}
.back-link {
    text-align: center;
    margin-top: 25px;
    font-size: 14px;
}
.back-link a {
    color: #00ff88;
    text-decoration: none;
    font-weight: 600;
}
</style>
</head>
<body>
<div class="overlay">
<header>
    <div class="logo-container">
        <h2>ExamDossiers BJ — Rectification</h2>
    </div>
</header>
<div class="form-container">
    <div class="form-box">
        <h1>Correction Dossier (<?php echo $examen_choisi; ?>)</h1>
        
        <?php if (!empty($message)) echo $message; ?>
        
        <?php if ($dossier): ?>
        <div class="motif-alert">
            ❌ <strong>Rappel du motif de rejet de la DEC :</strong><br>
            <span style="display: block; margin-top: 5px; font-style: italic; color: white;"><?php echo $motif_rejet_dec; ?></span>
        </div>
        
        <p style="text-align: center; color: rgba(255,255,255,0.6); font-size: 13px; margin-bottom: 25px;">Téléversez uniquement la ou les pièces réclamées. Laissez les autres vides pour conserver vos anciens fichiers valides.</p>
        
        <form action="corriger.php" method="POST" enctype="multipart/form-data">

            <?php if ($examen_choisi === "BAC"): ?>
                <div class="input-group"><label>1. Acte de naissance sécurisé / CIP (PDF) :</label><input type="file" name="bac_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Quittance des droits d'inscription (PDF) :</label><input type="file" name="bac_quittance" accept=".pdf"></div>
                <div class="input-group"><label>3. Attestation de réussite ou Relevé du BEPC (PDF) :</label><input type="file" name="bac_bepc" accept=".pdf"></div>
                <div class="input-group"><label>4. Photo d'identité numérisée sur fond blanc (PDF) :</label><input type="file" name="bac_photo" accept=".pdf"></div>
            <?php elseif ($examen_choisi === "BEPC"): ?>
                <div class="input-group"><label>1. Acte de naissance ou jugement supplétif (PDF) :</label><input type="file" name="bepc_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Certificat de scolarité de la classe de 3ème (PDF) :</label><input type="file" name="bepc_scolarite" accept=".pdf"></div>
                <div class="input-group"><label>3. Photo d'identité officielle numérisée (PDF) :</label><input type="file" name="bepc_photo" accept=".pdf"></div>
            <?php elseif ($examen_choisi === "CEP"): ?>
                <div class="input-group"><label>1. Acte de naissance de l'écolier (PDF) :</label><input type="file" name="cep_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Photo d'identité officielle de l'écolier (PDF) :</label><input type="file" name="cep_photo" accept=".pdf"></div>
            <?php elseif ($examen_choisi === "CAP"): ?>
                <div class="input-group"><label>1. Acte de naissance sécurisé / CIP (PDF) :</label><input type="file" name="cap_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Quittance des droits du Trésor Public (PDF) :</label><input type="file" name="cap_quittance" accept=".pdf"></div>
                <div class="input-group"><label>3. Certificat ou Attestation de réussite du CEP (PDF) :</label><input type="file" name="cap_cep" accept=".pdf"></div>
                <div class="input-group"><label>4. Photo d'identité numérisée (PDF) :</label><input type="file" name="cap_photo" accept=".pdf"></div>
            <?php endif; ?>

            <button type="submit" name="correction_submit" class="btn">Enregistrer les corrections (PDF)</button>
        </form>
        <?php endif; ?>
        
        <div class="back-link">
            <a href="dashboard.php">← Revenir au tableau de bord</a>
        </div>
    </div>
</div>
</div>
</body>
</html>
