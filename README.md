# 🌶 فلفليكس — Felflix v2

## 🚀 Installation rapide (3 étapes)

### 1. Copier dans htdocs
```
C:\xampp\htdocs\felflix\
```

### 2. Importer la base de données
- Ouvrir `http://localhost/phpmyadmin`
- Cliquer sur **felflix** (barre gauche)
- Onglet **Importer** → choisir `felflix.sql` → **Exécuter**

### 3. Ouvrir le site
```
http://localhost/felflix/view/index.php
```

---

## ⚙️ Configuration (si nécessaire)

Ouvrir `config/database.php` et vérifier :

```php
$db_pwd  = "";       // laisser vide si pas de mot de passe
// port 3307 si XAMPP utilise ce port (visible dans XAMPP Control Panel)
"mysql:host=$db_server;port=3307;dbname=$db_name;charset=utf8mb4"
```

---

## 📁 Structure
```
felflix/
├── config/database.php          ← Connexion BDD
├── model/User.php               ← Classe User
├── controller/
│   ├── traitement.php           ← Toutes les fonctions CRUD
│   ├── chatbot_api.php          ← API Groq (IA)
│   ├── logout.php
│   ├── delete_user.php
│   └── update_user.php
├── view/
│   ├── index.php                ← Page d'accueil
│   ├── movies.php               ← Tous les films
│   ├── film.php                 ← Détail film
│   ├── ramadan.php              ← Films ramadan
│   ├── tunisian.php             ← Films tunisiens
│   ├── watchlist.php            ← Ma liste
│   ├── login.php                ← Connexion ✅ BDD
│   ├── signup.php               ← Inscription ✅ BDD
│   ├── profile.php              ← Profil ✅ BDD
│   └── user_list.php            ← Admin ✅ BDD
└── assets/
    ├── css/style.css + chat.css
    └── js/script.js
```

---

## ✨ Fonctionnalités

| Feature | Status |
|---------|--------|
| Inscription / Connexion | ✅ Lié à MySQL |
| Déconnexion | ✅ Session PHP |
| Profil modifiable | ✅ Lié à MySQL |
| Panel Admin (CRUD users) | ✅ Lié à MySQL |
| Films + filtre genre | ✅ JS |
| Watchlist | ✅ localStorage |
| Chatbot أمي الفلفل | ✅ Groq AI |
| Recherche films | ✅ JS |

---

## 🔑 Créer un compte Admin

1. Aller sur `http://localhost/felflix/view/signup.php`
2. S'inscrire avec `admin@felflix.tn`
3. Aller dans phpMyAdmin → table `users` → changer `role` = `admin`

مصنوع بالحب في تونس 🇹🇳🌶
