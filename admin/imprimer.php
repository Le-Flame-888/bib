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
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tableau de bord - Impression</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 20px;
            }
            .table {
                font-size: 12px;
            }
            .card {
                break-inside: avoid;
            }
            .page-break {
                page-break-before: always;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <!-- Print Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3">Rapport du tableau de bord</h1>
            <div class="no-print">
                <button onclick="window.print()" class="btn btn-primary">
                    <i class="bi bi-printer me-2"></i>Imprimer
                </button>
            </div>
        </div>

        <!-- Statistics Summary -->
        <div class="row g-4 mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">Statistiques générales</h5>
                        <div class="row">
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Total Utilisateurs:</strong></p>
                                <h4><?php echo $stats['total_users']; ?></h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Total Livres:</strong></p>
                                <h4><?php echo $stats['total_books']; ?></h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Emprunts Actifs:</strong></p>
                                <h4><?php echo $stats['active_loans']; ?></h4>
                            </div>
                            <div class="col-md-3">
                                <p class="mb-1"><strong>Emprunts en Retard:</strong></p>
                                <h4><?php echo $stats['overdue_loans']; ?></h4>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Loans -->
        <div class="card mb-4">
            <div class="card-body">
                <h5 class="card-title mb-3">Emprunts récents</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Utilisateur</th>
                                <th>Titre</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_loans as $loan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loan['user_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($loan['titre']); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['date_emprunt'])); ?></td>
                                    <td><?php echo date('d/m/Y', strtotime($loan['date_retour_prevue'])); ?></td>
                                    <td><?php echo ucfirst($loan['statut']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Active Users -->
        <div class="card">
            <div class="card-body">
                <h5 class="card-title mb-3">Utilisateurs actifs</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Login</th>
                                <th>Dernière connexion</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($active_users as $user): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($user['user_nom']); ?></td>
                                    <td><?php echo htmlspecialchars($user['user_login']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($user['user_dern_conx'])); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="mt-4 text-center">
            <p class="text-muted">Généré le <?php echo date('d/m/Y H:i'); ?></p>
        </div>
    </div>

    <script>
        // Auto-print when the page loads
        window.onload = function() {
            if (!window.location.search.includes('noprint')) {
                window.print();
            }
        };
    </script>
</body>
</html> 