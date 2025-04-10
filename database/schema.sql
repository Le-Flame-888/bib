-- Création de la base de données
CREATE DATABASE IF NOT EXISTS bibliotheque CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE bibliotheque;

-- Table des rôles
CREATE TABLE IF NOT EXISTS n_role (
    id_role INT PRIMARY KEY AUTO_INCREMENT,
    nom_role VARCHAR(50) NOT NULL UNIQUE,
    description TEXT,
    niveau_acces INT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table des étudiants
CREATE TABLE IF NOT EXISTS `n_etudiants` (
  `etud_id` int(11) NOT NULL AUTO_INCREMENT,
  `etud_nom` varchar(50) NOT NULL,
  `etud_prenom` varchar(50) NOT NULL,
  `etud_nom_ar` varchar(50) DEFAULT NULL,
  `etud_prenom_ar` varchar(50) DEFAULT NULL,
  `etud_sexe` char(1) NOT NULL,
  `etud_cni` varchar(20) DEFAULT NULL,
  `etud_passport` varchar(20) DEFAULT NULL,
  `etud_datenaiss` date DEFAULT NULL,
  `etud_lieunaiss` varchar(50) DEFAULT NULL,
  `etud_lieunaiss_ar` varchar(50) DEFAULT NULL,
  `etud_adresse` varchar(200) DEFAULT NULL,
  `etud_ville_id` int(10) unsigned DEFAULT NULL,
  `etud_nat_id` int(10) unsigned NOT NULL,
  `etud_email` varchar(100) DEFAULT NULL,
  `etud_tel` varchar(50) DEFAULT NULL,
  `etud_status` tinyint(1) DEFAULT '0',
  `etud_user_id` int(10) unsigned DEFAULT NULL,
  PRIMARY KEY (`etud_id`),
  KEY `etud_ville_id` (`etud_ville_id`),
  KEY `etud_nat_id` (`etud_nat_id`),
  KEY `etud_user_id` (`etud_user_id`)
) ENGINE=InnoDB AUTO_INCREMENT=5565 DEFAULT CHARSET=utf8;

-- Table des utilisateurs
-- Table des utilisateurs (corrigée)
CREATE TABLE IF NOT EXISTS `n_utilisateurs` (
  `user_id` int(11) NOT NULL AUTO_INCREMENT,
  `user_nom` varchar(60) NOT NULL,
  `user_login` varchar(60) DEFAULT NULL,
  `user_photo` varchar(200) DEFAULT NULL,
  `user_estActif` tinyint(1) DEFAULT NULL,
  `user_role_id` int(11) DEFAULT NULL,
  `user_ref_id` int(11) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL, -- Ajout de la colonne manquante
  `family_name` varchar(50) DEFAULT NULL,
  `activation_code` varchar(50) DEFAULT NULL,
  `is_verified` tinyint(4) NOT NULL DEFAULT '0',
  `phone_number` varchar(20) DEFAULT NULL,
  `first_name` varchar(100) DEFAULT NULL,
  `civility` varchar(15) CHARACTER SET utf8 COLLATE utf8_general_ci DEFAULT NULL,
  `formation` int(11) DEFAULT NULL,
  `whatsapp_num` varchar(20) DEFAULT NULL,
  `centre_id` int(10) unsigned DEFAULT NULL,
  `user_dern_conx` datetime DEFAULT NULL,
  `user_accepter_reglement` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`user_id`),
  KEY `user_role_id` (`user_role_id`),
  KEY `user_ref_id` (`user_ref_id`),
  KEY `centre_id` (`centre_id`)
) ENGINE=InnoDB AUTO_INCREMENT=9782 DEFAULT CHARSET=utf8;

-- Table des auteurs
CREATE TABLE IF NOT EXISTS n_author (
    author_id INT PRIMARY KEY AUTO_INCREMENT,
    author_name VARCHAR(200) NOT NULL,
    author_status ENUM('Actif','Inactif') NOT NULL DEFAULT 'Actif',
    author_created_on VARCHAR(30) NOT NULL,
    author_updated_on VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table des catégories
CREATE TABLE IF NOT EXISTS n_category (
    category_id INT PRIMARY KEY AUTO_INCREMENT,
    category_name VARCHAR(200) NOT NULL,
    category_status ENUM('Actif','Inactif') NOT NULL DEFAULT 'Actif',
    category_created_on VARCHAR(30) NOT NULL,
    category_updated_on VARCHAR(30) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table des catégories de livres (alternative)
CREATE TABLE IF NOT EXISTS n_categorie_livres (
    id_categorie INT PRIMARY KEY AUTO_INCREMENT,
    nom_categorie VARCHAR(100) NOT NULL UNIQUE,
    description TEXT
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table des livres
-- Table des livres (corrigée)
CREATE TABLE IF NOT EXISTS n_livre (
    id_livre INT PRIMARY KEY AUTO_INCREMENT,
    titre VARCHAR(255) NOT NULL,
    auteur VARCHAR(200) NOT NULL,
    isbn VARCHAR(20) UNIQUE,
    date_publication DATE,
    author_id INT, -- Correction: alignement avec la colonne de référence
    category_id INT,
    id_categorie INT,
    quantite_totale INT NOT NULL,
    quantite_disponible INT NOT NULL,
    image_livre VARCHAR(255),
    statut ENUM('disponible', 'emprunté', 'en_reparation') DEFAULT 'disponible',
    FOREIGN KEY (id_categorie) REFERENCES n_categorie_livres(id_categorie),
    FOREIGN KEY (author_id) REFERENCES n_author(author_id), -- Correction: author_id au lieu de auteur_id
    FOREIGN KEY (category_id) REFERENCES n_category(category_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;

-- Table des réservations
CREATE TABLE IF NOT EXISTS n_reservation (
    id_reservation INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_livre INT NOT NULL,
    date_reservation DATETIME NOT NULL,
    date_expiration DATETIME NOT NULL,
    statut ENUM('en_attente', 'active', 'terminee', 'annulee') DEFAULT 'en_attente',
    FOREIGN KEY (id_utilisateur) REFERENCES n_utilisateurs(user_id),
    FOREIGN KEY (id_livre) REFERENCES n_livre(id_livre)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;



-- Table de liaison livres-auteurs
CREATE TABLE n_livre_auteurs (
    id_livre INT,
    id_auteur INT,
    PRIMARY KEY (id_livre, id_auteur),
    FOREIGN KEY (id_livre) REFERENCES n_livre(id_livre) ON DELETE CASCADE,
    FOREIGN KEY (id_auteur) REFERENCES n_author(author_id) ON DELETE CASCADE
);

-- Table des exemplaires
CREATE TABLE n_exemplaires (
    id_exemplaire INT PRIMARY KEY AUTO_INCREMENT,
    id_livre INT NOT NULL,
    code_barre VARCHAR(50) UNIQUE,
    statut ENUM('disponible', 'emprunte', 'reserve', 'maintenance') DEFAULT 'disponible',
    etat ENUM('neuf', 'bon', 'moyen', 'mauvais') DEFAULT 'bon',
    date_acquisition DATE,
    date_derniere_maintenance DATE,
    notes TEXT,
    FOREIGN KEY (id_livre) REFERENCES n_livre(id_livre) ON DELETE CASCADE
);

-- Table des emprunts
CREATE TABLE n_emprunts (
    id_emprunt INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_exemplaire INT NOT NULL,
    date_emprunt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_retour_prevue TIMESTAMP NOT NULL,
    date_retour_effective TIMESTAMP,
    statut ENUM('actif', 'rendu', 'en_retard', 'perdu') DEFAULT 'actif',
    notes TEXT,
    FOREIGN KEY (id_utilisateur) REFERENCES n_utilisateurs(user_id),
    FOREIGN KEY (id_exemplaire) REFERENCES n_exemplaires(id_exemplaire)
);

-- Table des amendes
CREATE TABLE n_amendes (
    id_amende INT PRIMARY KEY AUTO_INCREMENT,
    id_emprunt INT NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    raison TEXT,
    statut ENUM('en_attente', 'payee', 'annulee') DEFAULT 'en_attente',
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    date_paiement TIMESTAMP,
    FOREIGN KEY (id_emprunt) REFERENCES n_emprunts(id_emprunt)
);

-- Table des commentaires et notes
CREATE TABLE n_avis (
    id_avis INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    id_livre INT NOT NULL,
    note INT CHECK (note >= 1 AND note <= 5),
    commentaire TEXT,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES n_utilisateurs(user_id),
    FOREIGN KEY (id_livre) REFERENCES n_livre(id_livre)
);

-- Table des notifications
CREATE TABLE n_notifications (
    id_notification INT PRIMARY KEY AUTO_INCREMENT,
    id_utilisateur INT NOT NULL,
    type ENUM('date_retour', 'reservation', 'amende', 'systeme') NOT NULL,
    message TEXT NOT NULL,
    est_lu BOOLEAN DEFAULT FALSE,
    date_creation TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (id_utilisateur) REFERENCES n_utilisateurs(user_id)
);

-- Supprimer une des tables de catégories (par exemple n_categorie_livres)
-- DROP TABLE IF EXISTS n_categorie_livres;

-- Et ensuite modifier n_livre pour n'utiliser qu'une seule référence de catégorie
ALTER TABLE n_livre DROP FOREIGN KEY n_livre_ibfk_1; -- Supprime la contrainte de clé étrangère vers id_categorie
ALTER TABLE n_livre DROP COLUMN id_categorie; -- Supprime la colonne redondante

ALTER TABLE n_utilisateurs ADD COLUMN user_email VARCHAR(100) DEFAULT NULL;

-- Ajout de l'index pour optimiser les recherches sur user_email
CREATE INDEX idx_utilisateurs_email ON n_utilisateurs(user_email);

-- Index pour optimiser les recherches
CREATE INDEX idx_livre_titre ON n_livre(titre);
CREATE INDEX idx_livre_isbn ON n_livre(isbn);
CREATE INDEX idx_utilisateurs_email ON n_utilisateurs(user_email);
CREATE INDEX idx_emprunts_statut ON n_emprunts(statut);
CREATE INDEX idx_exemplaires_statut ON n_exemplaires(statut);
