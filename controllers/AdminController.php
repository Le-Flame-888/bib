<?php

class AdminController {

    public function addCategory() {
        // Check if user is admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
            header('Location: ' . APP_URL . '/auth/login');
            exit();
        }

        require_once VIEWS_PATH . 'admin/categories/add.php';
    }

    public function storeCategory() {
        // Check if user is admin
        if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
            header('Location: ' . APP_URL . '/auth/login');
            exit();
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $nom = filter_input(INPUT_POST, 'nom', FILTER_SANITIZE_STRING);
            $description = filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING);

            if (empty($nom)) {
                $_SESSION['error'] = "Le nom de la catégorie est requis";
                header('Location: ' . APP_URL . '/admin/categories/add');
                exit();
            }

            // Create category in database
            $categoryModel = new Category();
            $result = $categoryModel->create([
                'nom' => $nom,
                'description' => $description
            ]);

            if ($result) {
                $_SESSION['success'] = "Catégorie ajoutée avec succès";
                header('Location: ' . APP_URL . '/admin/categories');
                exit();
            } else {
                $_SESSION['error'] = "Erreur lors de l'ajout de la catégorie";
                header('Location: ' . APP_URL . '/admin/categories/add');
                exit();
            }
        }
    }
} 