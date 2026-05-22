
<?php
// ============================================================
//  TRAITEMENT — Fonctions BDD principales Felflix v9
//  Inclut : users, movies, genres, acteurs, filtres avancés
// ============================================================
require_once __DIR__.'/../config/database.php';
require_once __DIR__.'/../model/User.php';


// ─────────────────────────────────────────────────────────────
//  SECTION 1 : Utilisateurs
// ─────────────────────────────────────────────────────────────

function addUser($cnx, $data) {
    $c = $cnx->prepare("SELECT id FROM users WHERE email=?");
    $c->execute([$data['email']]);
    if ($c->fetch()) return ['success'=>false,'message'=>'Cet email est déjà utilisé!'];
    $r = $cnx->prepare("INSERT INTO users(nom,email,password,role,avatar,bio) VALUES(?,?,?,?,?,?)");
    $ok = $r->execute([$data['nom'],$data['email'],password_hash($data['password'],PASSWORD_BCRYPT),$data['role']??'user',$data['avatar']??'🌶',$data['bio']??'']);
    return $ok ? ['success'=>true,'id'=>$cnx->lastInsertId()] : ['success'=>false,'message'=>'Erreur'];
}

function loginUser($cnx, $email, $password) {
    $r = $cnx->prepare("SELECT * FROM users WHERE email=?");
    $r->execute([$email]);
    $row = $r->fetch();
    if (!$row) return ['success'=>false,'message'=>'Email mawjoudch! ❌'];
    if (!password_verify($password, $row['password'])) return ['success'=>false,'message'=>'Kalmet es-sir ghaltha! 🔐'];
    return ['success'=>true,'user'=>$row];
}

function getAllUsers($cnx) {
    $rows = $cnx->query("SELECT * FROM users ORDER BY id DESC")->fetchAll();
    $users = [];
    foreach ($rows as $row) {
        $u = new User($row['nom'],$row['email'],$row['password'],$row['role'],$row['avatar'],$row['bio']);
        $u->id = $row['id'];
        $u->created_at = $row['created_at'] ?? '';
        $users[] = $u;
    }
    return $users;
}

function searchUsers($cnx, $q) {
    $r = $cnx->prepare("SELECT * FROM users WHERE nom LIKE ? OR email LIKE ? ORDER BY id DESC");
    $r->execute(["%$q%","%$q%"]);
    $users = [];
    foreach ($r->fetchAll() as $row) {
        $u = new User($row['nom'],$row['email'],$row['password'],$row['role'],$row['avatar'],$row['bio']);
        $u->id = $row['id'];
        $u->created_at = $row['created_at'] ?? '';
        $users[] = $u;
    }
    return $users;
}

function getUserById($cnx, $id) {
    $r = $cnx->prepare("SELECT * FROM users WHERE id=?");
    $r->execute([$id]);
    return $r->fetch();
}

function updateUser($cnx, $id, $nom, $email, $avatar=null, $bio=null) {
    if ($avatar !== null && $bio !== null)
        return $cnx->prepare("UPDATE users SET nom=?,email=?,avatar=?,bio=? WHERE id=?")->execute([$nom,$email,$avatar,$bio,$id]);
    return $cnx->prepare("UPDATE users SET nom=?,email=? WHERE id=?")->execute([$nom,$email,$id]);
}

function deleteUser($cnx, $id) { return $cnx->prepare("DELETE FROM users WHERE id=?")->execute([$id]); }
function countUsers($cnx) { return $cnx->query("SELECT COUNT(*) FROM users")->fetchColumn(); }
function countPosts($cnx) { try { return $cnx->query("SELECT COUNT(*) FROM posts WHERE is_deleted=0")->fetchColumn(); } catch(Exception $e) { return 0; } }


// ─────────────────────────────────────────────────────────────
//  SECTION 2 : Films locaux — récupération
// ─────────────────────────────────────────────────────────────

/** Compte tous les films tunisiens */
function countMovies($cnx) {
    try { return $cnx->query("SELECT COUNT(*) FROM movies WHERE country_code='TN'")->fetchColumn(); }
    catch(Exception $e) { return 0; }
}

/** Retourne tous les films tunisiens triés par note */
function getTunisianMovies($cnx) {
    try { return $cnx->query("SELECT * FROM movies WHERE country_code='TN' ORDER BY rating DESC, year DESC")->fetchAll(); }
    catch(Exception $e) { return []; }
}

/** Retourne films OU séries tunisiennes */
function getTunisianByType($cnx, string $type = 'movie') {
    try {
        $stmt = $cnx->prepare("SELECT * FROM movies WHERE country_code='TN' AND type=? ORDER BY rating DESC, year DESC");
        $stmt->execute([$type]);
        return $stmt->fetchAll();
    } catch(Exception $e) { return []; }
}

/** Filtres avancés : genre, type, saison, âge, pays, année */
function getMoviesFiltered($cnx, array $f): array {
    $where  = ["1=1"];
    $params = [];

    // Type (movie / series)
    if (!empty($f['type'])) {
        $where[] = "m.type = ?";
        $params[] = $f['type'];
    }

    // Pays
    if (!empty($f['country'])) {
        $where[] = "m.country_code = ?";
        $params[] = $f['country'];
    }

    // Saison
    if (!empty($f['season'])) {
        $where[] = "(m.season = ? OR m.season = 'all')";
        $params[] = $f['season'];
    }

    // Tranche d'âge
    if (!empty($f['age'])) {
        $where[] = "m.age_rating = ?";
        $params[] = $f['age'];
    }

    // Année
    if (!empty($f['year_from'])) {
        $where[] = "m.year >= ?";
        $params[] = (int)$f['year_from'];
    }
    if (!empty($f['year_to'])) {
        $where[] = "m.year <= ?";
        $params[] = (int)$f['year_to'];
    }

    // Genre (via pivot)
    if (!empty($f['genre_id'])) {
        $where[] = "EXISTS (SELECT 1 FROM movie_genres mg WHERE mg.movie_id=m.id AND mg.genre_id=?)";
        $params[] = (int)$f['genre_id'];
    }

    // Recherche textuelle
    if (!empty($f['q'])) {
        $where[] = "(m.title LIKE ? OR m.description LIKE ?)";
        $params[] = '%'.$f['q'].'%';
        $params[] = '%'.$f['q'].'%';
    }

    // Tunisien uniquement (section tunisienne)
    if (!empty($f['tunisian_only'])) {
        $where[] = "m.country_code = 'TN'";
    }

    $sql = "SELECT m.* FROM movies m WHERE " . implode(' AND ', $where)
         . " ORDER BY m.rating DESC, m.year DESC";

    $stmt = $cnx->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}


// ─────────────────────────────────────────────────────────────
//  SECTION 3 : Genres
// ─────────────────────────────────────────────────────────────

function getAllGenres($cnx): array {
    try { return $cnx->query("SELECT * FROM genres ORDER BY name")->fetchAll(); }
    catch(PDOException $e) { return []; }
}

function getGenresByMovie($cnx, int $movieId): array {
    try {
        $stmt = $cnx->prepare(
            "SELECT g.* FROM genres g
             JOIN movie_genres mg ON g.id = mg.genre_id
             WHERE mg.movie_id = ? ORDER BY g.name"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) { return []; }
}

function syncMovieGenres($cnx, int $movieId, array $genreIds): void {
    $cnx->prepare("DELETE FROM movie_genres WHERE movie_id=?")->execute([$movieId]);
    $stmt = $cnx->prepare("INSERT IGNORE INTO movie_genres (movie_id, genre_id) VALUES (?,?)");
    foreach ($genreIds as $gid) $stmt->execute([$movieId, (int)$gid]);
}


// ─────────────────────────────────────────────────────────────
//  SECTION 4 : Acteurs
// ─────────────────────────────────────────────────────────────

function getActorsByMovie($cnx, int $movieId): array {
    try {
        $stmt = $cnx->prepare(
            "SELECT a.*, ma.character_name, ma.billing_order, ma.role
             FROM actors a
             JOIN movie_actors ma ON a.id = ma.actor_id
             WHERE ma.movie_id = ? AND ma.role = 'actor'
             ORDER BY ma.billing_order ASC LIMIT 20"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) { return []; }
}

function getDirectorsByMovie($cnx, int $movieId): array {
    try {
        $stmt = $cnx->prepare(
            "SELECT a.*, ma.billing_order FROM actors a
             JOIN movie_actors ma ON a.id = ma.actor_id
             WHERE ma.movie_id = ? AND ma.role = 'director'
             ORDER BY ma.billing_order ASC"
        );
        $stmt->execute([$movieId]);
        return $stmt->fetchAll();
    } catch(PDOException $e) { return []; }
}

function createActorIfNotExists($cnx, string $name, ?string $photoUrl = null): int {
    $stmt = $cnx->prepare("SELECT id FROM actors WHERE name=? LIMIT 1");
    $stmt->execute([$name]);
    $existing = $stmt->fetchColumn();
    if ($existing) return (int)$existing;
    $cnx->prepare("INSERT INTO actors (name, photo_url) VALUES (?,?)")->execute([$name, $photoUrl]);
    return (int)$cnx->lastInsertId();
}

function attachActorToMovie($cnx, int $movieId, int $actorId, string $role='actor', string $charName='', int $order=99): void {
    $cnx->prepare(
        "INSERT IGNORE INTO movie_actors (movie_id, actor_id, role, character_name, billing_order) VALUES (?,?,?,?,?)"
    )->execute([$movieId, $actorId, $role, $charName, $order]);
}










