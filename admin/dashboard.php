<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Connect to database
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Get statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM n_utilisateurs")->fetchColumn(),
    'total_books' => $db->query("SELECT COUNT(*) FROM n_livre")->fetchColumn(),
    'active_loans' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'actif'")->fetchColumn(),
    'overdue_loans' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'en_retard'")->fetchColumn()
];

// Get recent loans
$query = $db->query("
    SELECT e.*, u.user_nom, u.user_login, l.titre
    FROM n_emprunts e
    JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
    JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
    JOIN n_livre l ON ex.id_livre = l.id_livre
    ORDER BY e.date_emprunt DESC
    LIMIT 10
");
$recent_loans = $query->fetchAll(PDO::FETCH_ASSOC);

// Get active users
$query = $db->query("
    SELECT user_id, user_nom, user_login, user_dern_conx
    FROM n_utilisateurs
    WHERE user_dern_conx IS NOT NULL
    ORDER BY user_dern_conx DESC
    LIMIT 10
");
$active_users = $query->fetchAll(PDO::FETCH_ASSOC);

// Set page title
$page_title = "Administration";

// Include header and sidebar
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="content p-3" id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Tableau de bord administrateur</h1>
            <div>
                <a href="<?php echo APP_URL; ?>/admin/export_dashboard.php" class="btn btn-primary">
                    <i class="bi bi-download me-2"></i>Exporter
                </a>
                <a href="<?php echo APP_URL; ?>/admin/imprimer.php" class="btn btn-outline-primary">
                    <i class="bi bi-download me-2"></i>imprimer
                </a>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Users Card -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-primary mb-1">Utilisateurs</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['total_users']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-people text-primary fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Total des utilisateurs</p>
                    </div>
                </div>
            </div>

            <!-- Total Books Card -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-success mb-1">Livres</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['total_books']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-book text-success fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Total des livres</p>
                    </div>
                </div>
            </div>

            <!-- Active Loans Card -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-info mb-1">Emprunts actifs</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['active_loans']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-bookmark text-info fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts en cours</p>
                    </div>
                </div>
            </div>

            <!-- Overdue Loans Card -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-danger mb-1">Retards</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['overdue_loans']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="bi bi-exclamation-circle text-danger fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts en retard</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Loans Section -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-gray-800">Emprunts récents</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Utilisateur</th>
                                <th>Titre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_loans)): ?>
                                <?php foreach ($recent_loans as $loan): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($loan['user_nom']); ?></td>
                                        <td><?php echo htmlspecialchars($loan['titre']); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($loan['date_emprunt'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($loan['date_retour_prevue'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $loan['statut'] == 'actif' ? 'primary' : 'danger'; ?> rounded-pill">
                                                <?php echo ucfirst($loan['statut']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">Aucun emprunt récent</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Users Section -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 text-gray-800">Utilisateurs actifs</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Nom</th>
                                <th>Dernière connexion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($active_users)): ?>
                                <?php foreach ($active_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_nom']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($user['user_dern_conx'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-4 text-muted">Aucun utilisateur actif récemment</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add CSS for responsive behavior -->
<style>
    .content {
        transition: margin-left 0.3s ease;
        margin-left: 250px; /* Default sidebar width */
    }
    
    body.sidebar-collapsed .content {
        margin-left: 0; /* When sidebar is collapsed */
    }
    
    @media (max-width: 768px) {
        .content {
            margin-left: 0;
        }
    }
</style>

<!-- Add JavaScript for sidebar toggle -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Function to toggle sidebar state
        function toggleSidebar() {
            document.body.classList.toggle('sidebar-collapsed');
        }
        
        // Add event listener to the sidebar toggle button
        const sidebarToggle = document.querySelector('.navbar-toggler, #sidebarToggle');
        if (sidebarToggle) {
            sidebarToggle.addEventListener('click', toggleSidebar);
        }
        
        // Check for persisted sidebar state
        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            document.body.classList.add('sidebar-collapsed');
        }
        
        // Save sidebar state when changed
        document.body.addEventListener('classChange', function() {
            localStorage.setItem('sidebarCollapsed', document.body.classList.contains('sidebar-collapsed'));
        });
        
        // Create and dispatch custom event when sidebar class changes
        const observer = new MutationObserver(function(mutations) {
            mutations.forEach(function(mutation) {
                if (mutation.attributeName === 'class') {
                    document.body.dispatchEvent(new CustomEvent('classChange'));
                }
            });
        });
        
        observer.observe(document.body, { attributes: true });
    });
</script>

</body>
</html>