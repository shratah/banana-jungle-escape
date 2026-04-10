<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'User not logged in']);
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user_id = $_SESSION['user_id'];
    $game_type = $_POST['game_type'];
    $score = isset($_POST['score']) ? intval($_POST['score']) : 0;
    $coins_earned = isset($_POST['coins_earned']) ? intval($_POST['coins_earned']) : 0;
    $time_spent = isset($_POST['time_spent']) ? intval($_POST['time_spent']) : 0;
    $level_completed = isset($_POST['level_completed']) ? intval($_POST['level_completed']) : 0;
    $perfect_level = isset($_POST['perfect_level']) && $_POST['perfect_level'] === 'true';

    // Insert game session
    $sql = "INSERT INTO game_sessions (user_id, game_type, score, coins_earned, time_spent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiii", $user_id, $game_type, $score, $coins_earned, $time_spent);

    if ($stmt->execute()) {
        // Update the user's current coin balance with coins earned in this session, including mini-games
        if ($coins_earned > 0) {
            $updateCoinsSql = "UPDATE users SET current_coins = current_coins + ? WHERE id = ?";
            $updateCoinsStmt = $conn->prepare($updateCoinsSql);
            $updateCoinsStmt->bind_param("ii", $coins_earned, $user_id);
            $updateCoinsStmt->execute();
        }

        $response = ['status' => 'success', 'achievements' => [], 'bonus_coins' => 0];

        // Level completion achievements
        if ($game_type == 'main' && $level_completed > 0 && $level_completed <= 10) {
            awardAchievement($conn, $user_id, 'Completed Level ' . $level_completed, $response);
        }

        // 2. Coin Collector (Total coins >= 2000)
        $total_coins = $conn->query("SELECT SUM(coins_earned) as total FROM game_sessions WHERE user_id = $user_id")->fetch_assoc()['total'];
        if ($total_coins >= 2000) awardAchievement($conn, $user_id, 'Coin Collector', $response);

        // 3. Banana Master (50 total bananas)
        $total_bananas = $conn->query("SELECT SUM(score) as total FROM game_sessions WHERE user_id = $user_id AND game_type = 'main'")->fetch_assoc()['total'];
        if ($total_bananas >= 50) awardAchievement($conn, $user_id, 'Banana Master', $response);

        // 4. Scholar (50 total correct answers/puzzles)
        // For simplicity, we assume one session score = one banana = one correct answer
        if ($total_bananas >= 50) awardAchievement($conn, $user_id, 'Scholar', $response);

        // 5. First Escape
        if ($game_type == 'main' && $score >= 10) awardAchievement($conn, $user_id, 'First Escape', $response);

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save score']);
    }
}

function awardAchievement($conn, $user_id, $name, &$response) {
    $check = $conn->query("SELECT id FROM user_achievements WHERE user_id = $user_id AND achievement_id = (SELECT id FROM achievements WHERE name = '$name')");
    if ($check->num_rows == 0) {
        $conn->query("INSERT INTO user_achievements (user_id, achievement_id) SELECT $user_id, id FROM achievements WHERE name = '$name'");
        $response['achievements'][] = $name;
    }
}
?>
