<?php
// Function to calculate checksum (SHA-256)
function calculateChecksum($input) {
    return hash('sha256', $input); // SHA-256 hash
}

// Validate and parse the input data
function parseData($data) {
    // Split the data using # as a delimiter
    $parts = explode('#', $data);

    // Ensure the data has at least 4 parts (location, id, payload, checksum)
    if (count($parts) < 4) {
        throw new Exception("Invalid data format. Missing sections.");
    }

    // Extract the components
    $parsedLoc = $parts[1];
    $parsedId = $parts[2];
    $parsedPayload = $parts[3];
    $providedChecksum = $parts[4];

    // Validate the ID (must start with 'U' or 'S')
    if (empty($parsedId) || !in_array($parsedId[0], ['U', 'S'])) {
        throw new Exception("Invalid ID format. Table name must start with 'U' or 'S'.");
    }

    // Prepare the string for checksum validation (excluding the checksum itself)
    $checksumString = "#{$parsedLoc}#{$parsedId}#{$parsedPayload}#";
    $calculatedChecksum = calculateChecksum($checksumString);
    
    // Check if the provided checksum matches the calculated checksum
    if ($calculatedChecksum !== $providedChecksum) {
        throw new Exception("Checksum validation failed.");
    }

    return [$parsedLoc, $parsedId, $parsedPayload];
}

// Database creation and table handling using SQLite3
function handleDatabase($loc, $id, $type, $payload) {
    // Define the database directory and path
    $dbDir = __DIR__ . "/UHMU/data";
    $dbPath = "{$dbDir}/{$loc}.db";

    if (!is_dir($dbDir)) {
        if (!mkdir($dbDir, 0755, true)) {
            throw new Exception("Failed to create database directory.");
        }
    }

    // Check if the database file exists, if not, create it
    if (!file_exists($dbPath)) {
        // Create a new SQLite3 database file
        $db = new SQLite3($dbPath);
    } else {
        // Connect to the existing database
        $db = new SQLite3($dbPath);
    }

    // Determine table name and structure
    if (str_starts_with($id, 'U')) {
        // Define columns for 'U' as HEX or string
        $columns = "DateTime TEXT, CPU1 TEXT, CPU2 TEXT, CPU3 TEXT";  // Using TEXT for HEX or string
    } elseif (str_starts_with($id, 'S')) {
        // Define columns for 'S' as integer
        $valueColumns = implode(", ", array_map(fn($i) => "Value$i INTEGER", range(1, 41)));  // Using INTEGER for 'S'
        $columns = "DateTime TEXT, $valueColumns";
    } else {
        throw new Exception("Invalid table ID format.");
    }

    // Create table if it doesn't exist
    $createTableSQL = "CREATE TABLE IF NOT EXISTS $id ($columns)";
    if ($db->exec($createTableSQL)) {
        echo "Table created or already exists.\n";
    } else {
        throw new Exception("Failed to create table.");
    }

    // Parse payload and validate data structure
    $dataParts = explode(",", $payload);
    $dateTime = $dataParts[0];
    $values = array_slice($dataParts, 1);
    if (str_starts_with($id, 'U') && count($values) !== 3) {
        throw new Exception("Invalid data format for U type.");
    }
    if (str_starts_with($id, 'S') && count($values) !== 41) {
        throw new Exception("Invalid data format for S type.");
    }

    // Insert new data into the table
    $placeholders = implode(", ", array_fill(0, count($values) + 1, "?"));
    $insertSQL = "INSERT INTO $id VALUES ($placeholders)";
    $stmt = $db->prepare($insertSQL);
    $stmt->bindValue(1, $dateTime, SQLITE3_TEXT);

    // Bind the remaining values
    for ($i = 0; $i < count($values); $i++) {
        if (str_starts_with($id, 'U')) {
            // For 'U' type, bind the value as a string (e.g., hex or regular string)
            $stmt->bindValue($i + 2, $values[$i], SQLITE3_TEXT); // Bind values as TEXT
        } elseif (str_starts_with($id, 'S')) {
            // For 'S' type, bind the value as integer
            $stmt->bindValue($i + 2, (int)$values[$i], SQLITE3_INTEGER); // Bind values as INTEGER
        }
    }

    if ($stmt->execute()) {
        echo "Data inserted successfully.\n";
    } else {
        throw new Exception("Failed to insert data.");
    }

    return "Data added successfully.";
}

// Main script logic
try {
    // Receive and validate data from POST request
    $rawData = file_get_contents('php://input'); // Assume data is sent via POST
    if (empty($rawData)) {
        throw new Exception("No data received.");
    }

    [$parsedLoc, $parsedId, $parsedPayload] = parseData($rawData);

    // Handle database and insert data
    $result = handleDatabase($parsedLoc, $parsedId, 'U', $parsedPayload); // Assuming type 'U' for now
    echo $result;
} catch (Exception $e) {
    http_response_code(400);
    echo "Error: " . $e->getMessage() . "\n";
}
?>
