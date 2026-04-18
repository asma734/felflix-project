<?php
/**
 * watchlist_api.php — API REST pour la gestion de la watchlist
 * Gère les appels AJAX depuis detail.php et watchlist.php
 */
session_start();
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$user = $_SESSION['user'] ?? null;
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Non connecté']);
    exit;
}

$data   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $data['action'] ?? $_GET['action'] ?? 'add';
$uid    = $user['id'];

switch ($action) {

    // ── ADD TO WATCHLIST ──────────────────────────────────────────
    case 'add':
        $tmdb_id    = (int)($data['tmdb_id'] ?? 0);
        $tmdb_type  = in_array($data['tmdb_type'] ?? 'movie', ['movie','tv']) ? $data['tmdb_type'] : 'movie';
        $tmdb_title = trim($data['tmdb_title'] ?? '');
        $tmdb_poster= trim($data['tmdb_poster'] ?? '');
        $mood_id    = (int)($data['mood_id'] ?? 0);
        $category   = trim($data['category'] ?? 'My List') ?: 'My List';

        if (!$tmdb_id) {
            echo json_encode(['success' => false, 'message' => 'ID manquant']);
            exit;
        }

        // Check if already in watchlist
        $chk = $cnx->prepare("SELECT id FROM watchlist WHERE user_id=? AND tmdb_id=? AND tmdb_type=?");
        $chk->execute([$uid, $tmdb_id, $tmdb_type]);
        if ($chk->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Déjà dans ta liste', 'already' => true]);
            exit;
        }

        // Insert into watchlist
        $ins = $cnx->prepare(
            "INSERT INTO watchlist(user_id, tmdb_id, tmdb_title, tmdb_poster, tmdb_type, category_name)
             VALUES(?,?,?,?,?,?)"
        );
        $ins->execute([$uid, $tmdb_id, $tmdb_title, $tmdb_poster, $tmdb_type, $category]);
        $wl_id = $cnx->lastInsertId();

        // Add to watch_history with mood (for Mood Jar)
        if ($mood_id) {
            $hist = $cnx->prepare(
                "INSERT INTO watch_history(user_id, tmdb_id, tmdb_type, tmdb_title, mood_id)
                 VALUES(?,?,?,?,?)"
            );
            $hist->execute([$uid, $tmdb_id, $tmdb_type, $tmdb_title, $mood_id]);

            // Update emotional profile
            updateEmotionalProfile($cnx, $uid);
        }

        echo json_encode([
            'success'  => true,
            'message'  => "🌶 Ajouté à \"$category\" !",
            'wl_id'    => $wl_id,
            'category' => $category
        ]);
        break;

    // ── REMOVE FROM WATCHLIST ─────────────────────────────────────
    case 'remove':
        $wl_id = (int)($data['wl_id'] ?? 0);
        if ($wl_id) {
            $cnx->prepare("DELETE FROM watchlist WHERE id=? AND user_id=?")->execute([$wl_id, $uid]);
        } else {
            $tmdb_id   = (int)($data['tmdb_id'] ?? 0);
            $tmdb_type = $data['tmdb_type'] ?? 'movie';
            $cnx->prepare("DELETE FROM watchlist WHERE user_id=? AND tmdb_id=? AND tmdb_type=?")
                ->execute([$uid, $tmdb_id, $tmdb_type]);
        }
        echo json_encode(['success' => true, 'message' => 'Retiré de ta liste']);
        break;

    // ── MOVE TO CATEGORY ─────────────────────────────────────────
    case 'move':
        $wl_id    = (int)($data['wl_id'] ?? 0);
        $category = trim($data['category'] ?? 'My List');
        $cnx->prepare("UPDATE watchlist SET category_name=? WHERE id=? AND user_id=?")
            ->execute([$category, $wl_id, $uid]);
        echo json_encode(['success' => true, 'message' => "Déplacé vers \"$category\""]);
        break;

    // ── GET WATCHLIST ─────────────────────────────────────────────
    case 'get':
        $stmt = $cnx->prepare(
            "SELECT id, tmdb_id, tmdb_title, tmdb_poster, tmdb_type, category_name, added_at
             FROM watchlist WHERE user_id=? ORDER BY category_name, added_at DESC"
        );
        $stmt->execute([$uid]);
        $items = $stmt->fetchAll();

        // Group by category
        $grouped = [];
        foreach ($items as $item) {
            $cat = $item['category_name'] ?: 'My List';
            if (!isset($grouped[$cat])) $grouped[$cat] = [];
            $grouped[$cat][] = $item;
        }

        echo json_encode(['success' => true, 'grouped' => $grouped, 'total' => count($items)]);
        break;

    // ── CHECK IF IN WATCHLIST ─────────────────────────────────────
    case 'check':
        $tmdb_id   = (int)($data['tmdb_id'] ?? 0);
        $tmdb_type = $data['tmdb_type'] ?? 'movie';
        $chk = $cnx->prepare("SELECT id, category_name FROM watchlist WHERE user_id=? AND tmdb_id=? AND tmdb_type=?");
        $chk->execute([$uid, $tmdb_id, $tmdb_type]);
        $row = $chk->fetch();
        echo json_encode(['in_list' => (bool)$row, 'wl_id' => $row['id'] ?? null, 'category' => $row['category_name'] ?? null]);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Action inconnue']);
}

// ── HELPER: Update Emotional Profile ─────────────────────────────
function updateEmotionalProfile(PDO $cnx, int $uid): void {
    try {
        $stmt = $cnx->prepare(
            "SELECT m.name, COUNT(*) as cnt
             FROM watch_history w
             JOIN moods m ON w.mood_id = m.id
             WHERE w.user_id = ?
             GROUP BY m.name"
        );
        $stmt->execute([$uid]);
        $rows  = $stmt->fetchAll();
        $total = array_sum(array_column($rows, 'cnt'));
        if (!$total) return;

        // Mapping mood → emotional category
        $mapping = [
            'romansi'       => 'romantic_pct',
            'tfakkart'      => 'nostalgic_pct',
            '7zin'          => 'sad_pct',
            'metghachchech' => 'dark_pct',
            'motive'        => 'action_pct',
            'heyej'         => 'action_pct',
            'far7an'        => 'happy_pct',
            '5ayef'         => 'anxious_pct',
            'te3ben'        => 'sad_pct',
        ];

        $pcts = [
            'romantic_pct' => 0, 'nostalgic_pct' => 0, 'dark_pct' => 0,
            'action_pct'   => 0, 'happy_pct'     => 0, 'sad_pct'  => 0,
            'anxious_pct'  => 0
        ];

        $dominant  = '';
        $maxCount  = 0;

        foreach ($rows as $row) {
            $col = $mapping[$row['name']] ?? null;
            if ($col) $pcts[$col] += ($row['cnt'] / $total) * 100;
            if ($row['cnt'] > $maxCount) {
                $maxCount = $row['cnt'];
                $dominant = $row['name'];
            }
        }

        // Diversity score = unique moods / total moods * 100
        $diversityScore = (count($rows) / 9) * 100;

        // Balance score = 100 - standard deviation of percentages
        $vals    = array_values($pcts);
        $mean    = array_sum($vals) / count($vals);
        $variance= array_sum(array_map(fn($v) => pow($v - $mean, 2), $vals)) / count($vals);
        $std     = sqrt($variance);
        $balance = max(0, 100 - $std);

        $cnx->prepare(
            "INSERT INTO emotional_profiles
             (user_id, romantic_pct, nostalgic_pct, dark_pct, action_pct, happy_pct, sad_pct, anxious_pct, diversity_score, balance_score, dominant_mood)
             VALUES(?,?,?,?,?,?,?,?,?,?,?)
             ON DUPLICATE KEY UPDATE
             romantic_pct=VALUES(romantic_pct), nostalgic_pct=VALUES(nostalgic_pct),
             dark_pct=VALUES(dark_pct), action_pct=VALUES(action_pct),
             happy_pct=VALUES(happy_pct), sad_pct=VALUES(sad_pct),
             anxious_pct=VALUES(anxious_pct), diversity_score=VALUES(diversity_score),
             balance_score=VALUES(balance_score), dominant_mood=VALUES(dominant_mood)"
        )->execute([
            $uid,
            round($pcts['romantic_pct'], 1), round($pcts['nostalgic_pct'], 1),
            round($pcts['dark_pct'], 1),     round($pcts['action_pct'], 1),
            round($pcts['happy_pct'], 1),    round($pcts['sad_pct'], 1),
            round($pcts['anxious_pct'], 1),  round($diversityScore, 1),
            round($balance, 1),              $dominant
        ]);
    } catch (Exception $e) {
        // Silently fail — non-critical
    }
}
