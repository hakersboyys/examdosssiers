
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
    $conn = new PDO(
        "mysql:host=".$host.";dbname=".$db_name.";charset=utf8",
        $username,
        $password
    );
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_decision'])) {

    $dossier_id = intval($_POST['dossier_id']);
    $statut_decision = $_POST['statut_decision'];
    $motif_rejet = htmlspecialchars(strip_tags($_POST['motif_rejet']));

    if ($statut_decision === 'valide') {

        $stmt_up = $conn->prepare("
            UPDATE dossiers
            SET statut='valide', motif_rejet=NULL
            WHERE id=:id
        ");

        $stmt_up->execute([
            ':id' => $dossier_id
        ]);

        $message = "<p class='success'>Dossier N° ".$dossier_id." validé officiellement avec succès !</p>";

    } elseif ($statut_decision === 'rejete') {

        if (!empty($motif_rejet)) {

            $stmt_up = $conn->prepare("
                UPDATE dossiers
                SET statut='rejete', motif_rejet=:motif
                WHERE id=:id
            ");

            $stmt_up->execute([
                ':motif' => $motif_rejet,
                ':id' => $dossier_id
            ]);

            $message = "<p class='error'>Dossier N° ".$dossier_id." rejeté. Motif notifié au candidat.</p>";

        } else {

            $message = "<p class='error'>Erreur : Saisie d'un motif obligatoire en cas de rejet.</p>";
        }
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['lancer_numerotage'])) {

    $examen_cible = $_POST['examen_cible'];

    if ($examen_cible !== "Choisir un examen") {

        $stmt_num = $conn->prepare("
            SELECT d.id AS dos_id
            FROM dossiers d
            JOIN users u ON d.user_id = u.id
            JOIN examens e ON d.examen_id = e.id
            WHERE e.libelle = :lib
            AND d.statut = 'valide'
            AND d.numero_table IS NULL
            ORDER BY u.departement ASC, u.nom ASC, u.prenom ASC
        ");

        $stmt_num->execute([
            ':lib' => $examen_cible
        ]);

        $candidats_valides = $stmt_num->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($candidats_valides)) {

            $compteur = 1;
            $annee_courante = substr(date('Y'), -2);

            $stmt_update_num = $conn->prepare("
                UPDATE dossiers
                SET numero_table = :num
                WHERE id = :id
            ");

            foreach ($candidats_valides as $c) {

                $generer_num =
                    $annee_courante . "-" .
                    $examen_cible . "-" .
                    str_pad($compteur, 4, "0", STR_PAD_LEFT);

                $stmt_update_num->execute([
                    ':num' => $generer_num,
                    ':id' => $c['dos_id']
                ]);

                $compteur++;
            }

            $message = "<p class='success'>Génération achevée ! "
                     . ($compteur - 1)
                     . " numéros attribués pour le "
                     . $examen_cible
                     . ".</p>";

        } else {

            $message = "<p class='error'>Aucun dossier validé en attente de numérotage pour le "
                     . $examen_cible
                     . ".</p>";
        }
    }
}
$stmt_get = $conn->prepare("
    SELECT d.id AS dossier_id,
           d.statut,
           d.motif_rejet,
           d.date_depot,
           d.numero_table,
           u.nom,
           u.prenom,
           u.email,
           u.telephone,
           u.departement,
           u.type_candidat,
           u.etablissement,
           e.libelle AS examen_nom
    FROM dossiers d
    JOIN users u ON d.user_id = u.id
    JOIN examens e ON d.examen_id = e.id
    ORDER BY d.date_depot DESC
");

$stmt_get->execute();

$liste_dossiers = $stmt_get->fetchAll(PDO::FETCH_ASSOC);
$total_depots = count($liste_dossiers);
$total_valides = 0;
$total_en_attente = 0;
$total_rejetes = 0;

foreach ($liste_dossiers as $dos) {

    if ($dos['statut'] === 'valide') {
        $total_valides++;
    }
    elseif ($dos['statut'] === 'en_attente') {
        $total_en_attente++;
    }
    elseif ($dos['statut'] === 'rejete') {
        $total_rejetes++;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Bureau DEC — ExamDossiers</title>
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
    background: linear-gradient(135deg, rgba(11, 36, 19, 0.9) 0%, rgba(0, 0, 0, 0.75) 100%);
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

.logout {
    background: #ff4d4d;
    padding: 10px 20px;
    color: white;
    text-decoration: none;
    border-radius: 8px;
    font-weight: 700;
    transition: all 0.3s ease;
}

.logout:hover {
    background: #ff3333;
    transform: translateY(-2px);
}

.admin-container {
    max-width: 1350px;
    margin: 40px auto;
    padding: 0 20px;
}

.title-section {
    margin-bottom: 35px;
    border-bottom: 2px solid #00a859;
    padding-bottom: 15px;
}

.title-section h1 {
    margin: 0;
    font-size: 32px;
    font-weight: 800;
}

.panel-action {
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    padding: 25px;
    border-radius: 15px;
    margin-bottom: 35px;
}

.filters-grid {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
    margin-bottom: 25px;
}

.filter-item {
    display: flex;
    flex-direction: column;
    gap: 6px;
}

.filter-item label {
    font-size: 12px;
    color: rgba(255, 255, 255, 0.6);
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.filter-item select {
    padding: 10px 18px;
    border-radius: 8px;
    background: rgba(255, 255, 255, 0.95);
    color: #111;
    font-weight: 600;
    border: none;
    outline: none;
}

.table-container {
    background: rgba(255, 255, 255, 0.06);
    backdrop-filter: blur(16px);
    border: 1px solid rgba(255, 255, 255, 0.15);
    border-radius: 20px;
    overflow-x: auto;
    box-shadow: 0 20px 50px rgba(0, 0, 0, 0.4);
}

table {
    width: 100%;
    border-collapse: collapse;
    text-align: left;
}

th, td {
    padding: 16px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.08);
}

th {
    background: rgba(0, 168, 89, 0.3);
    color: white;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

td {
    font-size: 14px;
    vertical-align: middle;
}

tr:hover {
    background: rgba(255, 255, 255, 0.03);
}

.badge {
    padding: 5px 12px;
    border-radius: 6px;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    display: inline-block;
}

.en_attente { background: rgba(255, 159, 67, 0.15); color: #ff9f43; border: 1px solid rgba(255, 159, 67, 0.3); }
.valide { background: rgba(16, 172, 132, 0.15); color: #10ac84; border: 1px solid rgba(16, 172, 132, 0.3); }
.rejete { background: rgba(238, 82, 83, 0.15); color: #ee5253; border: 1px solid rgba(238, 82, 83, 0.3); }

.btn-instruire {
    background: #00a859;
    color: white;
    padding: 8px 15px;
    border: none;
    border-radius: 6px;
    font-weight: 700;
    font-size: 13px;
    cursor: pointer;
    transition: 0.3s;
}

.btn-instruire:hover {
    background: #00bf63;
}

.btn-num {
    background: #00a859;
    color: white;
    padding: 12px 28px;
    border: none;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
    font-size: 14px;
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
</style>
</head>
<body>
<div class="overlay">
<header>
    <div class="logo-container">
        <h2>ExamDossiers BJ — Direction DEC</h2>
    </div>
    <nav>
        <a href="index.php" class="logout" style="background: rgba(255,255,255,0.1); color: white;">Accueil Public</a>
        <a href="logout.php" class="logout">Déconnexion</a>
    </nav>
</header>

<div class="admin-container">
    <div class="title-section">
        <h1>Registre et Centralisation Logistique</h1>
    </div>

    <?php if (!empty($message)) echo $message; ?>
    <div style="display: flex; gap: 20px; margin-bottom: 30px; flex-wrap: wrap;">
        <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; flex: 1; min-width: 200px; border-left: 5px solid #ffffff;">
            <div style="font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 600; text-transform: uppercase;">Total Dossiers</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 5px;"><?php echo $total_depots; ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; flex: 1; min-width: 200px; border-left: 5px solid #10ac84;">
            <div style="font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 600; text-transform: uppercase;">Validés</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 5px; color: #10ac84;"><?php echo $total_valides; ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; flex: 1; min-width: 200px; border-left: 5px solid #ff9f43;">
            <div style="font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 600; text-transform: uppercase;">En Attente</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 5px; color: #ff9f43;"><?php echo $total_en_attente; ?></div>
        </div>
        <div style="background: rgba(255, 255, 255, 0.08); backdrop-filter: blur(10px); padding: 20px; border-radius: 12px; flex: 1; min-width: 200px; border-left: 5px solid #ee5253;">
            <div style="font-size: 13px; color: rgba(255,255,255,0.6); font-weight: 600; text-transform: uppercase;">Rejetés</div>
            <div style="font-size: 28px; font-weight: 800; margin-top: 5px; color: #ee5253;"><?php echo $total_rejetes; ?></div>
        </div>
    </div>
    <div class="panel-action">
        <h3 style="margin: 0 0 15px 0; font-size: 16px; color: #00ff88;">Filtres & Gestion Territoriale</h3>
        <div class="filters-grid">
            <div class="filter-item">
                <label>Examen</label>
                <select id="fExamen" onchange="filtrerTerritoire()">
                    <option value="TOUS">Tous les examens</option>
                    <option value="BAC">BAC</option>
                    <option value="BEPC">BEPC</option>
                    <option value="CAP">CAP</option>
                    <option value="CEP">CEP</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Département</label>
                <select id="fDept" onchange="filtrerTerritoire()">
                    <option value="TOUS">Tous les départements</option>
                    <option value="Alibori">Alibori</option>
                    <option value="Atacora">Atacora</option>
                    <option value="Atlantique">Atlantique</option>
                    <option value="Borgou">Borgou</option>
                    <option value="Collines">Collines</option>
                    <option value="Donga">Donga</option>
                    <option value="Kouffo">Kouffo</option>
                    <option value="Littoral">Littoral</option>
                    <option value="Mono">Mono</option>
                    <option value="Ouémé">Ouémé</option>
                    <option value="Plateau">Plateau</option>
                    <option value="Zou">Zou</option>
                </select>
            </div>
            <div class="filter-item">
                <label>Type de Candidat</label>
                <select id="fType" onchange="filtrerTerritoire()">
                    <option value="TOUS">Tous les statuts</option>
                    <option value="officiel">Officiel</option>
                    <option value="libre">Libre (Candidat Libre)</option>
                </select>
            </div>
        </div>

        <form action="admin.php" method="POST" style="border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; display: flex; align-items: center; gap: 15px; flex-wrap: wrap;">
            <span style="font-size: 14px; color: #ccc;">Déclencher l'attribution officielle globale des numéros de table pour :</span>
            <select name="examen_cible" style="padding: 10px; border-radius: 6px; font-weight: 600;" required>
                <option value="Choisir un examen">Choisir un examen</option>
                <option value="BAC">BAC 2026</option>
                <option value="BEPC">BEPC 2026</option>
                <option value="CAP">CAP 2026</option>
                <option value="CEP">CEP 2026</option>
            </select>
            <button type="submit" name="lancer_numerotage" class="btn-num">⚡ Générer le Numérotage National</button>
        </form>
    </div>
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Candidat</th>
                    <th>Provenance / Établissement</th>
                    <th>Département</th>
                    <th>Examen</th>
                    <th>N° de Table</th>
                    <th>Statut</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(empty($liste_dossiers)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; color: rgba(255,255,255,0.5); padding: 40px;">Aucun dossier transmis pour le moment dans la base.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach($liste_dossiers as $row): ?>
                        <tr>
                            <td><b>#<?php echo $row['dossier_id']; ?></b></td>
                            <td><strong><?php echo $row['nom'] . " " . $row['prenom']; ?></strong></td>
                            <td>
                                <span style="text-transform: capitalize; font-size: 11px; background: rgba(255,255,255,0.1); padding: 2px 6px; border-radius: 4px; font-weight: 600;"><?php echo $row['type_candidat']; ?></span><br>
                                <span style="color: #aaa; font-size: 13px;"><?php echo $row['etablissement']; ?></span>
                            </td>
                            <td><b><?php echo $row['departement']; ?></b></td>
                            <td><b><?php echo $row['examen_nom']; ?></b></td>
                            <td><b style="color: #00ff88; font-family: monospace; font-size: 15px; letter-spacing: 0.5px;"><?php echo $row['numero_table'] ?? 'Non généré'; ?></b></td>
                            <td><span class="badge <?php echo $row['statut']; ?>"><?php echo $row['statut'] === 'en_attente' ? 'En attente' : ucfirst($row['statut']); ?></span></td>
                            <td>
                                <a href="instruction.php?id=<?php echo $row['dossier_id']; ?>" class="btn-instruire">📁 Instruire</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</div>

<script>
function filtrerTerritoire() {
    var ex = document.getElementById("fExamen").value;
    var dp = document.getElementById("fDept").value;
    var ty = document.getElementById("fType").value;
    var lignes = document.querySelectorAll("table tbody tr");
    lignes.forEach(function(row) {
        var cellules = row.getElementsByTagName("td");
        if(cellules.length > 0) {
            var txtType = row.innerText.toLowerCase();
            var txtDept = row.innerText;
            var txtExam = row.innerText;
            var matchExam = (ex === "TOUS" || txtExam.indexOf(ex) > -1);
            var matchDept = (dp === "TOUS" || txtDept.indexOf(dp) > -1);
            var matchType = (ty === "TOUS" || txtType.indexOf(ty.toLowerCase()) > -1);
            if(matchExam && matchDept && matchType) {
                row.style.display = "";
            } else {
                row.style.display = "none";
            }
        }
    });
}
</script>
</body>
</html>
