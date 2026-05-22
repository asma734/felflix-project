<?php
// ============================================================
//  Vue Admin : Gestion des Films Tunisiens Locaux
//  Permet d'ajouter / modifier / supprimer des films locaux
//  et de leur associer des genres et des acteurs (v8)
//
//  Accès réservé aux admins — redirectionne sinon.
// ============================================================
session_start();

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: index.php');
    exit;
}

require_once '../controller/traitement.php';

$user    = $_SESSION['user'];
$message = '';

// ────────────────────────────────────────────────────────────
//  ACTION : Suppression d'un film
// ────────────────────────────────────────────────────────────
if (isset($_GET['del']) && (int)$_GET['del'] > 0) {
    // Les genres et acteurs seront supprimés via ON DELETE CASCADE
    $cnx->prepare("DELETE FROM movies WHERE id = ?")->execute([(int)$_GET['del']]);
    header('Location: admin_movies.php?msg=deleted');
    exit;
}

// ────────────────────────────────────────────────────────────
//  ACTION : Ajout ou modification d'un film
// ────────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_movie'])) {
    $editId      = (int)($_POST['movie_id'] ?? 0);
    $title       = trim($_POST['title']       ?? '');
    $description = trim($_POST['description'] ?? '');
    $year        = (int)($_POST['year']       ?? date('Y'));
    $rating      = (float)($_POST['rating']   ?? 0);
    $pepper      = (int)($_POST['pepper']     ?? 3);
    $isRamadan   = isset($_POST['is_ramadan']) ? 1 : 0;
    $emoji       = trim($_POST['emoji']       ?? '🎬');
    $bgColor     = trim($_POST['bg_color']    ?? '#e6394633');
    $trailerUrl  = trim($_POST['trailer_url'] ?? '');
    $genreIds    = array_map('intval', $_POST['genre_ids'] ?? []);

    // Acteurs : tableau de noms séparés par virgule
    $actorsRaw   = trim($_POST['actors']    ?? '');
    $directorRaw = trim($_POST['director']  ?? '');

    if (!$title) {
        $message = '⚠️ Le titre est obligatoire.';
    } else {
        if ($editId) {
            // Modification d'un film existant
            $cnx->prepare(
                "UPDATE movies SET title=?, description=?, year=?, rating=?, pepper=?,
                 is_ramadan=?, emoji=?, bg_color=?, trailer_url=? WHERE id=?"
            )->execute([$title, $description, $year, $rating, $pepper, $isRamadan, $emoji, $bgColor, $trailerUrl, $editId]);
            $movieId = $editId;
            $message = '✅ Film modifié avec succès.';
        } else {
            // Ajout d'un nouveau film
            $cnx->prepare(
                "INSERT INTO movies (title, description, year, rating, pepper, is_ramadan, emoji, bg_color, trailer_url, country)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Tunisia')"
            )->execute([$title, $description, $year, $rating, $pepper, $isRamadan, $emoji, $bgColor, $trailerUrl]);
            $movieId = (int)$cnx->lastInsertId();
            $message = '✅ Film ajouté avec succès.';
        }

        // Synchronise les genres (remplace les anciens)
        syncMovieGenres($cnx, $movieId, $genreIds);

        // Supprime les anciens acteurs du film avant de réinsérer
        $cnx->prepare("DELETE FROM movie_actors WHERE movie_id = ?")->execute([$movieId]);

        // Ajoute le réalisateur si renseigné
        if ($directorRaw) {
            $dirId = createActorIfNotExists($cnx, $directorRaw);
            attachActorToMovie($cnx, $movieId, $dirId, 'director', '', 1);
        }

        // Ajoute les acteurs (noms séparés par virgule)
        if ($actorsRaw) {
            $actorNames = array_filter(array_map('trim', explode(',', $actorsRaw)));
            foreach ($actorNames as $order => $name) {
                $actId = createActorIfNotExists($cnx, $name);
                attachActorToMovie($cnx, $movieId, $actId, 'actor', '', $order + 1);
            }
        }

        header("Location: admin_movies.php?msg=saved");
        exit;
    }
}

// ────────────────────────────────────────────────────────────
//  Chargement des données pour l'affichage
// ────────────────────────────────────────────────────────────
$movies    = getTunisianMovies($cnx);
$allGenres = getAllGenres($cnx);

// Film à éditer (si ?edit=ID dans l'URL)
$editMovie      = null;
$editGenreIds   = [];
$editActors     = '';
$editDirector   = '';

if (isset($_GET['edit']) && (int)$_GET['edit'] > 0) {
    $stmt = $cnx->prepare("SELECT * FROM movies WHERE id = ?");
    $stmt->execute([(int)$_GET['edit']]);
    $editMovie = $stmt->fetch();

    if ($editMovie) {
        // Pré-charge les genres du film
        $gStmt = $cnx->prepare(
            "SELECT genre_id FROM movie_genres WHERE movie_id = ?"
        );
        $gStmt->execute([$editMovie['id']]);
        $editGenreIds = $gStmt->fetchAll(PDO::FETCH_COLUMN);

        // Pré-charge les acteurs (noms séparés par virgule)
        $actorsList = getActorsByMovie($cnx, $editMovie['id']);
        $editActors = implode(', ', array_column($actorsList, 'name'));

        // Pré-charge le réalisateur
        $dirList = getDirectorsByMovie($cnx, $editMovie['id']);
        $editDirector = !empty($dirList) ? $dirList[0]['name'] : '';
    }
}

// Message flash depuis redirect
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'saved')   $message = '✅ Enregistré avec succès 🌶';
    if ($_GET['msg'] === 'deleted') $message = '🗑️ Film supprimé.';
}

$pageTitle  = 'Admin Films — Felflix';
$activePage = 'admin';
require_once '_header.php';
?>

<div class="wrap" style="padding-top:90px;padding-bottom:60px">

  <div style="display:flex;align-items:center;gap:12px;margin-bottom:6px">
    <a href="admin.php" class="btn-ghost btn-sm">← Retour Admin</a>
  </div>

  <h1 style="font-family:'Syne',sans-serif;font-weight:800;font-size:1.8rem;color:#fff;margin-bottom:4px">
    🎬 Gestion des Films Tunisiens
  </h1>
  <p style="color:var(--muted);font-size:.84rem;margin-bottom:24px">
    Ajouter, modifier ou supprimer les films locaux avec leurs genres et acteurs 🌶
  </p>

  <?php if ($message): ?>
    <div style="background:rgba(34,197,94,.1);border:1px solid rgba(34,197,94,.3);border-radius:10px;padding:12px 18px;color:#4ade80;margin-bottom:20px;font-size:.88rem">
      <?= htmlspecialchars($message) ?>
    </div>
  <?php endif; ?>

  <div style="display:grid;grid-template-columns:1fr 1.4fr;gap:28px;align-items:start">

    <!-- ── FORMULAIRE AJOUT / MODIFICATION ── -->
    <div class="glass-card" style="padding:24px;border-radius:16px">
      <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;color:#fff;margin-bottom:18px">
        <?= $editMovie ? '✏️ Modifier : ' . htmlspecialchars($editMovie['title']) : '➕ Ajouter un film' ?>
      </h2>

      <form method="POST">
        <input type="hidden" name="save_movie" value="1">
        <input type="hidden" name="movie_id" value="<?= $editMovie['id'] ?? 0 ?>">

        <!-- Titre -->
        <div style="margin-bottom:14px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Titre *</label>
          <input type="text" name="title" required
                 value="<?= htmlspecialchars($editMovie['title'] ?? '') ?>"
                 style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
        </div>

        <!-- Description -->
        <div style="margin-bottom:14px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Description</label>
          <textarea name="description" rows="3"
                    style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;font-size:.85rem;resize:none;outline:none;box-sizing:border-box"><?= htmlspecialchars($editMovie['description'] ?? '') ?></textarea>
        </div>

        <!-- Année / Note / Piment (ligne) -->
        <div style="display:grid;grid-template-columns:1fr 1fr 1fr;gap:10px;margin-bottom:14px">
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Année</label>
            <input type="number" name="year" min="1900" max="2030"
                   value="<?= $editMovie['year'] ?? date('Y') ?>"
                   style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
          </div>
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Note /10</label>
            <input type="number" name="rating" step="0.1" min="0" max="10"
                   value="<?= $editMovie['rating'] ?? '0.0' ?>"
                   style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
          </div>
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">🌶 Piment</label>
            <input type="number" name="pepper" min="1" max="5"
                   value="<?= $editMovie['pepper'] ?? 3 ?>"
                   style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
          </div>
        </div>

        <!-- Emoji / BG Color (ligne) -->
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:14px">
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Emoji</label>
            <input type="text" name="emoji" maxlength="4"
                   value="<?= htmlspecialchars($editMovie['emoji'] ?? '🎬') ?>"
                   style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
          </div>
          <div>
            <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Couleur BG</label>
            <input type="text" name="bg_color" maxlength="20"
                   value="<?= htmlspecialchars($editMovie['bg_color'] ?? '#e6394633') ?>"
                   style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
          </div>
        </div>

        <!-- Trailer URL -->
        <div style="margin-bottom:14px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">Trailer URL (embed YouTube)</label>
          <input type="url" name="trailer_url"
                 value="<?= htmlspecialchars($editMovie['trailer_url'] ?? '') ?>"
                 placeholder="https://www.youtube.com/embed/..."
                 style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
        </div>

        <!-- Réalisateur -->
        <div style="margin-bottom:14px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">🎬 Réalisateur</label>
          <input type="text" name="director"
                 value="<?= htmlspecialchars($editDirector) ?>"
                 placeholder="Ex: Nouri Bouzid"
                 style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
        </div>

        <!-- Acteurs -->
        <div style="margin-bottom:14px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:6px">🎭 Acteurs <span style="color:var(--dim)">(séparés par virgule)</span></label>
          <input type="text" name="actors"
                 value="<?= htmlspecialchars($editActors) ?>"
                 placeholder="Ex: Mohamed Ali, Fatma Ben Ali, Raouf Ben Amor"
                 style="width:100%;background:var(--card2);border:1px solid var(--border);color:#fff;padding:10px 12px;border-radius:10px;font-family:'Space Grotesk',sans-serif;outline:none;box-sizing:border-box">
        </div>

        <!-- Genres (checkboxes depuis la BDD) -->
        <div style="margin-bottom:16px">
          <label style="color:var(--muted);font-size:.8rem;display:block;margin-bottom:8px">🏷️ Genres</label>
          <div style="display:flex;flex-wrap:wrap;gap:8px">
            <?php foreach ($allGenres as $g): ?>
              <label style="display:flex;align-items:center;gap:5px;cursor:pointer;background:var(--card2);border:1px solid var(--border);border-radius:8px;padding:5px 10px;font-size:.78rem;color:var(--muted);transition:all .15s"
                     onmouseover="this.style.borderColor='var(--red)'" onmouseout="if(!this.querySelector('input').checked)this.style.borderColor='var(--border)'">
                <input type="checkbox" name="genre_ids[]" value="<?= $g['id'] ?>"
                       <?= in_array($g['id'], $editGenreIds) ? 'checked' : '' ?>
                       style="accent-color:var(--red)">
                <?= htmlspecialchars($g['icon'] ?? '') ?> <?= htmlspecialchars($g['name']) ?>
              </label>
            <?php endforeach; ?>
          </div>
        </div>

        <!-- Ramadan toggle -->
        <div style="margin-bottom:20px">
          <label style="display:flex;align-items:center;gap:8px;cursor:pointer;color:var(--muted);font-size:.88rem">
            <input type="checkbox" name="is_ramadan" value="1"
                   <?= !empty($editMovie['is_ramadan']) ? 'checked' : '' ?>
                   style="accent-color:#f59e0b;width:16px;height:16px">
            🌙 Film de Ramadan
          </label>
        </div>

        <div style="display:flex;gap:10px">
          <button type="submit" class="btn-hero btn-sm">
            <?= $editMovie ? '💾 Enregistrer' : '➕ Ajouter' ?>
          </button>
          <?php if ($editMovie): ?>
            <a href="admin_movies.php" class="btn-ghost btn-sm">Annuler</a>
          <?php endif; ?>
        </div>
      </form>
    </div>

    <!-- ── LISTE DES FILMS ── -->
    <div>
      <h2 style="font-family:'Syne',sans-serif;font-size:1.1rem;color:#fff;margin-bottom:14px">
        📋 Films existants (<?= count($movies) ?>)
      </h2>

      <?php if (empty($movies)): ?>
        <div class="empty-s">
          <span class="ei">🎬</span>
          <p>Mafammach films encore — zid l'awel! 🌶</p>
        </div>
      <?php else: ?>
        <div style="display:flex;flex-direction:column;gap:10px">
          <?php foreach ($movies as $m):
            $mGenres = getGenresByMovie($cnx, $m['id']);
            $mActors = getActorsByMovie($cnx, $m['id']);
          ?>
            <div class="glass-card" style="padding:14px 16px;border-radius:12px;display:flex;align-items:center;gap:14px">
              <!-- Emoji du film -->
              <div style="font-size:2rem;width:48px;height:48px;background:<?= htmlspecialchars($m['bg_color'] ?? '#e6394633') ?>;border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
                <?= htmlspecialchars($m['emoji'] ?? '🎬') ?>
              </div>

              <!-- Infos -->
              <div style="flex:1;min-width:0">
                <div style="font-weight:600;color:#fff;font-size:.9rem;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">
                  <?= htmlspecialchars($m['title']) ?>
                </div>
                <div style="color:var(--dim);font-size:.75rem;margin-top:2px">
                  <?= $m['year'] ?> · ⭐ <?= $m['rating'] ?> · 🌶 <?= $m['pepper'] ?>
                  <?php if ($m['is_ramadan']): ?> · 🌙<?php endif; ?>
                </div>

                <!-- Genres normalisés -->
                <?php if (!empty($mGenres)): ?>
                  <div style="display:flex;gap:4px;flex-wrap:wrap;margin-top:4px">
                    <?php foreach ($mGenres as $g): ?>
                      <span style="font-size:.62rem;background:rgba(255,255,255,.06);border-radius:5px;padding:1px 6px;color:var(--dim)">
                        <?= htmlspecialchars($g['icon'] ?? '') ?> <?= htmlspecialchars($g['name']) ?>
                      </span>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <!-- Acteurs -->
                <?php if (!empty($mActors)): ?>
                  <div style="color:var(--dim);font-size:.7rem;margin-top:3px">
                    🎭 <?= htmlspecialchars(implode(', ', array_column($mActors, 'name'))) ?>
                  </div>
                <?php endif; ?>
              </div>

              <!-- Actions -->
              <div style="display:flex;gap:6px;flex-shrink:0">
                <a href="admin_movies.php?edit=<?= $m['id'] ?>" class="btn-ghost btn-sm" style="padding:5px 10px;font-size:.75rem">✏️</a>
                <a href="admin_movies.php?del=<?= $m['id'] ?>" class="act-btn del btn-sm"
                   onclick="return confirm('Supprimer <?= htmlspecialchars(addslashes($m['title'])) ?> ?')"
                   style="padding:5px 10px;font-size:.75rem">🗑️</a>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>

  </div>
</div>

<?php require_once '_footer.php'; ?>
