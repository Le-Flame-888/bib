<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Vérifier si l'utilisateur est un utilisateur standard
if (!isset($_SESSION['user']['user_role_id']) || $_SESSION['user']['user_role_id'] != 3) {
    header('Location: ../admin/dashboard.php');
    exit();
}

// Récupérer l'ID de l'utilisateur depuis la session
$userId = isset($_SESSION['user']['id']) ? $_SESSION['user']['id'] : null;

if (!$userId) {
    // Si pas d'ID utilisateur, rediriger vers la connexion
    header('Location: ../auth/Connexion.php');
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Traitement du formulaire de mise à jour
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $phone_number = isset($_POST['phone_number']) ? trim($_POST['phone_number']) : '';
        $whatsapp_num = isset($_POST['whatsapp_num']) ? trim($_POST['whatsapp_num']) : '';
        $email = isset($_POST['email']) ? trim($_POST['email']) : '';
        
        // Mise à jour du profil
        $query = $db->prepare("
            UPDATE n_utilisateurs 
            SET phone_number = ?,   
                whatsapp_num = ?,
                user_email = ?
            WHERE user_id = ?
        ");
        
        $query->execute([$phone_number, $whatsapp_num, $email, $userId]);
        
        // Traitement de la photo de profil
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $filetype = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            
            if (in_array($filetype, $allowed)) {
                $newname = 'profile_' . $userId . '.' . $filetype;
                $upload_dir = '../public/uploads/profiles/';
                
                // Créer le dossier s'il n'existe pas
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                $upload_path = $upload_dir . $newname;
                
                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $query = $db->prepare("UPDATE n_utilisateurs SET user_photo = ? WHERE user_id = ?");
                    $query->execute([$newname, $userId]);
                }
            }
        }
        
        // Redirection pour éviter la soumission multiple du formulaire
        header('Location: profile.php?updated=1');
        exit();
    }

    // Récupération des informations de l'utilisateur
    $query = $db->prepare("
        SELECT u.*, r.nom_role
        FROM n_utilisateurs u
        LEFT JOIN n_role r ON u.user_role_id = r.id_role
        WHERE u.user_id = ?
    ");
    $query->execute([$userId]);
    $userInfo = $query->fetch(PDO::FETCH_ASSOC);

    if (!$userInfo) {
        throw new Exception('Utilisateur non trouvé');
    }

    // Récupération des statistiques
    $query_stats = $db->prepare("
        SELECT 
            COUNT(CASE WHEN statut = 'actif' THEN 1 END) as emprunts_actifs,
            COUNT(CASE WHEN statut = 'termine' THEN 1 END) as emprunts_termines,
            COUNT(CASE WHEN statut = 'en_retard' THEN 1 END) as emprunts_retard
        FROM n_emprunts
        WHERE id_utilisateur = ?
    ");
    $query_stats->execute([$userId]);
    $stats = $query_stats->fetch(PDO::FETCH_ASSOC);

    // Initialiser les statistiques à 0 si NULL
    $stats['emprunts_actifs'] = $stats['emprunts_actifs'] ?? 0;
    $stats['emprunts_termines'] = $stats['emprunts_termines'] ?? 0;
    $stats['emprunts_retard'] = $stats['emprunts_retard'] ?? 0;

} catch (Exception $e) {
    // En cas d'erreur, rediriger vers une page d'erreur ou afficher un message
    $_SESSION['error'] = "Une erreur est survenue : " . $e->getMessage();
    header('Location: error.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Bibliothèque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    <?php include '../includes/sidebar.php'; ?>
    
<div class="content p-3" id="content">
    <div class="container-fluid mt-4">
        <h1>Mon Profil</h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Votre profil a été mis à jour avec succès.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?= !empty($userInfo['user_photo']) ? '../public/uploads/profiles/' . htmlspecialchars($userInfo['user_photo']) : 'https://via.placeholder.com/150' ?>" 
                             class="rounded-circle mb-3" 
                             alt="Photo de profil"
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="card-title"><?= htmlspecialchars($userInfo['user_nom'] ?? 'Utilisateur') ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($userInfo['nom_role'] ?? 'Utilisateur') ?></p>
                    </div>
                </div>

                <!-- Statistiques -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Statistiques</h5>
                    </div>
                    <div class="card-body">
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Emprunts actifs
                                <span class="badge bg-primary rounded-pill"><?= (int)$stats['emprunts_actifs'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Emprunts terminés
                                <span class="badge bg-success rounded-pill"><?= (int)$stats['emprunts_termines'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Emprunts en retard
                                <span class="badge bg-danger rounded-pill"><?= (int)$stats['emprunts_retard'] ?></span>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Informations personnelles</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($userInfo['user_nom'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Numéro de téléphone</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                       value="<?= htmlspecialchars($userInfo['phone_number'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="whatsapp_num" class="form-label">Numéro WhatsApp</label>
                                <input type="tel" class="form-control" id="whatsapp_num" name="whatsapp_num"
                                       value="<?= htmlspecialchars($userInfo['whatsapp_num'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Photo de profil</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Mettre à jour le profil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
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
</body>
</html> 