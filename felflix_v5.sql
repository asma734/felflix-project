USE felflix;

-- users
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('user','admin') DEFAULT 'user',
    avatar VARCHAR(20) DEFAULT '🌶',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- movies (films tunisiens locaux)
CREATE TABLE IF NOT EXISTS movies (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    genre VARCHAR(50) NOT NULL,
    country VARCHAR(100) DEFAULT 'Tunisia',
    year YEAR NOT NULL,
    rating DECIMAL(3,1) DEFAULT 0.0,
    pepper TINYINT DEFAULT 3,
    is_ramadan TINYINT DEFAULT 0,
    description TEXT,
    trailer_url VARCHAR(255),
    emoji VARCHAR(10) DEFAULT '🎬',
    bg_color VARCHAR(20) DEFAULT '#e6394633',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- watchlist (supporte films TMDB ET séries TV)
CREATE TABLE IF NOT EXISTS watchlist (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT DEFAULT NULL,
    tmdb_id INT DEFAULT NULL,
    tmdb_title VARCHAR(255) DEFAULT NULL,
    tmdb_poster VARCHAR(300) DEFAULT NULL,
    tmdb_type ENUM('movie','tv') DEFAULT 'movie',
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_local(user_id, movie_id),
    UNIQUE KEY uq_tmdb(user_id, tmdb_id, tmdb_type),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(movie_id) REFERENCES movies(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- comments (films TMDB + séries + films locaux)
CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    movie_id INT DEFAULT NULL,
    tmdb_id INT DEFAULT NULL,
    tmdb_type ENUM('movie','tv') DEFAULT 'movie',
    content TEXT NOT NULL,
    likes INT DEFAULT 0,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- posts (mur communautaire)
CREATE TABLE IF NOT EXISTS posts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    tmdb_id INT DEFAULT NULL,
    tmdb_title VARCHAR(255) DEFAULT NULL,
    tmdb_type ENUM('movie','tv') DEFAULT 'movie',
    likes INT DEFAULT 0,
    is_deleted TINYINT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- likes posts
CREATE TABLE IF NOT EXISTS post_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    post_id INT NOT NULL,
    UNIQUE KEY uq(user_id,post_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(post_id) REFERENCES posts(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- likes comments
CREATE TABLE IF NOT EXISTS comment_likes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    comment_id INT NOT NULL,
    UNIQUE KEY uq(user_id,comment_id),
    FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY(comment_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Films tunisiens de base
INSERT IGNORE INTO movies(title,genre,country,year,rating,pepper,is_ramadan,description,emoji,bg_color) VALUES
('رجل الرماد','drame','Tunisia',1986,4.5,5,0,'Tحfa de Nouri Bouzid sur la masculinité.','🎭','#7c3aed33'),
('العصفور','drame','Tunisia',1973,4.9,5,0,'Film tunisien classique sur la vie populaire.','🕊️','#7c3aed33'),
('برهوم وبثينة','comedie','Tunisia',2022,4.2,3,0,'Comédie tunisienne sur un couple du quotidien.','😂','#d9770633'),
('خرما','comedie','Tunisia',1999,4.6,4,0,'Film culte 100% tunisien!','😄','#d9770633'),
('ليالي تونس النيون','action','Tunisia',2024,4.3,4,0,'Thriller action dans les rues de Tunis.','🌆','#e6394633'),
('رمضان كريم','drame','Tunisia',2023,4.1,3,1,'Série ramadan tunisienne.','🌙','#f59e0b33');
