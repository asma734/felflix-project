<?php
// ============================================================
//  API : Suggestions de Recherche en Temps Réel (Autocomplete)
// ============================================================
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/TitleModel.php';

$q = trim($_GET['q'] ?? '');
if (strlen($q) < 1) {
    echo json_encode([]);
    exit;
}

try {
    $titleModel = new TitleModel();
    $filters = ['q' => $q];
    
    [$total, $whereClause, $params] = $titleModel->countFiltered($filters);
    $results = $titleModel->getFiltered($whereClause, $params, 'imdb_rating DESC', 8, 0);

    $suggestions = [];
    foreach ($results as $r) {
        $suggestions[] = [
            'imdb_id'    => $r['imdb_id'],
            'title'      => $r['title'] ?? '',
            'year'       => $r['start_year'] ?? $r['year'] ?? '',
            'rating'     => $r['imdb_rating'] ?? null,
            'poster_url' => $r['poster_url'] ?? '',
            'type'       => $r['type'] ?? 'movie'
        ];
    }
    echo json_encode($suggestions);
} catch (\Throwable $e) {
    echo json_encode([]);
}
