<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// check user is authenticated and is admin
if (!is_authenticated() || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();

// get statistics
$stats = [
    'total_events' => 0,
    'total_users' => 0,
    'total_registrations' => 0,
    'upcoming_events' => 0
];

try {
    // get total events
    $stmt = $db->prepare("SELECT COUNT(*) FROM events");
    $stmt->execute();
    $stats['total_events'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin dashboard error - total events count: " . $e->getMessage());
}

try {
    // get total users
    $stmt = $db->prepare("SELECT COUNT(*) FROM users");
    $stmt->execute();
    $stats['total_users'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin dashboard error - total users count: " . $e->getMessage());
}

try {
    // get total registrations
    $stmt = $db->prepare("SELECT COUNT(*) FROM attendees WHERE status ='registered'");
    $stmt->execute();
    $stats['total_registrations'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin dashboard error - total registrations count: " . $e->getMessage());
}

try {
    // get upcoming events
    $stmt = $db->prepare("SELECT COUNT(*) FROM events WHERE event_date > NOW()");
    $stmt->execute();
    $stats['upcoming_events'] = $stmt->fetchColumn();
} catch (PDOException $e) {
    error_log("Admin dashboard error - upcoming events count: " . $e->getMessage());
}


require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Admin Dashboard</h2>

    <div class="row mt-4">
        <div class="col-md-3">
            <div class="card bg-primary text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Events</h5>
                    <h2 class="card-text"><?php echo $stats['total_events']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-success text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Users</h5>
                    <h2 class="card-text"><?php echo $stats['total_users']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-info text-white">
                <div class="card-body">
                    <h5 class="card-title">Total Registrations</h5>
                    <h2 class="card-text"><?php echo $stats['total_registrations']; ?></h2>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card bg-warning text-white">
                <div class="card-body">
                    <h5 class="card-title">Upcoming Events</h5>
                    <h2 class="card-text"><?php echo $stats['upcoming_events']; ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-4">
        <h3>Quick Links</h3>
        <div class="list-group">
            <a href="event_reports.php" class="list-group-item list-group-item-action">
                Download Event Reports
            </a>
            <a href="events.php" class="list-group-item list-group-item-action">
                Manage Events
            </a>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>