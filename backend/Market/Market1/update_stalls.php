<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Accept");

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

require_once "db_market.php";

try {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // GET: fetch map and its stalls
        if (!isset($_GET['map_id'])) throw new Exception("map_id is required");
        $mapId = (int)$_GET['map_id'];

        // Fetch map info
        $stmt = $pdo->prepare("SELECT * FROM maps WHERE id = ?");
        $stmt->execute([$mapId]);
        $map = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$map) throw new Exception("Map not found");

        // Fetch stalls
        $stmtStalls = $pdo->prepare("SELECT * FROM stalls WHERE map_id = ?");
        $stmtStalls->execute([$mapId]);
        $stalls = $stmtStalls->fetchAll(PDO::FETCH_ASSOC);

        // Fix image path - images are in revenue/uploads/
        if (!empty($map['image_path'])) {
            $imagePath = $map['image_path'];
            
            // Remove any leading slashes or incorrect paths
            $imagePath = ltrim($imagePath, '/');
            
            // If it's already a full URL, keep it
            if (preg_match('/^https?:\/\//', $imagePath)) {
                // Already a full URL, do nothing
            } 
            // If it contains uploads folder path, reconstruct properly
            else if (strpos($imagePath, 'uploads/') !== false) {
                // Extract just the filename after uploads/
                $parts = explode('uploads/', $imagePath);
                $filename = end($parts);
                $map['image_path'] = "http://localhost/revenue/uploads/" . $filename;
            }
            // If it's just a filename, add the uploads path
            else {
                $map['image_path'] = "http://localhost/revenue/uploads/" . $imagePath;
            }
        }

        echo json_encode([
            'status' => 'success',
            'map' => $map,
            'stalls' => $stalls
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // POST: update stalls
        $data = json_decode(file_get_contents("php://input"), true);
        if (!$data) throw new Exception("No data provided");

        if (!isset($data['map_id']) || !isset($data['stalls'])) {
            throw new Exception("map_id and stalls are required");
        }

        $mapId = (int)$data['map_id'];
        $stalls = $data['stalls'];

        if (!is_array($stalls)) {
            throw new Exception("Stalls must be an array");
        }

        $results = [
            'updated' => 0,
            'inserted' => 0,
            'errors' => []
        ];

        foreach ($stalls as $index => $stall) {
            try {
                // Validate required fields
                if (!isset($stall['name']) || !isset($stall['pos_x']) || !isset($stall['pos_y'])) {
                    $results['errors'][] = "Stall at index $index missing required fields";
                    continue;
                }

                if (isset($stall['id']) && $stall['id']) {
                    // Update existing stall
                    $stmt = $pdo->prepare("
                        UPDATE stalls SET
                            name = ?, pos_x = ?, pos_y = ?, status = ?, 
                            price = ?, height = ?, length = ?, width = ?
                        WHERE id = ? AND map_id = ?
                    ");
                    $stmt->execute([
                        $stall['name'] ?? 'Unnamed',
                        floatval($stall['pos_x']),
                        floatval($stall['pos_y']),
                        $stall['status'] ?? 'available',
                        isset($stall['price']) ? floatval($stall['price']) : null,
                        isset($stall['height']) ? floatval($stall['height']) : null,
                        isset($stall['length']) ? floatval($stall['length']) : null,
                        isset($stall['width']) ? floatval($stall['width']) : null,
                        $stall['id'],
                        $mapId
                    ]);
                    
                    if ($stmt->rowCount() > 0) {
                        $results['updated']++;
                    }
                } else {
                    // Insert new stall
                    $stmt = $pdo->prepare("
                        INSERT INTO stalls (map_id, name, pos_x, pos_y, status, price, height, length, width)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                    ");
                    $stmt->execute([
                        $mapId,
                        $stall['name'] ?? 'Unnamed',
                        floatval($stall['pos_x']),
                        floatval($stall['pos_y']),
                        $stall['status'] ?? 'available',
                        isset($stall['price']) ? floatval($stall['price']) : null,
                        isset($stall['height']) ? floatval($stall['height']) : null,
                        isset($stall['length']) ? floatval($stall['length']) : null,
                        isset($stall['width']) ? floatval($stall['width']) : null
                    ]);
                    $results['inserted']++;
                }
            } catch (Exception $e) {
                $results['errors'][] = "Stall {$stall['name']}: " . $e->getMessage();
            }
        }

        $message = "Stalls updated successfully: " . $results['inserted'] . " inserted, " . $results['updated'] . " updated";
        if (!empty($results['errors'])) {
            $message .= ". Errors: " . implode(", ", $results['errors']);
        }

        echo json_encode([
            'status' => 'success',
            'message' => $message,
            'results' => $results
        ]);
        exit;
    }

    throw new Exception("Unsupported request method: " . $_SERVER['REQUEST_METHOD']);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}