<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Vérifier si la requête est de type POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Connexion à la base de données
        $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
        
        // Vérifier que tous les champs requis sont présents
        if (!isset($_POST['id']) || !isset($_POST['etat'])) {
            throw new Exception("Données manquantes pour le retour");
        }

        // Mettre à jour l'emprunt
        $updateQuery = "UPDATE n_emprunts SET 
                       statut = 'rendu',
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
            // Rediriger avec un message de succès
            header('Location: loans.php?success=return');
        } else {
            throw new Exception("Erreur lors de l'enregistrement du retour");
        }
    } catch (Exception $e) {
        header('Location: loans.php?error=' . urlencode($e->getMessage()));
    }
} else {
    // Si ce n'est pas une requête POST, rediriger vers la page des emprunts
    header('Location: loans.php');
}
exit(); 