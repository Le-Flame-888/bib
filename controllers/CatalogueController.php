<?php

class CatalogueController {
    public function index() {
        // Inclure la vue du catalogue
        require_once __DIR__ . '/../views/catalogue/index.php';
    }

    public function view($id) {
        // Inclure la vue détaillée d'un livre
        require_once __DIR__ . '/../views/catalogue/view.php';
    }
}