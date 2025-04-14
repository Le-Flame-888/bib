<?php
require_once '../bootstrap.php';
require_once '../config/config.php';
require_once '../controllers/EmpruntsController.php';

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

// Initialiser le contrôleur
$controller = new EmpruntsController();

// Récupérer les emprunts de l'utilisateur
try {
    $emprunts = $controller->getUserEmprunts($_SESSION['user']['id']);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Définir le titre de la page
$page_title = "Mes emprunts";

// Inclure le header et le sidebar
require_once '../includes/header.php';
require_once '../includes/sidebar.php';
?>

<!-- Main Content Area -->
<div class="content p-3" id="content">
    <div class="container-fluid p-4">
        <!-- Header Section -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h3 mb-0 text-gray-800">Mes emprunts</h1>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-primary d-flex align-items-center gap-2" data-bs-toggle="modal" data-bs-target="#empruntModal">
                    <i class="bi bi-plus-circle"></i> Emprunter un livre
                </button>
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
                                            <?php echo date('d/m/Y', strtotime($emprunt['date_retour_prevue'])); ?>
                                            <?php if(strtotime($emprunt['date_retour_prevue']) < time() && $emprunt['statut'] !== 'termine'): ?>
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

<!-- Modal Emprunt -->
<div class="modal fade" id="empruntModal" tabindex="-1" aria-labelledby="empruntModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="empruntModalLabel">Emprunter un livre</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form action="emprunter.php" method="POST">
                <div class="modal-body">
                    <div class="mb-4">
                        <label class="form-label small fw-medium text-gray-800">Livre</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light border-end-0">
                                <i class="bi bi-search text-muted"></i>
                            </span>
                            <input type="text" class="form-control border-start-0" id="searchLivre" 
                                   placeholder="Rechercher par titre, auteur ou ISBN..." autocomplete="off"
                                   list="livresList">
                            <input type="hidden" name="livre_id" id="selectedLivreId" required>
                            <datalist id="livresList">
                                <?php
                                $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
                                $livresQuery = $db->query("SELECT id_livre, titre, isbn FROM n_livre ORDER BY titre");
                                while($livre = $livresQuery->fetch(PDO::FETCH_ASSOC)) {
                                    echo '<option value="' . htmlspecialchars($livre['titre']) . ' (ISBN: ' . htmlspecialchars($livre['isbn']) . ')" data-id="' . $livre['id_livre'] . '">';
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
                    <button type="submit" class="btn btn-primary px-4">Emprunter</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const searchLivre = document.getElementById('searchLivre');
    const selectedLivreId = document.getElementById('selectedLivreId');
    const form = document.querySelector('#empruntModal form');

    // Valider le formulaire avant soumission
    form.addEventListener('submit', function(e) {
        if (!selectedLivreId.value) {
            e.preventDefault();
            alert('Veuillez sélectionner un livre');
            return false;
        }
    });

    // Gérer la sélection dans le datalist
    searchLivre.addEventListener('input', function(e) {
        const datalist = document.getElementById('livresList');
        const options = datalist.getElementsByTagName('option');
        const value = e.target.value;

        // Chercher l'option correspondante
        for (let option of options) {
            if (option.value === value) {
                selectedLivreId.value = option.getAttribute('data-id');
                console.log('Livre sélectionné:', option.value, 'ID:', selectedLivreId.value);
                return;
            }
        }
        
        // Si aucune correspondance, réinitialiser l'ID
        selectedLivreId.value = '';
    });

    // Afficher les erreurs s'il y en a
    const urlParams = new URLSearchParams(window.location.search);
    const error = urlParams.get('error');
    if (error) {
        let errorMessage = 'Une erreur est survenue';
        switch(error) {
            case 'missing_data':
                errorMessage = 'Veuillez remplir tous les champs';
                break;
            case 'failed':
                errorMessage = 'L\'emprunt n\'a pas pu être enregistré';
                break;
            default:
                errorMessage = decodeURIComponent(error);
        }
        
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-danger alert-dismissible fade show';
        alertDiv.innerHTML = `
            ${errorMessage}
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        `;
        document.querySelector('.container-fluid').insertBefore(alertDiv, document.querySelector('.card'));
    }
});
</script>
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


