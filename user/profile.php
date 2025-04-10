<?php
require_once '../bootstrap.php';
session_start();

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['login'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

$userLogin = $_SESSION['login'];
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Traitement du formulaire de mise à jour
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $phone_number = $_POST['phone_number'] ?? '';
    $whatsapp_num = $_POST['whatsapp_num'] ?? '';
    $email = $_POST['email'] ?? '';
    
    // Mise à jour du profil
    $query = $db->prepare("
        UPDATE n_utilisateurs 
        SET phone_number = ?,
            whatsapp_num = ?,
            user_email = ?
        WHERE user_id = ?
    ");
    
    $query->execute([$phone_number, $whatsapp_num, $email, $userLogin]);
    
    // Traitement de la photo de profil
    if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] === 0) {
        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        $filename = $_FILES['profile_photo']['name'];
        $filetype = pathinfo($filename, PATHINFO_EXTENSION);
        
        if (in_array(strtolower($filetype), $allowed)) {
            $newname = 'profile_' . $userLogin . '.' . $filetype;
            $upload_path = '../public/uploads/profiles/' . $newname;
            
            if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                $query = $db->prepare("UPDATE n_utilisateurs SET user_photo = ? WHERE user_id = ?");
                $query->execute([$newname, $userLogin]);
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
$query->execute([$userLogin]);
$user = $query->fetch(PDO::FETCH_ASSOC);

// Récupération des statistiques
$query_stats = $db->prepare("
    SELECT 
        COUNT(CASE WHEN e.statut = 'actif' THEN 1 END) as emprunts_actifs,
        COUNT(CASE WHEN e.statut = 'rendu' THEN 1 END) as emprunts_termines,
        COUNT(CASE WHEN e.statut = 'en_retard' THEN 1 END) as emprunts_retard
    FROM n_emprunts e
    WHERE e.id_utilisateur = ?
");
$query_stats->execute([$userLogin]);
$stats = $query_stats->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mon Profil - Bibliothèque</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-4">
        <h1>Mon Profil</h1>
        
        <?php if (isset($_GET['updated'])): ?>
            <div class="alert alert-success">Votre profil a été mis à jour avec succès.</div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-body text-center">
                        <img src="<?= $user['user_photo'] ? '../public/uploads/profiles/' . htmlspecialchars($user['user_photo']) : 'https://via.placeholder.com/150' ?>" 
                             class="rounded-circle mb-3" 
                             alt="Photo de profil"
                             style="width: 150px; height: 150px; object-fit: cover;">
                        <h5 class="card-title"><?= htmlspecialchars($user['first_name'] . ' ' . $user['family_name']) ?></h5>
                        <p class="text-muted"><?= htmlspecialchars($user['nom_role'] ?? 'Utilisateur') ?></p>
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
                                <span class="badge bg-primary rounded-pill"><?= $stats['emprunts_actifs'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Emprunts terminés
                                <span class="badge bg-success rounded-pill"><?= $stats['emprunts_termines'] ?></span>
                            </li>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                Emprunts en retard
                                <span class="badge bg-danger rounded-pill"><?= $stats['emprunts_retard'] ?></span>
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
                                       value="<?= htmlspecialchars($user['user_email'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="phone_number" class="form-label">Numéro de téléphone</label>
                                <input type="tel" class="form-control" id="phone_number" name="phone_number"
                                       value="<?= htmlspecialchars($user['phone_number'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="whatsapp_num" class="form-label">Numéro WhatsApp</label>
                                <input type="tel" class="form-control" id="whatsapp_num" name="whatsapp_num"
                                       value="<?= htmlspecialchars($user['whatsapp_num'] ?? '') ?>">
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Photo de profil</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" accept="image/*">
                            </div>

                            <button type="submit" class="btn btn-primary">Mettre à jour le profil</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 