<?php
header('Content-Type: application/json');

$url = 'http://marcconrad.com/uob/banana/api.php?out=json&base64=yes';

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);

$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode(['error' => 'Proxy Error: ' . curl_error($ch)]);
} else {
    echo $response;
}

curl_close($ch);
?>
