<?php
require_once '../bootstrap.php';
require_once '../config/config.php';
require_once '../controllers/EmpruntsController.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user'])) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Debug - Log session info
error_log("Session user info: " . print_r($_SESSION['user'], true));

// Vérifier si l'utilisateur est un utilisateur standard
if ($_SESSION['user']['user_role_id'] != 3) {
    header('Location: ../admin/dashboard.php');
    exit();
}

// Vérifier si c'est une requête POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: emprunts.php');
    exit();
}

// Vérifier les données requises
if (!isset($_POST['livre_id']) || !isset($_POST['date_retour'])) {
    header('Location: emprunts.php?error=missing_data');
    exit();
}

try {
    // Debug - Log request info
    error_log("Attempting to create loan with: livre_id=" . $_POST['livre_id'] . ", user_id=" . $_SESSION['user']['id'] . ", date_retour=" . $_POST['date_retour']);
    
    // Initialiser le contrôleur
    $controller = new EmpruntsController();
    
    // Créer l'emprunt
    $result = $controller->createEmprunt(
        $_POST['livre_id'],
        $_SESSION['user']['id'],
        $_POST['date_retour']
    );
    
    if ($result) {
        header('Location: emprunts.php?success=1');
    } else {
        header('Location: emprunts.php?error=failed');
    }
} catch (Exception $e) {
    error_log("Error in emprunter.php: " . $e->getMessage());
    header('Location: emprunts.php?error=' . urlencode($e->getMessage()));
}
exit(); 