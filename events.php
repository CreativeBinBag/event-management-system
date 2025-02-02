<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

//pagination settings
$items_per_page = 10;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $items_per_page;

//search and filter parameters
$search = isset($_GET['search']) ? sanitize_input($_GET['search']) : '';
$sort = isset($_GET['sort']) ? sanitize_input($_GET['sort']) : 'event_date';
$order = isset($_GET['order']) ? sanitize_input($_GET['order']) : 'ASC';

try {
    $db = Database::getInstance()->getConnection();

    //base query 
    $query = "SELECT e.*, u.username as creator_name,
              (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as current_attendees
              FROM events e
              LEFT JOIN users u ON e.created_by = u.id
              WHERE e.event_date >= CURRENT_DATE()";

    //add search condition if search term exists
    if (!empty($search)) {
        $query .= " AND (e.title LIKE :search OR e.description LIKE :search OR e.location LIKE :search)";
    }

    //sorting
    $allowed_sort_fields = ['title', 'event_date', 'location'];
    $sort = in_array($sort, $allowed_sort_fields) ? $sort : 'event_date';
    $order = strtoupper($order) === 'DESC' ? 'DESC' : 'ASC';
    $query .= " ORDER BY e.$sort $order";

    //pagination
    $query .= " LIMIT :offset, :items_per_page";

    $stmt = $db->prepare($query);

    if (!empty($search)) {
        $search_term = "%$search%";
        $stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
    }

    $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
    $stmt->bindParam(':items_per_page', $items_per_page, PDO::PARAM_INT);
    $stmt->execute();

    $events = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //get total number of events for pagination
    $count_query = "SELECT COUNT(*) FROM events WHERE event_date >= CURRENT_DATE()";
    if (!empty($search)) {
        $count_query .= " AND (title LIKE :search OR description LIKE :search OR location LIKE :search)";
        $count_stmt = $db->prepare($count_query);
        $count_stmt->bindParam(':search', $search_term, PDO::PARAM_STR);
    } else {
        $count_stmt = $db->prepare($count_query);
    }

    $count_stmt->execute();
    $total_events = $count_stmt->fetchColumn();
    $total_pages = ceil($total_events / $items_per_page);

} catch (PDOException $e) {
    error_log("Events page error: " . $e->getMessage());
    $error = "An error occurred while fetching events.";
}

//check if this is an AJAX request
$is_ajax_request = isset($_GET['ajax']);

if (!$is_ajax_request) {
    require_once 'includes/header.php'; 
}
?>

<?php if (!$is_ajax_request): //only for full page load ?>
<div class="row mb-4">
    <div class="col-md-6">
        <h2>Upcoming Events</h2>
    </div>
    <div class="col-md-6 text-end">
        <?php if (is_authenticated()): ?>
            <a href="create_event.php" class="btn btn-primary">Create Event</a>
        <?php endif; ?>
    </div>
</div>

<!-- Search and Filter Form -->
<form method="GET" class="mb-4">
    <div class="row">
        <div class="col-md-4">
            <input type="text" name="search" id="eventSearch" class="form-control" placeholder="Search events..."
                   value="<?php echo htmlspecialchars($search); ?>">
        </div>
        <div class="col-md-3">
            <select name="sort" class="form-select">
                <option value="event_date" <?php echo $sort === 'event_date' ? 'selected' : ''; ?>>Date</option>
                <option value="title" <?php echo $sort === 'title' ? 'selected' : ''; ?>>Title</option>
                <option value="location" <?php echo $sort === 'location' ? 'selected' : ''; ?>>Location</option>
            </select>
        </div>
        <div class="col-md-3">
            <select name="order" class="form-select">
                <option value="ASC" <?php echo $order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                <option value="DESC" <?php echo $order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
            </select>
        </div>
        <div class="col-md-2">
            <button type="submit" class="btn btn-secondary w-100">Apply</button>
        </div>
    </div>
</form>
<?php endif; ?>


<?php if (isset($error)): ?>
    <?php echo display_error($error); ?>
<?php else: ?>
    <?php if (empty($events)): ?>
        <div class="alert alert-info">No events found.</div>
    <?php else: ?>
       
        <div class="row row-cols-1 row-cols-md-3 g-4" id="events-list">
            <?php foreach ($events as $event): ?>
                <div class="col">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                            <p class="card-text">
                                <strong>Date:</strong> <?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?><br>
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                                <strong>Available Spots:</strong> <?php echo $event['max_capacity'] - $event['current_attendees']; ?>/<?php echo $event['max_capacity']; ?>
                            </p>
                            <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?php echo $page === $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
<?php endif; ?>

<?php if (!$is_ajax_request): ?>
<?php require_once 'includes/footer.php'; ?>
<?php endif; ?>