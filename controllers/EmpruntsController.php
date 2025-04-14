<?php

class EmpruntsController {
    private $db;

    public function __construct() {
        try {
            $this->db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");
            $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            die("Erreur de connexion : " . $e->getMessage());
        }
    }

    public function getAllEmprunts() {
        try {
            $query = "SELECT e.*, u.user_nom, u.user_login, l.titre, l.isbn, ex.code_barre
                      FROM n_emprunts e
                      JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
                      JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
                      JOIN n_livre l ON ex.id_livre = l.id_livre
                      ORDER BY e.date_emprunt DESC";
            
            $stmt = $this->db->query($query);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getAllEmprunts: " . $e->getMessage());
            return [];
        }
    }

    public function getUserEmprunts($user_id) {
        try {
            $query = "SELECT e.*, u.user_nom, u.user_login, l.titre, l.isbn, ex.code_barre
                      FROM n_emprunts e
                      JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
                      JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
                      JOIN n_livre l ON ex.id_livre = l.id_livre
                      WHERE e.id_utilisateur = ?
                      ORDER BY e.date_emprunt DESC";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error in getUserEmprunts: " . $e->getMessage());
            throw new Exception("Erreur lors de la récupération des emprunts");
        }
    }

    public function createEmprunt($livre_id, $user_id, $date_retour) {
        try {
            // Vérifier si l'utilisateur existe
            $userCheck = $this->db->prepare("SELECT user_id FROM n_utilisateurs WHERE user_id = ?");
            $userCheck->execute([$user_id]);
            if (!$userCheck->fetch()) {
                throw new Exception("Utilisateur non trouvé");
            }

            // Vérifier si le livre existe
            $bookCheck = $this->db->prepare("SELECT id_livre FROM n_livre WHERE id_livre = ?");
            $bookCheck->execute([$livre_id]);
            if (!$bookCheck->fetch()) {
                throw new Exception("Livre non trouvé");
            }

            // Récupérer un exemplaire disponible
            $exemplaireQuery = "SELECT id_exemplaire 
                              FROM n_exemplaires 
                              WHERE id_livre = ? 
                              AND id_exemplaire NOT IN (
                                  SELECT id_exemplaire 
                                  FROM n_emprunts 
                                  WHERE statut IN ('actif', 'en_retard')
                              )
                              LIMIT 1";
            $exemplaireStmt = $this->db->prepare($exemplaireQuery);
            $exemplaireStmt->execute([$livre_id]);
            $exemplaire = $exemplaireStmt->fetch(PDO::FETCH_ASSOC);

            if (!$exemplaire) {
                throw new Exception("Aucun exemplaire disponible pour ce livre");
            }

            // Insérer l'emprunt
            $insertQuery = "INSERT INTO n_emprunts (id_exemplaire, id_utilisateur, date_emprunt, date_retour_prevue, statut) 
                          VALUES (?, ?, NOW(), ?, 'actif')";
            $insertStmt = $this->db->prepare($insertQuery);
            $result = $insertStmt->execute([
                $exemplaire['id_exemplaire'],
                $user_id,
                $date_retour
            ]);

            if (!$result) {
                throw new Exception("Erreur lors de l'enregistrement de l'emprunt");
            }

            return true;
        } catch (Exception $e) {
            error_log("Error in createEmprunt: " . $e->getMessage());
            throw $e;
        }
    }

    public function returnEmprunt($emprunt_id, $etat, $commentaire = '') {
        try {
            $query = "UPDATE n_emprunts SET 
                     statut = 'termine',
                     date_retour_effectif = NOW(),
                     etat_retour = ?,
                     commentaire_retour = ?
                     WHERE id_emprunt = ?";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute([$etat, $commentaire, $emprunt_id]);
        } catch (PDOException $e) {
            error_log("Error in returnEmprunt: " . $e->getMessage());
            throw $e;
        }
    }

    public function prolongerEmprunt($emprunt_id, $user_id) {
        try {
            // Vérifier si l'emprunt existe et appartient à l'utilisateur
            $checkQuery = "SELECT id_emprunt, date_retour 
                         FROM n_emprunts 
                         WHERE id_emprunt = ? 
                         AND id_utilisateur = ? 
                         AND statut = 'actif'";
            
            $checkStmt = $this->db->prepare($checkQuery);
            $checkStmt->execute([$emprunt_id, $user_id]);
            $emprunt = $checkStmt->fetch(PDO::FETCH_ASSOC);

            if (!$emprunt) {
                throw new Exception("Emprunt non trouvé ou non prolongeable");
            }

            // Prolonger de 15 jours
            $newDate = date('Y-m-d', strtotime($emprunt['date_retour'] . ' +15 days'));
            
            $updateQuery = "UPDATE n_emprunts 
                          SET date_retour = ?, 
                              date_prolongation = NOW() 
                          WHERE id_emprunt = ?";
            
            $updateStmt = $this->db->prepare($updateQuery);
            return $updateStmt->execute([$newDate, $emprunt_id]);
        } catch (Exception $e) {
            error_log("Error in prolongerEmprunt: " . $e->getMessage());
            throw $e;
        }
    }
} 