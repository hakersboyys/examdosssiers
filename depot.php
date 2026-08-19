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

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['depot_submit'])) {
    $examen_choisi = htmlspecialchars(strip_tags($_POST['examen']));

    if ($examen_choisi !== "Choisir un examen") {
        
        // VERROU ANTI-DOUBLON STRICT POUR LE PREMIER DÉPÔT
        $stmt_check = $conn->prepare("
            SELECT COUNT(*) 
            FROM dossiers d
            JOIN examens e ON d.examen_id = e.id
            WHERE d.user_id = :uid AND e.libelle = :lib AND e.annee = :annee
        ");
        $stmt_check->execute([
            ':uid' => $_SESSION['user_id'],
            ':lib' => $examen_choisi,
            ':annee' => date('Y')
        ]);
        $deja_inscrit = $stmt_check->fetchColumn();

        if ($deja_inscrit > 0) {
            $message = "<p class='error'>Opération refusée : Vous avez déjà soumis un dossier pour l'examen " . $examen_choisi . " cette année. Pour toute modification, veuillez attendre l'instruction de la DEC.</p>";
        } else {
            // TRAITEMENT STANDARD DES FICHIERS PDF
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

            $upload_success = true;
            $files_to_save = [];

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

            foreach ($pieces_attendues as $key) {
                if (!empty($_FILES[$key]['name'])) {
                    $file_name = time() . "_" . $key . "_" . basename($_FILES[$key]["name"]);
                    $target_file = $target_dir . $file_name;
                    $file_type = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));

                    if ($file_type === 'pdf') {
                        if (move_uploaded_file($_FILES[$key]["tmp_name"], $target_file)) {
                            $label_bdd = $libelles_pieces[$key];
                            $files_to_save[$label_bdd] = $target_file;
                        } else {
                            $upload_success = false;
                            $message = "<p class='error'>Erreur technique lors du transfert.</p>";
                            break;
                        }
                    } else {
                        $upload_success = false;
                        $message = "<p class='error'>Toutes les pièces doivent être obligatoirement en PDF.</p>";
                        break;
                    }
                } else {
                    $upload_success = false;
                    $message = "<p class='error'>Dossier incomplet. Toutes les pièces sont obligatoires.</p>";
                    break;
                }
            }

            if ($upload_success && !empty($files_to_save)) {
                $annee = date('Y');
                $stmt_exam = $conn->prepare("SELECT id FROM examens WHERE libelle = :lib LIMIT 1");
                $stmt_exam->execute([':lib' => $examen_choisi]);
                $exam_id = $stmt_exam->fetchColumn();

                if (!$exam_id) {
                    $ins_exam = $conn->prepare("INSERT INTO examens (libelle, annee, date_cloture) VALUES (:lib, :annee, '2026-06-30')");
                    $ins_exam->execute([':lib' => $examen_choisi, ':annee' => $annee]);
                    $exam_id = $conn->lastInsertId();
                }

                $stmt_dos = $conn->prepare("INSERT INTO dossiers (user_id, examen_id, statut) VALUES (:uid, :eid, 'en_attente')");
                $stmt_dos->execute([':uid' => $_SESSION['user_id'], ':eid' => $exam_id]);
                $dossier_id = $conn->lastInsertId();

                $stmt_doc = $conn->prepare("INSERT INTO documents (dossier_id, type_document, fichier_url) VALUES (:did, :type_doc, :url)");
                foreach ($files_to_save as $label => $url) {
                    $stmt_doc->execute([':did' => $dossier_id, ':type_doc' => $label, ':url' => $url]);
                }
                $message = "<p class='success'>Votre dossier pour le " . $examen_choisi . " a été transmis à la DEC !</p>";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Déposer un dossier — ExamDossiers</title>
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
    margin: 0 0 25px 0;
    font-size: 30px;
    font-weight: 800;
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
.input-group select,
.input-group input[type="file"] {
    width: 100%;
    padding: 12px;
    box-sizing: border-box;
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 10px;
    font-size: 14px;
    outline: none;
}
.input-group select {
    background: rgba(255, 255, 255, 0.95);
    color: #111;
    font-weight: 500;
}
.input-group input[type="file"] {
    background: rgba(255, 255, 255, 0.1);
    color: white;
    cursor: pointer;
}
.dynamic-section {
    display: none;
    border-top: 1px solid rgba(255, 255, 255, 0.1);
    padding-top: 15px;
    margin-top: 15px;
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
    color: #00ff88;
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
        <h2>ExamDossiers BJ</h2>
    </div>
    <nav>
        <a href="dashboard.php">Mon Tableau de bord</a>
    </nav>
</header>
<div class="form-container">
    <div class="form-box">
        <h1>Dépôt Numérique (PDF)</h1>
        <?php if (!empty($message)) echo $message; ?>
        
        <form action="depot.php" method="POST" enctype="multipart/form-data">
            <div class="input-group">
                <label for="examen">Sélectionnez votre examen :</label>
                <select name="examen" id="examen" onchange="toggleExamenSections()" required>
                    <option value="Choisir un examen">Choisir un examen</option>
                    <option value="BAC">BAC (Baccalauréat)</option>
                    <option value="BEPC">BEPC (Brevet d'Études du Premier Cycle)</option>
                    <option value="CAP">CAP (Certificat d'Aptitude Professionnelle)</option>
                    <option value="CEP">CEP (Certificat d'Études Primaires)</option>
                </select>
            </div>

            <!-- SECTIONS DYNAMIQUES -->
            <div id="section_BAC" class="dynamic-section">
                <h3 style="color:#00ff88; font-size:16px; margin-bottom:15px;">Dossier réglementaire du BAC :</h3>
                <div class="input-group"><label>1. Acte de naissance sécurisé / CIP (PDF) :</label><input type="file" name="bac_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Quittance des droits d'inscription (PDF) :</label><input type="file" name="bac_quittance" accept=".pdf"></div>
                <div class="input-group"><label>3. Attestation de réussite ou Relevé du BEPC (PDF) :</label><input type="file" name="bac_bepc" accept=".pdf"></div>
                <div class="input-group"><label>4. Photo d'identité numérisée sur fond blanc (PDF) :</label><input type="file" name="bac_photo" accept=".pdf"></div>
            </div>

            <div id="section_BEPC" class="dynamic-section">
                <h3 style="color:#00ff88; font-size:16px; margin-bottom:15px;">Dossier réglementaire du BEPC :</h3>
                <div class="input-group"><label>1. Acte de naissance ou jugement supplétif (PDF) :</label><input type="file" name="bepc_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Certificat de scolarité de la classe de 3ème (PDF) :</label><input type="file" name="bepc_scolarite" accept=".pdf"></div>
                <div class="input-group"><label>3. Photo d'identité officielle numérisée (PDF) :</label><input type="file" name="bepc_photo" accept=".pdf"></div>
            </div>

            <div id="section_CEP" class="dynamic-section">
                <h3 style="color:#00ff88; font-size:16px; margin-bottom:15px;">Dossier réglementaire du CEP :</h3>
                <div class="input-group"><label>1. Acte de naissance de l'écolier (PDF) :</label><input type="file" name="cep_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Photo d'identité officielle de l'écolier (PDF) :</label><input type="file" name="cep_photo" accept=".pdf"></div>
            </div>

            <div id="section_CAP" class="dynamic-section">
                <h3 style="color:#00ff88; font-size:16px; margin-bottom:15px;">Dossier réglementaire du CAP :</h3>
                <div class="input-group"><label>1. Acte de naissance sécurisé / CIP (PDF) :</label><input type="file" name="cap_naissance" accept=".pdf"></div>
                <div class="input-group"><label>2. Quittance des droits du Trésor Public (PDF) :</label><input type="file" name="cap_quittance" accept=".pdf"></div>
                <div class="input-group"><label>3. Certificat ou Attestation de réussite du CEP (PDF) :</label><input type="file" name="cap_cep" accept=".pdf"></div>
                <div class="input-group"><label>4. Photo d'identité numérisée (PDF) :</label><input type="file" name="cap_photo" accept=".pdf"></div>
            </div>

            <button type="submit" name="depot_submit" class="btn">Soumettre le dossier complet (PDF)</button>
        </form>
        
        <div class="back-link"><a href="dashboard.php">← Revenir au tableau de bord</a></div>
    </div>
</div>
</div>
<script>
function toggleExamenSections() {
    var examenSelect = document.getElementById("examen").value;
    var toutesLesSections = ["BAC", "BEPC", "CEP", "CAP"];
    toutesLesSections.forEach(function(nomExamen) {
        var section = document.getElementById("section_" + nomExamen);
        if (section) {
            section.style.display = "none";
            var inputs = section.getElementsByTagName("input");
            for (var i = 0; i < inputs.length; i++) { inputs[i].required = false; }
        }
    });
    if (examenSelect !== "Choisir un examen") {
        var sectionActive = document.getElementById("section_" + examenSelect);
        if (sectionActive) {
            sectionActive.style.display = "block";
            var inputsActifs = sectionActive.getElementsByTagName("input");
            for (var j = 0; j < inputsActifs.length; j++) { inputsActifs[j].required = true; }
        }
    }
}
window.onload = toggleExamenSections;
</script>
</body>
</html>
