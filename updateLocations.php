<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

$configFile = __DIR__ . '/UHMU/pointconfig.json';

// Function to read config
function readConfig($file) {
    if (!file_exists($file)) {
        return [];
    }
    $content = file_get_contents($file);
    return json_decode($content, true) ?: [];
}

// Function to write config
function writeConfig($file, $data) {
    // Check if file is writable
    if (file_exists($file) && !is_writable($file)) {
        throw new Exception('Config file is not writable. File permissions: ' . substr(sprintf('%o', fileperms($file)), -4));
    }
    
    // // Check if directory is writable
    // $dir = dirname($file);
    // if (!is_writable($dir)) {
    //     throw new Exception('Config directory is not writable. Directory: ' . $dir);
    // }
    
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
    $result = file_put_contents($file, $json);
    
    if ($result === false) {
        throw new Exception('Failed to write to file. Check file permissions and disk space.');
    }
    
    return $result;
}

// Function to add or update a location
function updateLocation($config, $locationKey, $key = null, $alias = null, $clients = null) {
    // If key is not provided, use the location key as alias
    if ($key === null || $key === '') {
        $key = $locationKey;
    }

    // If alias is not provided, use the location key as alias
    if ($alias === null || $alias === '') {
        $alias = $locationKey;
    }
    
    // If clients is not provided, initialize as empty object
    if ($clients === null) {
        $clients = new stdClass();
    }
    
    // Create or update the location entry
    $config[$locationKey] = [
        'KEY' => $key,
        'CLIENTS' => $clients,
        'ALIAS' => $alias
    ];
    
    return $config;
}

// Main execution
try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Only POST method is allowed');
    }
    
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON input');
    }
    
    $action = $data['action'] ?? '';
    
    switch ($action) {
        case 'addLocation':
        case 'updateLocation':
            $locationKey = $data['locationKey'] ?? '';
            
            if (empty($locationKey)) {
                throw new Exception('locationKey is required');
            }
            
            // Validate location key format (alphanumeric and underscores only)
            if (!preg_match('/^[A-Za-z0-9_]+$/', $locationKey)) {
                throw new Exception('locationKey must contain only alphanumeric characters and underscores');
            }
            
            // Read current config
            $config = readConfig($configFile);
            
            // Check if location exists (for update operation)
            $isUpdate = isset($config[$locationKey]);

            // Handle key
            if (isset($data['key'])) {
                $key = $data['key'];
            } elseif ($isUpdate && isset($config[$locationKey]['KEY'])) {
                $key = $config[$locationKey]['KEY'];
            } else {
                // Default to location key for new locations
                $key = $locationKey;
            }
            
            // Handle alias
            if (isset($data['alias'])) {
                $alias = $data['alias'];
            } elseif ($isUpdate && isset($config[$locationKey]['ALIAS'])) {
                // Retain existing alias if not provided
                $alias = $config[$locationKey]['ALIAS'];
            } else {
                // Default to location key for new locations
                $alias = $locationKey;
            }
            
            // Handle clients
            if (isset($data['clients'])) {
                $clients = $data['clients'];
            } elseif ($isUpdate && isset($config[$locationKey]['CLIENTS'])) {
                // Retain existing clients if not provided
                $clients = $config[$locationKey]['CLIENTS'];
            } else {
                // Default to empty object for new locations
                $clients = new stdClass();
            }
            
            // Update location
            $config = updateLocation($config, $locationKey, $key, $alias, $clients);
            
            // Write back to file
            writeConfig($configFile, $config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Location ' . ($isUpdate ? 'updated' : 'added') . ' successfully',
                'location' => $config[$locationKey]
            ]);
            break;
            
        case 'deleteLocation':
            $locationKey = $data['locationKey'] ?? '';
            
            if (empty($locationKey)) {
                throw new Exception('locationKey is required');
            }
            
            $config = readConfig($configFile);
            
            if (!isset($config[$locationKey])) {
                throw new Exception('Location not found');
            }
            
            // Delete the associated database file if it exists
            $dbPath = __DIR__ . "/UHMU/data/$locationKey.db";
            if (file_exists($dbPath)) {
                unlink($dbPath);
            }
            
            unset($config[$locationKey]);
            
            writeConfig($configFile, $config);
            
            echo json_encode([
                'success' => true,
                'message' => 'Location deleted successfully'
            ]);
            break;
            
        default:
            throw new Exception('Invalid action. Supported actions: addLocation, updateLocation, deleteLocation');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
