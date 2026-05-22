# 🏛️ Architecture Technique du Projet Felflix

## 1. Concept de l'Architecture Hybride
Le projet Felflix repose sur une architecture dite **hybride**, combinant des données stockées localement et des ressources récupérées dynamiquement via une API externe.

### A. La Couche Locale (MySQL)
*   **Emplacement** : `C:\xampp\mysql\data\omdb_website\`
*   **Rôle** : C'est le "cerveau" interne du site. Elle gère tout ce qui est spécifique à l'application :
    *   **Indexation** : Titres, années et notes de plus de 64 000 films pour une recherche instantanée.
    *   **Gestion Utilisateurs** : Comptes, avatars, mots de passe.
    *   **Contenu Exclusif** : Films tunisiens ajoutés manuellement (Table `movies`).
    *   **Social & Historique** : Commentaires, Likes, Bocal à Piments (Mood Jar) et Listes de favoris.

### B. La Couche Distante (API TMDB)
*   **Source** : The Movie Database (TMDB)
*   **Rôle** : C'est le "fournisseur de médias". Elle apporte la richesse visuelle :
    *   **Vidéos** : Trailers et bandes-annonces YouTube.
    *   **Images** : Affiches haute définition, images de fond (backdrops).
    *   **Casting** : Photos des visages des acteurs et biographies détaillées.
*   **Mécanisme** : Le site utilise l'identifiant IMDb (ex: `tt0111161`) comme clé pour interroger les serveurs de TMDB en temps réel.

---

## 2. Anatomie de la Base de Données Locale
Dans le dossier `mysql/data/omdb_website`, les données sont organisées en fichiers binaires optimisés.

### Les types de fichiers :
*   **`.frm` (Format)** : Le squelette de la table (colonnes, types de données).
*   **`.ibd` (InnoDB Data)** : Le contenu réel (texte, chiffres) compressé et indexé.

### Les fichiers stratégiques :
1.  **`db.opt`** : Définit l'encodage (UTF-8) pour supporter les caractères spéciaux et l'arabe.
2.  **`titles.ibd`** : Contient le catalogue international (64 047 entrées). C'est le fichier le plus volumineux.
3.  **`users.ibd`** : Stocke les informations de profil et la sécurité (mots de passe hachés).
4.  **`watchlist.ibd`** : Enregistre les films que les utilisateurs ont choisi d'ajouter à leur liste.
5.  **`watch_history.ibd`** : C'est la base du **Mood Jar**. Il lie un film à une émotion (piment) et à une date.

---

## 3. Avantages de cette Architecture
*   **Performance** : La recherche locale est 100 fois plus rapide qu'une recherche sur Internet.
*   **Économie de Stockage** : Nous n'avons pas besoin de stocker des téraoctets de vidéos et d'images HD, TMDB s'en charge pour nous.
*   **Personnalisation** : Nous pouvons ajouter nos propres films (Tunisie) et fonctionnalités (Mood Jar) qui n'existent pas sur les plateformes mondiales.
