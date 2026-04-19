<?php
// ================================================================
//  FELFLIX — ai/recommender.php
//  Moteur de recommandation (Similarité de Jaccard)
//  Adapté pour la table `films` du code de la prof
//  (genres stockés en texte : "Action, Thriller" et non en JSON)
// ================================================================

// ----------------------------------------------------------------
//  FONCTION DE BASE : Jaccard
//  J(A,B) = |A ∩ B| / |A ∪ B|
// ----------------------------------------------------------------
function jaccard_similarity(array $a, array $b): float {
    if (empty($a) && empty($b)) return 0.0;
    $a = array_map('strtolower', array_map('trim', $a));
    $b = array_map('strtolower', array_map('trim', $b));
    $intersection = array_intersect($a, $b);
    $union        = array_unique(array_merge($a, $b));
    return count($union) === 0 ? 0.0 : count($intersection) / count($union);
}

// ----------------------------------------------------------------
//  Convertir le champ genre texte → tableau
//  "Action, Thriller, Crime"  →  ["Action", "Thriller", "Crime"]
// ----------------------------------------------------------------
function genre_to_array(string $genre_str): array {
    if (empty(trim($genre_str))) return [];
    return array_filter(array_map('trim', explode(',', $genre_str)));
}

// ----------------------------------------------------------------
//  Score de similarité entre deux films
// ----------------------------------------------------------------
function calculate_similarity(array $filmA, array $filmB): float {
    // Similarité des genres (le critère principal)
    $genres_a = genre_to_array($filmA['genre'] ?? '');
    $genres_b = genre_to_array($filmB['genre'] ?? '');
    $score_genre = jaccard_similarity($genres_a, $genres_b);

    // Bonus si même langue d'origine (ex: deux films français)
    $bonus_langue = 0.0;
    if (!empty($filmA['origine']) && $filmA['origine'] === $filmB['origine']) {
        $bonus_langue = 0.15;
    }

    // Score final : genres 85% + bonus langue 15%
    return min(1.0, round($score_genre * 0.85 + $bonus_langue, 4));
}

// ----------------------------------------------------------------
//  RECOMMANDATION : films similaires à un film donné
// ----------------------------------------------------------------
function recommend_films(PDO $cnx, int $film_id, int $top_n = 10): array {
    // Film de référence
    $stmt = $cnx->prepare("SELECT * FROM films WHERE id = :id");
    $stmt->execute([':id' => $film_id]);
    $reference = $stmt->fetch();
    if (!$reference) return [];

    // Tous les autres films (on prend les 500 mieux notés)
    $stmt = $cnx->prepare(
        "SELECT * FROM films WHERE id != :id AND votes > 20
         ORDER BY rating DESC LIMIT 500"
    );
    $stmt->execute([':id' => $film_id]);
    $candidates = $stmt->fetchAll();

    // Calculer les scores
    $results = [];
    foreach ($candidates as $film) {
        $score = calculate_similarity($reference, $film);
        if ($score >= 0.10) {
            $results[] = ['film' => $film, 'score' => $score];
        }
    }

    // Trier par score décroissant
    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $top_n);
}

// ----------------------------------------------------------------
//  RECOMMANDATION PERSONNALISÉE basée sur les favoris de l'user
// ----------------------------------------------------------------
function recommend_for_user(PDO $cnx, int $user_id, int $top_n = 10): array {
    // Films favoris de l'utilisateur
    $stmt = $cnx->prepare(
        "SELECT f.* FROM films f
         INNER JOIN favorites fav ON f.id = fav.film_id
         WHERE fav.user_id = :uid"
    );
    $stmt->execute([':uid' => $user_id]);
    $liked = $stmt->fetchAll();

    // Pas de favoris → retourner les films populaires
    if (empty($liked)) {
        $stmt = $cnx->prepare("SELECT * FROM films ORDER BY rating DESC LIMIT :n");
        $stmt->bindValue(':n', $top_n, PDO::PARAM_INT);
        $stmt->execute();
        return array_map(fn($f) => ['film' => $f, 'score' => null], $stmt->fetchAll());
    }

    // Construire le profil de l'utilisateur : genres préférés
    $genre_count = [];
    foreach ($liked as $film) {
        foreach (genre_to_array($film['genre'] ?? '') as $g) {
            $g = strtolower(trim($g));
            $genre_count[$g] = ($genre_count[$g] ?? 0) + 1;
        }
    }
    arsort($genre_count);
    $top_genres = array_keys(array_slice($genre_count, 0, 3));

    // IDs déjà vus
    $seen_ids = implode(',', array_column($liked, 'id'));

    $stmt = $cnx->prepare(
        "SELECT * FROM films
         WHERE id NOT IN ($seen_ids) AND votes > 20
         ORDER BY rating DESC LIMIT 200"
    );
    $stmt->execute();
    $candidates = $stmt->fetchAll();

    $results = [];
    foreach ($candidates as $film) {
        $film_genres = array_map('strtolower', genre_to_array($film['genre'] ?? ''));
        $score = jaccard_similarity($top_genres, $film_genres);
        if ($score > 0) {
            $results[] = ['film' => $film, 'score' => round($score, 4)];
        }
    }

    usort($results, fn($a, $b) => $b['score'] <=> $a['score']);
    return array_slice($results, 0, $top_n);
}
