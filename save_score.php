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

    // Add bonus coins for perfect levels
    if ($game_type == 'main' && $level_completed > 0 && $perfect_level) {
        $coins_earned += 300; // Perfectionist bonus
    }

    // Insert game session
    $sql = "INSERT INTO game_sessions (user_id, game_type, score, coins_earned, time_spent) VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("isiii", $user_id, $game_type, $score, $coins_earned, $time_spent);

    if ($stmt->execute()) {
        // Update the user's current coin balance
        if ($coins_earned > 0) {
            $updateCoinsSql = "UPDATE users SET current_coins = current_coins + ? WHERE id = ?";
            $updateCoinsStmt = $conn->prepare($updateCoinsSql);
            $updateCoinsStmt->bind_param("ii", $coins_earned, $user_id);
            $updateCoinsStmt->execute();
        }

        $response = ['status' => 'success', 'achievements' => [], 'gift_boxes' => 0];

        // LEVEL COMPLETION ACHIEVEMENTS (Main game only)
        if ($game_type == 'main' && $level_completed > 0 && $level_completed <= 10) {
            $achievement_name = 'Completed Level ' . $level_completed;
            awardAchievementWithGift($conn, $user_id, $achievement_name, $response);
        }

        // COIN ACHIEVEMENTS - Based on total coins earned in game sessions
        $total_coins_sql = "SELECT SUM(coins_earned) as total FROM game_sessions WHERE user_id = ?";
        $stmt_coins = $conn->prepare($total_coins_sql);
        $stmt_coins->bind_param("i", $user_id);
        $stmt_coins->execute();
        $total_coins = $stmt_coins->get_result()->fetch_assoc()['total'] ?? 0;
        
        if ($total_coins >= 500) awardAchievementWithGift($conn, $user_id, 'Collected 500 Coins', $response);
        if ($total_coins >= 1000) awardAchievementWithGift($conn, $user_id, 'Collected 1000 Coins', $response);

        // BANANA ACHIEVEMENTS - Total bananas from main game only
        $total_bananas_sql = "SELECT SUM(score) as total FROM game_sessions WHERE user_id = ? AND game_type = 'main'";
        $stmt_bananas = $conn->prepare($total_bananas_sql);
        $stmt_bananas->bind_param("i", $user_id);
        $stmt_bananas->execute();
        $total_bananas = $stmt_bananas->get_result()->fetch_assoc()['total'] ?? 0;
        
        if ($total_bananas >= 10) awardAchievementWithGift($conn, $user_id, 'Collected 10 Bananas', $response);
        if ($total_bananas >= 20) awardAchievementWithGift($conn, $user_id, 'Collected 20 Bananas', $response);
        if ($total_bananas >= 35) awardAchievementWithGift($conn, $user_id, 'Banana Master', $response);
        if ($total_bananas >= 50) awardAchievementWithGift($conn, $user_id, 'Banana King', $response);

        echo json_encode($response);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to save score']);
    }
}

// Award achievement AND create gift box
function awardAchievementWithGift($conn, $user_id, $name, &$response) {
    // Get achievement ID
    $ach_sql = "SELECT id FROM achievements WHERE name = ?";
    $stmt = $conn->prepare($ach_sql);
    $stmt->bind_param("s", $name);
    $stmt->execute();
    $ach_result = $stmt->get_result();
    
    if ($ach_result->num_rows > 0) {
        $achievement_id = $ach_result->fetch_assoc()['id'];
        
        // Check if already earned
        $check_sql = "SELECT id FROM user_achievements WHERE user_id = ? AND achievement_id = ?";
        $stmt_check = $conn->prepare($check_sql);
        $stmt_check->bind_param("ii", $user_id, $achievement_id);
        $stmt_check->execute();
        
        if ($stmt_check->get_result()->num_rows == 0) {
            // Award achievement
            $insert_ach = "INSERT INTO user_achievements (user_id, achievement_id) VALUES (?, ?)";
            $stmt_insert = $conn->prepare($insert_ach);
            $stmt_insert->bind_param("ii", $user_id, $achievement_id);
            $stmt_insert->execute();
            
            // Create gift box
            $check_gift = "SELECT id FROM user_giftboxes WHERE user_id = ? AND achievement_id = ? AND claimed = FALSE";
            $stmt_gift = $conn->prepare($check_gift);
            $stmt_gift->bind_param("ii", $user_id, $achievement_id);
            $stmt_gift->execute();
            
            if ($stmt_gift->get_result()->num_rows == 0) {
                $insert_gift = "INSERT INTO user_giftboxes (user_id, achievement_id, claimed) VALUES (?, ?, FALSE)";
                $stmt_insert_gift = $conn->prepare($insert_gift);
                $stmt_insert_gift->bind_param("ii", $user_id, $achievement_id);
                $stmt_insert_gift->execute();
                $response['gift_boxes']++;
            }
            
            $response['achievements'][] = $name;
        }
    }
}
?>
