<?php
class Category {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function create($data) {
        $sql = "INSERT INTO categories (nom, description) VALUES (:nom, :description)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':nom' => $data['nom'],
            ':description' => $data['description']
        ]);
    }

    public function getAll() {
        $sql = "SELECT * FROM categories ORDER BY nom ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getById($id) {
        $sql = "SELECT * FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function update($id, $data) {
        $sql = "UPDATE categories SET nom = :nom, description = :description WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            ':id' => $id,
            ':nom' => $data['nom'],
            ':description' => $data['description']
        ]);
    }

    public function delete($id) {
        $sql = "DELETE FROM categories WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([':id' => $id]);
    }
} 