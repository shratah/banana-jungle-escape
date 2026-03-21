<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.html");
    exit();
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];

// Fetch User Preferences & Balance
$user_sql = "SELECT current_coins, current_lives, theme, language FROM users WHERE id = ?";
$stmt = $conn->prepare($user_sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$user_data = $stmt->get_result()->fetch_assoc();

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
    <script src="js/lang.js"></script>
</head>
<body class="theme-<?php echo $user_data['theme']; ?>">
    <div class="dashboard-container">
        <!-- Sidebar/Nav -->
        <nav class="sidebar">
            <div class="logo">🍌 Jungle Dash</div>
            <ul class="nav-links">
                <li class="active"><a href="#" data-t="summary">Summary</a></li>
                <li><a href="index.html" data-t="play_game">Play Game</a></li>
                <li><a href="minigame.html" data-t="minigame">Mini-Game</a></li>
                <li><a href="logout.php" data-t="logout">Logout</a></li>
            </ul>
            <div class="user-info">
                <div class="avatar"><?php echo strtoupper(substr($username, 0, 1)); ?></div>
                <span data-t="welcome">Welcome</span>, <span><?php echo htmlspecialchars($username); ?></span>
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
                        <h3 data-t="total_coins">Available Coins</h3>
                        <p id="currentCoinsDisplay"><?php echo number_format($user_data['current_coins'] ?? 0); ?></p>
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

                <!-- Shop Section -->
                <section class="card shop-section">
                    <h2>🛒 Jungle Shop</h2>
                    <div class="shop-grid">
                        <div class="shop-item">
                            <div class="icon">🧲</div>
                            <h4>Shield Magnet</h4>
                            <p class="description">Auto-collect coins!</p>
                            <p class="price">200 🪙</p>
                            <button class="buy-btn" onclick="buyItem('powerup', 'magnet')">Buy Power-up</button>
                        </div>
                        <div class="shop-item">
                            <div class="icon">⏸️</div>
                            <h4>Time Freeze</h4>
                            <p class="description">Stop falling items!</p>
                            <p class="price">200 🪙</p>
                            <button class="buy-btn" onclick="buyItem('powerup', 'freeze')">Buy Power-up</button>
                        </div>
                        <div class="shop-item">
                            <div class="icon">🌈</div>
                            <h4>Rainbow Mode</h4>
                            <p class="description">Double coin value!</p>
                            <p class="price">200 🪙</p>
                            <button class="buy-btn" onclick="buyItem('powerup', 'rainbow')">Buy Power-up</button>
                        </div>
                        <div class="shop-item">
                            <div class="icon">🍀</div>
                            <h4>Lucky Charm</h4>
                            <p class="description">Rare item boost!</p>
                            <p class="price">200 🪙</p>
                            <button class="buy-btn" onclick="buyItem('powerup', 'lucky')">Buy Power-up</button>
                        </div>
                        <div class="shop-item">
                            <div class="icon">💎</div>
                            <h4>Perfectionist</h4>
                            <p class="description">Buy this Achievement</p>
                            <p class="price">500 🪙</p>
                            <button class="buy-btn" onclick="buyItem('achievement', 'Perfectionist')">Unlock Now</button>
                        </div>
                    </div>
                </section>

                <!-- Settings Section -->
                <section class="card settings-section">
                    <h2 data-t="settings">Settings & Customization</h2>
                    
                    <div class="settings-group">
                        <label data-t="theme_select">Choose Theme</label>
                        <div class="theme-grid">
                            <button class="theme-opt forest" onclick="updateSetting('theme', 'forest')">Forest</button>
                            <button class="theme-opt sea" onclick="updateSetting('theme', 'sea')">Sea</button>
                            <button class="theme-opt banana" onclick="updateSetting('theme', 'banana')">Banana</button>
                            <button class="theme-opt night" onclick="updateSetting('theme', 'night')">Night</button>
                        </div>
                    </div>

                    <div class="settings-group" style="margin-top: 2rem;">
                        <label data-t="lang_select">Language</label>
                        <div class="lang-grid">
                            <button class="lang-opt" onclick="updateSetting('language', 'en')">English</button>
                            <button class="lang-opt" onclick="updateSetting('language', 'ta')">தமிழ் (Tamil)</button>
                        </div>
                    </div>
                </section>
            </div>
        </main>
    </div>

    <script>
    const currentLang = "<?php echo $user_data['language']; ?>";
    applyLanguage(currentLang);

    function updateSetting(type, value) {
        const formData = new FormData();
        formData.append(type, value);

        fetch('sync_stats.php', {
            method: 'POST',
            body: formData
        })
        .then(() => {
            if (type === 'theme') {
                document.body.className = 'theme-' + value;
            } else if (type === 'language') {
                applyLanguage(value);
            }
            showToast("Preference saved! ✨", "success");
        })
        .catch(err => console.error("Error updating setting:", err));
    }

    function showToast(msg, type) {
        // Simple alert for now, can be improved
        alert(msg);
    }
    function buyItem(category, name) {
        const formData = new FormData();
        formData.append('action', category === 'powerup' ? 'buy_powerup' : 'buy_achievement');
        formData.append(category === 'powerup' ? 'type' : 'name', name);

        fetch('shop_actions.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                alert(`Successfully purchased ${name}!`);
                document.getElementById('currentCoinsDisplay').innerText = data.new_coins.toLocaleString();
                if (category === 'achievement') location.reload(); // Refresh to show unlocked achievement
            } else {
                alert("Error: " + data.message);
            }
        })
        .catch(err => console.error("Error:", err));
    }
    </script>
</body>
</html>
