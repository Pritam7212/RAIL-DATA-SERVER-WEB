<?php
function respond($data, $status = 200) {
    http_response_code($status);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Main script
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Fetch URL parameters
    $type = $_GET['type'] ?? null;
    $loc = $_GET['loc'] ?? null;
    $id = $_GET['id'] ?? null;
    $n = $_GET['n'] ?? null;

    // Validate required parameters
    if (!$type || !$loc) {
        respond(["error" => "Missing required parameters: type and loc"], 400);
    }
    if ($type === "full" && !$id) {
        respond(["error" => "Missing required parameter: id for type=full"], 400);
    }
    if ($type === "first_n" && (!$id || !$n || !is_numeric($n))) {
        respond(["error" => "Missing or invalid parameters: id and n required for type=first_n"], 400);
    }

    // Database path
    $dbPath = __DIR__ . "/UHMU/data/$loc.db";
    if (!file_exists($dbPath)) {
        respond(["error" => "Database for location $loc does not exist"], 404);
    }

    try {
        $db = new PDO("sqlite:$dbPath");
    } catch (PDOException $e) {
        respond(["error" => "Failed to connect to database"], 500);
    }

    if ($type === "full") {
        // Return full data for the given id
        $query = "SELECT * FROM $id";
    } elseif ($type === "first_n") {
        // Return the latest n values for the given id
        $query = "SELECT * FROM $id ORDER BY DateTime DESC LIMIT :n";
    } else {
        respond(["error" => "Invalid type parameter"], 400);
    }

    try {
        $stmt = $db->prepare($query);
        if ($type === "first_n") {
            $stmt->bindValue(':n', (int)$n, PDO::PARAM_INT);
        }
        $stmt->execute();
        $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$result) {
            respond(["error" => "No data found for the given query"], 404);
        }
        respond($result);
    } catch (PDOException $e) {
        respond(["error" => "Failed to execute query: " . $e->getMessage()], 500);
    }
}
?>
