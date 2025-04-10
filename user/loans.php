<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Vérifier si l'utilisateur est un administrateur
if ($_SESSION['user']['user_role_id'] != 3) {
    header('Location: ../admin/dashboard.php');
    exit();
}

// Connexion à la base de données
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$message = '';

switch ($action) {
    case 'borrow':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $id_exemplaire = $_POST['id_exemplaire'] ?? '';
            $date_retour_prevue = $_POST['date_retour_prevue'] ?? '';
            
            if (!empty($id_exemplaire) && !empty($date_retour_prevue)) {
                // Vérifier si l'exemplaire est disponible
                $stmt = $db->prepare("SELECT statut FROM n_exemplaires WHERE id_exemplaire = ?");
                $stmt->execute([$id_exemplaire]);
                $exemplaire = $stmt->fetch();
                
                if ($exemplaire && $exemplaire['statut'] === 'disponible') {
                    // Vérifier le nombre d'emprunts actifs de l'utilisateur
                    $stmt = $db->prepare("
                        SELECT COUNT(*) FROM n_emprunts 
                        WHERE id_utilisateur = ? AND statut = 'actif'
                    ");
                    $stmt->execute([$_SESSION['user']['id']]);
                    $emprunts_actifs = $stmt->fetchColumn();
                    
                    if ($emprunts_actifs < 3) { // Limite de 3 emprunts simultanés
                        // Créer l'emprunt
                        $stmt = $db->prepare("
                            INSERT INTO n_emprunts (id_utilisateur, id_exemplaire, date_retour_prevue) 
                            VALUES (?, ?, ?)
                        ");
                        if ($stmt->execute([$_SESSION['user']['id'], $id_exemplaire, $date_retour_prevue])) {
                            // Mettre à jour le statut de l'exemplaire
                            $stmt = $db->prepare("UPDATE n_exemplaires SET statut = 'emprunté' WHERE id_exemplaire = ?");
                            $stmt->execute([$id_exemplaire]);
                            $message = '<div class="alert alert-success">Emprunt enregistré avec succès.</div>';
                        } else {
                            $message = '<div class="alert alert-danger">Erreur lors de l\'enregistrement de l\'emprunt.</div>';
                        }
                    } else {
                        $message = '<div class="alert alert-warning">Vous avez atteint la limite de 3 emprunts simultanés.</div>';
                    }
                } else {
                    $message = '<div class="alert alert-danger">Cet exemplaire n\'est pas disponible.</div>';
                }
            }
        }
        break;
}

// Récupérer les emprunts de l'utilisateur
$emprunts = $db->prepare("
    SELECT e.*, l.titre as livre_titre, ex.code_barre
    FROM n_emprunts e
    JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
    JOIN n_livre l ON ex.id_livre = l.id_livre
    WHERE e.id_utilisateur = ?
    ORDER BY e.date_emprunt DESC
");
$emprunts->execute([$_SESSION['user']['id']]);
$emprunts = $emprunts->fetchAll(PDO::FETCH_ASSOC);

// Récupérer la liste des exemplaires disponibles
$exemplaires = $db->query("
    SELECT ex.id_exemplaire, ex.code_barre, l.titre, l.isbn
    FROM n_exemplaires ex
    JOIN n_livre l ON ex.id_livre = l.id_livre
    WHERE ex.statut = 'disponible'
    ORDER BY l.titre
")->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Mes emprunts";
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h2 text-dark">Mes emprunts</h1>
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#borrowBookModal">
            <i class="bi bi-plus-circle"></i> Emprunter un livre
        </button>
    </div>

    <?php echo $message; ?>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>Livre</th>
                            <th>Code exemplaire</th>
                            <th>Date emprunt</th>
                            <th>Date retour prévue</th>
                            <th>Statut</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($emprunts)): ?>
                            <tr>
                                <td colspan="5" class="text-center">Vous n'avez pas d'emprunts en cours</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($emprunts as $emprunt): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($emprunt['livre_titre']); ?></td>
                                    <td><?php echo htmlspecialchars($emprunt['code_exemplaire']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?></td>
                                    <td>
                                        <?php
                                        $statut_class = '';
                                        switch ($emprunt['statut']) {
                                            case 'actif':
                                                $statut_class = 'text-primary';
                                                break;
                                            case 'rendu':
                                                $statut_class = 'text-success';
                                                break;
                                            case 'en_retard':
                                                $statut_class = 'text-danger';
                                                break;
                                            case 'perdu':
                                                $statut_class = 'text-warning';
                                                break;
                                        }
                                        ?>
                                        <span class="<?php echo $statut_class; ?>">
                                            <?php echo ucfirst($emprunt['statut']); ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Emprunt de livre -->
<div class="modal fade" id="borrowBookModal" tabindex="-1" aria-labelledby="borrowBookModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark" id="borrowBookModalLabel">Emprunter un livre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?action=borrow" method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="id_exemplaire" class="form-label text-dark">Livre (exemplaire)</label>
                        <select class="form-select" id="id_exemplaire" name="id_exemplaire" required>
                            <option value="">Sélectionner un exemplaire</option>
                            <?php foreach ($exemplaires as $exemplaire): ?>
                                <option value="<?php echo $exemplaire['id_exemplaire']; ?>">
                                    <?php echo htmlspecialchars($exemplaire['titre'] . ' (ISBN: ' . $exemplaire['isbn'] . ' - Code: ' . $exemplaire['code_exemplaire'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="date_retour_prevue" class="form-label text-dark">Date de retour prévue</label>
                        <input type="date" class="form-control" id="date_retour_prevue" name="date_retour_prevue" 
                               required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>">
                        <small class="form-text text-muted">La durée maximale d'emprunt est de 30 jours</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Emprunter</button>
                </div>
            </form>
        </div>
    </div>
</div>

