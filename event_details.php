<?php
require_once 'includes/functions.php';
require_once 'config/database.php';

if (!isset($_GET['id'])) {
    redirect('events.php'); // redirect if event ID is not provided
}

$event_id = (int)$_GET['id']; 
$error = '';
$success = '';

try {
    $db = Database::getInstance()->getConnection(); 

    // fetch event details, creator username, and attendee count
    $stmt = $db->prepare("
        SELECT e.*, u.username as creator_name,
        (SELECT COUNT(*) FROM attendees WHERE event_id = e.id AND status = 'registered') as current_attendees  -- Filter by 'registered' status for count!
        FROM events e
        LEFT JOIN users u ON e.created_by = u.id
        WHERE e.id = ?
    ");
    $stmt->execute([$event_id]);
    $event = $stmt->fetch(PDO::FETCH_ASSOC); // fetch event details

    if (!$event) {
        redirect('events.php'); 
    }
    error_log("EVENT DETAILS (Initial Load): " . print_r($event, true)); // *** ADDED LOGGING ***


    //check if current user is registered for the event
    $is_registered = false;
    if (is_authenticated()) {
        $stmt = $db->prepare("
            SELECT id FROM attendees
            WHERE event_id = ? AND user_id = ? AND status = 'registered' -- Filter by 'registered' status!
        ");
        $stmt->execute([$event_id, $_SESSION['user_id']]);
        $is_registered = $stmt->rowCount() > 0; //check if user is registered
    }
    error_log("IS_REGISTERED (Initial Load): " . ($is_registered ? 'true' : 'false')); // *** ADDED LOGGING ***


    //check if user is authorized to view attendees list (admin or event creator)
    $show_attendees = false;
    $attendees = [];
    if (is_authenticated() && (is_admin() || $_SESSION['user_id'] == $event['created_by'])) {
        $show_attendees = true; //show attendees list if authorized
        $stmt = $db->prepare("
          SELECT u.username, u.email, a.registration_date, a.status
          FROM attendees a
          JOIN users u ON a.user_id = u.id
          WHERE a.event_id = ? AND a.status = 'registered' -- **CRUCIAL: Filter by 'registered' status for initial load!**
          ORDER BY a.registration_date ASC
      ");
        $stmt->execute([$event_id]);
        $attendees = $stmt->fetchAll(PDO::FETCH_ASSOC); //fetch the attendees list
    }
    error_log("ATTENDEES LIST (Initial Load): " . print_r($attendees, true)); // *** ADDED LOGGING ***


} catch (PDOException $e) {
    error_log("Event details error: " . $e->getMessage()); 
    $error = "An error occurred while fetching event details."; 
}
?>

<?php require_once 'includes/header.php'; ?>

<div class="container">
    <?php if ($error) echo display_error($error);  ?>
    <?php if ($success) echo display_success($success);  ?>

    <?php if (isset($event)): ?>
        <div class="row">
            <div class="col-md-8">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h2><?php echo htmlspecialchars($event['title']); ?></h2>
                    <?php if (is_authenticated() && ($_SESSION['user_id'] == $event['created_by'] || is_admin())): ?>
                        <div class="btn-group">
                            <button type="button" class="btn btn-outline-primary" onclick="showEditForm(<?php echo $event_id; ?>)">
                                Edit Event
                            </button>
                            <button type="button" class="btn btn-outline-danger" onclick="confirmDelete(<?php echo $event_id; ?>)">
                                Delete Event
                            </button>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Event Details</h5>
                        <p class="card-text">
                            <strong>Date and Time:</strong> <?php echo date('F j, Y, g:i a', strtotime($event['event_date'])); ?><br>
                            <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                            <strong>Created by:</strong> <?php echo htmlspecialchars($event['creator_name']); ?><br>
                            <strong class="event-capacity-<?php echo $event_id; ?>">Available Spots:</strong> <?php echo $event['max_capacity'] - $event['current_attendees']; ?>
                            of <?php echo $event['max_capacity']; ?>
                        </p>
                        <div class="description">
                            <h6>Description:</h6>
                            <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                        </div>

                        <?php if (is_authenticated()): ?>
                            <div id="registration-area-<?php echo $event_id; ?>" class="mt-3"> 
                                <input type="hidden" id="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                <div id="registration-button-<?php echo $event_id; ?>">
                                    <?php if (!$is_registered): ?>
                                        <?php if ($event['current_attendees'] < $event['max_capacity']): ?>
                                            <button type="button" class="btn btn-primary register-event-btn" data-event-id="<?php echo $event_id; ?>" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="register">
                                                Register for Event
                                            </button>
                                        <?php else: ?>
                                            <button type="button" class="btn btn-secondary" disabled>Event Full</button>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <button type="button" class="btn btn-danger register-event-btn" data-event-id="<?php echo $event_id; ?>" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="cancel">
                                            Cancel Registration
                                        </button>
                                    <?php endif; ?>
                                </div>
                                <div id="registration-message" class="mt-2"></div>
                            </div>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary">Login to Register</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if ($show_attendees): ?>
                <div class="col-md-4">
                    <div class="card attendees-card-<?php echo $event_id; ?>">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Attendees</h5>
                            <?php if (is_admin()): ?>
                                <a href="event_reports.php?export=1&event_id=<?php echo $event_id; ?>"
                                   class="btn btn-sm btn-success">Export CSV</a>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (empty($attendees)): ?>
                                <p>No attendees yet.</p>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-sm attendees-table-<?php echo $event_id; ?>">
                                        <thead>
                                        <tr>
                                            <th>Username</th>
                                            <th>Registration Date</th>
                                            <th>Status</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php foreach ($attendees as $attendee): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($attendee['username']);?></td>
                                                <td><?php echo date('Y-m-d', strtotime($attendee['registration_date'])); ?></td>
                                                <td><?php echo ucfirst(htmlspecialchars($attendee['status'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="modal fade" id="editEventModal" tabindex="-1" aria-labelledby="editEventModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editEventModalLabel">Edit Event</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
             <form id="editEventForm">
             <div class="modal-body">

                    <div class="mb-3">
                        <label for="title" class="form-label">Event Title *</label>
                        <input type="text" class="form-control" id="title" name="title"
                               maxlength="200" required>
                        <div class="invalid-feedback">
                            Please provide an event title.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control" id="description" name="description"
                                  rows="4" required></textarea>
                        <div class="invalid-feedback">
                            Please provide an event description.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="event_date" class="form-label">Event Date and Time *</label>
                        <input type="datetime-local" class="form-control" id="event_date"
                               name="event_date" required>
                        <div class="invalid-feedback">
                            Please provide a valid event date and time.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="location" class="form-label">Location *</label>
                        <input type="text" class="form-control" id="location" name="location"
                               maxlength="200" required>
                        <div class="invalid-feedback">
                            Please provide an event location.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="max_capacity" class="form-label">Maximum Capacity *</label>
                        <input type="number" class="form-control" id="max_capacity"
                               name="max_capacity" min="1" max="1000" required>
                        <div class="invalid-feedback">
                            Please provide a maximum capacity between 1 and 1000.
                        </div>
                        <small class="form-text text-muted">Enter a number between 1 and 1000</small>
                    </div>

            </div>
            <div class="modal-footer">
               <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
               <button type="button" class="btn btn-primary" onclick="updateEvent()">Save Changes</button>
            </div>
           </form>
        </div>
    </div>
</div>

<script>
function handleRegistration(eventId, action) {
    const csrfToken = document.getElementById('csrf_token').value;
    const button = document.querySelector(`button[onclick*="${action}"]`);
    const originalText = button.innerHTML;

    button.disabled = true;
    button.innerHTML = 'Processing...';

    const formData = new FormData();
    formData.append('csrf_token', csrfToken);
    formData.append('event_id', eventId);
    formData.append('action', action);

    fetch('ajax/event_registration.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.error) {
            throw new Error(data.error);
        }

        //update registration button
        const buttonHtml = data.is_registered
            ? `<button type="button" class="btn btn-danger register-event-btn" data-event-id="${eventId}" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="cancel">Cancel Registration</button>`
            : `<button type="button" class="btn btn-primary register-event-btn" data-event-id="${eventId}" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="register">Register for Event</button>`;
        document.getElementById(`registration-button-${eventId}`).innerHTML = buttonHtml; //updated to use ID and eventId

       
        const cardText = document.querySelector(`.event-capacity-${eventId}`); //updated selector to use class and eventId
         cardText.textContent = `Available Spots: ${data.available_spots} of ${data.total_capacity}`; //access data from data.data

         //update attendees table if present and new data is available
           if (data.attendees) { 
            const tableBody = document.querySelector(`.attendees-table-${eventId} tbody`); 
            if (tableBody) {
                if (data.attendees.length > 0) { 
                     updateAttendeesTableUI(eventId, data.attendees);
                 }  else {
                      updateAttendeesTableUI(eventId, []); //pass empty array to clear table
                }
             }
         }
     
        document.getElementById('registration-message').innerHTML =
            `<div class="alert alert-success">${data.message}</div>`;
    })
    .catch(error => {
        document.getElementById('registration-message').innerHTML =
            `<div class="alert alert-danger">${error.message}</div>`;
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = (action === 'register') ? 'Register for Event' : 'Cancel Registration';
    });
}
const modalHtml = ` ... `;

//append modal to body
document.body.insertAdjacentHTML('beforeend', modalHtml);

//initialize modal
let editEventModal;
document.addEventListener('DOMContentLoaded', function() {
    editEventModal = new bootstrap.Modal(document.getElementById('editEventModal'));
});

function showEditForm(eventId) {
    // populate form with current values
    document.getElementById('title').value = document.querySelector('h2').textContent;
    document.getElementById('description').value = document.querySelector('.description p').textContent;
    document.getElementById('location').value = document.querySelector('.card-text').innerHTML.split('<br>')[1].split(':')[1].trim();

    const dateText = document.querySelector('.card-text').innerHTML.split('<br>')[0].split(':')[1].trim();
    const date = new Date(dateText);
    const formattedDate = date.toISOString().slice(0, 16);
    document.getElementById('event_date').value = formattedDate;

    //get max capacity from available spots text
    const spotsText = document.querySelector('.card-text').innerHTML.split('<br>')[3];
    const totalCapacity = spotsText.match(/of (\d+)/)[1];
    document.getElementById('max_capacity').value = totalCapacity;

    editEventModal.show();
}

 function updateEvent() {
        //update event function
        const eventId = new URLSearchParams(window.location.search).get('id');
        const form = document.getElementById('editEventForm');
        const formData = new FormData(form);

        formData.append('event_id', eventId);
        formData.append('action', 'update');
         formData.append('csrf_token', document.getElementById('csrf_token').value);

        fetch('ajax/event_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }

            //update page content
             document.querySelector('h2').textContent = data.data.event.title;
            document.querySelector('.description p').textContent = data.data.event.description;

            const cardText = document.querySelector('.card-text');
            const lines = cardText.innerHTML.split('<br>');
             lines[0] = `<strong>Date and Time:</strong> ${new Date(data.data.event.event_date).toLocaleString()}`;
            lines[1] = `<strong>Location:</strong> ${data.data.event.location}`;
            cardText.innerHTML = lines.join('<br>');

            //close modal
            editEventModal.hide();

            document.getElementById('registration-message').innerHTML =
                `<div class="alert alert-success">${data.message}</div>`;
        })
         .catch(error => {
            document.getElementById('registration-message').innerHTML =
                `<div class="alert alert-danger">${error.message}</div>`;
        });
    }

function confirmDelete(eventId) {
    //confirm delete function
    if (confirm('Are you sure you want to delete this event? This action cannot be undone.')) {
        const formData = new FormData();
        formData.append('event_id', eventId);
        formData.append('action', 'delete');
        formData.append('csrf_token', document.getElementById('csrf_token').value);

        fetch('ajax/event_operations.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.error) {
                throw new Error(data.error);
            }
            window.location.href = data.data.redirect;
        })
        .catch(error => {
            document.getElementById('registration-message').innerHTML =
                `<div class="alert alert-danger">${error.message}</div>`;
        });
    }
}
</script>

<?php require_once 'includes/footer.php'; ?>