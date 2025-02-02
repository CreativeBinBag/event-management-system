<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!is_authenticated() || !isset($_SESSION['is_admin']) || $_SESSION['is_admin'] != 1) {
    redirect('login.php');
}

$db = Database::getInstance()->getConnection();

//get all events for reports
$stmt = $db->prepare("
    SELECT e.*,
           (SELECT COUNT(a.id) FROM attendees a WHERE a.event_id = e.id AND a.status = 'registered') as registrant_count, -- Modified COUNT to filter by status
           e.max_capacity - (SELECT COUNT(a.id) FROM attendees a WHERE a.event_id = e.id AND a.status = 'registered') as available_spots -- Modified COUNT to filter by status
    FROM events e
    LEFT JOIN attendees a ON e.id = a.event_id -- Keep the LEFT JOIN for cases with no attendees to show all events
    GROUP BY e.id
    ORDER BY e.event_date DESC
");
$stmt->execute();
$events = $stmt->fetchAll(PDO::FETCH_ASSOC);

//handle CSV export
if (isset($_GET['export']) && isset($_GET['event_id'])) {
    $event_id = (int)$_GET['event_id'];

    try {
        //get event details for CSV filename
        $stmt = $db->prepare("SELECT title FROM events WHERE id = ?");
        $stmt->execute([$event_id]);
        $event = $stmt->fetch(PDO::FETCH_ASSOC);

        //get attendees for CSV export
        $stmt = $db->prepare("
        SELECT u.username, u.email, a.registration_date
        FROM attendees a
        JOIN users u ON a.user_id = u.id
        WHERE a.event_id = ?
        AND a.status = 'registered'  -- ADDED STATUS FILTER HERE
        ORDER BY a.registration_date
    ");
        $stmt->execute([$event_id]);
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC);

        //generate CSV
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $event['title'] . '_attendees.csv"');

        $output = fopen('php://output', 'w');
        fputcsv($output, ['Username', 'Email', 'Registration Date']);

        foreach ($attendees as $attendee) {
            fputcsv($output, $attendee);
        }

        fclose($output);
        exit();

    } catch (PDOException $e) {
        error_log("CSV export error: " . $e->getMessage());
        echo "Error generating CSV file."; 
        exit(); //stop further page rendering after error
    }
}

require_once 'includes/header.php';
?>

<div class="container mt-4">
    <h2>Event Reports</h2>

    <div class="table-responsive">
        <table class="table table-striped">
            <thead>
            <tr>
                <th>Event Title</th>
                <th>Date</th>
                <th>Location</th>
                <th>Registrants</th>
                <th>Available Spots</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($events as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']);?></td>
                    <td><?php echo date('Y-m-d H:i', strtotime($event['event_date'])); ?></td>
                    <td><?php echo htmlspecialchars($event['location']);?></td>
                    <td><?php echo $event['registrant_count']; ?></td>
                    <td><?php echo $event['available_spots']; ?></td>
                    <td>
                        <a href="event_reports.php?export=1&event_id=<?php echo $event['id']; ?>"
                           class="btn btn-sm btn-primary">
                            Export Attendees (CSV)  
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>