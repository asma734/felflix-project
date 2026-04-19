# 🎬 FELFLIX — Instructions d'installation

## ✅ ÉTAPE 1 — Importer la base de données

1. Ouvrir **phpMyAdmin** → `http://localhost/phpmyadmin`
2. Cliquer sur l'onglet **"Importer"** (en haut)
3. Cliquer **"Choisir un fichier"** → sélectionner `felflix_database.sql`
4. Cliquer **"Exécuter"**
5. Vous devez voir les 4 tables créées : `users`, `films`, `favorites`, `ratings`

---

## ✅ ÉTAPE 2 — Copier le dossier dans XAMPP

Copier tout le dossier `felflix/` dans :
```
C:\xampp\htdocs\felflix\
```

---

## ✅ ÉTAPE 3 — Vérifier la connexion BDD

Ouvrir `config/database.php` et vérifier :
```php
$db_port = 3306;   // ← Mettre 3307 si votre XAMPP utilise ce port
$db_name = "felflix";
```

Pour vérifier votre port : phpMyAdmin → Variables → rechercher "port"

---

## ✅ ÉTAPE 4 — Importer les films depuis TMDB

Ouvrir dans le navigateur (XAMPP doit être démarré) :
```
http://localhost/felflix/scripts/import_films.php?year=2024
http://localhost/felflix/scripts/import_films.php?year=2023
http://localhost/felflix/scripts/import_films.php?year=2022
```

Attendre la fin de chaque import (quelques minutes).

---

## ✅ ÉTAPE 5 — Lancer l'application

```
http://localhost/felflix/view/signup.php
```

---

## 📁 Structure du projet

```
felflix/
├── felflix_database.sql      ← À importer dans phpMyAdmin EN PREMIER
├── config/
│   ├── database.php          ← Connexion BDD
│   └── data.php              ← Alias pour le script de la prof
├── model/
│   └── User.php              ← Classe User
├── controller/
│   ├── traitement.php        ← Toutes les fonctions (users + films + favoris)
│   ├── update_user.php       ← Modifier un user
│   └── delete_user.php       ← Supprimer un user
├── ai/
│   └── recommender.php       ← Algorithme de recommandation (Jaccard)
├── scripts/
│   └── import_films.php      ← Import TMDB → BDD
└── view/
    ├── signup.php             ← Connexion / Inscription
    ├── index.php              ← Liste des films (page principale)
    ├── film_detail.php        ← Détail d'un film + recommandations
    ├── recommendations.php    ← Recommandations personnalisées + favoris
    ├── user_list.php          ← Admin : liste des utilisateurs
    └── Logout.php             ← Déconnexion
```

---

## 🔗 Navigation

| Page | URL |
|------|-----|
| Connexion | `view/signup.php` |
| Accueil films | `view/index.php` |
| Détail + IA | `view/film_detail.php?id=X` |
| Recommandations | `view/recommendations.php` |
| Admin users | `view/user_list.php` |
| Import films | `scripts/import_films.php?year=2024` |
