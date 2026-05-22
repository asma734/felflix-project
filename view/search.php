<?php
// ============================================================
//  VUE : Recherche Avancée (Intégration Felfel)
// ============================================================
session_start();
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../model/TitleModel.php';
require_once __DIR__.'/../model/GenreModel.php';

$titleModel = new TitleModel();
$genreModel = new GenreModel();

// Récupération des filtres depuis l'URL
$filters = [
    'q'           => trim($_GET['q'] ?? ''),
    'type'        => in_array($_GET['type'] ?? '', ['movie', 'series']) ? $_GET['type'] : '',
    'genre_id'    => (int)($_GET['genre_id']    ?? 0),
    'country_id'  => (int)($_GET['country_id']  ?? 0),
    'language_id' => (int)($_GET['language_id'] ?? 0),
    'year_from'   => (int)($_GET['year_from']   ?? 0),
    'year_to'     => (int)($_GET['year_to']     ?? 0),
    'min_rating'  => (float)($_GET['min_rating'] ?? 0),
];

// Tri
$sortKey = $_GET['sort'] ?? 'rating';
$sortMap = [
    'rating'    => 't.imdb_rating DESC, t.imdb_votes DESC',
    'year_desc' => 'ISNULL(t.start_year), t.start_year DESC',
    'year_asc'  => 'ISNULL(t.start_year), t.start_year ASC',
    'title'     => 't.title ASC'
];
$orderBy = $sortMap[$sortKey] ?? $sortMap['rating'];

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24;

[$total, $whereClause, $params] = $titleModel->countFiltered($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$page = min($page, $totalPages);
$results = $titleModel->getFiltered($whereClause, $params, $orderBy, $perPage, ($page - 1) * $perPage);

// Chargement des listes pour les filtres
$genres    = $genreModel->getAllGenres();
$countries = $genreModel->getAllCountries();
$languages = $genreModel->getAllLanguages();

$pageTitle = ($filters['q'] ? "\"{$filters['q']}\" — " : '') . 'Recherche avancée 🌶';
$activePage = 'search';
require_once '_header.php';
?>

<div class="wrap" style="padding-top:120px; padding-bottom:20px">
    <!-- Header Style Image 1 -->
    <div class="search-header-premium" style="margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 15px; margin-bottom: 10px;">
            <img src="assets/img/pepper_logo.png" onerror="this.src='data:image/svg+xml;utf8,<svg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'%23e63946\'><path d=\'M12 2C10.9 2 10 2.9 10 4V6H14V4C14 2.9 13.1 2 12 2M14 6C16.2 6 18 7.8 18 10C18 13.5 14 18 12 22C10 18 6 13.5 6 10C6 7.8 7.8 6 10 6H14M12 8C10.9 8 10 8.9 10 10C10 11.1 10.9 12 12 12C13.1 12 14 11.1 14 10C14 8.9 13.1 8 12 8Z\'/></svg>'" style="height: 40px;">
            <h1 style="font-family: 'Space Grotesk', sans-serif; font-weight: 800; font-size: 2.8rem; margin: 0; color: #fff; letter-spacing: -1px;">Aflam <span style="color: #f5a623;">m7ar7ra</span></h1>
        </div>
        <p style="color: var(--muted); font-size: 0.95rem; margin-left: 5px;"><?= number_format($total, 0, '.', ' ') ?> films trouvés</p>
    </div>

    <!-- Barre de filtres Premium (Image 1) -->
    <form method="GET" class="filter-bar-premium">
        <div class="f-group">
            <label><i class="fas fa-search"></i> RECHERCHE</label>
            <input name="q" value="<?= h($filters['q']) ?>" placeholder="Titre du film..."/>
        </div>
        
        <div class="f-group">
            <label><i class="fas fa-mask"></i> GENRE</label>
            <select name="genre_id">
                <option value="0">— tous —</option>
                <?php foreach($genres as $g): ?>
                    <option value="<?= $g['id'] ?>" <?= $filters['genre_id']==$g['id']?'selected':'' ?>><?= h($g['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f-group">
            <label><i class="fas fa-globe"></i> PAYS</label>
            <select name="country_id">
                <option value="0">— tous —</option>
                <?php foreach($countries as $c): ?>
                    <option value="<?= $c['id'] ?>" <?= $filters['country_id']==$c['id']?'selected':'' ?>><?= h($c['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f-group">
            <label><i class="fas fa-comment"></i> LANGUE</label>
            <select name="language_id">
                <option value="0">— toutes —</option>
                <?php foreach($languages as $l): ?>
                    <option value="<?= $l['id'] ?>" <?= $filters['language_id']==$l['id']?'selected':'' ?>><?= h($l['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="f-group group-year">
            <label><i class="fas fa-calendar-alt"></i> ANNÉE DE</label>
            <input type="number" name="year_from" value="<?= $filters['year_from']?:'1980' ?>" placeholder="1980"/>
        </div>

        <div class="f-group group-year">
            <label><i class="fas fa-calendar-check"></i> À</label>
            <input type="number" name="year_to" value="<?= $filters['year_to']?:'2025' ?>" placeholder="2025"/>
        </div>

        <div class="f-group">
            <label><i class="fas fa-star"></i> NOTE MIN</label>
            <input type="number" step="0.1" name="min_rating" value="<?= $filters['min_rating']?:'7.0' ?>" placeholder="7.0"/>
        </div>

        <div class="f-group">
            <label><i class="fas fa-sort-amount-down"></i> TRIER PAR</label>
            <select name="sort">
                <option value="rating"    <?= $sortKey==='rating'?'selected':'' ?>>⭐ Mieux notés</option>
                <option value="year_desc" <?= $sortKey==='year_desc'?'selected':'' ?>>📅 Plus récent</option>
                <option value="year_asc"  <?= $sortKey==='year_asc'?'selected':'' ?>>📅 Plus ancien</option>
                <option value="title"     <?= $sortKey==='title'?'selected':'' ?>>🔤 Alphabétique</option>
            </select>
        </div>

        <button type="submit" class="btn-filter-premium">
            Filtrer <i class="fas fa-pepper-hot"></i>
        </button>
    </form>

    <style>
    .filter-bar-premium {
        background: rgba(20, 15, 30, 0.7);
        backdrop-filter: blur(20px);
        border: 1px solid rgba(255, 255, 255, 0.08);
        border-radius: 24px;
        padding: 20px 30px;
        display: flex;
        align-items: flex-end;
        gap: 12px;
        margin-bottom: 50px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.4);
    }
    .f-group {
        flex: 1;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .f-group label {
        font-size: 0.65rem;
        font-weight: 700;
        color: rgba(255, 255, 255, 0.4);
        letter-spacing: 1px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 5px;
    }
    .f-group label i { font-size: 0.7rem; color: #4f46e5; }
    .f-group input, .f-group select {
        background: rgba(0, 0, 0, 0.3);
        border: 1px solid rgba(255, 255, 255, 0.1);
        border-radius: 12px;
        color: #fff;
        padding: 10px 14px;
        font-size: 0.85rem;
        outline: none;
        transition: all 0.2s;
        width: 100%;
    }
    .f-group input:focus, .f-group select:focus {
        border-color: #f5a623;
        background: rgba(0, 0, 0, 0.5);
    }
    .group-year { flex: 0 0 80px; }
    .btn-filter-premium {
        background: linear-gradient(135deg, #e63946, #f5a623);
        border: none;
        border-radius: 18px;
        color: #fff;
        padding: 0 35px;
        height: 48px;
        font-weight: 700;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.3s;
        display: flex;
        align-items: center;
        gap: 10px;
        box-shadow: 0 10px 20px rgba(230, 57, 70, 0.3);
        white-space: nowrap;
    }
    .btn-filter-premium:hover {
        transform: translateY(-3px);
        box-shadow: 0 15px 30px rgba(230, 57, 70, 0.4);
        filter: brightness(1.1);
    }
    </style>

    <!-- Résultats -->
    <div class="grid">
        <?php if(empty($results)): ?>
            <div class="empty-s" style="grid-column: 1/-1;">
                <span class="ei">🔍</span>
                <p>Aucun résultat ne correspond à vos critères.</p>
                <a href="search.php" class="btn-ghost">Réinitialiser</a>
            </div>
        <?php else: ?>
            <?php foreach($results as $m): ?>
                <?php include __DIR__ . '/shared/_card.php'; ?>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Pagination -->
    <?php if($totalPages > 1): ?>
        <div class="pagination" style="margin-top:50px; display:flex; justify-content:center; gap:8px;">
            <?php 
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            for($i = $start; $i <= $end; $i++): 
                $qs = $_GET; $qs['page'] = $i;
            ?>
                <a href="?<?= http_build_query($qs) ?>" class="btn-nav <?= $i === $page ? 'active' : '' ?>" style="padding:8px 15px; border-radius:10px;">
                    <?= $i ?>
                </a>
            <?php endfor; ?>
        </div>
    <?php endif; ?>
</div>

<?php require_once '_footer.php'; ?>
