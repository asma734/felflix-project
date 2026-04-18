<?php
session_start();
require_once '../config/database.php';
$user = $_SESSION['user'] ?? null;
if (!$user) {
    header('Location: login.php');
    exit;
}

// Fetch all moods
$moods = $cnx->query("SELECT * FROM moods")->fetchAll();

// User history
$historyStmt = $cnx->prepare("SELECT w.*, m.name as mood_name, m.color as mood_color, m.icon as mood_icon, m.tone, m.emotional_intensity FROM watch_history w JOIN moods m ON w.mood_id = m.id WHERE w.user_id = ? ORDER BY w.added_at ASC");
$historyStmt->execute([$user['id']]);
$history = $historyStmt->fetchAll();

$peppers = [];
$counts = [];
foreach ($moods as $m) $counts[$m['name']] = 0;

foreach ($history as $h) {
    $peppers[] = [
        'id' => $h['id'],
        'mood_id' => $h['mood_id'],
        'icon' => $h['mood_icon'],
        'color' => $h['mood_color'],
        'title' => htmlspecialchars($h['tmdb_title'] ?? 'Film')
    ];
    $counts[$h['mood_name']]++;
}

$dominantMood = '';
if (!empty($history)) {
    arsort($counts);
    $dominantMood = array_key_first($counts);
}

$pageTitle = "Mood Jar — Felflix 🌶";
$activePage = 'moodjar';
require_once '_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
  .mood-jar-container {
    padding-top: 100px;
    padding-bottom: 60px;
    min-height: 100vh;
    background: radial-gradient(circle at center, #1a0f14 0%, #050308 100%);
    color: #fff;
    font-family: 'Inter', sans-serif;
  }
  .jar-section {
    display: flex;
    flex-direction: column;
    align-items: center;
    position: relative;
    margin-bottom: 50px;
  }
  .glass-jar {
    width: 320px;
    height: 400px;
    background: rgba(255, 255, 255, 0.05);
    border: 2px solid rgba(255, 255, 255, 0.1);
    border-radius: 40px 40px 100px 100px;
    position: relative;
    box-shadow: 0 0 50px rgba(230, 57, 70, 0.1), inset 0 0 20px rgba(255, 255, 255, 0.05);
    overflow: hidden;
    backdrop-filter: blur(10px);
    display: flex;
    justify-content: center;
    align-items: flex-end;
    padding-bottom: 20px;
  }
  .jar-lid {
    width: 200px;
    height: 30px;
    background: linear-gradient(90deg, #444, #777, #444);
    border-radius: 10px;
    position: absolute;
    top: -25px;
    left: 50%;
    transform: translateX(-50%);
    box-shadow: 0 2px 10px rgba(0,0,0,0.5);
    z-index: 10;
  }
  .pepper-sprite {
    position: absolute;
    font-size: 2.5rem;
    filter: drop-shadow(0 0 10px currentColor);
    animation: floatJar 6s ease-in-out infinite alternate;
    transition: transform 0.3s;
    user-select: none;
    cursor: pointer;
  }
  .pepper-sprite:hover {
    transform: scale(1.3);
    z-index: 20;
  }
  @keyframes floatJar {
    0% { transform: translateY(0px) rotate(0deg); }
    100% { transform: translateY(-20px) rotate(15deg); }
  }
  .dashboard-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 30px;
    max-width: 1000px;
    margin: 0 auto;
    padding: 0 20px;
  }
  .dash-card {
    background: rgba(20, 15, 25, 0.6);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 20px;
    padding: 24px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
    backdrop-filter: blur(15px);
  }
  .dash-card h3 {
    margin-top: 0;
    font-family: 'Syne', sans-serif;
    color: #e63946;
    margin-bottom: 20px;
  }
  .dominant-mood {
    text-align: center;
  }
  .dominant-mood .icon {
    font-size: 5rem;
    filter: drop-shadow(0 0 20px rgba(255,255,255,0.2));
    margin-bottom: 20px;
    animation: pulse 2s infinite alternate;
  }
  @keyframes pulse {
    0% { transform: scale(1); }
    100% { transform: scale(1.1); filter: drop-shadow(0 0 40px currentColor); }
  }
  @media (max-width: 768px) {
    .dashboard-grid { grid-template-columns: 1fr; }
  }
</style>

<div class="mood-jar-container">
  <div class="container">
    <div class="jar-section">
      <h1 style="font-family:'Syne',sans-serif; text-align:center; margin-bottom: 40px; font-size:3rem; font-weight:800; background: -webkit-linear-gradient(#ff6a00, #ee0979); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">🫙 Mood Jar</h1>
      
      <div class="jar-lid"></div>
      <div class="glass-jar" id="glassJar">
        <!-- Peppers injected here via JS -->
      </div>
      <p style="margin-top: 20px; color: #888;">Ce mois-ci, tu as collecté <strong style="color:#fff"><?= count($peppers); ?> piments</strong>.</p>
    </div>

    <div class="dashboard-grid">
      <div class="dash-card">
        <h3>📈 Évolution de l'Humeur</h3>
        <canvas id="moodLineChart"></canvas>
      </div>
      <div class="dash-card">
        <h3>🥧 Répartition</h3>
        <canvas id="moodPieChart"></canvas>
      </div>
      <div class="dash-card dominant-mood" style="grid-column: 1 / -1; display:flex; justify-content:space-between; align-items:center;">
        <div style="text-align:left;">
            <h3>🏆 Humeur Dominante</h3>
            <p style="font-size: 1.2rem; color: #ccc;">Ton aura cinématique ce mois-ci est principalement : <br><strong style="font-size:2rem; color: #fff;"><?= $dominantMood ?: 'Vide' ?></strong></p>
            <p style="color: #888; font-size: 0.9rem; margin-top: 10px;">Basé sur tes derniers films et ressentis.</p>
            <?php if($dominantMood): ?>
            <button class="btn-hero btn-sm" onclick="askAiReco('<?= addslashes($dominantMood) ?>')" style="margin-top:15px;">Demander une reco à 3ami l Felfil 🌶️</button>
            <?php endif; ?>
        </div>
        <div class="icon" id="dominantIcon">
            <?php 
               $domIcon = '🫙';
               foreach($moods as $m) if($m['name'] === $dominantMood) $domIcon = $m['icon'];
               echo $domIcon;
            ?>
        </div>
      </div>
      
      <!-- GAMIFICATION / PREDICTION SECTION -->
      <div class="dash-card" style="grid-column: 1 / -1; margin-top:10px;">
          <h3>🧠 Insights & Gamification</h3>
          <div style="display:flex; justify-content:space-between; flex-wrap:wrap; gap:20px;">
              <div style="background:rgba(0,0,0,0.3); padding:20px; border-radius:15px; flex:1;">
                  <h4 style="color:var(--gold); margin-bottom:10px;">🔮 Future Mood Predictor</h4>
                  <p style="color:#aaa; font-size:0.9rem; line-height:1.5;">
                  <?php 
                      if(count($history) > 3) {
                          $last = array_slice($history, -3);
                          $trend = $last[0]['emotional_intensity'] + $last[1]['emotional_intensity'] + $last[2]['emotional_intensity'];
                          if($trend < 12) echo "Tendance basse détectée. Tu sembles glisser vers des états calmes ou mélancoliques 🌧️. Essaye de regarder une comédie !";
                          else if($trend > 22) echo "Tendance haute détectée. Beaucoup d'intensité / adrénaline 🔥. Prêt pour un film plus relaxant ou réfléchi ?";
                          else echo "Équilibre parfait. Tes émotions voyagent de manière harmonieuse ⚖️.";
                      } else {
                          echo "Continue à remplir ton Mood Jar pour que l'IA puisse prédire tes prochaines tendances.";
                      }
                  ?>
                  </p>
              </div>
              
              <div style="background:rgba(0,0,0,0.3); padding:20px; border-radius:15px; flex:1;">
                  <h4 style="color:#10b981; margin-bottom:10px;">🏅 Badges Émotionnels</h4>
                  <div style="display:flex; gap:10px; align-items:center;">
                  <?php 
                      $uniqueMoodsCount = count(array_unique(array_column($history, 'mood_id')));
                      if ($uniqueMoodsCount >= 5) {
                          echo '<div style="text-align:center;"><div style="font-size:2rem; filter:drop-shadow(0 0 10px #10b981);">🧭</div><div style="font-size:0.75rem; color:#fff; margin-top:5px;">Explorateur</div></div>';
                      }
                      if ($dominantMood && $counts[$dominantMood] >= 4) {
                          echo '<div style="text-align:center;"><div style="font-size:2rem; filter:drop-shadow(0 0 10px #f59e0b);">🧸</div><div style="font-size:0.75rem; color:#fff; margin-top:5px;">Comfort Seeker</div></div>';
                      }
                      if (empty($history)) {
                          echo '<span style="color:#888;">Aucun badge pour le moment.</span>';
                      } else if ($uniqueMoodsCount < 5 && (!isset($counts[$dominantMood]) || $counts[$dominantMood] < 4)) {
                          echo '<div style="text-align:center;"><div style="font-size:2rem; filter:drop-shadow(0 0 10px #888);">🌱</div><div style="font-size:0.75rem; color:#fff; margin-top:5px;">Novice</div></div>';
                      }
                  ?>
                  </div>
              </div>
          </div>
      </div>
    </div>
  </div>
</div>

<script>
const peppersData = <?= json_encode($peppers) ?>;
const historyData = <?= json_encode($history) ?>;

// Render Peppers inside Jar using Physics-like random positioning
const jar = document.getElementById('glassJar');
peppersData.forEach((p, index) => {
    let el = document.createElement('div');
    el.className = 'pepper-sprite';
    el.innerHTML = p.icon;
    el.title = p.title;
    el.style.color = p.color;
    
    // Random position within the jar bottom area
    let x = 30 + Math.random() * 200; // 0 to max width
    let y = 30 + (Math.random() * 250); // stack from bottom
    
    el.style.left = x + 'px';
    el.style.bottom = y + 'px';
    el.style.animationDelay = (Math.random() * 2) + 's';
    
    jar.appendChild(el);
});

// Chart.js Setup
const ctxLine = document.getElementById('moodLineChart').getContext('2d');
const ctxPie = document.getElementById('moodPieChart').getContext('2d');

let labels = historyData.map((h, i) => `Film ${i+1}`);
let lineData = historyData.map(h => parseInt(h.emotional_intensity));
let lineColors = historyData.map(h => h.mood_color);

new Chart(ctxLine, {
    type: 'line',
    data: {
        labels: labels,
        datasets: [{
            label: 'Intensité Émotionnelle',
            data: lineData,
            borderColor: '#e63946',
            backgroundColor: 'rgba(230, 57, 70, 0.2)',
            borderWidth: 2,
            tension: 0.4,
            fill: true,
            pointBackgroundColor: lineColors,
            pointRadius: 6,
            pointHoverRadius: 8
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { min: 0, max: 10, grid: { color: 'rgba(255,255,255,0.1)' }, ticks: { color: '#888' } },
            x: { grid: { display: false }, ticks: { color: '#888' } }
        }
    }
});

let pieLabels = [];
let pieCounts = [];
let pieColors = [];

let freq = {};
historyData.forEach(h => {
    if(!freq[h.mood_name]) freq[h.mood_name] = {count:0, color:h.mood_color};
    freq[h.mood_name].count++;
});

for (let key in freq) {
    pieLabels.push(key);
    pieCounts.push(freq[key].count);
    pieColors.push(freq[key].color);
}

new Chart(ctxPie, {
    type: 'doughnut',
    data: {
        labels: pieLabels,
        datasets: [{
            data: pieCounts,
            backgroundColor: pieColors,
            borderColor: 'rgba(10,5,15,1)',
            borderWidth: 3
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'right', labels: { color: '#fff' } }
        }
    }
});

function askAiReco(moodName) {
    // We launch the chatbot modal with a specific prompt!
    if(window.top && window.top.toggleChat) {
        window.top.toggleChat();
        setTimeout(() => {
            const input = document.getElementById('chatInput');
            if(input) {
                input.value = `Mon humeur dominante est ${moodName}. Tu peux me recommander un film adapté pour équilibrer ou renforcer ça ?`;
            }
        }, 500);
    } else {
        alert("Ouvre le Chat 3ami l Felfil !");
    }
}
</script>

<?php require_once '_footer.php'; ?>
