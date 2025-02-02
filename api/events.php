<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

$db = Database::getInstance()->getConnection();

// get event ID from query parameter
$event_id = isset($_GET['id']) ? (int)$_GET['id'] : null;

// Validate event_id 
if ($event_id !== null && (!is_int($event_id) || $event_id <= 0)) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid event ID']);
    exit;
}

try {
    if ($event_id) {
        // get specific event details 
        $stmt = $db->prepare("
            SELECT e.*, u.username as creator_name,
                   COUNT(a.id) as attendee_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN attendees a ON e.id = a.event_id
            WHERE e.id = ?
            GROUP BY e.id
        ");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$event) {
            http_response_code(404);
            echo json_encode(['error' => 'Event not found']);
            exit;
        }

        echo json_encode($event);

    } else {
        // Get all events
        $stmt = $db->prepare(" 
            SELECT e.*, u.username as creator_name,
                   COUNT(a.id) as attendee_count
            FROM events e
            LEFT JOIN users u ON e.created_by = u.id
            LEFT JOIN attendees a ON e.id = a.event_id
            GROUP BY e.id
            ORDER BY e.event_date DESC
        ");
        $stmt->execute(); // execute prepared statement
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode($events);
    }

} catch (PDOException $e) {
    error_log("API events error: " . $e->getMessage()); 
    http_response_code(500);
    echo json_encode(['error' => 'Database error']); 
}
?>