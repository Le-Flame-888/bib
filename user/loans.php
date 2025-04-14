<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Vérifier si l'utilisateur est un utilisateur standard
if ($_SESSION['user']['user_role_id'] != 3) {
    header('Location: ../admin/dashboard.php');
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les emprunts de l'utilisateur
    $query = "SELECT e.*, u.user_nom, u.user_login, l.titre, l.isbn, ex.code_barre
              FROM n_emprunts e
              JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
              JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
              JOIN n_livre l ON ex.id_livre = l.id_livre
              WHERE e.id_utilisateur = ?
              ORDER BY e.date_emprunt DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['user']['user_id']]);
    $emprunts = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    $error = "Une erreur est survenue lors de la récupération des emprunts.";
}

// Définir le titre de la page
$page_title = "Mes emprunts";

// Inclure le header et le sidebar
//require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="content w-100 m-0 pt-3" id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Mes emprunts</h1>
            <div class="d-flex gap-2">
                <span class="badge bg-primary rounded-pill px-3 py-2">
                    <i class="bi bi-book me-1"></i>
                    <?php echo count($emprunts); ?> emprunt<?php echo count($emprunts) > 1 ? 's' : ''; ?>
                </span>
            </div>
        </div>

        <?php if (isset($error)): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Emprunts Table -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Livre</th>
                                <th>Code barre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($emprunts)): ?>
                                <?php foreach ($emprunts as $emprunt): ?>
                                    <tr>
                                        <td class="px-4 fw-medium">
                                            <?php echo htmlspecialchars($emprunt['titre']); ?>
                                            <div class="small text-muted">ISBN: <?php echo htmlspecialchars($emprunt['isbn']); ?></div>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo htmlspecialchars($emprunt['code_barre']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($emprunt['date_retour'])); ?>
                                            <?php if(strtotime($emprunt['date_retour']) < time() && $emprunt['statut'] !== 'termine'): ?>
                                                <div class="small text-danger">En retard</div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            switch ($emprunt['statut']) {
                                                case 'actif':
                                                    $statusClass = 'bg-success';
                                                    break;
                                                case 'en_retard':
                                                    $statusClass = 'bg-danger';
                                                    break;
                                                case 'termine':
                                                    $statusClass = 'bg-info';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $statusClass; ?> bg-opacity-10 text-<?php echo str_replace('bg-', '', $statusClass); ?> rounded-pill px-3">
                                                <?php echo ucfirst($emprunt['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Vous n'avez aucun emprunt en cours
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
