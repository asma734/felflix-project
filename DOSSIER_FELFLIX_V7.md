# 🌶 FELFLIX v7 — DOSSIER COMPLET
## Système d'Intelligence Émotionnelle Cinématographique

---

## 📋 TABLE DES MATIÈRES

1. Vue d'ensemble du projet
2. Architecture technique
3. Nouvelles fonctionnalités
4. Base de données (schéma)
5. API Endpoints
6. Logique du Chatbot 3ami l felfil
7. Instructions d'installation
8. Guide de mise à jour

---

## 1. VUE D'ENSEMBLE

Felflix v7 transforme la plateforme en un **système d'intelligence émotionnelle** utilisant le cinéma.
Ce n'est plus un simple site de films — c'est un assistant émotionnel qui :

- Comprend les émotions et situations de vie via le langage naturel
- Suit l'évolution émotionnelle mensuelle (Mood Jar)
- Recommande des films adaptés à l'état émotionnel réel
- Construit un profil ADN émotionnel long-terme
- Offre une expérience sociale (posts, amis, communauté)

---

## 2. ARCHITECTURE TECHNIQUE

```
felflix/
├── assets/
│   ├── css/
│   │   ├── style.css          — CSS principal (dark cinematic UI)
│   │   └── chat.css           — Styles chatbot
│   └── js/
│       └── script.js          — JS utilitaires + canvas BG
│
├── config/
│   └── database.php           — Connexion PDO MySQL
│
├── controller/
│   ├── chatbot_api.php        — ✨ NOUVEAU: API chatbot émotionnel complet
│   ├── watchlist_api.php      — ✨ NOUVEAU: API REST watchlist (AJAX)
│   ├── tmdb.php               — Intégration TMDB API
│   ├── traitement.php         — Helpers PHP (login, update user, etc.)
│   ├── logout.php             — Déconnexion
│   └── update_user.php        — Mise à jour profil (legacy)
│
├── model/
│   └── User.php               — Modèle utilisateur
│
├── view/
│   ├── _header.php            — Navigation responsive
│   ├── _footer.php            — ✨ AMÉLIORÉ: Chatbot + Match/Escape mode
│   ├── index.php              — Page d'accueil
│   ├── movies.php             — Liste des films
│   ├── series.php             — Liste des séries
│   ├── detail.php             — ✨ AMÉLIORÉ: Modal AJAX add-to-list + mood
│   ├── mood_jar.php           — ✨ REFAIT: Mood Jar cinématique complet
│   ├── profile.php            — ✨ REFAIT: Profil social avec posts
│   ├── watchlist.php          — ✨ REFAIT: Liste par catégories avec move
│   ├── search_friends.php     — ✨ REFAIT: Recherche sociale (follow/unfollow)
│   ├── community.php          — Feed communautaire
│   ├── search.php             — Recherche TMDB
│   ├── login.php              — Connexion
│   ├── signup.php             — Inscription
│   └── admin.php              — Panel administrateur
│
├── felflix_setup.sql          — Schema original
└── felflix_v7_upgrade.sql     — ✨ NOUVEAU: Script de mise à jour v7
```

---

## 3. NOUVELLES FONCTIONNALITÉS

### 🫙 MOOD JAR (REFAIT ENTIÈREMENT)
**Fichier :** `view/mood_jar.php`

- **Jar cinématique glassmorphism** avec effets de lumière et reflets
- **Piments animés** (float, pulse, hover) à l'intérieur du jar
- **Tooltip** au hover sur chaque piment (film + date + mood)
- **Filtre par mood** via pills cliquables
- **Effet de chaleur** si dominance de moods sombres
- **Particles** flottantes à l'intérieur du jar
- **Charts** : Line chart (évolution intensité) + Doughnut (répartition)
- **Humeur Dominante** avec bouton direct vers chatbot
- **Future Mood Predictor** : tendance haussière/baissière détectée
- **Badges** : Explorateur, Arcoiris, Comfort Seeker, Cinéphile, Novice
- **ADN Émotionnel** : barres animées avec % par catégorie
- **Drift Alert** : 3ami l felfil intervient si pattern sombre détecté

### 🤖 CHATBOT 3AMI L FELFIL (REFAIT)
**Fichier :** `controller/chatbot_api.php`

- **Détection de 10 situations de vie** :
  - heartbreak, sick, rainy, tired, lonely, motivated, stressed, nostalgic, failure, celebration
- **Contexte météo** (basé sur mention utilisateur)
- **Contexte heure** (0h-5h = films calmes, soirée = prime time, etc.)
- **Mode MATCH vs ESCAPE** :
  - MATCH → films similaires à l'humeur actuelle
  - ESCAPE → films contraste pour changer d'état
- **Mémoire conversationnelle** (30 derniers messages en session)
- **Intégration Mood Jar** : utilise les 5 derniers moods + profil ADN
- **Drift Detection** : prévient si pattern sombre détecté
- **Logging** dans table `chatbot_logs`
- **Multilingue** : derja, français, anglais, mix

### 📋 WATCHLIST (REFAIT)
**Fichier :** `view/watchlist.php` + `controller/watchlist_api.php`

- **Ajout AJAX** : plus de rechargement de page
- **Modal de sélection** : catégorie + mood en une seule interface
- **Catégories** : Envie de voir, En cours, Vu, Favoris, My List
- **Mood badge** visible sur chaque carte (piment coloré)
- **Move to category** : modal de déplacement entre catégories
- **Mise à jour du profil émotionnel** automatique à chaque ajout
- **Toast notification** : confirmation visuelle après ajout

### 👤 PROFIL SOCIAL (REFAIT)
**Fichier :** `view/profile.php`

- **Cover photo** cinématique avec pattern
- **Stats** : Ma liste, Posts, Piments, Année
- **Composer de posts** avec tag de film optionnel
- **Feed de posts** avec like + suppression
- **ADN Émotionnel** mini-affichage
- **Search bar** intégrée pour trouver des amis

### 🔍 RECHERCHE D'AMIS (REFAIT)
**Fichier :** `view/search_friends.php`

- **Follow / Unfollow** (table `friendships`)
- **Suggestions** basées sur l'activité
- **Section "Je suis"** avec liste des personnes suivies
- **Cartes profil** avec stats (films, posts)

---

## 4. BASE DE DONNÉES

### Nouvelles tables (script `felflix_v7_upgrade.sql`)

```sql
-- Humeurs disponibles (9 piments)
moods (id, name, name_fr, icon, color, tone, emotional_intensity, pace)

-- Historique de visionnage avec mood
watch_history (id, user_id, tmdb_id, tmdb_type, tmdb_title, mood_id, added_at)

-- Profil émotionnel long-terme
emotional_profiles (
  id, user_id,
  romantic_pct, nostalgic_pct, dark_pct, action_pct,
  happy_pct, sad_pct, anxious_pct,
  diversity_score, balance_score, dominant_mood,
  updated_at
)

-- Logs chatbot
chatbot_logs (id, user_id, user_message, bot_reply, mood_tags, session_id, created_at)

-- Scènes recommandables
scenes (id, tmdb_id, tmdb_type, title, scene_description, scene_emotion, timestamp_sec, mood_id)

-- Système social (follow/unfollow)
friendships (id, user_id, friend_id, status, created_at)

-- Commentaires sur posts
post_comments (id, post_id, user_id, content, created_at)
```

### Colonnes ajoutées aux tables existantes

```sql
-- watchlist
ALTER TABLE watchlist ADD COLUMN category_name VARCHAR(100) DEFAULT 'My List';

-- users
ALTER TABLE users ADD COLUMN cover_image VARCHAR(300);
ALTER TABLE users ADD COLUMN followers_count INT DEFAULT 0;

-- posts
ALTER TABLE posts ADD COLUMN image_url VARCHAR(300);
ALTER TABLE posts ADD COLUMN tmdb_poster VARCHAR(300);
```

### Les 9 Piments (Moods)

| Nom         | Français          | Icon | Tone       | Intensité |
|-------------|-------------------|------|------------|-----------|
| 7zin        | Triste/Déprimé    | 🫙   | dark       | 3         |
| te3ben      | Fatigué           | 😮‍💨  | calm       | 2         |
| far7an      | Heureux           | 🌶️   | light      | 7         |
| heyej       | Excité            | ⚡   | intense    | 9         |
| motive      | Motivé            | 🔥   | intense    | 8         |
| metghachchech| Énervé           | 💢   | chaotic    | 8         |
| tfakkart    | Nostalgique       | 🌅   | reflective | 6         |
| 5ayef       | Anxieux           | 💫   | tense      | 7         |
| romansi     | Romantique        | 💖   | warm       | 6         |

---

## 5. API ENDPOINTS

### `POST /controller/chatbot_api.php`

**Body JSON :**
```json
{
  "message": "Aujourd'hui je suis malade et il pleut",
  "action": "message",
  "mode": "match"
}
```

**Réponse :**
```json
{
  "reply": "3ami comprend...",
  "situation": "sick",
  "mode": "match",
  "history_len": 4
}
```

**Actions disponibles :**
- `message` — envoyer un message
- `reset`   — réinitialiser la conversation

**Modes :**
- `match`  — recommander films similaires à l'humeur
- `escape` — recommander films contraste
- `null`   — laisser 3ami décider

---

### `POST /controller/watchlist_api.php`

**Action `add` :**
```json
{
  "action": "add",
  "tmdb_id": 12345,
  "tmdb_type": "movie",
  "tmdb_title": "Inception",
  "tmdb_poster": "https://...",
  "mood_id": 3,
  "category": "Envie de voir"
}
```

**Actions disponibles :**
- `add`    — ajouter avec mood et catégorie
- `remove` — retirer de la liste
- `move`   — changer de catégorie
- `get`    — récupérer toute la liste groupée
- `check`  — vérifier si un film est dans la liste

---

## 6. LOGIQUE CHATBOT — 3AMI L FELFIL

### Pipeline de traitement

```
1. Réception message utilisateur
   ↓
2. Chargement contexte utilisateur (Mood Jar, ADN émotionnel)
   ↓
3. Détection situation de vie (10 catégories par mots-clés)
   ↓
4. Contexte temporel (heure du jour)
   ↓
5. Mode MATCH/ESCAPE (depuis session)
   ↓
6. Recherche TMDB (si titre mentionné)
   ↓
7. Construction System Prompt (tout le contexte)
   ↓
8. Appel Groq API (LLaMA 3.3 70B)
   ↓
9. Logging en base de données
   ↓
10. Réponse avec metadata (situation, mode)
```

### Détection de situations (keyword matching)

```php
'heartbreak' → ['coeur brisé','rupture','7bibi','bkait','pleure'...]
'sick'       → ['malade','mrith','fièvre','flu','7ami'...]
'rainy'      → ['il pleut','tsakeb','pluie','rain','orage'...]
'tired'      → ['fatigué','te3ben','épuisé','besoin de repos'...]
'lonely'     → ['seul','wa7di','lonely','ennui','vide'...]
'motivated'  → ['motivé','3ayzek','fit3al','prêt','heyej'...]
'stressed'   → ['stress','anxieux','5ayef','angoisse','panic'...]
'nostalgic'  → ['tfakkart','nostalgie','passé','enfance'...]
'failure'    → ['raté','échoué','perdu','rien marche'...]
'celebration'→ ['fête','réussi','yabarna','bravo','anniversaire'...]
```

### Mapping Humeur → Recommandation

```
malade + froid/pluie  → films cozy, légers, réconfortants
"je me sens vide"     → films introspectifs lents
triste + MATCH        → films poignants, cathartiques
triste + ESCAPE       → films espoir, feel-good
motivé                → films inspirants, dépassement
nostalgique           → films 80s/90s, coming-of-age
anxieux               → films calmes, nature, comédie légère
en colère + MATCH     → action cathartique
en colère + ESCAPE    → humour absurde
nuit tardive (0h-5h)  → films atmosphériques, calmes
```

---

## 7. INSTRUCTIONS D'INSTALLATION

### Prérequis
- PHP 8.0+
- MySQL 8.0+
- XAMPP / WAMP / hébergement web

### Étapes

**1. Copier les fichiers**
```
Extraire le ZIP dans htdocs/felflix/ (XAMPP)
```

**2. Créer la base de données**
```sql
-- Dans phpMyAdmin > onglet SQL :
-- Exécuter d'abord le schema original :
SOURCE felflix_setup.sql;

-- Puis le script de mise à jour v7 :
SOURCE felflix_v7_upgrade.sql;
```

**3. Configurer la connexion**
Éditer `config/database.php` :
```php
$host = 'localhost';
$db   = 'felflix';
$user = 'root';
$pass = '';  // ton mot de passe MySQL
```

**4. Compte de test**
```
Email    : admin@felflix.tn
Password : admin123
Rôle     : admin
```

**5. Accéder au site**
```
http://localhost/felflix/view/index.php
```

---

## 8. GUIDE DE MISE À JOUR (depuis v5/v6)

Si tu as déjà une instance Felflix :

```sql
-- Exécuter UNIQUEMENT felflix_v7_upgrade.sql
-- Il utilise IF NOT EXISTS et ADD COLUMN IF NOT EXISTS
-- Donc il est SAFE sur une base existante
SOURCE felflix_v7_upgrade.sql;
```

Puis remplacer ces fichiers :
- `view/mood_jar.php`       (refait)
- `view/profile.php`        (refait)
- `view/watchlist.php`      (refait)
- `view/search_friends.php` (refait)
- `view/detail.php`         (modifié)
- `view/_footer.php`        (modifié)
- `controller/chatbot_api.php`  (refait)
- `controller/watchlist_api.php` (NOUVEAU)

---

## 9. CRÉDITS & APIS

| Service      | Usage                              |
|--------------|------------------------------------|
| TMDB API     | Données films/séries (gratuit)     |
| Groq API     | LLaMA 3.3 70B (chatbot)            |
| Chart.js     | Graphiques Mood Jar                |
| Bootstrap 5  | Grid + composants UI               |
| Font Awesome | Icônes                             |
| Google Fonts | Syne + Space Grotesk               |

---

*Felflix v7 — Made with 🌶 in Tunisia*
