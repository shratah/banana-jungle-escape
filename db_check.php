<?php
include 'db.php';

echo "<h2>Database Connectivity & Schema Check</h2>";

if ($conn->connect_error) {
    die("<p style='color:red'>Connection failed: " . $conn->connect_error . "</p>");
}
echo "<p style='color:green'>Database connected successfully!</p>";

$tables = ['users', 'game_sessions', 'achievements', 'user_achievements', 'user_giftboxes'];

foreach ($tables as $table) {
    echo "<h3>Table: $table</h3>";
    $result = $conn->query("DESCRIBE $table");
    if (!$result) {
        echo "<p style='color:red'>Error describing $table: " . $conn->error . "</p>";
    } else {
        echo "<table border='1'><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            foreach ($row as $val) echo "<td>$val</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
}

echo "<h3>Testing Dashboard Query</h3>";
$test_user_id = 1; // Assuming user 1 exists
$sql = "SELECT current_coins, current_lives, theme, language FROM users LIMIT 1";
$res = $conn->query($sql);
if (!$res) {
    echo "<p style='color:red'>Dashboard query failed: " . $conn->error . "</p>";
} else {
    echo "<p style='color:green'>Dashboard query succeeded!</p>";
}
?>
