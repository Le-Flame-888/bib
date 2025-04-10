<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Chargement des fichiers nécessaires
require_once 'config/config.php';
require_once 'core/Router.php';
require_once 'core/Controller.php';
require_once 'core/Database.php';

// Démarrage de la session
if (function_exists('session_start')) {
    session_start();
} else {
    // Handle case where session functionality is not available
    error_log('Session functionality is not available');
}

// Instanciation du routeur
$router = new Router();

// Traitement de la requête
$router->dispatch();
?>