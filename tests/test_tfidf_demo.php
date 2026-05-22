<?php
/**
 * Script de Démonstration Interactif : Moteur de Recherche TF-IDF + Modèle Booléen
 * 
 * Ce script permet de tester directement le moteur linguistique dans le terminal
 * et de visualiser les scores TF-IDF calculés pour chaque film du catalogue Felflix.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../model/TitleModel.php';

// Initialisation du modèle avec la base de données réelle
$pdo = db();
$titleModel = new TitleModel($pdo);

// 1. Récupération des films du catalogue pour analyse
$st = $pdo->query("SELECT * FROM titles LIMIT 100");
$titles = $st->fetchAll();

if (empty($titles)) {
    echo "⚠️ Le catalogue de la base de données est vide. Veuillez ajouter des films ou lancer les fixtures pour tester.\n";
    exit;
}

echo "=======================================================================\n";
echo "🔥 FELFEL SEARCH ENGINE : Démonstration TF-IDF + Modèle Booléen 🔥\n";
echo "=======================================================================\n";
echo "Nombre de documents indexés en mémoire : " . count($titles) . "\n";

echo "\n🎬 Quelques exemples de films réels présents dans votre BDD :\n";
$examples = array_slice($titles, 0, 5);
foreach ($examples as $idx => $ex) {
    echo "   " . ($idx + 1) . ". \"" . $ex['title'] . "\" (ID: " . $ex['imdb_id'] . ")\n";
}
echo "\n";

// Choisir intelligemment deux films de la BDD pour faire des requêtes booléennes pertinentes
$title1 = $examples[0]['title'] ?? 'Inception';
$title2 = $examples[1]['title'] ?? 'Interstellar';

// Extraire le premier mot significatif de chaque titre pour la recherche
$words1 = $titleModel->preprocessText($title1);
$word1 = $words1[0] ?? $title1;

$words2 = $titleModel->preprocessText($title2);
$word2 = $words2[0] ?? $title2;

// Liste de requêtes de test dynamiques basées sur votre base de données réelle !
$testQueries = [
    $word1,                         // Recherche simple
    "$word1 or $word2",             // Modèle Booléen OR (Fuzzy Max)
    "$word1 and $word2",            // Modèle Booléen AND (Fuzzy Min)
];

foreach ($testQueries as $query) {
    echo "-----------------------------------------------------------------------\n";
    echo "🔍 Requête de test exécutée : \"$query\"\n";
    echo "-----------------------------------------------------------------------\n";
    
    // Prétraitement linguistique des termes de recherche
    $terms = $titleModel->preprocessText($query);
    echo "📝 Termes après normalisation, stop-words et racinisation (stemming) :\n";
    echo "   [" . implode(', ', $terms) . "]\n\n";
    
    // Exécution du calcul TF-IDF + Modèle Booléen
    $scores = $titleModel->searchTfidfBoolean($query, $titles);
    
    if (empty($scores)) {
        echo "❌ Aucun résultat trouvé (Tous les scores TF-IDF sont à 0 ou aucun match booléen).\n\n";
        continue;
    }
    
    echo "🏆 Résultats classés par score TF-IDF décroissant :\n";
    foreach ($scores as $imdbId => $score) {
        // Recherche des détails du film
        $film = array_filter($titles, fn($t) => $t['imdb_id'] === $imdbId);
        $film = array_values($film)[0] ?? null;
        
        if ($film) {
            printf("  - [%s] %-40s | 📊 Score TF-IDF : %0.4f\n", $imdbId, $film['title'], $score);
        }
    }
    echo "\n";
}
echo "=======================================================================\n";
echo "👉 Astuce : Vous pouvez tester cela directement sur l'interface du site\n";
echo "   en allant sur la page : view/search.php?q=" . urlencode($word1 . " or " . $word2) . "\n";
echo "=======================================================================\n";
