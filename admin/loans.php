<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Vérifier si l'utilisateur est un administrateur
if ($_SESSION['user']['user_role_id'] != 1) {
    header('Location: ../user/dashboard.php');
    exit();
}

// Connexion à la base de données
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête
$query = "SELECT e.*, u.user_nom, u.user_login, l.titre, l.isbn, ex.code_barre
          FROM n_emprunts e
          JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
          JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
          JOIN n_livre l ON ex.id_livre = l.id_livre
          WHERE 1=1";

$params = [];

if (!empty($search)) {
    $query .= " AND (l.titre LIKE ? OR u.user_nom LIKE ? OR u.user_login LIKE ? OR ex.code_barre LIKE ?)";
    $params = array_merge($params, ["%$search%", "%$search%", "%$search%", "%$search%"]);
}

if ($status !== 'all') {
    $query .= " AND e.statut = ?";
    $params[] = $status;
}

// Compte total pour la pagination
$countStmt = $db->prepare(str_replace('e.*, u.user_nom', 'COUNT(*) as count', $query));
$countStmt->execute($params);
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
$totalPages = ceil($total / $limit);

// Ajout de la pagination et du tri à la requête principale
$query .= " ORDER BY e.date_emprunt DESC LIMIT $limit OFFSET $offset";
$stmt = $db->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Statistiques des emprunts
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM n_emprunts")->fetchColumn(),
    'actif' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'actif'")->fetchColumn(),
    'en_retard' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'en_retard'")->fetchColumn(),
    'termine' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'termine'")->fetchColumn()
];

// Définir le titre de la page
$page_title = "Gestion des emprunts";

// Inclure le header et le sidebar
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="content w-100 m-0 pt-5"  id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Gestion des emprunts</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#newLoanModal">
                    <i class="bi bi-plus-circle"></i> Nouvel emprunt
                </button>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <!-- Total Loans -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-primary mb-1">Total</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['total']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-primary bg-opacity-10 p-3">
                                <i class="bi bi-book text-primary fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts au total</p>
                    </div>
                </div>
            </div>

            <!-- Active Loans -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-success mb-1">En cours</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['actif']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                <i class="bi bi-clock-history text-success fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts actifs</p>
                    </div>
                </div>
            </div>

            <!-- Overdue Loans -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-danger mb-1">Retards</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['en_retard']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-danger bg-opacity-10 p-3">
                                <i class="bi bi-exclamation-circle text-danger fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts en retard</p>
                    </div>
                </div>
            </div>

            <!-- Completed Loans -->
            <div class="col-12 col-sm-6 col-xl-3">
                <div class="card h-100 border-0 shadow-sm rounded-3">
                    <div class="card-body p-4">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div>
                                <h5 class="card-title text-info mb-1">Terminés</h5>
                                <h2 class="display-6 mb-0 fw-bold"><?php echo $stats['termine']; ?></h2>
                            </div>
                            <div class="rounded-circle bg-info bg-opacity-10 p-3">
                                <i class="bi bi-check-circle text-info fs-4"></i>
                            </div>
                        </div>
                        <p class="text-muted mb-0">Emprunts terminés</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Search and Filter Section -->
        <div class="card border-0 shadow-sm rounded-3 mb-4">
            <div class="card-body">
                <form action="" method="GET" class="row g-3">
                    <div class="col-12 col-md-6">
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" name="search" 
                                   placeholder="Rechercher par titre, utilisateur ou code barre..." 
                                   value="<?php echo htmlspecialchars($search); ?>">
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <select class="form-select" name="status">
                            <option value="all" <?php echo $status === 'all' ? 'selected' : ''; ?>>Tous les statuts</option>
                            <option value="actif" <?php echo $status === 'actif' ? 'selected' : ''; ?>>En cours</option>
                            <option value="en_retard" <?php echo $status === 'en_retard' ? 'selected' : ''; ?>>En retard</option>
                            <option value="termine" <?php echo $status === 'termine' ? 'selected' : ''; ?>>Terminés</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-primary w-100">Filtrer</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loans Table Card -->
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 text-gray-800">Liste des emprunts</h5>
                    <span class="badge bg-primary rounded-pill">
                        <?php echo $total; ?> emprunt<?php echo $total > 1 ? 's' : ''; ?>
                    </span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="px-4">Livre</th>
                                <th>Code barre</th>
                                <th>Emprunteur</th>
                                <th>Date d'emprunt</th>
                                <th>Date de retour</th>
                                <th>Statut</th>
                                <th class="px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($loans)): ?>
                                <?php foreach ($loans as $loan): ?>
                                    <tr>
                                        <td class="px-4 fw-medium">
                                            <?php echo htmlspecialchars($loan['titre']); ?>
                                            <div class="small text-muted">ISBN: <?php echo htmlspecialchars($loan['isbn']); ?></div>
                                        </td>
                                        <td class="text-muted">
                                            <?php echo htmlspecialchars($loan['code_barre']); ?>
                                        </td>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <div class="rounded-circle bg-primary bg-opacity-10 d-flex align-items-center justify-content-center me-2" 
                                                     style="width: 32px; height: 32px;">
                                                    <span class="text-primary fw-bold">
                                                        <?php echo strtoupper(substr($loan['user_nom'], 0, 1)); ?>
                                                    </span>
                                                </div>
                                                <div>
                                                    <?php echo htmlspecialchars($loan['user_nom']); ?>
                                                    <div class="small text-muted"><?php echo htmlspecialchars($loan['user_login']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo date('d/m/Y', strtotime($loan['date_emprunt'])); ?></td>
                                        <td><?php echo date('d/m/Y', strtotime($loan['date_retour'])); ?></td>
                                        <td>
                                            <?php
                                            $statusClass = 'bg-secondary';
                                            switch ($loan['statut']) {
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
                                                <?php echo ucfirst($loan['statut']); ?>
                                            </span>
                                        </td>
                                        <td class="px-4">
                                            <div class="d-flex gap-2">
                                                <?php if ($loan['statut'] !== 'termine'): ?>
                                                    <button class="btn btn-sm btn-outline-success d-flex align-items-center gap-1"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#returnLoanModal"
                                                            data-id="<?php echo $loan['id_emprunt']; ?>"
                                                            data-titre="<?php echo htmlspecialchars($loan['titre']); ?>">
                                                        <i class="bi bi-check-circle"></i>
                                                        <span>Retourner</span>
                                                    </button>
                                                <?php endif; ?>
                                                <button class="btn btn-sm btn-outline-primary d-flex align-items-center gap-1"
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewLoanModal"
                                                        data-id="<?php echo $loan['id_emprunt']; ?>">
                                                    <i class="bi bi-eye"></i>
                                                    <span>Détails</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="text-center py-4 text-muted">
                                        <?php echo empty($search) ? 'Aucun emprunt trouvé' : 'Aucun résultat pour votre recherche'; ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page-1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                            <i class="bi bi-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = max(1, $page-2); $i <= min($totalPages, $page+2); $i++): ?>
                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $page >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $page+1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo $status; ?>">
                            <i class="bi bi-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<!-- New Loan Modal -->
<div class="modal fade" id="newLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-gray-800">Nouvel emprunt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?action=add" method="POST">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Code barre de l'exemplaire</label>
                        <input type="text" class="form-control form-control-lg" name="code_barre" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Identifiant de l'utilisateur</label>
                        <input type="text" class="form-control form-control-lg" name="user_login" required>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Date de retour prévue</label>
                        <input type="date" class="form-control form-control-lg" name="date_retour" required>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Return Loan Modal -->
<div class="modal fade" id="returnLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title text-gray-800">Retour d'emprunt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?action=return" method="POST" id="returnForm">
                <input type="hidden" name="id" id="returnId">
                <div class="modal-body">
                    <p class="text-muted mb-4">Confirmez-vous le retour du livre : <strong id="returnBookTitle"></strong> ?</p>
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">État du livre</label>
                        <select class="form-select form-select-lg" name="etat" required>
                            <option value="bon">Bon état</option>
                            <option value="abime">Abîmé</option>
                            <option value="perdu">Perdu</option>
                        </select>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Commentaire</label>
                        <textarea class="form-control" name="commentaire" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success px-4">Confirmer le retour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Return loan modal handler
    const returnModal = document.getElementById('returnLoanModal');
    if (returnModal) {
        returnModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const titre = button.getAttribute('data-titre');

            returnModal.querySelector('#returnId').value = id;
            returnModal.querySelector('#returnBookTitle').textContent = titre;
        });
    }
});
</script>

