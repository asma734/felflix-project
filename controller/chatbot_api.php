<?php
session_start();
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

require_once __DIR__.'/../controller/tmdb.php';
require_once __DIR__.'/../config/database.php';

$groqKey = getenv('GROQ_API_KEY') ?: 'your_groq_api_key_here';

$data        = json_decode(file_get_contents('php://input'), true);
if (!$data) {
    $data = $_POST ?: $_GET;
}
$userMessage = trim($data['message'] ?? '');
$action      = $data['action'] ?? $_GET['action'] ?? 'message';
$user        = $_SESSION['user'] ?? null;

if (!$user) {
    echo json_encode(['reply' => 'Vous devez être inscrit pour discuter avec 3ami l felfil! 🌶']);
    return;
}

// ── GET USER EMOTIONAL PROFILE ──
$emotionalContext = "";
if ($user) {
    try {
        $stmt = $cnx->prepare("SELECT m.name, m.tone FROM watch_history w JOIN moods m ON w.mood_id = m.id WHERE w.user_id = ? ORDER BY w.added_at DESC LIMIT 3");
        $stmt->execute([$user['id']]);
        $recentMoods = $stmt->fetchAll();
        $moodList = array_map(fn($m) => $m['name'], $recentMoods);
        if ($moodList) {
            $emotionalContext = "L'utilisateur a récemment regardé des films avec ces ressentis : " . implode(', ', $moodList) . ". ";
        }
    } catch (\Throwable $e) {}
}

// ── RESET CONVERSATION ──
if ($action === 'reset') {
    $_SESSION['chat_history'] = [];
    echo json_encode(['reply' => "Conv jedida! 🌶 Salam, ana 3ami l felfil — a9li 3la ay film walla mosalsala!"]);
    return;
}

// ── INIT HISTORY ──
if (!isset($_SESSION['chat_history'])) {
    $_SESSION['chat_history'] = [];
}

if (empty($userMessage)) {
    echo json_encode(['reply' => 'Ekteb 7aja! 🌶']);
    return;
}

// ── TMDB CONTEXT ──
$tmdbContext = '';
$results = tmdbSearch($userMessage, 'fr-FR');
if (empty($results)) $results = tmdbSearch($userMessage, 'en-US');

if (!empty($results)) {
    $infos = [];
    foreach (array_slice($results, 0, 2) as $r) {
        $type   = $r['media_type'] ?? 'movie';
        $detail = ($type === 'tv') ? tmdbTVDetail($r['id'], 'fr-FR') : tmdbMovieDetail($r['id'], 'fr-FR');
        if (empty($detail['id'])) {
            $detail = ($type === 'tv') ? tmdbTVDetail($r['id'], 'en-US') : tmdbMovieDetail($r['id'], 'en-US');
        }
        if (!empty($detail['id'])) {
            $typeLabel = $type === 'tv' ? '[SÉRIE TV]' : '[FILM]';
            $infos[]   = "$typeLabel " . tmdbSummary($detail);
        }
    }
    if ($infos) $tmdbContext = "Données TMDB:\n" . implode("\n\n", $infos);
}

// ── SYSTEM PROMPT ──
$sys  = "Tu es '3ami l felfil' 🌶, actant comme un assistant émotionnel cinéma pour l'appli Felflix.\n\n";
$sys .= "RÈGLES ABSOLUES:\n";
$sys .= "1. LANGUE : Réponds EXACTEMENT dans la langue de l'utilisateur. S'il parle derja tunisienne, réponds en derja tunisienne.\n";
$sys .= "2. ASSISTANCE ÉMOTIONNELLE : $emotionalContext Tu dois analyser l'humeur actuelle ou la situation de vie (ex: cœur brisé, triste, malade, motivation) de l'utilisateur.\n";
$sys .= "3. MATCH ou ESCAPE : S'il est triste, demande s'il veut un film pour rester dans ce mood (Match) ou un film pour remonter le moral (Escape).\n";
$sys .= "4. RECOMMANDATIONS INTELLIGENTES : Ne te contente pas des genres. Utilise le ton, le rythme, et l'intensité émotionnelle. Par exemple 'Je me sens vide' -> film introspectif lent.\n";
$sys .= "5. STYLE : Sois un ami cinéphile sage et profond. Utilise des emojis liés à l'humeur (🌶🔥🌧️🌅🎬).\n";
$sys .= "6. MÉTÉO/CONTEXTE : Si l'utilisateur mentionne la météo (ex: il pleut aujourd'hui), recommande des films 'cozy' ou atmosphériques.\n";
if ($tmdbContext) $sys .= "\nINFOS TMDB:\n$tmdbContext\n";

// ── BUILD MESSAGES ARRAY (avec historique complet !) ──
$messages = [["role" => "system", "content" => $sys]];
// On ajoute les 20 derniers échanges (10 tours)
foreach (array_slice($_SESSION['chat_history'], -20) as $h) {
    $messages[] = $h;
}
$messages[] = ["role" => "user", "content" => $userMessage];

// ── CALL GROQ ──
$body = json_encode([
    "model"       => "llama-3.3-70b-versatile",
    "messages"    => $messages,
    "temperature" => 0.75,
    "max_tokens"  => 700
], JSON_UNESCAPED_UNICODE);

$ch = curl_init("https://api.groq.com/openai/v1/chat/completions");
curl_setopt_array($ch, [
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => $body,
    CURLOPT_HTTPHEADER     => ["Content-Type: application/json", "Authorization: Bearer $groqKey"],
    CURLOPT_TIMEOUT        => 30
]);
if (defined('PHPUNIT_TEST_SUITE') && isset($GLOBALS['mock_groq_response'])) {
    $response = $GLOBALS['mock_groq_response'];
    $code     = $GLOBALS['mock_groq_code'] ?? 200;
    $err      = $GLOBALS['mock_groq_err'] ?? '';
} else {
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err      = curl_error($ch);
    curl_close($ch);
}

if ($err || $code !== 200) {
    echo json_encode(['reply' => '😅 Chkoun ma3andoch connexion, 3awed a7awel!']);
    return;
}

$result = json_decode($response, true);
$reply  = $result['choices'][0]['message']['content'] ?? '🌶 Reformule ta question!';

// ── SAVE HISTORY (max 30 messages) ──
$_SESSION['chat_history'][] = ["role" => "user",      "content" => $userMessage];
$_SESSION['chat_history'][] = ["role" => "assistant", "content" => $reply];
if (count($_SESSION['chat_history']) > 30) {
    $_SESSION['chat_history'] = array_slice($_SESSION['chat_history'], -30);
}

echo json_encode([
    'reply'       => $reply,
    'history_len' => count($_SESSION['chat_history'])
], JSON_UNESCAPED_UNICODE);
