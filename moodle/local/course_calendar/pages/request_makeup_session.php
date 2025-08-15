<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/request_makeup_session.php'));
$PAGE->set_title('Request Makeup Session');
$PAGE->set_heading('Request Makeup Session');

$userid = $USER->id;

global $DB;

// Get original absence ID from URL
$original_absence_id = optional_param('absence_id', 0, PARAM_INT);

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $original_absence_id = required_param('original_absence_id', PARAM_INT);
    $room_id = required_param('room_id', PARAM_INT);
    $makeup_start_datetime = required_param('makeup_start_datetime', PARAM_TEXT);
    $makeup_end_datetime = required_param('makeup_end_datetime', PARAM_TEXT);
    $reason = required_param('reason', PARAM_TEXT);
    
    // Convert datetime-local to timestamps
    $requested_begin_time = strtotime(str_replace('T', ' ', $makeup_start_datetime));
    $requested_end_time = strtotime(str_replace('T', ' ', $makeup_end_datetime));
    
    // Validation
    $errors = [];
    
    // Check if requested time is in the future
    if ($requested_begin_time <= time()) {
        $errors[] = 'Makeup session must be scheduled for future time.';
    }
    
    // Check if end time is after start time
    if ($requested_end_time <= $requested_begin_time) {
        $errors[] = 'End time must be after start time.';
    }
    
    // Get original absence details for validation
    $original_absence = $DB->get_record_sql("
        SELECT ar.*, cs.*, c.id as course_id, c.fullname as course_name
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        WHERE ar.id = :absence_id AND ar.request_type = 0 AND ar.status = 1", 
        ['absence_id' => $original_absence_id]);
    
    if (!$original_absence) {
        $errors[] = 'Original absence request not found or not approved.';
    }
    
    // Check for room conflicts
    if (empty($errors)) {
        $room_conflict = check_room_availability($room_id, $requested_begin_time, $requested_end_time);
        if ($room_conflict) {
            $errors[] = 'Room is not available at the requested time. Conflict with: ' . $room_conflict;
        }
    }
    
    // Check for teacher conflicts
    if (empty($errors)) {
        $teacher_conflict = check_teacher_availability($userid, $requested_begin_time, $requested_end_time);
        if ($teacher_conflict) {
            $errors[] = 'You have a schedule conflict at the requested time: ' . $teacher_conflict;
        }
    }
    
    // Check if already requested makeup for this absence
    if (empty($errors)) {
        $existing_request = $DB->get_record('local_course_calendar_absence_request', [
            'original_absence_id' => $original_absence_id,
            'teacher_id' => $userid,
            'request_type' => 1,  // Makeup request
            'status' => 0  // Pending
        ]);
        
        if ($existing_request) {
            $errors[] = 'You already have a pending makeup request for this cancelled session.';
        }
    }
    
    if (empty($errors)) {
        // Create makeup request
        $record = new stdClass();
        $record->original_absence_id = $original_absence_id;
        $record->teacher_id = $userid;
        $record->course_section_id = $original_absence->course_section_id;
        $record->makeup_room_id = $room_id;
        $record->makeup_date = strtotime(date('Y-m-d', $requested_begin_time)); // Convert to timestamp
        $record->makeup_start_time = $requested_begin_time;
        $record->makeup_end_time = $requested_end_time;
        $record->reason = $reason;
        $record->request_type = 1; // Makeup request
        $record->status = 0; // Pending
        $record->createdtime = time();
        $record->modifiedtime = time(); // Add modifiedtime field
        
        try {
            $DB->insert_record('local_course_calendar_absence_request', $record);
            redirect(new moodle_url('/local/course_calendar/pages/my_makeup_requests.php'),
                    'Makeup session request submitted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            $errors[] = 'Database error: ' . $e->getMessage();
        }
    }
    
    if (!empty($errors)) {
        $errormsg = implode('<br>', $errors);
    }
}

// Get original absence details if absence_id provided
$original_absence = null;
if ($original_absence_id > 0) {
    $original_absence = $DB->get_record_sql("
        SELECT ar.*, cs.*, c.id as course_id, c.fullname as course_name,
               cs.class_begin_time, cs.class_end_time
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        WHERE ar.id = :absence_id AND ar.request_type = 0 AND ar.status = 1", 
        ['absence_id' => $original_absence_id]);
}

// Get available rooms
$rooms = $DB->get_records('local_course_calendar_course_room', null, 'room_building, room_floor, room_number');

/**
 * Check if room is available at given time
 */
function check_room_availability($room_id, $start_time, $end_time) {
    global $DB;
    
    // Check existing course sections
    $conflicts = $DB->get_records_sql("
        SELECT cs.id, c.fullname, cs.class_begin_time, cs.class_end_time
        FROM {local_course_calendar_course_section} cs
        JOIN {course} c ON c.id = cs.courseid
        WHERE cs.course_room_id = :room_id 
        AND cs.visible = 1 
        AND cs.is_cancel = 0
        AND cs.class_begin_time < :end_time
        AND cs.class_end_time > :start_time",
        [
            'room_id' => $room_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($conflicts)) {
        $conflict = reset($conflicts);
        return $conflict->fullname . ' (' . date('Y-m-d H:i', $conflict->class_begin_time) . 
               ' - ' . date('H:i', $conflict->class_end_time) . ')';
    }
    
    // Check approved makeup sessions
    $makeup_conflicts = $DB->get_records_sql("
        SELECT ar.id, c.fullname, ar.makeup_start_time, ar.makeup_end_time
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        WHERE ar.request_type = 1 AND ar.makeup_room_id = :room_id 
        AND ar.status = 1
        AND ar.makeup_start_time < :end_time
        AND ar.makeup_end_time > :start_time",
        [
            'room_id' => $room_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($makeup_conflicts)) {
        $conflict = reset($makeup_conflicts);
        return $conflict->fullname . ' (Makeup - ' . date('Y-m-d H:i', $conflict->makeup_start_time) . 
               ' - ' . date('H:i', $conflict->makeup_end_time) . ')';
    }
    
    return false;
}

/**
 * Check if teacher is available at given time
 */
function check_teacher_availability($teacher_id, $start_time, $end_time) {
    global $DB;
    
    // Check existing course sections where teacher is enrolled
    $conflicts = $DB->get_records_sql("
        SELECT cs.id, c.fullname, cs.class_begin_time, cs.class_end_time
        FROM {local_course_calendar_course_section} cs
        JOIN {course} c ON c.id = cs.courseid
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        WHERE ue.userid = :teacher_id 
        AND cs.visible = 1 
        AND cs.is_cancel = 0
        AND cs.class_begin_time < :end_time
        AND cs.class_end_time > :start_time",
        [
            'teacher_id' => $teacher_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ]);
    
    if (!empty($conflicts)) {
        $conflict = reset($conflicts);
        return $conflict->fullname . ' (' . date('Y-m-d H:i', $conflict->class_begin_time) . 
               ' - ' . date('H:i', $conflict->class_end_time) . ')';
    }
    
    return false;
}

echo $OUTPUT->header();

// Display error if any
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

// Back button
echo '<div class="mb-3">';
echo '<a href="' . new moodle_url('/local/course_calendar/pages/my_absence_requests.php') . '" class="btn btn-secondary">← Back to My Requests</a>';
echo '</div>';

if (!$original_absence) {
    echo $OUTPUT->notification('Please select an approved absence request to request makeup.', 'error');
    echo $OUTPUT->footer();
    exit;
}

// Display original absence info
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Original Absence Request</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<p><strong>Course:</strong> ' . s($original_absence->course_name) . '</p>';
echo '<p><strong>Original Session Date/Time:</strong> ' . date('Y-m-d H:i', $original_absence->class_begin_time) . 
     ' - ' . date('H:i', $original_absence->class_end_time) . '</p>';
echo '<p><strong>Absence Reason:</strong> ' . s($original_absence->reason) . '</p>';
echo '</div>';
echo '</div>';

// Room Availability Checker Card
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0"><i class="fa fa-search"></i> Room Availability Checker</h5>';
echo '<small class="text-muted">Check room schedules and availability (for information only)</small>';
echo '</div>';
echo '<div class="card-body">';

echo '<div class="row">';
echo '<div class="col-md-6">';
echo '<label for="check_start_datetime">Start Date & Time</label>';
echo '<input type="datetime-local" id="check_start_datetime" class="form-control mb-3">';
echo '</div>';
echo '<div class="col-md-6">';
echo '<label for="check_end_datetime">End Date & Time</label>';
echo '<input type="datetime-local" id="check_end_datetime" class="form-control mb-3">';
echo '</div>';
echo '</div>';

echo '<button type="button" id="check-availability-btn" class="btn btn-info" onclick="checkRoomAvailability()" disabled>';
echo '<i class="fa fa-search"></i> Check Room Availability';
echo '</button>';
echo '<small class="form-text text-muted ml-2" id="check-help-text">Select start and end date/time first</small>';

echo '<div id="availability-results" style="display: none;" class="mt-4">';
echo '<div id="availability-loading" style="display: none;">';
echo '<i class="fa fa-spinner fa-spin"></i> Checking room availability...';
echo '</div>';
echo '<div id="availability-list"></div>';
echo '</div>';

echo '</div>';
echo '</div>';

// Makeup request form
echo '<div class="card">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Request Makeup Session</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<form method="post" action="" class="mform" id="makeup-form">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
echo '<input type="hidden" name="original_absence_id" value="' . $original_absence_id . '">';

// Start datetime
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break" for="makeup_start_datetime">Start Date & Time <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<input type="datetime-local" name="makeup_start_datetime" id="makeup_start_datetime" class="form-control" required min="' . date('Y-m-d\TH:i', strtotime('+1 hour')) . '">';
echo '</div>';
echo '</div>';

// End datetime
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break" for="makeup_end_datetime">End Date & Time <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<input type="datetime-local" name="makeup_end_datetime" id="makeup_end_datetime" class="form-control" required>';
echo '</div>';
echo '</div>';

// Find available rooms button
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"></div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<button type="button" id="find-rooms-btn" class="btn btn-primary" onclick="findAvailableRooms()" disabled>';
echo '<i class="fa fa-search"></i> Find Available Rooms';
echo '</button>';
echo '<small class="form-text text-muted ml-2" id="find-help-text">Select start and end date/time first</small>';
echo '</div>';
echo '</div>';

// Room selection section
echo '<div class="form-group row fitem" id="room-selection-section" style="display: none;">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break">Select Room <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<div id="room-finding-loading" style="display: none;">';
echo '<i class="fa fa-spinner fa-spin"></i> Finding available rooms...';
echo '</div>';
echo '<div id="available-rooms-list"></div>';
echo '<input type="hidden" name="room_id" id="selected_makeup_room_id" required>';
echo '</div>';
echo '</div>';

// Reason
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break" for="reason">Reason for Makeup <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<textarea name="reason" id="reason" class="form-control w-100" rows="6" required placeholder="Explain why this makeup session is needed and any additional details..." style="min-height: 120px; resize: vertical; width: 100% !important;"></textarea>';
echo '</div>';
echo '</div>';

// Submit buttons
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"></div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<button type="submit" class="btn btn-success" id="submit-makeup-btn" disabled>Submit Makeup Request</button>';
echo ' <a href="' . new moodle_url('/local/course_calendar/pages/my_absence_requests.php') . '" class="btn btn-secondary ml-2">Cancel</a>';
echo '</div>';
echo '</div>';

echo '</form>';

// Enhanced JavaScript for new design
echo '<script>
// Room Availability Checker Functions
function updateCheckButton() {
    var startDateTime = document.getElementById("check_start_datetime").value;
    var endDateTime = document.getElementById("check_end_datetime").value;
    var checkBtn = document.getElementById("check-availability-btn");
    var helpText = document.getElementById("check-help-text");
    
    if (startDateTime && endDateTime && new Date(startDateTime) < new Date(endDateTime)) {
        checkBtn.disabled = false;
        helpText.innerHTML = "Click to check room availability";
        helpText.className = "form-text text-info ml-2";
    } else {
        checkBtn.disabled = true;
        if (!startDateTime || !endDateTime) {
            helpText.innerHTML = "Select start and end date/time first";
        } else {
            helpText.innerHTML = "End time must be after start time";
        }
        helpText.className = "form-text text-muted ml-2";
    }
}

function checkRoomAvailability() {
    var startDateTime = document.getElementById("check_start_datetime").value;
    var endDateTime = document.getElementById("check_end_datetime").value;
    
    if (startDateTime && endDateTime) {
        var startTimestamp = new Date(startDateTime).getTime() / 1000;
        var endTimestamp = new Date(endDateTime).getTime() / 1000;
        
        // Show loading
        document.getElementById("availability-results").style.display = "block";
        document.getElementById("availability-loading").style.display = "block";
        document.getElementById("availability-list").innerHTML = "";
        
        // Make AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "' . new moodle_url('/local/course_calendar/ajax/get_available_rooms.php') . '", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById("availability-loading").style.display = "none";
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        displayAvailabilityResults(response.rooms, startDateTime, endDateTime);
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        document.getElementById("availability-list").innerHTML = "<div class=\\"alert alert-danger\\">Error checking availability</div>";
                    }
                } else {
                    document.getElementById("availability-list").innerHTML = "<div class=\\"alert alert-danger\\">Error checking availability</div>";
                }
            }
        };
        
        var params = "sesskey=' . sesskey() . '&start_time=" + startTimestamp + "&end_time=" + endTimestamp + "&mode=range";
        xhr.send(params);
    }
}

function displayAvailabilityResults(rooms, startDateTime, endDateTime) {
    var resultDiv = document.getElementById("availability-list");
    
    if (rooms.length === 0) {
        resultDiv.innerHTML = "<div class=\\"alert alert-warning\\">No rooms found</div>";
        return;
    }
    
    var startDate = new Date(startDateTime);
    var endDate = new Date(endDateTime);
    
    var html = "<div class=\\"alert alert-info\\"><strong>Room availability from " + 
               startDate.toLocaleString() + " to " + endDate.toLocaleString() + ":</strong></div>";
    
    rooms.forEach(function(room) {
        html += "<div class=\\"card mb-2\\">";
        html += "<div class=\\"card-header\\"><strong>" + room.room_building + " - Floor " + room.room_floor + " - Room " + room.room_number + "</strong>";
        if (room.capacity) {
            html += " <small class=\\"text-muted\\">(Capacity: " + room.capacity + ")</small>";
        }
        html += "</div>";
        html += "<div class=\\"card-body\\">";
        
        if (room.available_slots && room.available_slots.available_slots.length > 0) {
            html += "<div class=\\"text-success\\"><strong>✓ Available slots:</strong></div>";
            room.available_slots.available_slots.forEach(function(slot) {
                var slotStart = new Date(slot.start * 1000);
                var slotEnd = new Date(slot.end * 1000);
                html += "<div class=\\"small text-success\\">• " + slotStart.toLocaleString() + " - " + slotEnd.toLocaleString() + " (" + slot.duration_hours + "h)</div>";
            });
        } else {
            html += "<div class=\\"text-warning\\"><strong>⚠ No available time in this period</strong></div>";
        }
        
        if (room.available_slots && room.available_slots.conflicts.length > 0) {
            html += "<div class=\\"text-danger mt-2\\"><strong>✗ Occupied by:</strong></div>";
            room.available_slots.conflicts.forEach(function(conflict) {
                var conflictStart = new Date(conflict.start * 1000);
                var conflictEnd = new Date(conflict.end * 1000);
                html += "<div class=\\"small text-danger\\">• " + conflictStart.toLocaleString() + " - " + conflictEnd.toLocaleString() + ": " + conflict.name + "</div>";
            });
        }
        
        html += "</div>";
        html += "</div>";
    });
    
    resultDiv.innerHTML = html;
}

// Makeup Request Form Functions
function updateFindButton() {
    var startDateTime = document.getElementById("makeup_start_datetime").value;
    var endDateTime = document.getElementById("makeup_end_datetime").value;
    var findBtn = document.getElementById("find-rooms-btn");
    var helpText = document.getElementById("find-help-text");
    
    if (startDateTime && endDateTime && new Date(startDateTime) < new Date(endDateTime)) {
        findBtn.disabled = false;
        helpText.innerHTML = "Click to find available rooms";
        helpText.className = "form-text text-info ml-2";
    } else {
        findBtn.disabled = true;
        if (!startDateTime || !endDateTime) {
            helpText.innerHTML = "Select start and end date/time first";
        } else {
            helpText.innerHTML = "End time must be after start time";
        }
        helpText.className = "form-text text-muted ml-2";
    }
    
    // Hide room selection if time changed
    document.getElementById("room-selection-section").style.display = "none";
    document.getElementById("selected_makeup_room_id").value = "";
    updateSubmitButton();
}

function findAvailableRooms() {
    var startDateTime = document.getElementById("makeup_start_datetime").value;
    var endDateTime = document.getElementById("makeup_end_datetime").value;
    
    if (startDateTime && endDateTime) {
        var startTimestamp = new Date(startDateTime).getTime() / 1000;
        var endTimestamp = new Date(endDateTime).getTime() / 1000;
        
        // Show loading
        document.getElementById("room-selection-section").style.display = "block";
        document.getElementById("room-finding-loading").style.display = "block";
        document.getElementById("available-rooms-list").innerHTML = "";
        
        // Disable find button
        var findBtn = document.getElementById("find-rooms-btn");
        findBtn.disabled = true;
        findBtn.innerHTML = \'<i class="fa fa-spinner fa-spin"></i> Searching...\';
        
        // Make AJAX request
        var xhr = new XMLHttpRequest();
        xhr.open("POST", "' . new moodle_url('/local/course_calendar/ajax/get_available_rooms.php') . '", true);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        
        xhr.onreadystatechange = function() {
            if (xhr.readyState === 4) {
                document.getElementById("room-finding-loading").style.display = "none";
                
                // Reset find button
                findBtn.disabled = false;
                findBtn.innerHTML = \'<i class="fa fa-search"></i> Find Available Rooms\';
                
                if (xhr.status === 200) {
                    try {
                        var response = JSON.parse(xhr.responseText);
                        displayMakeupRoomResults(response.rooms, startDateTime, endDateTime);
                    } catch (e) {
                        console.error("Error parsing response:", e);
                        document.getElementById("available-rooms-list").innerHTML = "<div class=\\"alert alert-danger\\">Error finding rooms</div>";
                    }
                } else {
                    document.getElementById("available-rooms-list").innerHTML = "<div class=\\"alert alert-danger\\">Error finding rooms</div>";
                }
            }
        };
        
        var params = "sesskey=' . sesskey() . '&start_time=" + startTimestamp + "&end_time=" + endTimestamp + "&mode=exact&check_teacher=' . $userid . '";
        xhr.send(params);
    }
}

function displayMakeupRoomResults(rooms, startDateTime, endDateTime) {
    var resultDiv = document.getElementById("available-rooms-list");
    
    if (rooms.length === 0) {
        resultDiv.innerHTML = "<div class=\\"alert alert-warning\\">No rooms available for the selected time period, or you have a schedule conflict.</div>";
        return;
    }
    
    var html = "<div class=\\"alert alert-success\\"><strong>" + rooms.length + " rooms available and you have no schedule conflicts:</strong></div>";
    html += "<div class=\\"row\\">";
    
    rooms.forEach(function(room, index) {
        html += "<div class=\\"col-md-6 mb-2\\">";
        html += "<div class=\\"card room-select-card\\" onclick=\\"selectMakeupRoom(" + room.id + ", this)\\" style=\\"cursor: pointer;\\">";
        html += "<div class=\\"card-body\\">";
        html += "<div class=\\"form-check\\">";
        html += "<input class=\\"form-check-input\\" type=\\"radio\\" name=\\"makeup_room_selection\\" value=\\"" + room.id + "\\">";
        html += "<label class=\\"form-check-label w-100\\">";
        html += "<strong>" + room.room_building + " - Floor " + room.room_floor + " - Room " + room.room_number + "</strong>";
        if (room.capacity) {
            html += "<br><small class=\\"text-muted\\">Capacity: " + room.capacity + " people</small>";
        }
        html += "</label>";
        html += "</div>";
        html += "</div>";
        html += "</div>";
        html += "</div>";
    });
    
    html += "</div>";
    resultDiv.innerHTML = html;
}

function selectMakeupRoom(roomId, cardElement) {
    // Remove selection from all cards
    var allCards = document.querySelectorAll(".room-select-card");
    allCards.forEach(function(card) {
        card.classList.remove("border-primary");
        card.querySelector("input[type=radio]").checked = false;
    });
    
    // Add selection to clicked card
    cardElement.classList.add("border-primary");
    cardElement.querySelector("input[type=radio]").checked = true;
    document.getElementById("selected_makeup_room_id").value = roomId;
    
    updateSubmitButton();
}

function updateSubmitButton() {
    var selectedRoom = document.getElementById("selected_makeup_room_id").value;
    var submitBtn = document.getElementById("submit-makeup-btn");
    
    if (selectedRoom) {
        submitBtn.disabled = false;
    } else {
        submitBtn.disabled = true;
    }
}

// Event listeners
document.getElementById("check_start_datetime").addEventListener("change", updateCheckButton);
document.getElementById("check_end_datetime").addEventListener("change", updateCheckButton);

document.getElementById("makeup_start_datetime").addEventListener("change", updateFindButton);
document.getElementById("makeup_end_datetime").addEventListener("change", updateFindButton);

// Initialize
updateCheckButton();
updateFindButton();

// Form validation
document.getElementById("makeup-form").addEventListener("submit", function(e) {
    var selectedRoom = document.getElementById("selected_makeup_room_id").value;
    if (!selectedRoom) {
        e.preventDefault();
        alert("Please select a room for the makeup session.");
        return false;
    }
});
</script>

<style>
.room-select-card {
    transition: all 0.2s ease;
    border: 2px solid transparent;
}

.room-select-card:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.room-select-card.border-primary {
    border-color: #0d6efd !important;
    box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.25);
}

#check-availability-btn, #find-rooms-btn {
    transition: all 0.3s ease;
}

#check-availability-btn:disabled, #find-rooms-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

#check-availability-btn:not(:disabled):hover, #find-rooms-btn:not(:disabled):hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

#submit-makeup-btn:disabled {
    opacity: 0.6;
}

.alert-info {
    border-left: 4px solid #0d6efd;
    background: linear-gradient(90deg, #e7f3ff 0%, #f8f9fa 100%);
}

.alert-success {
    border-left: 4px solid #198754;
    background: linear-gradient(90deg, #d1eddd 0%, #f8f9fa 100%);
}
</style>';

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
?>
