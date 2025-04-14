<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

header('Content-Type: application/json');

if (!isset($_GET['term'])) {
    echo json_encode([]);
    exit();
}

try {
    $db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $term = $_GET['term'];
    
    // Rechercher les livres disponibles
    $query = "SELECT DISTINCT l.id_livre, l.titre, l.isbn, l.auteur
              FROM n_livre l
              JOIN n_exemplaires e ON l.id_livre = e.id_livre
              WHERE e.id_exemplaire NOT IN (
                  SELECT id_exemplaire 
                  FROM n_emprunts 
                  WHERE statut IN ('actif', 'en_retard')
              )
              AND (l.titre LIKE ? OR l.isbn LIKE ? OR l.auteur LIKE ?)
              ORDER BY l.titre
              LIMIT 10";
    
    $stmt = $db->prepare($query);
    $searchTerm = "%$term%";
    $stmt->execute([$searchTerm, $searchTerm, $searchTerm]);
    
    $livres = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode($livres);
} catch (PDOException $e) {
    error_log("Error in search_livres.php: " . $e->getMessage());
    echo json_encode([]);
} 