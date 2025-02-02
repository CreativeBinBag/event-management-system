document.addEventListener('DOMContentLoaded', function() {
    // initialize tooltips (Bootstrap)
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });

    // Bootstrap Form validation (Client-side)
    const forms = document.querySelectorAll('.needs-validation');
    Array.prototype.slice.call(forms).forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // AJAX event registration
    const registerButtons = document.querySelectorAll('.register-event-btn');
    registerButtons.forEach(button => {
        button.addEventListener('click', handleRegistrationButtonClick);
    });

    // dynamic search functionality
    const searchInput = document.getElementById('eventSearch');
    if (searchInput) {
        let timeout = null;
        searchInput.addEventListener('keyup', function() {
            clearTimeout(timeout);
            timeout = setTimeout(() => {
                const searchTerm = this.value;
                updateEventsList(searchTerm);
            }, 500);
        });
    }
});

// helper Functions
function showAlert(type, message) {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.role = 'alert';
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    `;
    document.querySelector('.container').prepend(alertDiv);
    setTimeout(() => {
        alertDiv.remove();
    }, 5000);
}

function updateCapacityDisplayUI(eventId, availableSpots, totalCapacity) {
    const capacityElement = document.querySelector(`.event-capacity-${eventId}`);
    if (capacityElement) {
        capacityElement.textContent = `Available Spots: ${availableSpots} of ${totalCapacity}`;
    }
}

function updateRegistrationButtonUI(eventId, isRegistered) {
    const registrationButtonContainer = document.getElementById(`registration-button-${eventId}`);
    if (registrationButtonContainer) {
        if (isRegistered) {
            registrationButtonContainer.innerHTML = `<button type="button" class="btn btn-danger register-event-btn" data-event-id="${eventId}" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="cancel">Cancel Registration</button>`;
        } else {
            registrationButtonContainer.innerHTML = `<button type="button" class="btn btn-primary register-event-btn" data-event-id="${eventId}" data-csrf="<?php echo generate_csrf_token(); ?>" data-action="register">Register for Event</button>`;
        }
        // reattach event listener to the newly created button
        const newButton = registrationButtonContainer.querySelector('.register-event-btn');
        if (newButton) {
            newButton.addEventListener('click', handleRegistrationButtonClick);
        }
    }
}

function updateAttendeesTableUI(eventId, attendees) {

    const tableBody = document.querySelector(`.attendees-table-${eventId} tbody`); 
    const noAttendeesMsg = document.querySelector(`.attendees-card-${eventId} .card-body > p`); 

    if (tableBody) {
        if (attendees && attendees.length > 0) {
            tableBody.innerHTML = ''; 
            attendees.forEach(attendee => { 
                let row = tableBody.insertRow();
                let usernameCell = row.insertCell();
                let registrationDateCell = row.insertCell();
                let statusCell = row.insertCell();

                usernameCell.textContent = attendee.username;
                registrationDateCell.textContent = new Date(attendee.registration_date).toLocaleDateString();
                statusCell.textContent = attendee.status.charAt(0).toUpperCase() + attendee.status.slice(1);
            });

            if (noAttendeesMsg && noAttendeesMsg.textContent === 'No attendees yet.') {
                noAttendeesMsg.style.display = 'none'; 
            }
        } else {
            tableBody.innerHTML = ''; // clear table body if no attendees
            if (noAttendeesMsg) {
                noAttendeesMsg.style.display = 'block'; 
            }
        }
    } else {
        console.warn("tableBody element NOT FOUND for eventId:", eventId); // *** ADDED LOGGING - WARNING IF NOT FOUND ***
    }
}


function updateEventsList(searchTerm) {
    const params = new URLSearchParams(window.location.search);
    params.set('search', searchTerm);
    params.set('ajax', '1');

    fetch(`/events.php?${params.toString()}`)
        .then(response => response.text())
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const newEventsList = doc.querySelector('#events-list');
            const currentEventsList = document.querySelector('#events-list');

            if (newEventsList && currentEventsList) {
                currentEventsList.innerHTML = newEventsList.innerHTML;
            }

            // update URL without refreshing
            window.history.pushState({}, '', `?${params.toString()}`);
        });
}

function exportData(type) {
    window.location.href = `/event_reports.php?export=1&type=${type}`;
}


function handleRegistrationButtonClick(e) {
    e.preventDefault();
    const button = e.target;
    const eventId = button.dataset.eventId;
    const action = button.dataset.action;

    button.disabled = true;
    button.innerHTML = 'Processing...';

    const csrfToken = generateCSRFToken(); // get fresh CSRF token on each click 

    fetch('ajax/event_registration.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `event_id=${eventId}&csrf_token=${csrfToken}&action=${action}`
    })
    .then(response => response.json())
    .then(data => {
        console.log("AJAX Response Data:", data); 
        if (data.message) {
            showAlert('success', data.message);
            if (data) {
                updateRegistrationButtonUI(eventId, data.is_registered);
                updateCapacityDisplayUI(eventId, data.available_spots, data.total_capacity); 
                if (data.attendees) {
                    updateAttendeesTableUI(eventId, data.attendees);
                }
            }
        } else {
            showAlert('danger', data.error);
        }
    })
    .catch(error => {
        showAlert('danger', 'An error occurred during registration. Please try again.');
        console.error("AJAX registration error:", error);
    })
    .finally(() => {
        button.disabled = false;
        button.innerHTML = (action === 'register') ? 'Register for Event' : 'Cancel Registration';
    });
}

function generateCSRFToken() {
    return document.querySelector('#csrf_token').value;
}