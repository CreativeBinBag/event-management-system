<?php
require_once 'includes/functions.php'; 
require_once 'config/database.php';   
require_once 'includes/header.php';   

//get and sanitize search term 
$search_term = isset($_GET['q']) ? sanitize_input(trim($_GET['q'])) : '';
$results = [];

if ($search_term) {
    try {
        $db = Database::getInstance()->getConnection(); 
        $search_param = "%{$search_term}%"; //prepare search parameter for LIKE queries

        // Search events prepared statement
        $stmt = $db->prepare("
        SELECT
            e.*,
            u.username as creator_name,
            (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as attendee_count, -- **CHANGED to subquery with status filter**
            'event' as type
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        LEFT JOIN attendees a ON e.id = a.event_id  -- Still need JOIN for search conditions
        WHERE e.title LIKE ?
           OR e.description LIKE ?
           OR e.location LIKE ?
        GROUP BY e.id
        ORDER BY e.event_date DESC
    ");
        $stmt->execute([$search_param, $search_param, $search_param]); // Execute event search query
        $events = $stmt->fetchAll(PDO::FETCH_ASSOC); // Fetch event results

        // Search attendees across all events - prepared statement for attendee search
        $stmt = $db->prepare("
           SELECT DISTINCT
                u.username,
                u.email,
                GROUP_CONCAT(e.title) as registered_events,
                COUNT(DISTINCT e.id) as event_count,
                MAX(a.registration_date) as latest_registration,
                'attendee' as type
            FROM users u
            JOIN attendees a ON u.id = a.user_id
            JOIN events e ON a.event_id = e.id
            WHERE (u.username LIKE ?
            OR u.email LIKE ?
            OR e.title LIKE ?)
            AND a.status = 'registered'  -- ADDED STATUS FILTER HERE
            GROUP BY u.id
            ORDER BY u.username
        ");
        $stmt->execute([$search_param, $search_param, $search_param]); 
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC); 

        $results = [ //combine results
            'events' => $events,
            'attendees' => $attendees
        ];
    } catch (PDOException $e) {
        error_log("Search error: " . $e->getMessage()); 
    }
}
?>

<div class="container mt-4">
    <h2>Search Events and Attendees</h2>

    <form method="GET" class="mb-4">
        <div class="input-group">
            <input type="text"
                   name="q"
                   class="form-control"
                   placeholder="Search by event title, description, location, or attendee name"
                   value="<?php echo htmlspecialchars($search_term); ?>">
            <button type="submit" class="btn btn-primary">Search</button>
        </div>
    </form>

    <?php if ($search_term): ?>
        <?php if (empty($results['events']) && empty($results['attendees'])): ?>
            <div class="alert alert-info">No results found.</div> <?php ?>
        <?php else: ?>
            <!-- Events Results Section -->
            <?php if (!empty($results['events'])): ?>
                <h3>Events (<?php echo count($results['events']); ?>)</h3>
                <div class="row">
                    <?php foreach ($results['events'] as $event): ?>
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="event_details.php?id=<?php echo $event['id']; ?>">
                                            <?php echo htmlspecialchars($event['title']); ?>
                                        </a>
                                    </h5>
                                    <p class="card-text">
                                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?><br>
                                        <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                        <strong>Created by:</strong> <?php echo htmlspecialchars($event['creator_name']); ?><br>
                                        <strong>Attendees:</strong> <?php echo $event['attendee_count']; ?>
                                    </p>
                                    <p class="card-text text-muted">
                                        <?php
                                        $desc = htmlspecialchars($event['description']); 
                                        echo strlen($desc) > 100 ? substr($desc, 0, 100) . '...' : $desc; 
                                        ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Attendees Results Section -->
            <?php if (!empty($results['attendees'])): ?>
                <h3 class="mt-4">Attendees (<?php echo count($results['attendees']); ?>)</h3>
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Registered Events</th>
                            <th>Total Events</th>
                            <th>Latest Registration</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($results['attendees'] as $attendee): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($attendee['username']); ?></td>
                                <td><?php echo htmlspecialchars($attendee['email']); ?></td>
                                <td>
                                    <?php
                                    $events = explode(',', $attendee['registered_events']); //explode registered events string
                                    $events = array_map('htmlspecialchars', $events); 
                                    echo implode(', ', array_slice($events, 0, 3)); //display max 3 event names
                                    if (count($events) > 3) echo '...'; //indicate if more events are registered
                                    ?>
                                </td>
                                <td><?php echo $attendee['event_count']; ?></td>
                                <td><?php echo date('Y-m-d', strtotime($attendee['latest_registration'])); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>