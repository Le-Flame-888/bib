<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Load config
require_once '../config/config.php';

// Check if user is logged in
if (!isset($_SESSION['user'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Non autorisé']);
    exit();
}

// Check if loan ID is provided
if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'ID de l\'emprunt manquant']);
    exit();
}

try {
    // Connect to database
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Prepare and execute query
    $query = $db->prepare("
        SELECT 
            e.*,
            l.titre,
            l.isbn,
            ex.code_barre,
            u.user_nom as emprunteur
        FROM n_emprunts e
        JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
        JOIN n_livre l ON ex.id_livre = l.id_livre
        JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
        WHERE e.id_emprunt = ? AND e.id_utilisateur = ?
    ");
    
    $query->execute([$_GET['id'], $_SESSION['user']['id']]);
    $loan = $query->fetch(PDO::FETCH_ASSOC);

    if ($loan) {
        // Format dates
        $loan['date_emprunt'] = date('d/m/Y', strtotime($loan['date_emprunt']));
        $loan['date_retour_prevue'] = date('d/m/Y', strtotime($loan['date_retour_prevue']));
        if ($loan['date_retour']) {
            $loan['date_retour'] = date('d/m/Y', strtotime($loan['date_retour']));
        }

        echo json_encode($loan);
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'Emprunt non trouvé']);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erreur lors de la récupération des détails']);
}
?> 