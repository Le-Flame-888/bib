<?php
// bootstrap.php (in root folder)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config and core files
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../core/Router.php';
require_once __DIR__ . '/../models/Book.php';

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Instantiate the router
$router = new Router();

// Check if user is logged in and has the correct role
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 3) {
    // Redirect to login page or show an error
    header('Location: /auth/Connexion.php');
    exit();
}



require_once '../bootstrap.php'; 

// Récupérer les informations de l'utilisateur
$user_id = $_SESSION['user']['id'];
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Récupérer les emprunts en cours
$query_emprunts = $db->prepare("
    SELECT e.*, ex.code_barre, l.titre, l.isbn
    FROM n_emprunts e
    JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
    JOIN n_livre l ON ex.id_livre = l.id_livre
    WHERE e.id_utilisateur = ? AND e.statut = 'actif'
");
$query_emprunts->execute([$user_id]);
$emprunts_actifs = $query_emprunts->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les réservations en cours
$query_reservations = $db->prepare("
    SELECT r.*, l.titre
    FROM n_reservation r
    JOIN n_livre l ON r.id_livre = l.id_livre
    WHERE r.id_utilisateur = ? AND r.statut = 'en_attente'
");
$query_reservations->execute([$user_id]);
$reservations = $query_reservations->fetchAll(PDO::FETCH_ASSOC);

// Récupérer les notifications non lues
$query_notifications = $db->prepare("
    SELECT *
    FROM n_notifications
    WHERE id_utilisateur = ? AND est_lu = 0
    ORDER BY date_creation DESC
");
$query_notifications->execute([$user_id]);
$notifications = $query_notifications->fetchAll(PDO::FETCH_ASSOC);

// Définir le titre de la page
$page_title = "Tableau de bord";

// Inclure le header et sidebar
require_once '../Includes/Header.php';
require_once '../Includes/Sidebar.php';
?>

<!-- Main Content Area -->
<div class="content w-100 m-0 pt-5"  id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Mon tableau de bord</h1>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Active Loans Card -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-primary mb-1">Emprunts actifs</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo count($emprunts_actifs); ?></h2>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-book text-primary fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Livres actuellement empruntés</p>
                    </div>
                </div>
            </div>

            <!-- Reservations Card -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-success mb-1">Réservations</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo count($reservations); ?></h2>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-bookmark text-success fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Réservations en attente</p>
                    </div>
                </div>
            </div>

            <!-- Notifications Card -->
            <div class="col-12 col-sm-6 col-xl-4">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-info mb-1">Notifications</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo count($notifications); ?></h2>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-bell text-info fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-3">Notifications non lues</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Active Loans Section -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-gray-800">Mes emprunts en cours</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Titre</th>
                                        <th>Code barre</th>
                                        <th>Date d'emprunt</th>
                                        <th>Date de retour</th>
                                        <th class="px-4">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($emprunts_actifs)): ?>
                                        <?php foreach ($emprunts_actifs as $emprunt): ?>
                                            <tr>
                                                <td class="px-4"><?php echo htmlspecialchars($emprunt['titre']); ?></td>
                                                <td><?php echo htmlspecialchars($emprunt['code_barre']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($emprunt['date_emprunt'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?></td>
                                                <td class="px-4">
                                                    <span class="badge bg-primary rounded-pill">
                                                        <?php echo ucfirst($emprunt['statut']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="5" class="text-center py-4 text-muted">Aucun emprunt en cours</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Reservations and Notifications -->
        <div class="row g-4">
            <!-- Reservations -->
            <div class="col-12 col-xl-7">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-gray-800">Mes réservations</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="px-4">Titre</th>
                                        <th>Date de réservation</th>
                                        <th>Date d'expiration</th>
                                        <th class="px-4">Statut</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($reservations)): ?>
                                        <?php foreach ($reservations as $reservation): ?>
                                            <tr>
                                                <td class="px-4"><?php echo htmlspecialchars($reservation['titre']); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($reservation['date_reservation'])); ?></td>
                                                <td><?php echo date('d/m/Y', strtotime($reservation['date_expiration'])); ?></td>
                                                <td class="px-4">
                                                    <span class="badge bg-warning rounded-pill">
                                                        <?php echo ucfirst($reservation['statut']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="4" class="text-center py-4 text-muted">Aucune réservation en cours</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Notifications -->
            <div class="col-12 col-xl-5">
                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-header bg-white py-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0 text-gray-800">Notifications</h5>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php if (!empty($notifications)): ?>
                            <div class="list-group list-group-flush">
                                <?php foreach ($notifications as $notification): ?>
                                    <div class="list-group-item px-4 py-3">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <h6 class="mb-0 text-primary">
                                                <?php echo htmlspecialchars($notification['type']); ?>
                                            </h6>
                                            <small class="text-muted">
                                                <?php echo date('d/m/Y H:i', strtotime($notification['date_creation'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-0 text-muted small">
                                            <?php echo htmlspecialchars($notification['message']); ?>
                                        </p>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4 text-muted">
                                Aucune notification non lue
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Print functionality
    const printBtn = document.getElementById('printBtn');
    if (printBtn) {
        printBtn.addEventListener('click', function() {
            window.print();
        });
    }
    
    // Export functionality
    const exportBtn = document.getElementById('exportBtn');
    if (exportBtn) {
        exportBtn.addEventListener('click', function() {
            window.location.href = '<?php echo APP_URL; ?>/user/export_dashboard.php';
        });
    }
});
</script>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 