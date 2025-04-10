<?php
/**
 * Controller pour la gestion des emprunts
 */
class EmpruntsController {
    private $db;
    
    /**
     * Constructeur du controller
     */
    public function __construct() {
        // Connexion à la base de données
        $this->db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
    }
    
    /**
     * Récupère tous les emprunts d'un utilisateur
     * 
     * @param int $user_id Identifiant de l'utilisateur
     * @return array Liste des emprunts
     */
    public function getUserEmprunts($user_id) {
        $query = $this->db->prepare("
            SELECT 
                e.*,
                ex.code_barre,
                l.titre,
                l.isbn,
                DATEDIFF(e.date_retour_prevue, NOW()) as jours_restants
            FROM n_emprunts e
            JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
            JOIN n_livre l ON ex.id_livre = l.id_livre
            WHERE e.id_utilisateur = ?
            ORDER BY e.date_emprunt DESC
        ");
        $query->execute([$user_id]);
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Récupère tous les livres disponibles pour l'emprunt
     * 
     * @return array Liste des livres disponibles
     */
    public function getLivresDisponibles() {
        $query = $this->db->prepare("
            SELECT 
                l.*,
                e.id_exemplaire,
                e.code_barre,
                a.author_name
            FROM n_livre l
            JOIN n_exemplaires e ON l.id_livre = e.id_livre
            LEFT JOIN n_author a ON l.author_id = a.author_id
            WHERE e.statut = 'disponible'
            ORDER BY l.titre ASC
        ");
        $query->execute();
        return $query->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Créer un nouvel emprunt
     * 
     * @param int $user_id Identifiant de l'utilisateur
     * @param int $exemplaire_id Identifiant de l'exemplaire
     * @return bool Succès de l'opération
     */
    public function creerEmprunt($user_id, $exemplaire_id) {
        // Vérifier si l'exemplaire est disponible
        $query = $this->db->prepare("
            SELECT id_exemplaire, id_livre 
            FROM n_exemplaires 
            WHERE id_exemplaire = ? AND statut = 'disponible'
        ");
        $query->execute([$exemplaire_id]);
        $exemplaire = $query->fetch(PDO::FETCH_ASSOC);
        
        if (!$exemplaire) {
            return false; // L'exemplaire n'est pas disponible
        }
        
        // Commencer une transaction
        $this->db->beginTransaction();
        
        try {
            // Créer l'emprunt
            $query = $this->db->prepare("
                INSERT INTO n_emprunts (id_utilisateur, id_exemplaire, date_emprunt, date_retour_prevue, statut)
                VALUES (?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), 'actif')
            ");
            $success1 = $query->execute([$user_id, $exemplaire_id]);
            
            // Mettre à jour le statut de l'exemplaire
            $query = $this->db->prepare("
                UPDATE n_exemplaires 
                SET statut = 'emprunte' 
                WHERE id_exemplaire = ?
            ");
            $success2 = $query->execute([$exemplaire_id]);
            
            if ($success1 && $success2) {
                $this->db->commit();
                return true;
            } else {
                $this->db->rollBack();
                return false;
            }
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }
    
    /**
     * Prolonger un emprunt
     * 
     * @param int $emprunt_id Identifiant de l'emprunt
     * @param int $user_id Identifiant de l'utilisateur (pour vérification)
     * @return bool Succès de l'opération
     */
    public function prolongerEmprunt($emprunt_id, $user_id) {
        // Vérifier si l'emprunt existe et appartient à l'utilisateur
        $query = $this->db->prepare("
            SELECT id_emprunt, date_retour_prevue
            FROM n_emprunts
            WHERE id_emprunt = ? AND id_utilisateur = ? AND statut = 'actif'
        ");
        $query->execute([$emprunt_id, $user_id]);
        $emprunt = $query->fetch(PDO::FETCH_ASSOC);
        
        if (!$emprunt) {
            return false; // L'emprunt n'existe pas ou n'appartient pas à l'utilisateur
        }
        
        // Prolonger l'emprunt de 7 jours
        $query = $this->db->prepare("
            UPDATE n_emprunts
            SET date_retour_prevue = DATE_ADD(date_retour_prevue, INTERVAL 7 DAY)
            WHERE id_emprunt = ?
        ");
        
        return $query->execute([$emprunt_id]);
    }
}