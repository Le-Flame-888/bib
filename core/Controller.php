<?php
abstract class Controller {
    protected $db;
    protected $view;
    protected $model;

    public function __construct() {
        $this->db = new Database();
    }

    protected function loadModel($model) {
        $modelFile = MODELS_PATH . $model . '.php';
        if (file_exists($modelFile)) {
            require_once $modelFile;
            $modelClass = ucfirst($model);
            $this->model = new $modelClass($this->db);
            return true;
        }
        return false;
    }

    protected function render($view, $data = []) {
        // Extraction des données pour les rendre disponibles dans la vue
        extract($data);

        // Début de la mise en mémoire tampon
        ob_start();

        // Inclusion du fichier de vue
        $viewFile = VIEWS_PATH . $view . '.php';
        if (file_exists($viewFile)) {
            require_once $viewFile;
        } else {
            throw new Exception('Vue non trouvée : ' . $view);
        }

        // Récupération du contenu de la mise en mémoire tampon
        $content = ob_get_clean();

        // Inclusion du template principal
        require_once VIEWS_PATH . 'layout/main.php';
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
        exit();
    }

    protected function redirect($url) {
        header('Location: ' . APP_URL . '/' . $url);
        exit();
    }

    protected function isPost() {
        return $_SERVER['REQUEST_METHOD'] === 'POST';
    }

    protected function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
    }

    protected function getPost($key = null) {
        if ($key === null) {
            return $_POST;
        }
        return isset($_POST[$key]) ? $_POST[$key] : null;
    }

    protected function getQuery($key = null) {
        if ($key === null) {
            return $_GET;
        }
        return isset($_GET[$key]) ? $_GET[$key] : null;
    }

    protected function setFlash($message, $type = 'info') {
        $_SESSION['flash'] = [
            'message' => $message,
            'type' => $type
        ];
    }

    protected function hasFlash() {
        return isset($_SESSION['flash']);
    }

    protected function getFlash() {
        if ($this->hasFlash()) {
            $flash = $_SESSION['flash'];
            unset($_SESSION['flash']);
            return $flash;
        }
        return null;
    }
}