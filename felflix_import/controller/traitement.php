<?php
// ================================================================
//  FELFLIX — controller/traitement.php
//  Toutes les fonctions : Users + Films + Favoris
// ================================================================
include_once(__DIR__ . "/../config/database.php");
include_once(__DIR__ . "/../model/User.php");

// ================================================================
//  SECTION USERS
// ================================================================

function AddUser($cnx, $data) {
    $password = password_hash($data['password'], PASSWORD_DEFAULT);
    $req = $cnx->prepare(
        "INSERT INTO users (nom, email, password) VALUES (:nom, :email, :password)"
    );
    return $req->execute([
        ':nom'      => $data['user_name'],
        ':email'    => $data['email'],
        ':password' => $password
    ]);
}

function getAllUsers($cnx) {
    $req = $cnx->prepare("SELECT * FROM users");
    $req->execute();
    $users = [];
    foreach ($req->fetchAll() as $row) {
        $u = new User($row['nom'], $row['email'], $row['password']);
        $u->id = $row['id'];
        $users[] = $u;
    }
    return $users;
}

function searchUsers($cnx, $name) {
    $req = $cnx->prepare("SELECT * FROM users WHERE nom LIKE :name");
    $req->execute([':name' => "%$name%"]);
    $users = [];
    foreach ($req->fetchAll() as $row) {
        $u = new User($row['nom'], $row['email'], $row['password']);
        $u->id = $row['id'];
        $users[] = $u;
    }
    return $users;
}

function ConnectUser($cnx, $data) {
    $req = $cnx->prepare("SELECT * FROM users WHERE email = :email");
    $req->execute([':email' => $data['email']]);
    $user = $req->fetch();
    if ($user && password_verify($data['password'], $user['password'])) {
        return $user;
    }
    return false;
}

// ================================================================
//  SECTION FILMS
// ================================================================

function getAllFilms($cnx, $limit = 20, $offset = 0) {
    $stmt = $cnx->prepare(
        "SELECT * FROM films ORDER BY rating DESC LIMIT :limit OFFSET :offset"
    );
    $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function getFilmById($cnx, $id) {
    $stmt = $cnx->prepare("SELECT * FROM films WHERE id = :id");
    $stmt->execute([':id' => $id]);
    return $stmt->fetch();
}

function searchFilms($cnx, $query) {
    $stmt = $cnx->prepare(
        "SELECT * FROM films
         WHERE title LIKE :q OR genre LIKE :q OR description LIKE :q
         ORDER BY rating DESC
         LIMIT 50"
    );
    $stmt->execute([':q' => "%$query%"]);
    return $stmt->fetchAll();
}

function getFilmsByGenre($cnx, $genre) {
    $stmt = $cnx->prepare(
        "SELECT * FROM films WHERE genre LIKE :genre ORDER BY rating DESC LIMIT 50"
    );
    $stmt->execute([':genre' => "%$genre%"]);
    return $stmt->fetchAll();
}

function countFilms($cnx) {
    return $cnx->query("SELECT COUNT(*) FROM films")->fetchColumn();
}

// ================================================================
//  SECTION FAVORIS
// ================================================================

function addFavorite($cnx, $user_id, $film_id) {
    $stmt = $cnx->prepare(
        "INSERT IGNORE INTO favorites (user_id, film_id) VALUES (:u, :f)"
    );
    return $stmt->execute([':u' => $user_id, ':f' => $film_id]);
}

function removeFavorite($cnx, $user_id, $film_id) {
    $stmt = $cnx->prepare(
        "DELETE FROM favorites WHERE user_id = :u AND film_id = :f"
    );
    return $stmt->execute([':u' => $user_id, ':f' => $film_id]);
}

function getUserFavorites($cnx, $user_id) {
    $stmt = $cnx->prepare(
        "SELECT f.* FROM films f
         INNER JOIN favorites fav ON f.id = fav.film_id
         WHERE fav.user_id = :u
         ORDER BY fav.added_at DESC"
    );
    $stmt->execute([':u' => $user_id]);
    return $stmt->fetchAll();
}

function isFavorite($cnx, $user_id, $film_id) {
    $stmt = $cnx->prepare(
        "SELECT id FROM favorites WHERE user_id = :u AND film_id = :f"
    );
    $stmt->execute([':u' => $user_id, ':f' => $film_id]);
    return (bool) $stmt->fetch();
}
