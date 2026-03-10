<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch User Stats
$stats_sql = "SELECT 
    SUM(score) as total_score, 
    SUM(coins_earned) as total_coins, 
    COUNT(*) as games_played,
    SUM(time_spent) as total_time
    FROM game_sessions WHERE user_id = ?";
$stmt = $conn->prepare($stats_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$stats = $stmt->get_result()->fetch_assoc();

// Fetch History
$history_sql = "SELECT game_type, score, coins_earned, time_spent, played_at 
                FROM game_sessions WHERE user_id = ? 
                ORDER BY played_at DESC LIMIT 10";
$stmt = $conn->prepare($history_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$history = $stmt->get_result();

// Fetch Achievements
$ach_sql = "SELECT a.name, a.description, ua.earned_at 
            FROM achievements a
            LEFT JOIN user_achievements ua ON a.id = ua.achievement_id AND ua.user_id = ?
            ORDER BY ua.earned_at DESC";
$stmt = $conn->prepare($ach_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$achievements = $stmt->get_result();

// Fetch Top 10 Leaderboard
$leaderboard_sql = "SELECT u.username, SUM(gs.score) as total_score 
                    FROM users u
                    JOIN game_sessions gs ON u.id = gs.user_id
                    GROUP BY u.id
                    ORDER BY total_score DESC LIMIT 10";
$leaderboard_result = $conn->query($leaderboard_sql);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Banana Jungle Escape</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
    <div class="dashboard-container">
        <!-- Sidebar/Nav -->
        <nav class="sidebar">
            <div class="logo">🍌 Jungle Dash</div>
            <ul class="nav-links">
                <li class="active"><a href="#">Summary</a></li>
                <li><a href="index.html">Play Game</a></li>
                <li><a href="minigame.html">Mini-Game</a></li>
                <li><a href="logout.php">Logout</a></li>
            </ul>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <span><?php echo htmlspecialchars($username); ?></span>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="content">
            <header>
                <h1>Welcome back, <span><?php echo htmlspecialchars($username); ?></span>!</h1>
                <p>Track your progress and climb the leaderboard.</p>
            </header>

            <!-- Stats Grid -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="icon">🏆</div>
                    <div class="info">
                        <h3>Total Score</h3>
                        <p><?php echo number_format($stats['total_score'] ?? 0); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon">🪙</div>
                    <div class="info">
                        <h3>Total Coins</h3>
                        <p><?php echo number_format($stats['total_coins'] ?? 0); ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon">🎮</div>
                    <div class="info">
                        <h3>Games Played</h3>
                        <p><?php echo $stats['games_played'] ?? 0; ?></p>
                    </div>
                </div>
                <div class="stat-card">
                    <div class="icon">⏱️</div>
                    <div class="info">
                        <h3>Time Spent</h3>
                        <p><?php 
                            $time = $stats['total_time'] ?? 0;
                            echo floor($time / 60) . "m " . ($time % 60) . "s";
                        ?></p>
                    </div>
                </div>
            </div>

            <div class="main-grid">
                <!-- Recent History -->
                <section class="card history-section">
                    <h2>Recent History</h2>
                    <table>
                        <thead>
                            <tr>
                                <th>Game</th>
                                <th>Score</th>
                                <th>Coins</th>
                                <th>Time</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($row = $history->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo ucfirst($row['game_type']); ?></td>
                                <td><?php echo $row['score']; ?></td>
                                <td><?php echo $row['coins_earned']; ?> 🪙</td>
                                <td><?php echo $row['time_spent']; ?>s</td>
                                <td><?php echo date('M d, H:i', strtotime($row['played_at'])); ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </section>

                <!-- Leaderboard -->
                <aside class="card leaderboard-section">
                    <h2>Global Top 10</h2>
                    <ul class="leaderboard-list">
                        <?php 
                        $rank = 1;
                        while($row = $leaderboard_result->fetch_assoc()): 
                        ?>
                        <li class="<?php echo ($row['username'] == $username) ? 'current-user' : ''; ?>">
                            <span class="rank"><?php echo $rank++; ?></span>
                            <span class="name"><?php echo htmlspecialchars($row['username']); ?></span>
                            <span class="score"><?php echo number_format($row['total_score']); ?></span>
                        </li>
                        <?php endwhile; ?>
                    </ul>
                </aside>

                <!-- Achievements -->
                <section class="card achievements-section">
                    <h2>Achievements</h2>
                    <div class="achievements-grid">
                        <?php while($row = $achievements->fetch_assoc()): ?>
                        <div class="achievement-item <?php echo $row['earned_at'] ? 'earned' : 'locked'; ?>">
                            <div class="ach-icon"><?php echo $row['earned_at'] ? '🌟' : '🔒'; ?></div>
                            <div class="ach-info">
                                <h4><?php echo htmlspecialchars($row['name']); ?></h4>
                                <p><?php echo htmlspecialchars($row['description']); ?></p>
                            </div>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </section>
            </div>
        </main>
    </div>
</body>
</html>
