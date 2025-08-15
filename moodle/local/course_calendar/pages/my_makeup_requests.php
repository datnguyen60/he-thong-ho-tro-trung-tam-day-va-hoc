<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/my_makeup_requests.php'));
$PAGE->set_title('My Makeup Requests');
$PAGE->set_heading('My Makeup Requests');

$userid = $USER->id;

global $DB;

// Handle withdraw request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $action = optional_param('action', '', PARAM_ALPHA);
    $request_id = optional_param('request_id', 0, PARAM_INT);
    
    if ($action === 'withdraw' && $request_id > 0) {
        // Verify that this request belongs to current user and is still pending
        $request = $DB->get_record('local_course_calendar_absence_request', [
            'id' => $request_id,
            'teacher_id' => $userid,
            'request_type' => 1,  // Makeup request
            'status' => 0  // Only pending requests can be withdrawn
        ]);
        
        if ($request) {
            try {
                $DB->delete_record('local_course_calendar_absence_request', ['id' => $request_id]);
                redirect(new moodle_url('/local/course_calendar/pages/my_makeup_requests.php'),
                        'Makeup request withdrawn successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
            } catch (Exception $e) {
                $errormsg = 'Error withdrawing request: ' . $e->getMessage();
            }
        } else {
            $errormsg = 'Cannot withdraw this request. It may not exist or is no longer pending.';
        }
    }
}

// Get makeup requests for current user
$requests = $DB->get_records_sql("
    SELECT ar.*, 
           c.fullname as course_name,
           room.room_building, room.room_floor, room.room_number,
           original_cs.class_begin_time as original_begin_time,
           original_cs.class_end_time as original_end_time,
           original_ar.reason as original_absence_reason,
           approver.firstname as approver_firstname, approver.lastname as approver_lastname
    FROM {local_course_calendar_absence_request} ar
    JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
    JOIN {course} c ON c.id = cs.courseid
    JOIN {local_course_calendar_course_room} room ON room.id = ar.makeup_room_id
    LEFT JOIN {local_course_calendar_absence_request} original_ar ON original_ar.id = ar.original_absence_id
    LEFT JOIN {local_course_calendar_course_section} original_cs ON original_cs.id = original_ar.course_section_id
    LEFT JOIN {user} approver ON approver.id = ar.approver_id
    WHERE ar.teacher_id = :userid AND ar.request_type = 1
    ORDER BY ar.createdtime DESC",
    ['userid' => $userid]);

/**
 * Get status display text
 */
function get_makeup_status_display($status) {
    switch ($status) {
        case 0: return '<span class="badge badge-warning">Pending</span>';
        case 1: return '<span class="badge badge-success">Approved</span>';
        case 2: return '<span class="badge badge-danger">Rejected</span>';
        default: return '<span class="badge badge-secondary">Unknown</span>';
    }
}

echo $OUTPUT->header();

// Display error if any
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

// Navigation links
echo '<div class="mb-3">';
echo '<a href="' . new moodle_url('/local/course_calendar/pages/my_absence_requests.php') . '" class="btn btn-secondary">← Back to Absence Requests</a>';
echo '</div>';

// Page header
echo '<div class="d-flex justify-content-between align-items-center mb-4">';
echo '<h3>My Makeup Session Requests</h3>';
echo '</div>';

if (empty($requests)) {
    echo '<div class="alert alert-info">';
    echo '<h5>No Makeup Requests Found</h5>';
    echo '<p>You haven\'t submitted any makeup session requests yet.</p>';
    echo '<p>To request a makeup session, you need to first have an approved absence request for a cancelled session.</p>';
    echo '</div>';
} else {
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5 class="mb-0">Makeup Requests (' . count($requests) . ')</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead class="table-dark">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Course</th>';
    echo '<th>Original Session</th>';
    echo '<th>Requested Makeup</th>';
    echo '<th>Room</th>';
    echo '<th>Status</th>';
    echo '<th>Submitted</th>';
    echo '<th>Approver</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($requests as $request) {
        echo '<tr>';
        echo '<td>' . $request->id . '</td>';
        echo '<td>' . s($request->course_name) . '</td>';
        
        // Original session info
        echo '<td>';
        echo '<strong>' . date('Y-m-d', $request->original_begin_time) . '</strong><br>';
        echo '<small>' . date('H:i', $request->original_begin_time) . ' - ' . date('H:i', $request->original_end_time) . '</small>';
        echo '</td>';
        
        // Requested makeup info
        echo '<td>';
        echo '<strong>' . date('Y-m-d', $request->makeup_start_time) . '</strong><br>';
        echo '<small>' . date('H:i', $request->makeup_start_time) . ' - ' . date('H:i', $request->makeup_end_time) . '</small>';
        echo '</td>';
        
        // Room info
        echo '<td>';
        echo s($request->room_building) . '<br>';
        echo '<small>Floor ' . $request->room_floor . ', Room ' . $request->room_number . '</small>';
        echo '</td>';
        
        echo '<td>' . get_makeup_status_display($request->status) . '</td>';
        echo '<td><small>' . userdate($request->createdtime) . '</small></td>';
        
        // Approver info
        echo '<td>';
        if ($request->approver_firstname) {
            echo '<small>' . s($request->approver_firstname . ' ' . $request->approver_lastname) . '</small>';
        } else {
            echo '-';
        }
        echo '</td>';
        
        // Actions
        echo '<td>';
        if ($request->status == 0) { // Pending
            echo '<form method="post" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to withdraw this makeup request?\')">';
            echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            echo '<input type="hidden" name="action" value="withdraw">';
            echo '<input type="hidden" name="request_id" value="' . $request->id . '">';
            echo '<button type="submit" class="btn btn-sm btn-danger">Withdraw</button>';
            echo '</form>';
        } else {
            echo '-';
        }
        echo '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
    
    echo '</div>';
    echo '</div>';
}

// Info card about makeup sessions
echo '<div class="card mt-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">About Makeup Sessions</h5>';
echo '</div>';
echo '<div class="card-body">';
echo '<ul>';
echo '<li><strong>Makeup sessions</strong> can only be requested for cancelled class sessions.</li>';
echo '<li>You need to select an available room and time slot that doesn\'t conflict with existing schedules.</li>';
echo '<li>All makeup requests require <strong>manager approval</strong> before they become official.</li>';
echo '<li>You can <strong>withdraw</strong> pending requests, but approved/rejected requests cannot be modified.</li>';
echo '<li>The system automatically checks for room and schedule conflicts.</li>';
echo '</ul>';
echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
?>
