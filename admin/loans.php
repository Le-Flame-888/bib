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
try {
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    error_log("Database connection successful.");
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    exit("Database connection error.");
}

// Traitement du retour d'un emprunt
if (isset($_GET['action']) && $_GET['action'] === 'return' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        if (!isset($_POST['id']) || !isset($_POST['etat'])) {
            throw new Exception("Données manquantes pour le retour");
        }

        // Mettre à jour l'emprunt
        $updateQuery = "UPDATE n_emprunts SET 
                       statut = 'termine',
                       date_retour_effective = NOW(),
                       etat_retour = ?,
                       commentaire_retour = ?
                       WHERE id_emprunt = ?";
        
        $updateStmt = $db->prepare($updateQuery);
        $result = $updateStmt->execute([
            $_POST['etat'],
            $_POST['commentaire'] ?? null,
            $_POST['id']
        ]);

        if ($result) {
            header('Location: loans.php?success=return');
        } else {
            throw new Exception("Erreur lors de l'enregistrement du retour");
        }
        exit();
    } catch (Exception $e) {
        $error = $e->getMessage();
        header('Location: loans.php?error=' . urlencode($error));
        exit();
    }
}

// Traitement de la création d'un nouvel emprunt
if (isset($_GET['action']) && $_GET['action'] === 'add' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Debug: Print POST data
        error_log("POST data: " . print_r($_POST, true));
        
        // Vérifier que tous les champs requis sont présents
        if (!isset($_POST['livre_id']) || !isset($_POST['user_id']) || !isset($_POST['date_retour'])) {
            throw new Exception("Tous les champs sont requis");
        }

        // Récupérer un exemplaire disponible du livre
        $exemplaireQuery = "SELECT id_exemplaire 
                           FROM n_exemplaires 
                           WHERE id_livre = ? 
                           AND id_exemplaire NOT IN (
                               SELECT id_exemplaire 
                               FROM n_emprunts 
                               WHERE statut IN ('actif', 'en_retard')
                           )
                           LIMIT 1";
        $exemplaireStmt = $db->prepare($exemplaireQuery);
        $exemplaireStmt->execute([$_POST['livre_id']]);
        $exemplaire = $exemplaireStmt->fetch(PDO::FETCH_ASSOC);

        if (!$exemplaire) {
            throw new Exception("Aucun exemplaire disponible pour ce livre");
        }

        // Debug: Print exemplaire data
        error_log("Exemplaire found: " . print_r($exemplaire, true));

        // Insérer le nouvel emprunt
        $insertQuery = "INSERT INTO n_emprunts (id_exemplaire, id_utilisateur, date_emprunt, date_retour_prevue, statut) 
                       VALUES (?, ?, CURRENT_DATE(), ?, 'actif')";
        
        $insertStmt = $db->prepare($insertQuery);
        $result = $insertStmt->execute([
            $exemplaire['id_exemplaire'],
            $_POST['user_id'],
            $_POST['date_retour']
        ]);

        // Debug: Print insert result and any errors
        error_log("Insert result: " . ($result ? "success" : "failed"));
        if (!$result) {
            error_log("PDO Error Info: " . print_r($insertStmt->errorInfo(), true));
            throw new Exception("Erreur lors de l'enregistrement de l'emprunt");
        }

        // Rediriger avec un message de succès
        header('Location: loans.php?success=add');
        exit();
    } catch (Exception $e) {
        error_log("Error creating loan: " . $e->getMessage());
        header('Location: loans.php?error=' . urlencode($e->getMessage()));
        exit();
    }
}

// Récupération des détails d'un emprunt pour le modal
if (isset($_GET['action']) && $_GET['action'] === 'get_details' && isset($_GET['id'])) {
    try {
        error_log("Fetching details for loan ID: " . $_GET['id']);
        
        $detailsQuery = "SELECT e.id_emprunt, e.date_emprunt, e.date_retour, e.statut,
                               e.date_retour_effective, e.etat_retour, e.commentaire_retour,
                               u.user_nom, u.user_login, 
                               l.titre, l.isbn, l.auteur,
                               ex.code_barre
                        FROM n_emprunts e
                        JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
                        JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
                        JOIN n_livre l ON ex.id_livre = l.id_livre
                        WHERE e.id_emprunt = ?";
        
        error_log("Query: " . $detailsQuery);
        
        $detailsStmt = $db->prepare($detailsQuery);
        $detailsStmt->execute([$_GET['id']]);
        $details = $detailsStmt->fetch(PDO::FETCH_ASSOC);
        
        error_log("Details found: " . print_r($details, true));
        
        if ($details) {
            // Formater les dates pour l'affichage
            $details['date_emprunt'] = date('d/m/Y', strtotime($details['date_emprunt']));
            $details['date_retour_prevue'] = date('d/m/Y', strtotime($details['date_retour']));
            if ($details['date_retour_effective']) {
                $details['date_retour_effective'] = date('d/m/Y', strtotime($details['date_retour_effective']));
            }
            
            error_log("Formatted details: " . print_r($details, true));
            
            header('Content-Type: application/json');
            echo json_encode($details);
        } else {
            error_log("No details found for loan ID: " . $_GET['id']);
            http_response_code(404);
            echo json_encode(['error' => 'Emprunt non trouvé']);
        }
        exit();
    } catch (Exception $e) {
        error_log("Error fetching loan details: " . $e->getMessage());
        http_response_code(500);
        echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
        exit();
    }
}

// Debug: Print all loans in database
$debugQuery = "SELECT e.*, u.user_nom, l.titre 
               FROM n_emprunts e 
               JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id 
               JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire 
               JOIN n_livre l ON ex.id_livre = l.id_livre 
               ORDER BY e.date_emprunt DESC";
$debugStmt = $db->query($debugQuery);
error_log("All loans in database: " . print_r($debugStmt->fetchAll(PDO::FETCH_ASSOC), true));

// Récupérer la liste des livres disponibles
$livresQuery = "SELECT l.id_livre, l.titre, l.isbn, l.auteur,
                COUNT(e.id_exemplaire) as total_exemplaires,
                COUNT(CASE WHEN em.statut IN ('actif', 'en_retard') THEN 1 END) as exemplaires_empruntes
                FROM n_livre l
                LEFT JOIN n_exemplaires e ON l.id_livre = e.id_livre 
                LEFT JOIN n_emprunts em ON e.id_exemplaire = em.id_exemplaire
                GROUP BY l.id_livre
                HAVING total_exemplaires > exemplaires_empruntes
                ORDER BY l.titre";

$livresStmt = $db->prepare($livresQuery);
$livresStmt->execute();
$livresDisponibles = $livresStmt->fetchAll(PDO::FETCH_ASSOC);

// Pagination
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Filtres
$status = isset($_GET['status']) ? $_GET['status'] : 'all';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// Construction de la requête
$query = "SELECT e.*, e.date_retour_prevue as date_retour_prevue, u.user_nom, u.user_login, l.titre, l.isbn, ex.code_barre, e.id_emprunt
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
$countStmt = $db->prepare(str_replace('e.*, e.date_retour_prevue as date_retour_prevue', 'COUNT(*) as count', $query));
$countStmt->execute($params);
$total = $countStmt->fetch(PDO::FETCH_ASSOC)['count'];
$totalPages = ceil($total / $limit);

// Ajout de la pagination et du tri à la requête principale
$query .= " ORDER BY e.date_emprunt DESC LIMIT $limit OFFSET $offset";

// Debug: Print the final query
error_log("Main query: " . $query);
error_log("Query params: " . print_r($params, true));

$stmt = $db->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Debug: Print the results
error_log("Loans fetched: " . print_r($loans, true));

// Statistiques des emprunts
$stats = [
    'total' => $db->query("SELECT COUNT(*) FROM n_emprunts")->fetchColumn(),
    'actif' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'actif'")->fetchColumn(),
    'en_retard' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'en_retard'")->fetchColumn(),
    'termine' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'termine'")->fetchColumn(),
];

// Add success message display
if (isset($_GET['success'])) {
    $success_message = '';
    switch ($_GET['success']) {
        case 'return':
            $success_message = "Le retour a été enregistré avec succès.";
            break;
        case 'add':
            $success_message = "L'emprunt a été créé avec succès.";
            break;
    }
}

// Définir le titre de la page
$page_title = "Gestion des emprunts";

// Inclure le header et le sidebar
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="content p-3"  id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Gestion des emprunts</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#empruntModal">
                    <i class="bi bi-plus-circle"></i> Nouvel emprunt
                </button>
            </div>
        </div>

        <?php if (isset($success_message)): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars($success_message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php echo htmlspecialchars(urldecode($_GET['error'])); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

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
                                <th class="text-end pe-4">Actions</th>
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
                                                    <?php echo htmlspecialchars($loan['user_nom']); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($loan['date_emprunt'])); ?>
                                        </td>
                                        <td>
                                            <?php echo date('d/m/Y', strtotime($loan['date_retour_prevue'])); ?>
                                            <?php if(strtotime($loan['date_retour_prevue']) < time() && $loan['statut'] !== 'termine'): ?>
                                                <div class="small text-danger">En retard</div>
                                            <?php endif; ?>
                                        </td>
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
                                        <td class="text-end pe-4">
                                                <?php if ($loan['statut'] !== 'termine'): ?>
                                                <button type="button" class="btn btn-outline-success btn-sm" 
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#returnLoanModal"
                                                            data-id="<?php echo $loan['id_emprunt']; ?>"
                                                            data-titre="<?php echo htmlspecialchars($loan['titre']); ?>">
                                                    <i class="bi bi-check-circle me-1"></i> Retourner
                                                    </button>
                                                <?php endif; ?>
                                            <button type="button" class="btn btn-outline-primary btn-sm ms-2" 
                                                        data-bs-toggle="modal"
                                                        data-bs-target="#viewLoanModal"
                                                    data-id="<?php echo $loan['id_emprunt']; ?>"
                                                    data-titre="<?php echo htmlspecialchars($loan['titre']); ?>">
                                                <i class="bi bi-info-circle me-1"></i> Détails
                                                </button>
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

<!-- Modal Emprunt -->
<div class="modal fade" id="empruntModal" tabindex="-1" aria-labelledby="empruntModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="empruntModalLabel">Nouvel emprunt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="?action=add" method="POST">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Livre</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchLivre" 
                                   placeholder="Rechercher par titre, auteur ou ISBN..." autocomplete="off"
                                   list="livresList" required>
                            <input type="hidden" name="livre_id" id="selectedLivreId" required>
                            <datalist id="livresList">
                                <?php
                                $livresQuery = $db->query("SELECT l.id_livre, l.titre, l.isbn 
                                                         FROM n_livre l 
                                                         WHERE EXISTS (
                                                             SELECT 1 FROM n_exemplaires e 
                                                             WHERE e.id_livre = l.id_livre 
                                                             AND e.id_exemplaire NOT IN (
                                                                 SELECT id_exemplaire FROM n_emprunts 
                                                                 WHERE statut IN ('actif', 'en_retard')
                                                             )
                                                         )
                                                         ORDER BY l.titre");
                                while($livre = $livresQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($livre['titre']) . ' (ISBN: ' . htmlspecialchars($livre['isbn']) . ')" data-id="' . $livre['id_livre'] . '">';
                                }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Utilisateur</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-person text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchUser" 
                                   placeholder="Rechercher un utilisateur..." autocomplete="off"
                                   list="usersList" required>
                            <input type="hidden" name="user_id" id="selectedUserId" required>
                            <datalist id="usersList">
                                <?php
                                $usersQuery = $db->query("SELECT user_id, user_nom FROM n_utilisateurs WHERE user_role_id = 3 ORDER BY user_nom");
                                while($user = $usersQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($user['user_nom']) . '" data-id="' . $user['user_id'] . '">';
                                }
                                ?>
                            </datalist>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Date de retour prévue</label>
                        <input type="date" class="form-control" name="date_retour" required
                               min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>"
                               max="<?php echo date('Y-m-d', strtotime('+30 days')); ?>"
                               value="<?php echo date('Y-m-d', strtotime('+15 days')); ?>">
                        <div class="form-text">La durée maximale d'emprunt est de 30 jours</div>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary px-4">Enregistrer</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Retour -->
<div class="modal fade" id="returnLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Retour de livre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="return_loan.php" method="POST">
                <input type="hidden" name="id" id="returnLoanId">
                <div class="modal-body">
                    <p class="mb-4">Vous êtes sur le point d'enregistrer le retour du livre :</p>
                    <p class="fw-bold mb-4" id="returnLoanTitle"></p>
                    
                    <div class="mb-3">
                        <label class="form-label">État du livre</label>
                        <select class="form-select" name="etat" required>
                            <option value="bon">Bon état</option>
                            <option value="moyen">État moyen</option>
                            <option value="mauvais">Mauvais état</option>
                            <option value="perdu">Perdu</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Commentaire (optionnel)</label>
                        <textarea class="form-control" name="commentaire" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer border-0">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-success">Confirmer le retour</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Détails -->
<div class="modal fade" id="viewLoanModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title">Détails de l'emprunt</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <h6 class="mb-3">Informations du livre</h6>
                <p class="mb-2"><strong>Titre:</strong> <span id="detailsLoanTitle"></span></p>
                <p class="mb-2"><strong>ISBN:</strong> <span id="detailsLoanIsbn"></span></p>
                <p class="mb-2"><strong>Code barre:</strong> <span id="detailsLoanBarcode"></span></p>
                
                <hr>
                
                <h6 class="mb-3">Informations de l'emprunt</h6>
                <p class="mb-2"><strong>Emprunteur:</strong> <span id="detailsLoanUser"></span></p>
                <p class="mb-2"><strong>Date d'emprunt:</strong> <span id="detailsLoanBorrowDate"></span></p>
                <p class="mb-2"><strong>Date de retour prévue:</strong> <span id="detailsLoanReturnDate"></span></p>
                <p class="mb-2"><strong>Statut:</strong> <span id="detailsLoanStatus"></span></p>
                
                <div id="detailsLoanReturnInfo" class="d-none">
                    <!-- Les informations de retour seront injectées ici par JavaScript -->
                </div>
            </div>
            <div class="modal-footer border-0">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Fermer</button>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Gestion de la sélection du livre
    const searchLivre = document.getElementById('searchLivre');
    const selectedLivreId = document.getElementById('selectedLivreId');
    const form = document.querySelector('#empruntModal form');

    searchLivre.addEventListener('input', function(e) {
        const datalist = document.getElementById('livresList');
        const options = datalist.getElementsByTagName('option');
        const value = e.target.value;

        for (let option of options) {
            if (option.value === value) {
                selectedLivreId.value = option.getAttribute('data-id');
                console.log('Livre sélectionné:', option.value, 'ID:', selectedLivreId.value);
                return;
            }
        }
        selectedLivreId.value = '';
    });

    // Gestion de la sélection de l'utilisateur
    const searchUser = document.getElementById('searchUser');
    const selectedUserId = document.getElementById('selectedUserId');

    searchUser.addEventListener('input', function(e) {
        const datalist = document.getElementById('usersList');
        const options = datalist.getElementsByTagName('option');
        const value = e.target.value;

        for (let option of options) {
            if (option.value === value) {
                selectedUserId.value = option.getAttribute('data-id');
                console.log('Utilisateur sélectionné:', option.value, 'ID:', selectedUserId.value);
                return;
            }
        }
        selectedUserId.value = '';
    });

    // Validation du formulaire
    form.addEventListener('submit', function(e) {
        if (!selectedLivreId.value || !selectedUserId.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un livre et un utilisateur');
            return false;
        }
        console.log('Soumission du formulaire:', {
            livre_id: selectedLivreId.value,
            user_id: selectedUserId.value,
            date_retour: form.querySelector('[name="date_retour"]').value
        });
    });

    // Return loan modal handler
    const returnModal = document.getElementById('returnLoanModal');
    if (returnModal) {
        returnModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');
            const titre = button.getAttribute('data-titre');

            document.getElementById('returnLoanId').value = id;
            document.getElementById('returnLoanTitle').textContent = titre;
        });
    }

    // View loan details modal handler
    const viewModal = document.getElementById('viewLoanModal');
    if (viewModal) {
        viewModal.addEventListener('show.bs.modal', function(event) {
            const button = event.relatedTarget;
            const id = button.getAttribute('data-id');

            // Fetch loan details
            fetch(`loans.php?action=get_details&id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    // Book information
                    document.getElementById('detailsLoanTitle').textContent = data.titre;
                    document.getElementById('detailsLoanIsbn').textContent = data.isbn;
                    document.getElementById('detailsLoanBarcode').textContent = data.code_barre;
                    document.getElementById('detailsLoanUser').textContent = data.user_nom;
                    document.getElementById('detailsLoanBorrowDate').textContent = data.date_emprunt;
                    document.getElementById('detailsLoanReturnDate').textContent = data.date_retour_prevue;
                    document.getElementById('detailsLoanStatus').textContent = data.statut.charAt(0).toUpperCase() + data.statut.slice(1);
                    
                    // Afficher les informations de retour si l'emprunt est terminé
                    const returnInfoDiv = document.getElementById('detailsLoanReturnInfo');
                    if (data.statut === 'termine' && data.date_retour_effective) {
                        returnInfoDiv.innerHTML = `
                            <hr>
                            <h6 class="mb-3">Informations de retour</h6>
                            <p class="mb-2"><strong>Date de retour effective:</strong> ${data.date_retour_effective}</p>
                            <p class="mb-2"><strong>État au retour:</strong> ${data.etat_retour || 'Non spécifié'}</p>
                            ${data.commentaire_retour ? `<p class="mb-0"><strong>Commentaire:</strong> ${data.commentaire_retour}</p>` : ''}
                        `;
                        returnInfoDiv.classList.remove('d-none');
                    } else {
                        returnInfoDiv.classList.add('d-none');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('Une erreur est survenue lors du chargement des détails');
                });
        });
    }
});
</script>

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

<script src="../public/js/Sidebar.js"></script>