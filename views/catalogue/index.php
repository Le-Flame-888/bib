<?php
require_once __DIR__ . '/../../config/config.php';

// Load data
// Get books from database with pagination and filters
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$searchQuery = isset($_GET['search']) ? trim($_GET['search']) : '';
$currentCategory = isset($_GET['category']) ? (int)$_GET['category'] : null;

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Sélectionner tous les champs nécessaires
    $query = "SELECT b.*, c.nom_categorie,
              (SELECT COUNT(*) = 0 FROM n_emprunts e WHERE e.id_exemplaire = b.id_livre AND e.date_retour_effective IS NULL) as available
              FROM n_livre b 
              LEFT JOIN n_categorie_livres c ON b.id_categorie = c.id_categorie ";

    $params = [];
    $conditions = [];

    if ($searchQuery) {
        $conditions[] = "(
            b.titre LIKE :search 
            OR b.auteur LIKE :search 
            OR b.isbn LIKE :search 
            OR c.nom_categorie LIKE :search
            OR b.date_publication LIKE :search 
            OR COALESCE(b.mots_cles, '') LIKE :search
        )";
        $params[':search'] = "%$searchQuery%";
    }

    if ($currentCategory) {
        $conditions[] = "b.id_categorie = :category";
        $params[':category'] = $currentCategory;
    }

    if (!empty($conditions)) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    // Get total count for pagination
    $countQuery = "SELECT COUNT(*) as total FROM n_livre b";
    if (!empty($conditions)) {
        $countQuery .= " LEFT JOIN n_categorie_livres c ON b.id_categorie = c.id_categorie";
        $countQuery .= " WHERE " . implode(" AND ", $conditions);
    }
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $totalBooks = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];

    // Add pagination
    $itemsPerPage = defined('ITEMS_PER_PAGE') ? ITEMS_PER_PAGE : 12;
    $offset = ($currentPage - 1) * $itemsPerPage;
    $query .= " ORDER BY b.titre LIMIT " . $itemsPerPage . " OFFSET " . $offset;

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $books = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get categories
    $categoryQuery = "SELECT id_categorie, nom_categorie FROM n_categorie_livres ORDER BY nom_categorie";
    $categoryStmt = $db->prepare($categoryQuery);
    $categoryStmt->execute();
    $categories = $categoryStmt->fetchAll(PDO::FETCH_ASSOC);

    $totalPages = ceil($totalBooks / $itemsPerPage);

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
<!-- Reste du contenu HTML... -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle : APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="<?php echo APP_URL; ?>/public/css/style.css" rel="stylesheet">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary-custom">
        <div class="container">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>">
                <img src="<?php echo APP_URL; ?>/public/images/SupMTI - W Logo.png" alt="Library Logo" class="img-fluid" style="max-width: 150px;">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarMain">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo APP_URL; ?>/views/catalogue/index.php">
                            <i class="fas fa-book me-1"></i>Catalogue
                        </a>
                    </li>
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/loans">
                                <i class="fas fa-book-reader me-1"></i>Mes emprunts
                            </a>
                        </li>
                        <?php if ($_SESSION['user']['role'] === 'admin' || $_SESSION['user']['role'] === 'librarian'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="navbarAdmin" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-cog me-1"></i>Administration
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/books">Gestion des livres</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/users">Gestion des utilisateurs</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/loans">Gestion des emprunts</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/categories">Gestion des catégories</a></li>
                                    <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/reports">Rapports</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                <form class="d-flex me-3" action="index.php" method="GET">
                    <input class="form-control me-2" type="search" name="search" placeholder="Rechercher un livre..." required value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
                    <button class="btn btn-outline-light" type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarUser" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user me-1"></i><?php echo isset($_SESSION['user']['nom']) ? htmlspecialchars($_SESSION['user']['nom']) : 'User'; ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/profile">Mon profil</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/auth/logout">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="<?php echo APP_URL; ?>/auth/Connexion.php">Connexion</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    
<div class="container mt-5 py-4">
    <!-- Hero Section -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h1 class="display-4 mb-3">Catalogue de la Bibliothèque</h1>
            <p class="lead text-muted">Découvrez notre collection de livres et d'ouvrages</p>
        </div>
    </div>

    <!-- Search and Filter Section -->
    <div class="card mb-4 shadow-sm">
        <div class="card-body">
            <div class="row g-3">
                <!-- Search Bar -->
                <div class="col-md-8">
                    <form action="index.php" method="GET" class="d-flex">
                        <div class="input-group">
                            <span class="input-group-text bg-white">
                                <i class="fas fa-search text-primary"></i>
                            </span>
                            <input type="text" name="search" class="form-control" placeholder="Rechercher par titre, auteur, ISBN..." 
                                   value="<?php echo htmlspecialchars($searchQuery ?? ''); ?>">
                            <button type="submit" class="btn btn-primary">Rechercher</button>
                        </div>
                    </form>
                </div>
                <!-- Category Filter -->
                <div class="col-md-4">
                    <select class="form-select" onchange="window.location.href=this.value">
                        <option value="index.php" <?php echo !isset($currentCategory) ? 'selected' : ''; ?>>Toutes les catégories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="index.php?category=<?php echo urlencode($cat['id_categorie']); ?>" 
                                    <?php echo (isset($currentCategory) && $currentCategory == $cat['id_categorie']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Section -->
    <div class="row mb-4">
        <div class="col-12">
            <?php if (!empty($searchQuery) || isset($currentCategory)): ?>
                <div class="alert alert-info">
                    <?php if (!empty($searchQuery)): ?>
                        <i class="fas fa-search me-2"></i>Résultats pour : "<?php echo htmlspecialchars($searchQuery); ?>"
                    <?php endif; ?>
                    <?php if (isset($currentCategory)): ?>
                        <?php foreach ($categories as $cat): ?>
                            <?php if ($cat['id_categorie'] == $currentCategory): ?>
                                <i class="fas fa-folder me-2"></i>Catégorie : <?php echo htmlspecialchars($cat['nom_categorie']); ?>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Books Grid -->
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 row-cols-xl-4 g-4">
    <?php if (!empty($books)): ?>
        <?php foreach ($books as $book): ?>
            <div class="col">
                <div class="card h-100 shadow-sm hover-effect">
                    <div class="card-img-top bg-light text-center p-4">
                        <i class="fas fa-book fa-4x text-primary"></i>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title text-truncate" title="<?php echo htmlspecialchars($book['titre']); ?>">
                            <?php echo htmlspecialchars($book['titre']); ?>
                        </h5>
                        <p class="card-text mb-2">
                            <small class="text-muted">
                                <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($book['auteur']); ?>
                            </small>
                        </p>
                        <p class="card-text mb-2">
                            <small class="text-muted">
                                <i class="fas fa-folder me-1"></i><?php echo htmlspecialchars($book['nom_categorie']); ?>
                            </small>
                        </p>
                        <p class="card-text">
                            <span class="badge <?php echo $book['available'] ? 'bg-success' : 'bg-danger'; ?>">
                                <i class="fas <?php echo $book['available'] ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                <?php echo $book['available'] ? 'Disponible' : 'Indisponible'; ?>
                            </span>
                        </p>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="<?php echo APP_URL; ?>/views/catalogue/view.php?id=<?php echo $book['id_livre']; ?>" 
                           class="btn btn-primary w-100">
                            <i class="fas fa-info-circle me-1"></i>Détails
                        </a>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="col-12">
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>Aucun livre ne correspond à votre recherche
            </div>
        </div>
    <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="row mt-5">
        <div class="col-12">
            <nav aria-label="Navigation des pages">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?php echo $currentPage <= 1 ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?page=<?php echo $currentPage - 1; ?><?php echo isset($currentCategory) ? '&category=' . urlencode($currentCategory) : ''; ?><?php echo isset($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                            <i class="fas fa-chevron-left"></i>
                        </a>
                    </li>
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $currentPage == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="index.php?page=<?php echo $i; ?><?php echo isset($currentCategory) ? '&category=' . urlencode($currentCategory) : ''; ?><?php echo isset($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?php echo $currentPage >= $totalPages ? 'disabled' : ''; ?>">
                        <a class="page-link" href="index.php?page=<?php echo $currentPage + 1; ?><?php echo isset($currentCategory) ? '&category=' . urlencode($currentCategory) : ''; ?><?php echo isset($searchQuery) ? '&search=' . urlencode($searchQuery) : ''; ?>">
                            <i class="fas fa-chevron-right"></i>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.hover-effect {
    transition: transform 0.2s ease-in-out;
}
.hover-effect:hover {
    transform: translateY(-5px);
}
.card {
    border: none;
    border-radius: 10px;
}
.card-img-top {
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
}
.badge {
    padding: 8px 12px;
    border-radius: 20px;
}
.page-link {
    color: #0d6efd;
    border: none;
    padding: 0.5rem 1rem;
    margin: 0 3px;
    border-radius: 5px;
}
.page-link:hover {
    background-color: #0d6efd;
    color: white;
}
.page-item.active .page-link {
    background-color: #0d6efd;
    border: none;
}
.form-select, .form-control {
    border-radius: 5px;
    padding: 10px;
}
.btn-primary {
    padding: 10px 20px;
    border-radius: 5px;
}
</style>

<?php 
// Corriger le chemin du footer
include __DIR__ . '/../../Includes/Footer.php'; 
?>

</body>
</html>   