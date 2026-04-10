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
        $response = ['status' => 'success', 'achievements' => [], 'bonus_coins' => 0];

        // 1. Perfectionist Bonus (Level finished without losing heart)
        if ($game_type == 'main' && $level_completed > 0 && $perfect_level) {
            $bonus = 300;
            $response['bonus_coins'] = $bonus;
            $conn->query("UPDATE users SET current_coins = current_coins + $bonus WHERE id = $user_id");
            
            // Award Perfectionist Achievement
            awardAchievement($conn, $user_id, 'Perfectionist', $response);
        }

        // Award coin and banana achievements after saving session
        $total_coins = $conn->query("SELECT SUM(coins_earned) as total FROM game_sessions WHERE user_id = $user_id")->fetch_assoc()['total'];
        if ($total_coins >= 500) awardAchievement($conn, $user_id, '500 Coins', $response);
        if ($total_coins >= 1000) awardAchievement($conn, $user_id, '1000 Coins', $response);

        $total_bananas = $conn->query("SELECT SUM(score) as total FROM game_sessions WHERE user_id = $user_id AND game_type = 'minigame'")->fetch_assoc()['total'];
        if ($total_bananas >= 10) awardAchievement($conn, $user_id, '10 Bananas', $response);
        if ($total_bananas >= 20) awardAchievement($conn, $user_id, '20 Bananas', $response);

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save score']);
    }
}

function awardAchievement($conn, $user_id, $name, &$response) {
    $check_sql = "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = (SELECT id FROM achievements WHERE name = ?)";
    $stmt = $conn->prepare($check_sql);
    $stmt->bind_param("is", $user_id, $name);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows == 0) {
        $insert_sql = "INSERT INTO user_achievements (user_id, achievement_id) SELECT ?, id FROM achievements WHERE name = ?";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("is", $user_id, $name);
        $stmt->execute();
        $response['achievements'][] = $name;
    }
}
?>
