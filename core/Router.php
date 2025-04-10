<?php
class Router {
    private $controller;
    private $action;
    private $params;

    public function __construct() {
        $this->parseUrl();
    }

    private function parseUrl() {
        // Get URL from PATH_INFO if available (more reliable than $_GET['url'])
        $url = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';
        
        // Remove base path and query string
        $basePath = parse_url(APP_URL, PHP_URL_PATH);
        $url = str_replace($basePath, '', $url);
        $url = parse_url($url, PHP_URL_PATH);
        $url = ltrim($url, '/');
        
        // Split into parts
        $parts = explode('/', $url);
        
        // Check for direct file access
        $filePath = $url;
        if (!empty($filePath)) {
            // Try with .php extension
            if (file_exists($filePath . '.php')) {
                require_once $filePath . '.php';
                exit;
            }
            // Try exact path
            if (file_exists($filePath)) {
                require_once $filePath;
                exit;
            }
        }
        
        // Set controller, action, and parameters
        $controllerName = !empty($parts[0]) ? ucfirst($parts[0]) : ucfirst(DEFAULT_CONTROLLER);
        $this->controller = $controllerName . 'Controller';
        $this->action = !empty($parts[1]) ? $parts[1] : DEFAULT_ACTION;
        $this->params = array_slice($parts, 2);
    }

    public function dispatch() {
        $controllerFile = CONTROLLERS_PATH . $this->controller . '.php';

        if (file_exists($controllerFile)) {
            require_once $controllerFile;

            if (class_exists($this->controller)) {
                $controller = new $this->controller();

                if (method_exists($controller, $this->action)) {
                    call_user_func_array([$controller, $this->action], $this->params);
                } else {
                    // Action non trouvée
                    die("Error: Action '{$this->action}' not found in controller '{$this->controller}'");
                }
            } else {
                // Classe du contrôleur non trouvée
                die("Error: Controller class '{$this->controller}' not found");
            }
        } else {
            // Fichier du contrôleur non trouvé
            die("Error: Controller file '{$controllerFile}' not found");
        }
    }


        // Add these router helper functions
    function getCurrentController() {
        $url = isset($_GET['url']) ? rtrim($_GET['url'], '/') : '';
        $url = filter_var($url, FILTER_SANITIZE_URL);
        $parts = explode('/', $url);
        return !empty($parts[0]) ? $parts[0] : DEFAULT_CONTROLLER;
    }

    function isActiveRoute($section, $page) {
        $url = $_SERVER['REQUEST_URI'];
        $basePath = parse_url(APP_URL, PHP_URL_PATH);
        $url = str_replace($basePath, '', $url);
        $url = parse_url($url, PHP_URL_PATH);
        $url = ltrim($url, '/');
        
        $parts = explode('/', $url);
        $currentSection = !empty($parts[0]) ? $parts[0] : 'home';
        
        // Extract the current page and remove the .php extension if it exists
        $currentPage = !empty($parts[1]) ? $parts[1] : '';
        $currentPage = preg_replace('/\.php$/', '', $currentPage);
        
        // Add visible debugging for troubleshooting
        echo "<!-- DEBUG: URL=$url, Section=$section/$currentSection, Page=$page/$currentPage -->";
        
        // Handle index pages
        if ($currentPage === 'index' || $currentPage === '') {
            $currentPage = '';
        }
        if ($page === 'index') {
            $page = '';
        }
        
        return ($currentSection === $section && $currentPage === $page);
    }

}