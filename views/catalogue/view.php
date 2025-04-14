<?php
require_once __DIR__ . '/../../config/config.php';

// Vérifier si l'ID du livre est fourni
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . APP_URL . '/views/catalogue/index.php');
    exit;
}

$bookId = (int)$_GET['id'];

try {
    $db = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME, DB_USER, DB_PASS);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Récupérer les détails du livre
    $query = "SELECT l.*, c.nom_categorie,
              (SELECT COUNT(*) = 0 FROM n_emprunts e 
               WHERE e.id_exemplaire = l.id_livre 
               AND e.date_retour_effective IS NULL) as is_available
              FROM n_livre l
              LEFT JOIN n_categorie_livres c ON l.id_categorie = c.id_categorie
              WHERE l.id_livre = :id";

    $stmt = $db->prepare($query);
    $stmt->execute(['id' => $bookId]);
    $book = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$book) {
        header('Location: ' . APP_URL . '/views/catalogue/index.php');
        exit;
    }

    // Définir les variables pour la vue
    $pageTitle = $book['titre'] . ' - ' . APP_NAME;
    $isAvailable = (bool)$book['is_available'];

} catch(PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}


?>
<?php
// Inclure l'en-tête
include __DIR__ . '/../../includes/header.php';
?>
<div class="content flex-grow-1 w-100 m-0 pt-5"  id="content">
    <div class="container-fluid py-5">
        <!-- Fil d'Ariane -->
        <nav aria-label="breadcrumb" class="mb-4">
            <ol class="breadcrumb">
                <li class="breadcrumb-item">
                    <a href="<?php echo APP_URL; ?>/views/catalogue/index.php" class="text-decoration-none">
                        <i class="fas fa-home me-1"></i>Catalogue
                    </a>
                </li>
                <li class="breadcrumb-item active" aria-current="page">
                    <?php echo htmlspecialchars($book['titre']); ?>
                </li>
            </ol>
        </nav>

        <div class="row">
            <!-- Colonne de gauche : Image et actions -->
            <div class="col-md-4 mb-4">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-img-top bg-light text-center p-5 rounded-top">
                        <i class="fas fa-book fa-6x text-primary"></i>
                    </div>
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <span class="badge <?php echo $isAvailable ? 'bg-success' : 'bg-danger'; ?> px-4 py-2">
                                <i class="fas <?php echo $isAvailable ? 'fa-check' : 'fa-times'; ?> me-1"></i>
                                <?php echo $isAvailable ? 'Disponible' : 'Indisponible'; ?>
                            </span>
                        </div>
                        <?php if ($isAvailable && isset($_SESSION['user'])): ?>
                            <a href="<?php echo APP_URL; ?>/loans/request/<?php echo $book['id_livre']; ?>" 
                            class="btn btn-primary w-100">
                                <i class="fas fa-book-reader me-2"></i>Emprunter ce livre
                            </a>
                        <?php elseif (!isset($_SESSION['user'])): ?>
                            <a href="<?php echo APP_URL; ?>/auth/Connexion.php" class="btn btn-outline-primary w-100">
                                <i class="fas fa-sign-in-alt me-2"></i>Connectez-vous pour emprunter
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Colonne de droite : Informations du livre -->
            <div class="col-md-8">
                <div class="card shadow-sm border-0 rounded-3 mb-4">
                    <div class="card-body">
                        <h1 class="h2 mb-4"><?php echo htmlspecialchars($book['titre']); ?></h1>
                        
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h5 class="text-muted mb-3">Informations générales</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-user text-primary me-2"></i>
                                        <strong>Auteur:</strong> <?php echo htmlspecialchars($book['auteur']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-folder text-primary me-2"></i>
                                        <strong>Catégorie:</strong> <?php echo htmlspecialchars($book['nom_categorie']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-calendar text-primary me-2"></i>
                                        <strong>Date de publication:</strong> <?php echo htmlspecialchars($book['date_publication']); ?>
                                    </li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h5 class="text-muted mb-3">Détails techniques</h5>
                                <ul class="list-unstyled">
                                    <li class="mb-2">
                                        <i class="fas fa-barcode text-primary me-2"></i>
                                        <strong>ISBN:</strong> <?php echo htmlspecialchars($book['isbn']); ?>
                                    </li>
                                    <?php if (!empty($book['editeur'])): ?>
                                    <li class="mb-2">
                                        <i class="fas fa-building text-primary me-2"></i>
                                        <strong>Éditeur:</strong> <?php echo htmlspecialchars($book['editeur']); ?>
                                    </li>
                                    <?php endif; ?>
                                    
                                    <li class="mb-2">
                                        <i class="fas fa-language text-primary me-2"></i>
                                        <strong>Mots clés:</strong> <?php echo htmlspecialchars($book['mots_cles']); ?>
                                    </li>
                                    <li class="mb-2">
                                        <i class="fas fa-language text-primary me-2"></i>
                                        <strong>Quantité:</strong> <?php echo htmlspecialchars($book['quantite_totale']); ?>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        <?php if (!empty($book['description'])): ?>
                        <div class="mt-4">
                            <h5 class="text-muted mb-3">
                                <i class="fas fa-align-left text-primary me-2"></i>Description
                            </h5>
                            <div class="p-3 bg-light rounded">
                                <?php echo nl2br(htmlspecialchars($book['description'])); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<style>
.breadcrumb {
    background: transparent;
    margin: 0;
    padding: 0;
}
.breadcrumb-item a {
    color: #0d6efd;
}
.breadcrumb-item.active {
    color: #6c757d;
}
.card {
    transition: transform 0.2s ease-in-out;
}
.card:hover {
    transform: translateY(-5px);
}
.badge {
    font-size: 0.9rem;
    border-radius: 20px;
}
.list-unstyled li {
    font-size: 1rem;
    line-height: 1.6;
}
.bg-light {
    background-color: #f8f9fa !important;
}
.btn-primary {
    padding: 12px 20px;
    border-radius: 5px;
    font-weight: 500;
}
.text-primary {
    color: #0d6efd !important;
}
.rounded-3 {
    border-radius: 15px !important;
}
</style>

<?php 
include __DIR__ . '/../../Includes/Footer.php'; 
?>