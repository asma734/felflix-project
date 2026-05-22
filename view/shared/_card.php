<?php
/**
 * Composant de carte de film unifié
 * Affiche le poster, le titre, l'année, la note et les acteurs principaux.
 */

// On s'assure d'avoir accès aux modèles si nécessaire
require_once __DIR__ . '/../../model/PersonModel.php';
$personModel = new PersonModel();

// $m : le tableau contenant les données du film (provenant de titles ou movies)
$_id    = $m['imdb_id'] ?? ($m['id'] ?? '');
$_title = $m['title']   ?? ($m['name'] ?? 'Titre inconnu');
$_year  = $m['start_year'] ?? ($m['year'] ?? '');
$_type  = $m['type'] ?? 'movie';
$_rate  = round((float)($m['imdb_rating'] ?? $m['rating'] ?? 0), 1);
$_pos   = trim((string)($m['poster_url'] ?? ''));

// Gestion du poster par défaut
if ($_pos === 'N/A' || $_pos === '') {
    $_pos = 'data:image/svg+xml;utf8,'.rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 200 300"><defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#261932"/><stop offset="1" stop-color="#15101e"/></linearGradient></defs><rect width="200" height="300" fill="url(#g)"/><text x="100" y="150" font-family="sans-serif" font-size="60" text-anchor="middle" fill="#d62828">🌶</text><text x="100" y="200" font-family="sans-serif" font-size="14" text-anchor="middle" fill="#c9b89a">No poster</text></svg>');
}

// Récupération des acteurs (nouveauté intégrée de Felfel)
$actors = [];
if (!empty($_id) && str_starts_with($_id, 'tt')) {
    $actors = $personModel->getActorsByTitle($_id);
}
?>

<a href="detail.php?id=<?= h($_id) ?>" class="mcard" id="movie-<?= h($_id) ?>">
    <div class="mcard-poster">
        <img src="<?= h($_pos) ?>" alt="<?= h($_title) ?>" loading="lazy" onerror="this.src='data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='">
        <span class="badge-type <?= $_type === 'series' ? 'type-serie' : 'type-film' ?>">
            <?= $_type === 'series' ? 'Série' : 'Film' ?>
        </span>
    </div>
    <div class="mcard-body">
        <div class="mcard-title"><?= h($_title) ?></div>
        
        <!-- Affichage des acteurs (Demande utilisateur) -->
        <?php if (!empty($actors)): ?>
            <div class="mcard-actors" style="font-size: 0.7rem; color: var(--muted); margin-bottom: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">
                <i class="fas fa-users" style="font-size: 0.6rem;"></i> 
                <?= h(implode(', ', array_column($actors, 'name'))) ?>
            </div>
        <?php endif; ?>

        <div class="mcard-meta">
            <span class="mcard-rating">⭐ <?= $_rate ?: 'N/A' ?></span>
            <?php if ($_year): ?>
                <span class="mcard-year"><?= h((string)$_year) ?></span>
            <?php endif; ?>
        </div>
        <button class="mcard-btn" type="button">Voir + 🌶</button>
    </div>
</a>
