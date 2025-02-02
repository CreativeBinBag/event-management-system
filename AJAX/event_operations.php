<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

// only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed'])); 
}

// ensure user is authenticated
if (!is_authenticated()) {
    http_response_code(401);
    exit(json_encode(['success' => false, 'error' => 'Please login to continue'])); 
}
// validate input
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (!verify_csrf_token($csrf_token)) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid request'])); 
}

try {
    $db = Database::getInstance()->getConnection();

    // get event details to check permissions
    $stmt = $db->prepare("
        SELECT * FROM events WHERE id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$event) {
        http_response_code(404);
        exit(json_encode(['success' => false, 'error' => 'Event not found']));
    }

    // check if user has permission
    if (!is_admin() && $_SESSION['user_id'] != $event['created_by']) {
        http_response_code(403);
        exit(json_encode(['success' => false, 'error' => 'You do not have permission to modify this event'])); 
    }

    $response = ['success' => true]; 

    if ($action === 'update') {
        // validate update data
        $title = sanitize_input(trim($_POST['title'] ?? '')); 
        $description = sanitize_input(trim($_POST['description'] ?? '')); 
        $location = sanitize_input(trim($_POST['location'] ?? ''));
        $event_date = trim($_POST['event_date'] ?? '');
        $max_capacity = (int)($_POST['max_capacity'] ?? 0);

        if (empty($title) || empty($description) || empty($location) || empty($event_date) || $max_capacity <= 0) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'error' => 'All fields are required'])); 
        }

        // Checking if new capacity is less than current attendees
        $stmt = $db->prepare("SELECT COUNT(*) FROM attendees WHERE event_id = ?");
        $stmt->execute([$event_id]);
        $current_attendees = $stmt->fetchColumn();

        if ($max_capacity < $current_attendees) {
            http_response_code(400);
            exit(json_encode(['success' => false, 'error' => 'New capacity cannot be less than current attendees'])); 
        }

        $stmt = $db->prepare("
            UPDATE events 
            SET title = ?, description = ?, location = ?, event_date = ?, max_capacity = ?, 
                updated_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$title, $description, $location, $event_date, $max_capacity, $event_id]);

        $response['message'] = 'Event updated successfully';
        $response['data'] = [ 
            'event' => [
                'title' => $title,
                'description' => $description,
                'location' => $location,
                'event_date' => $event_date,
                'max_capacity' => $max_capacity
            ]
        ];

    } elseif ($action === 'delete') {
        // begin transaction
        $db->beginTransaction();

        try {
            // delete attendees first
            $stmt = $db->prepare("DELETE FROM attendees WHERE event_id = ?");
            $stmt->execute([$event_id]);

            // then delete the event
            $stmt = $db->prepare("DELETE FROM events WHERE id = ?");
            $stmt->execute([$event_id]);

            $db->commit();
            $response['message'] = 'Event deleted successfully';
            $response['data'] = ['redirect' => 'events.php']; 

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }

    echo json_encode($response);

} catch (Exception $e) {
    error_log("Event operation error: " . $e->getMessage() . ". User ID: " . ($_SESSION['user_id'] ?? 'unknown') . ", Event ID: " . $event_id); // 
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'An error occurred while processing your request']); 
}
?>