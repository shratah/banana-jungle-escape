<?php
// Test API endpoint - helps debug what the API is returning
header('Content-Type: text/html; charset=utf-8');

echo "<h1>API Test</h1>";
echo "<p>Testing connection to: http://marcconrad.com/uob/banana/api.php?out=json&base64=yes</p>";

$url = 'http://marcconrad.com/uob/banana/api.php?out=json&base64=yes';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 15);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
    'Accept: application/json'
]);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curl_error = curl_error($ch);

echo "<h2>Connection Status:</h2>";
echo "<p>HTTP Status: <strong>" . ($curl_error ? "Error: $curl_error" : $http_code) . "</strong></p>";

if ($curl_error) {
    echo "<p style='color: red;'>❌ CURL Error: $curl_error</p>";
} elseif ($http_code === 200) {
    echo "<p style='color: green;'>✅ Connected successfully (HTTP 200)</p>";
    
    $data = json_decode($response, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        echo "<p style='color: green;'>✅ Valid JSON received</p>";
        
        echo "<h2>Response Data:</h2>";
        echo "<ul>";
        echo "<li><strong>Has 'question' field:</strong> " . (isset($data['question']) ? "✅ Yes" : "❌ No") . "</li>";
        echo "<li><strong>Has 'solution' field:</strong> " . (isset($data['solution']) ? "✅ Yes" : "❌ No") . "</li>";
        
        if (isset($data['question'])) {
            $q_preview = substr($data['question'], 0, 100);
            echo "<li><strong>Question starts with:</strong> " . htmlspecialchars($q_preview) . "...</li>";
            if (strpos($data['question'], 'data:image') === 0) {
                echo "<li style='color: green;'>✅ Question is valid data URL (data:image format)</li>";
            } else {
                echo "<li style='color: orange;'>⚠️ Question is NOT a data URL, wrapping will be needed</li>";
            }
        }
        
        if (isset($data['solution'])) {
            echo "<li><strong>Solution:</strong> " . intval($data['solution']) . "</li>";
        }
        echo "</ul>";
        
        echo "<h2>Preview:</h2>";
        if (isset($data['question'])) {
            echo "<img src='" . htmlspecialchars($data['question']) . "' style='max-width: 300px; border: 1px solid #ccc;' alt='Puzzle Image'>";
        }
    } else {
        echo "<p style='color: red;'>❌ Invalid JSON: " . json_last_error_msg() . "</p>";
        echo "<p>Response preview: " . htmlspecialchars(substr($response, 0, 500)) . "</p>";
    }
} else {
    echo "<p style='color: red;'>❌ HTTP Error: " . $http_code . "</p>";
    echo "<p>Response: " . htmlspecialchars(substr($response, 0, 500)) . "</p>";
}

curl_close($ch);

echo "<hr>";
echo "<p><a href='test_api.php'>Refresh Test</a> | <a href='index.html'>Back to Game</a></p>";
?>
