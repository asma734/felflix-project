<?php
// ================================================================
//  FELFLIX — scripts/import_films.php
//  Script d'import TMDB (code de la prof, adapté pour Felflix)
//
//  UTILISATION :
//    http://localhost/felflix/scripts/import_films.php?year=2024
//    http://localhost/felflix/scripts/import_films.php?year=2023
//    http://localhost/felflix/scripts/import_films.php?year=2022
//    http://localhost/felflix/scripts/import_films.php?year=2021
//    http://localhost/felflix/scripts/import_films.php?year=2020
//
//  5 années × 5 pages × 20 films = 500 films minimum
//  Augmenter $pages_max à 10 pour avoir 1000 films par an
// ================================================================
include(__DIR__ . "/../config/data.php");

set_time_limit(0);  // Pas de timeout (l'import peut prendre quelques minutes)

$api_key   = "0af659951bc2ddd955f948f945a7f49f";
$year      = $_GET['year'] ?? 2024;
$pages_max = 10;   // ← 10 pages × 20 films = 200 films par année

$imported = 0;
$skipped  = 0;

echo "<!DOCTYPE html><html><head><meta charset='utf-8'>";
echo "<title>Import TMDB</title></head><body>";
echo "<h2>🎬 Import films — Année $year</h2>";

for ($page = 1; $page <= $pages_max; $page++) {

    $url  = "https://api.themoviedb.org/3/discover/movie?api_key=$api_key&primary_release_year=$year&page=$page";
    $data = @file_get_contents($url);
    if (!$data) { echo "⚠️ Page $page inaccessible<br>"; continue; }

    $movies = json_decode($data, true);
    if (!isset($movies['results'])) continue;

    foreach ($movies['results'] as $movie) {
        $id = $movie['id'];

        // Détails du film
        $details_data = @file_get_contents("https://api.themoviedb.org/3/movie/$id?api_key=$api_key");
        if (!$details_data) continue;
        $details = json_decode($details_data, true);

        // Trailer YouTube
        $trailer    = "";
        $video_data = @file_get_contents("https://api.themoviedb.org/3/movie/$id/videos?api_key=$api_key");
        if ($video_data) {
            $videos = json_decode($video_data, true);
            foreach ($videos['results'] ?? [] as $vid) {
                if ($vid['type'] == "Trailer" && $vid['site'] == "YouTube") {
                    $trailer = "https://www.youtube.com/watch?v=" . $vid['key'];
                    break;
                }
            }
        }

        // Données du film
        $title    = $details['title']            ?? '';
        $desc     = $details['overview']         ?? '';
        $poster   = $details['poster_path']      ?? '';
        $date     = $details['release_date']     ?: null;
        $duration = $details['runtime']          ?? 0;
        $rating   = $details['vote_average']     ?? 0;
        $votes    = $details['vote_count']       ?? 0;
        $origine  = $details['original_language'] ?? '';

        // Genres → "Action, Thriller, Crime"
        $genre = implode(", ", array_column($details['genres'] ?? [], 'name'));

        // Vérifier doublon
        $check = $cnx->prepare("SELECT id FROM films WHERE title = ?");
        $check->execute([$title]);

        if (!$check->fetch()) {
            $req = $cnx->prepare("
                INSERT INTO films (title, description, poster, release_date, duration, genre, origine, rating, votes, trailer)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $req->execute([$title, $desc, $poster, $date, $duration, $genre, $origine, $rating, $votes, $trailer]);
            $imported++;
            echo "✅ [$imported] $title<br>";
        } else {
            $skipped++;
        }

        flush();           // Afficher en temps réel dans le navigateur
        usleep(200000);    // 0.2s de pause pour respecter la limite API
    }

    echo "<br>📄 <strong>Page $page/$pages_max terminée</strong><br><br>";
    flush();
}

// Bilan
$total = $cnx->query("SELECT COUNT(*) FROM films")->fetchColumn();
echo "<hr>";
echo "<h3>✅ Import terminé !</h3>";
echo "<p>Films importés cette session : <strong>$imported</strong></p>";
echo "<p>Films ignorés (doublons) : <strong>$skipped</strong></p>";
echo "<p>Total films dans la base : <strong>$total</strong></p>";
echo "<br><a href='../view/index.php'>→ Voir les films</a>";
echo "</body></html>";
