<?php
// Configuration de la base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'bibliotheque');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration de l'application
define('APP_NAME', 'Système de Gestion de Bibliothèque');
define('APP_URL', 'http://localhost/bibv2'); // No trailing slash
define('DEFAULT_CONTROLLER', 'home');
define('DEFAULT_ACTION', 'index');


// Configuration des chemins
define('ROOT_PATH', realpath(__DIR__ . '/..'));
define('CONTROLLERS_PATH', 'controllers/');
define('MODELS_PATH', ROOT_PATH . '/models/'); 
define('VIEWS_PATH', ROOT_PATH . '/views/');
define('UPLOADS_PATH', ROOT_PATH . '/public/uploads/');

// Configuration de la sécurité
define('HASH_SALT', 'votre_sel_de_hachage_securise');
define('SESSION_LIFETIME', 3600); // 1 heure

// Configuration des emails
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@example.com');
define('SMTP_PASS', 'your_password');

// Configuration des limites
define('MAX_LOGIN_ATTEMPTS', 3);
define('BOOKS_PER_PAGE', 10);
define('MAX_LOAN_DAYS', 14);
define('MAX_LOANS_PER_USER', 5);

// In config/config.php
define('ITEMS_PER_PAGE', 12); // Or whatever number makes sense for your pagination
?>