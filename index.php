<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

try {
    $db = Database::getInstance()->getConnection();
    
    //get upcoming events with registration count
    $stmt = $db->prepare("
    SELECT e.*,
    u.username as creator_name,
    (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as current_attendees  -- **ADDED status filter**
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.event_date >= CURRENT_DATE()
    ORDER BY e.event_date ASC
    LIMIT 6
    ");
    $stmt->execute();
    $upcoming_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //get featured events(events with highest registration)
    $stmt = $db->prepare("
    SELECT e.*,
    u.username as creator_name,
    (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as registration_count 
    FROM events e
    LEFT JOIN users u ON e.created_by = u.id
    LEFT JOIN attendees a ON e.id = a.event_id  -- Still need JOIN for featured events, but count from subquery
    WHERE e.event_date >= CURRENT_DATE()
    GROUP BY e.id
    ORDER BY registration_count DESC
    LIMIT 3
    ");
    $stmt->execute();
    $featured_events = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log("Index page error: " . $e->getMessage());
    $error = "An error occurred while fetching events.";
}
?>

<?php require_once 'includes/header.php'; ?>

<!-- hero section -->
<div class="jumbotron bg-light p-5 rounded-3 mb-4">
    <h1 class="display-4">Welcome to Event Management</h1>
    <p class="lead">Discover and join amazing events or create your own!</p>
    <?php if (!is_authenticated()): ?>
        <hr class="my-4">
        <p>Join our community to start participating in events.</p>
        <a class="btn btn-primary btn-lg" href="register.php" role="button">Register Now</a>
    <?php else: ?>
        <a class="btn btn-primary btn-lg" href="create_event.php" role="button">Create Event</a>
    <?php endif; ?>
</div>

<?php if (isset($error)): ?>
    <?php echo display_error($error); ?>
<?php else: ?>
    <!-- featured events section -->
    <?php if (!empty($featured_events)): ?>
        <h2 class="mb-4">Featured Events</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4 mb-5">
            <?php foreach ($featured_events as $event): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?>
                            </h6>
                            <p class="card-text">
                                <?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 150)) . '...'); ?>
                            </p>
                            <p class="card-text">
                                <small class="text-muted">
                                    Created by: <?php echo htmlspecialchars($event['creator_name']); ?>
                                </small>
                            </p>
                            <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <!-- upcoming events section -->
    <h2 class="mb-4">Upcoming Events</h2>
    <?php if (empty($upcoming_events)): ?>
        <div class="alert alert-info">No upcoming events available.</div>
    <?php else: ?>
        <div class="row row-cols-1 row-cols-md-3 g-4">
            <?php foreach ($upcoming_events as $event): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">
                                <?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?>
                            </h6>
                            <p class="card-text">
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                <strong>Available Spots:</strong> 
                                <?php echo $event['max_capacity'] - $event['current_attendees']; ?> 
                                of <?php echo $event['max_capacity']; ?>
                            </p>
                            <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                        <div class="card-footer">
                            <small class="text-muted">
                                Created by: <?php echo htmlspecialchars($event['creator_name']); ?>
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="events.php" class="btn btn-secondary">View All Events</a>
        </div>
    <?php endif; ?>
<?php endif; ?>

<?php require_once 'includes/footer.php'; ?>