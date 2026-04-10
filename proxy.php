<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
$curl_error = curl_errno($ch);

if ($curl_error) {
    http_response_code(500);
    echo json_encode(['error' => 'Network Error: ' . curl_error($ch), 'code' => $curl_error]);
} elseif ($http_code !== 200) {
    http_response_code($http_code);
    echo json_encode(['error' => 'API HTTP Error: ' . $http_code]);
} else {
    // Validate JSON response
    $data = json_decode($response, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(502);
        echo json_encode(['error' => 'Invalid JSON from API: ' . json_last_error_msg()]);
    } else {
        // Ensure required fields exist
        if (empty($data['question']) || $data['solution'] === null) {
            http_response_code(502);
            echo json_encode(['error' => 'API response missing required fields']);
        } else {
            // Ensure image data is properly formatted as base64 data URL
            if (strpos($data['question'], 'data:image') === 0) {
                // Already a proper data URL
                echo json_encode($data);
            } elseif (strpos($data['question'], 'data:') === 0) {
                // Some other data URL format
                echo json_encode($data);
            } else {
                // Wrap raw base64 as data URL
                $data['question'] = 'data:image/png;base64,' . $data['question'];
                echo json_encode($data);
            }
        }
    }
}

curl_close($ch);
?>
