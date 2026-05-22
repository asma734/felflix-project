<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DFS Recommendations</title>
  <link rel="stylesheet" href="movie-site.css">
  <link rel="stylesheet" href="bfs.css">
</head>
<body>
  <main class="page-shell bfs-shell">
    <section class="bfs-hero">
      <div class="topbar">
        <div class="brand">
          <div class="brand-mark">DF</div>
          <div>
            <p class="eyebrow">Traversal</p>
            <h1>DFS Recommendations</h1>
          </div>
        </div>

        <a class="ghost-link" href="index.html">Back To Home</a>
      </div>

      <div class="bfs-intro">
        <h2>Explore deeper recommendation paths</h2>
        <p class="bfs-text">
          DFS was integrated to explore deep paths in the movie graph, helping the system identify indirect relationships and uncover less obvious recommendations.
        </p>
      </div>
    </section>

    <section class="catalog-section bfs-panel">
      <form class="toolbar bfs-form" method="get" action="dfs.php">
        <select class="bfs-select" name="id">
          <?php foreach ($movies as $movie): ?>
            <option value="<?= h((string)$movie['id']) ?>" <?= $selectedId === $movie['id'] ? 'selected' : '' ?>>
              <?= h((string)$movie['title']) ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button class="toolbar-button" type="submit">Run DFS</button>
      </form>

      <?php if ($pageError !== null): ?>
        <div class="message-box error-box">
          <h3>Request Failed</h3>
          <p><?= h($pageError) ?></p>
        </div>
      <?php endif; ?>
    </section>

    <section class="catalog-section bfs-panel">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Selected Movie</p>
          <h2>Start Node</h2>
        </div>
      </div>

      <?php if ($selectedMovie !== null): ?>
        <a class="movie-card-link" href="movie.html?id=<?= h((string)$selectedMovie['id']) ?>">
          <article class="movie-card bfs-selected-card">
            <div class="poster"><?= render_poster_html_dfs($selectedMovie) ?></div>
            <div class="movie-body">
              <div class="movie-title-row">
                <h3><?= h((string)$selectedMovie['title']) ?></h3>
                <span class="score"><?= h((string)$selectedMovie['age']) ?></span>
              </div>
              <p class="movie-meta"><?= h((string)$selectedMovie['year']) ?> - <?= h((string)$selectedMovie['duration']) ?> min</p>
              <p class="movie-genre"><?= h((string)$selectedMovie['genre']) ?></p>
              <p class="movie-download">Added on <?= h((string)$selectedMovie['download']) ?></p>
            </div>
          </article>
        </a>
      <?php else: ?>
        <div class="message-box">
          <h3>No Movie Selected</h3>
          <p>Choose a movie and submit the form.</p>
        </div>
      <?php endif; ?>
    </section>

    <section class="catalog-section bfs-panel">
      <div class="section-heading">
        <div>
          <p class="eyebrow">Recommendations</p>
          <h2>Deep Path Results</h2>
        </div>
      </div>

      <?php if ($recommendations === []): ?>
        <div class="message-box">
          <h3>No Recommendations</h3>
          <p>No deep recommendation path was found for this start node.</p>
        </div>
      <?php else: ?>
        <div class="movie-grid bfs-grid">
          <?php foreach ($recommendations as $movie): ?>
            <a class="movie-card-link" href="movie.html?id=<?= h((string)$movie['id']) ?>">
              <article class="movie-card">
                <div class="poster"><?= render_poster_html_dfs($movie) ?></div>
                <div class="movie-body">
                  <p class="bfs-link-info">
                    Depth <?= h((string)$movie['distance']) ?> from <?= h((string)$movie['linked_from']) ?>
                  </p>
                  <div class="movie-title-row">
                    <h3><?= h((string)$movie['title']) ?></h3>
                    <span class="score"><?= h((string)$movie['age']) ?></span>
                  </div>
                  <p class="movie-meta"><?= h((string)$movie['year']) ?> - <?= h((string)$movie['duration']) ?> min</p>
                  <p class="movie-genre"><?= h((string)$movie['genre']) ?></p>
                  <div class="bfs-reasons">
                    <?php foreach (($movie['match_reasons'] ?? []) as $reason): ?>
                      <span class="bfs-reason"><?= h((string)$reason) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              </article>
            </a>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </section>
  </main>
</body>
</html>
