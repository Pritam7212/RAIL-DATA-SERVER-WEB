<?php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $pointConfigPath = __DIR__ . '/UHMU/pointconfig.json';
    
    if (file_exists($pointConfigPath)) {
        $pointConfigData = file_get_contents($pointConfigPath);
        $jsonData = json_decode($pointConfigData);
        
        if ($jsonData !== null) {
            echo $pointConfigData;
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Invalid data found for locations']);
        }
    } else {
        http_response_code(404);
        echo json_encode(['error' => 'No locations found']);
    }
} else {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Only GET requests are supported.']);
}
?>
