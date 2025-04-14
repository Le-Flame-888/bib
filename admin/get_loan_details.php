<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Vérifier si l'utilisateur est connecté et est un administrateur
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
    http_response_code(403);
    echo json_encode(['error' => 'Accès non autorisé']);
    exit();
}

// Vérifier si l'ID est fourni
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID manquant']);
    exit();
}

try {
    // Connexion à la base de données
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    
    // Récupérer les détails de l'emprunt
    $query = "SELECT e.*, u.user_nom, u.user_login, l.titre, l.isbn, l.auteur, 
                     ex.code_barre, e.date_emprunt, e.date_retour_prevue, e.statut,
                     e.date_retour_effective, e.commentaire_retour
              FROM n_emprunts e
              JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
              JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
              JOIN n_livre l ON ex.id_livre = l.id_livre
              WHERE e.id_emprunt = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_GET['id']]);
    $details = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($details) {
        // Formater les dates pour l'affichage
        $details['date_emprunt'] = date('d/m/Y', strtotime($details['date_emprunt']));
        $details['date_retour_prevue'] = date('d/m/Y', strtotime($details['date_retour_prevue']));
        if ($details['date_retour_effective']) {
            $details['date_retour_effective'] = date('d/m/Y', strtotime($details['date_retour_effective']));
        }
        
        header('Content-Type: application/json');
        echo json_encode($details);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Emprunt non trouvé']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur serveur: ' . $e->getMessage()]);
}
exit(); 