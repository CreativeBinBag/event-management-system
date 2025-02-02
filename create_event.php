<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

// check user is authenticated
if (!is_authenticated()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['create_event'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error = "Invalid request";
    } else {
        $title = sanitize_input($_POST['title']);
        $description = sanitize_input($_POST['description']);
        $event_date = sanitize_input($_POST['event_date']);
        $location = sanitize_input($_POST['location']);
        $max_capacity = (int)$_POST['max_capacity'];

        //validation
        if (empty($title) || empty($description) || empty($event_date) || empty($location) || empty($max_capacity)) {
            $error = "All fields are required";
        } elseif ($max_capacity < 1 || $max_capacity > 1000) {
            $error = "Maximum capacity must be between 1 and 1000";
        } elseif (strtotime($event_date) < time()) {
            $error = "Event date must be in the future";
        } else {
            try {
                $db = Database::getInstance()->getConnection();
                
                $stmt = $db->prepare("
                    INSERT INTO events (title, description, event_date, location, max_capacity, created_by)
                    VALUES (?, ?, ?, ?, ?, ?)
                ");
                
                $stmt->execute([
                    $title,
                    $description,
                    $event_date,
                    $location,
                    $max_capacity,
                    $_SESSION['user_id']
                ]);

                $event_id = $db->lastInsertId();
                $_SESSION['success_message'] = "Event created successfully!";
                redirect("event_details.php?id=" . $event_id);
            } catch (PDOException $e) {
                error_log("Event creation error: " . $e->getMessage());
                $error = "An error occurred while creating the event.";
            }
        }
    }
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h4>Create New Event</h4>
            </div>
            <div class="card-body">
                <?php if ($error) echo display_error($error); ?>
                <?php if ($success) echo display_success($success); ?>

                <form method="POST" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" class="needs-validation" novalidate>
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">

                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               maxlength="200" required
                               value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                        <div class="invalid-feedback">
                            Please provide an event title.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="4" required><?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?></textarea>
                        <div class="invalid-feedback">
                            Please provide an event description.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date and Time *</label>
                        <input type="datetime-local" class="form-control" id="event_date" 
                               name="event_date" required
                               value="<?php echo isset($_POST['event_date']) ? htmlspecialchars($_POST['event_date']) : ''; ?>">
                        <div class="invalid-feedback">
                            Please provide a valid event date and time.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location" 
                               maxlength="200" required
                               value="<?php echo isset($_POST['location']) ? htmlspecialchars($_POST['location']) : ''; ?>">
                        <div class="invalid-feedback">
                            Please provide an event location.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="max_capacity" class="form-label">Maximum Capacity *</label>
                        <input type="number" class="form-control" id="max_capacity" 
                               name="max_capacity" min="1" max="1000" required
                               value="<?php echo isset($_POST['max_capacity']) ? htmlspecialchars($_POST['max_capacity']) : ''; ?>">
                        <div class="invalid-feedback">
                            Please provide a maximum capacity between 1 and 1000.
                        </div>
                        <small class="form-text text-muted">Enter a number between 1 and 1000</small>
                    </div>

                    <div class="d-grid gap-2">
                        <button type="submit" name="create_event" class="btn btn-primary">Create Event</button>
                        <a href="events.php" class="btn btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
//set minimum date to current date
document.addEventListener('DOMContentLoaded', function() {
    const now = new Date();
    now.setMinutes(now.getMinutes() - now.getTimezoneOffset());
    document.getElementById('event_date').min = now.toISOString().slice(0,16);
});

//form validation
(function () {
    'use strict'
    var forms = document.querySelectorAll('.needs-validation')
    Array.prototype.slice.call(forms)
        .forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!form.checkValidity()) {
                    event.preventDefault()
                    event.stopPropagation()
                }
                form.classList.add('was-validated')
            }, false)
        })
})()
</script>

<?php require_once 'includes/footer.php'; ?>