<?php
require_once '../includes/functions.php';
require_once '../config/database.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['error' => 'Method not allowed']));
}

// ensure user is authenticated
if (!is_authenticated()) {
    http_response_code(401);
    exit(json_encode(['error' => 'Please login to continue']));
}

// validate input
$event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';
$csrf_token = isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '';

if (!verify_csrf_token($csrf_token)) {
    http_response_code(400);
    exit(json_encode(['error' => 'Invalid request']));
}

try {
    $db = Database::getInstance()->getConnection();

    // get current event info
    $eventStmt = $db->prepare("
        SELECT e.*, u.username as creator_name,
        (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as current_attendees
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $eventStmt->execute([$event_id]);
    $event_result = $eventStmt->fetch(PDO::FETCH_ASSOC);

    if (!$event_result) {
        http_response_code(404);
        exit(json_encode(['error' => 'Event not found']));
    }

    $response = [];

    if ($action === 'register') {
        if ($event_result['current_attendees'] >= $event_result['max_capacity']) {
            http_response_code(400);
            exit(json_encode(['error' => 'Sorry, this event is already at full capacity.']));
        }

        $stmt = $db->prepare("
            INSERT INTO attendees (event_id, user_id, status, registration_date)
            VALUES (?, ?, 'registered', CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$event_id, $_SESSION['user_id']]);

        $response['message'] = 'Successfully registered for the event!';
        $response['is_registered'] = true;

    } elseif ($action === 'cancel') {
        $stmt = $db->prepare("
            UPDATE attendees
            SET status = 'cancelled'
            WHERE event_id = ? AND user_id = ?
        ");
        $stmt->execute([$event_id, $_SESSION['user_id']]);

        $response['message'] = 'Registration cancelled successfully.';
        $response['is_registered'] = false;
    }

    // re-fetch updated event info and attendees list using separate statements
    $eventStmtUpdated = $db->prepare("
        SELECT e.*, u.username as creator_name,
        (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as current_attendees
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $eventStmtUpdated->execute([$event_id]);
    $event_result_updated = $eventStmtUpdated->fetch(PDO::FETCH_ASSOC);

    $attendeesStmt = $db->prepare("
        SELECT u.username, a.registration_date, a.status
        FROM attendees a
        JOIN users u ON a.user_id = u.id
        WHERE a.event_id = ? AND a.status = 'registered'  -- ENSURE `status = 'registered'` IS HERE
        ORDER BY a.registration_date ASC
    ");
    $attendeesStmt->execute([$event_id]);
    $attendees_result = $attendeesStmt->fetchAll(PDO::FETCH_ASSOC);

    $response['available_spots'] = $event_result_updated['max_capacity'] - $event_result_updated['current_attendees'];
    $response['total_capacity'] = $event_result_updated['max_capacity'];
    $response['attendees'] = $attendees_result;

    error_log("AJAX Response Data: " . json_encode($response));

    echo json_encode($response);

} catch (PDOException $e) {
    error_log("Registration error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => 'An error occurred while processing your request']);
}
?>