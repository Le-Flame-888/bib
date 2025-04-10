<?php

class CatalogueController extends Controller {
    private $bookModel;

    public function __construct() {
        parent::__construct();
        $this->bookModel = new Book();
    }

    public function index() {
        // Récupérer les paramètres de filtrage
        $category = isset($_GET['category']) ? $_GET['category'] : null;
        $search = isset($_GET['search']) ? $_GET['search'] : null;
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $perPage = 12; // Nombre de livres par page

        // Récupérer les livres avec pagination et filtres
        $books = $this->bookModel->getBooks($page, $perPage, $category, $search);
        $totalBooks = $this->bookModel->getTotalBooks($category, $search);
        $totalPages = ceil($totalBooks / $perPage);

        // Récupérer toutes les catégories pour le filtre
        $categories = $this->bookModel->getAllCategories();

        // Préparer les données pour la vue
        $data = [
            'books' => $books,
            'categories' => $categories,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'currentCategory' => $category,
            'searchQuery' => $search
        ];

        // Charger la vue
        $this->view('catalogue/index', $data);
    }

    public function view($id) {
        // Récupérer les détails du livre
        $book = $this->bookModel->getBookById($id);
        if (!$book) {
            header('Location: ' . APP_URL . '/catalogue');
            exit();
        }

        // Vérifier la disponibilité du livre
        $isAvailable = $this->bookModel->isBookAvailable($id);

        $data = [
            'book' => $book,
            'isAvailable' => $isAvailable
        ];

        $this->view('catalogue/view', $data);
    }
}