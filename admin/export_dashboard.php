<?php
require_once '../bootstrap.php';
require_once '../config/config.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user']) || $_SESSION['user']['user_role_id'] != 1) {
    header('Location: ../auth/Connexion.php');
    exit();
}

// Connect to database
$db = new PDO("mysql:host=localhost;dbname=bibliotheque;charset=utf8", "root", "");

// Get statistics
$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM n_utilisateurs")->fetchColumn(),
    'total_books' => $db->query("SELECT COUNT(*) FROM n_livre")->fetchColumn(),
    'active_loans' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'actif'")->fetchColumn(),
    'overdue_loans' => $db->query("SELECT COUNT(*) FROM n_emprunts WHERE statut = 'en_retard'")->fetchColumn()
];

// Get recent loans
$query = $db->query("
    SELECT e.*, u.user_nom, u.user_login, l.titre
    FROM n_emprunts e
    JOIN n_utilisateurs u ON e.id_utilisateur = u.user_id
    JOIN n_exemplaires ex ON e.id_exemplaire = ex.id_exemplaire
    JOIN n_livre l ON ex.id_livre = l.id_livre
    ORDER BY e.date_emprunt DESC
    LIMIT 10
");
$recent_loans = $query->fetchAll(PDO::FETCH_ASSOC);

// Get active users
$query = $db->query("
    SELECT user_id, user_nom, user_login, user_dern_conx
    FROM n_utilisateurs
    WHERE user_dern_conx IS NOT NULL
    ORDER BY user_dern_conx DESC
    LIMIT 10
");
$active_users = $query->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=dashboard_export_' . date('Y-m-d') . '.csv');

// Create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for proper character encoding in Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Add statistics section
fputcsv($output, ['Statistiques Générales']);
fputcsv($output, ['Total Utilisateurs', $stats['total_users']]);
fputcsv($output, ['Total Livres', $stats['total_books']]);
fputcsv($output, ['Emprunts Actifs', $stats['active_loans']]);
fputcsv($output, ['Emprunts en Retard', $stats['overdue_loans']]);
fputcsv($output, []); // Empty line for spacing

// Add recent loans section
fputcsv($output, ['Emprunts Récents']);
fputcsv($output, ['Utilisateur', 'Titre', 'Date Emprunt', 'Date Retour', 'Statut']);
foreach ($recent_loans as $loan) {
    fputcsv($output, [
        $loan['user_nom'],
        $loan['titre'],
        $loan['date_emprunt'],
        $loan['date_retour_prevue'],
        $loan['statut']
    ]);
}
fputcsv($output, []); // Empty line for spacing

// Add active users section
fputcsv($output, ['Utilisateurs Actifs']);
fputcsv($output, ['Nom', 'Login', 'Dernière Connexion']);
foreach ($active_users as $user) {
    fputcsv($output, [
        $user['user_nom'],
        $user['user_login'],
        $user['user_dern_conx']
    ]);
}

// Close the file pointer
fclose($output);
exit(); 