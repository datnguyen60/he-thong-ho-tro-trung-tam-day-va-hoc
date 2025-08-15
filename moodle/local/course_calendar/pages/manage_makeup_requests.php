<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/manage_makeup_requests.php'));
$PAGE->set_title('Manage Makeup Requests');
$PAGE->set_heading('Manage Makeup Requests');

$userid = $USER->id;

global $DB;

// Get filter parameters
$filter_status = optional_param('status_filter', -1, PARAM_INT); // -1 = all, 0 = pending, 1 = approved, 2 = rejected
$filter_course = optional_param('course_filter', 0, PARAM_INT);
$filter_start = optional_param('date_start', date('Y-m-d', strtotime('-1 month')), PARAM_TEXT);
$filter_end = optional_param('date_end', date('Y-m-d', strtotime('+1 month')), PARAM_TEXT);

// Convert date filters to timestamps
$start_timestamp = strtotime($filter_start . ' 00:00:00');
$end_timestamp = strtotime($filter_end . ' 23:59:59');

// Handle approval/rejection actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $action = required_param('action', PARAM_ALPHA);
    $request_id = required_param('request_id', PARAM_INT);
    $notes = optional_param('notes', '', PARAM_TEXT);
    
    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 1 : 2;
        
        try {
            // Start transaction to ensure data consistency
            $transaction = $DB->start_delegated_transaction();
            
            // Get makeup request details from unified table
            $makeup_request = $DB->get_record('local_course_calendar_absence_request', [
                'id' => $request_id,
                'request_type' => 1  // Makeup request
            ]);
            
            if (!$makeup_request || $makeup_request->status != 0) {
                throw new Exception('Invalid request or request is not pending.');
            }
            
            // If approving, perform additional validation
            if ($action === 'approve') {
                // Double-check room availability
                $room_conflict = check_room_availability_advanced($makeup_request->makeup_room_id, 
                    $makeup_request->makeup_start_time, $makeup_request->makeup_end_time, $request_id);
                if ($room_conflict) {
                    throw new Exception('Room conflict detected: ' . $room_conflict);
                }
                
                // Double-check teacher availability
                $teacher_conflict = check_teacher_availability_advanced($makeup_request->teacher_id,
                    $makeup_request->makeup_start_time, $makeup_request->makeup_end_time);
                if ($teacher_conflict) {
                    throw new Exception('Teacher conflict detected: ' . $teacher_conflict);
                }
                
                // Create actual course section for approved makeup
                create_makeup_course_section($makeup_request, $userid);
            }
            
            // Update makeup request status
            $update_record = new stdClass();
            $update_record->id = $request_id;
            $update_record->status = $new_status;
            $update_record->approver_id = $userid;
            $update_record->approvedtime = time();
            
            $DB->update_record('local_course_calendar_absence_request', $update_record);
            
            // Commit the transaction
            $transaction->allow_commit();
            
            $message = ($action === 'approve') ? 'Makeup request approved successfully and session created!' : 'Makeup request rejected successfully!';
            redirect(new moodle_url('/local/course_calendar/pages/manage_makeup_requests.php'), 
                    $message, null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            // Rollback transaction on error
            if (isset($transaction)) {
                $transaction->rollback($e);
            }
            $errormsg = 'Error processing request: ' . $e->getMessage();
        }
    }
}

// Build SQL query to get makeup requests for courses where user is manager
$where_conditions = [
    'ar.createdtime >= :start_time', 
    'ar.createdtime <= :end_time'
];
$params = [
    'managerid' => $userid, 
    'start_time' => $start_timestamp, 
    'end_time' => $end_timestamp
];

if ($filter_status >= 0) {
    $where_conditions[] = 'ar.status = :status';
    $params['status'] = $filter_status;
}

if ($filter_course > 0) {
    $where_conditions[] = 'c.id = :courseid';
    $params['courseid'] = $filter_course;
}

// Query to get makeup requests from courses where current user has manager role
$sql = "SELECT ar.*,
               c.fullname as course_name,
               t.firstname as teacher_firstname, t.lastname as teacher_lastname,
               room.room_building, room.room_floor, room.room_number,
               app.firstname as approver_firstname, app.lastname as approver_lastname,
               original_cs.class_begin_time as original_begin_time,
               original_cs.class_end_time as original_end_time,
               original_ar.reason as original_absence_reason
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id
        JOIN {user} t ON t.id = ar.teacher_id
        JOIN {local_course_calendar_course_room} room ON room.id = ar.makeup_room_id
        LEFT JOIN {local_course_calendar_absence_request} original_ar ON original_ar.id = ar.original_absence_id
        LEFT JOIN {local_course_calendar_course_section} original_cs ON original_cs.id = original_ar.course_section_id
        LEFT JOIN {user} app ON app.id = ar.approver_id
        WHERE ar.request_type = 1 AND ra.userid = :managerid AND ra.roleid = 1 AND " . implode(' AND ', $where_conditions) . "
        ORDER BY ar.createdtime DESC";$requests = $DB->get_records_sql($sql, $params);

// Get only courses where current user has manager role
$courses_sql = "SELECT DISTINCT c.id, c.fullname
                FROM {local_course_calendar_absence_request} ar
                JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
                JOIN {course} c ON c.id = cs.courseid
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                WHERE ar.request_type = 1 AND ra.userid = :userid AND ra.roleid = 1
                ORDER BY c.fullname";
$managed_courses = $DB->get_records_sql($courses_sql, ['userid' => $userid]);

/**
 * Advanced room availability check excluding current request
 */
function check_room_availability_advanced($room_id, $start_time, $end_time, $exclude_request_id = null) {
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
    
    // Check other approved makeup sessions
    $exclude_condition = $exclude_request_id ? " AND ar.id != :exclude_id" : "";
    $exclude_params = $exclude_request_id ? ['exclude_id' => $exclude_request_id] : [];
    
    $makeup_conflicts = $DB->get_records_sql("
        SELECT ar.id, c.fullname, ar.makeup_start_time, ar.makeup_end_time
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        WHERE ar.request_type = 1 AND ar.makeup_room_id = :room_id 
        AND ar.status = 1" . $exclude_condition . "
        AND ar.makeup_start_time < :end_time
        AND ar.makeup_end_time > :start_time",
        array_merge([
            'room_id' => $room_id,
            'start_time' => $start_time,
            'end_time' => $end_time
        ], $exclude_params));
    
    if (!empty($makeup_conflicts)) {
        $conflict = reset($makeup_conflicts);
        return $conflict->fullname . ' (Makeup - ' . date('Y-m-d H:i', $conflict->makeup_start_time) . 
               ' - ' . date('H:i', $conflict->makeup_end_time) . ')';
    }
    
    return false;
}

/**
 * Advanced teacher availability check
 */
function check_teacher_availability_advanced($teacher_id, $start_time, $end_time) {
    global $DB;
    
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

/**
 * Create actual course section for approved makeup
 */
function create_makeup_course_section($makeup_request, $approver_id) {
    global $DB;
    
    // Get course info from the existing course section
    $original_section = $DB->get_record('local_course_calendar_course_section', 
        ['id' => $makeup_request->course_section_id]);
    
    if (!$original_section) {
        throw new Exception('Original course section not found');
    }
    
    // Create new course section record for makeup
    $section = new stdClass();
    $section->courseid = $original_section->courseid;
    $section->created_user_id = $approver_id;
    $section->modified_user_id = $approver_id;
    $section->course_room_id = $makeup_request->makeup_room_id;
    $section->createdtime = time();
    $section->modifiedtime = time();
    $section->visible = 1;
    $section->class_begin_time = $makeup_request->makeup_start_time;
    $section->class_end_time = $makeup_request->makeup_end_time;
    $section->class_total_sessions = 1;
    $section->reason = 'Makeup session for cancelled class';
    $section->is_cancel = 0;
    $section->is_makeup = 1;
    $section->is_accepted = 1;
    
    $DB->insert_record('local_course_calendar_course_section', $section);
}

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

/**
 * Get action buttons for pending requests
 */
function get_makeup_action_buttons($request_id, $status) {
    if ($status == 0) { // Pending
        $approve_btn = '<button type="button" class="btn btn-sm btn-success" onclick="confirmAction(' . $request_id . ', \'approve\')">Approve</button> ';
        $reject_btn = '<button type="button" class="btn btn-sm btn-danger" onclick="confirmAction(' . $request_id . ', \'reject\')">Reject</button>';
        return $approve_btn . $reject_btn;
    } else {
        return '-';
    }
}

echo $OUTPUT->header();

// Display error if any
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

// Navigation
echo '<div class="mb-3">';
echo '<a href="' . new moodle_url('/local/course_calendar/pages/manage_absence_requests.php') . '" class="btn btn-secondary">← Back to Absence Requests</a>';
echo '</div>';

// Filter Controls
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Filters</h5>';
echo '</div>';
echo '<div class="card-body">';

echo '<form method="get" class="row g-3">';

echo '<div class="col-md-3">';
echo '<label for="course_filter" class="form-label">Filter by Course:</label>';
echo '<select name="course_filter" id="course_filter" class="form-control">';
echo '<option value="0">All Courses</option>';
foreach ($managed_courses as $course) {
    $selected = ($filter_course == $course->id) ? 'selected' : '';
    echo '<option value="' . $course->id . '" ' . $selected . '>' . s($course->fullname) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="status_filter" class="form-label">Status:</label>';
echo '<select name="status_filter" id="status_filter" class="form-control">';
echo '<option value="-1"' . ($filter_status == -1 ? ' selected' : '') . '>All Status</option>';
echo '<option value="0"' . ($filter_status == 0 ? ' selected' : '') . '>Pending</option>';
echo '<option value="1"' . ($filter_status == 1 ? ' selected' : '') . '>Approved</option>';
echo '<option value="2"' . ($filter_status == 2 ? ' selected' : '') . '>Rejected</option>';
echo '</select>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label for="date_start" class="form-label">Start Date:</label>';
echo '<input type="date" name="date_start" id="date_start" class="form-control" value="' . $filter_start . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label for="date_end" class="form-label">End Date:</label>';
echo '<input type="date" name="date_end" id="date_end" class="form-control" value="' . $filter_end . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">&nbsp;</label><br>';
echo '<button type="submit" class="btn btn-primary">Apply Filters</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

// Requests Table
echo '<div class="card">';
echo '<div class="card-header d-flex justify-content-between align-items-center">';
echo '<h5 class="mb-0">Makeup Session Requests (' . count($requests) . ')</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($requests)) {
    echo '<p class="text-center">No makeup requests found for the selected criteria.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead class="table-dark">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Course</th>';
    echo '<th>Teacher</th>';
    echo '<th>Original Session</th>';
    echo '<th>Requested Makeup</th>';
    echo '<th>Room</th>';
    echo '<th>Reason</th>';
    echo '<th>Status</th>';
    echo '<th>Requested</th>';
    echo '<th>Approver</th>';
    echo '<th>Actions</th>';
    echo '</tr>';
    echo '</thead>';
    echo '<tbody>';
    
    foreach ($requests as $request) {
        echo '<tr>';
        echo '<td>' . $request->id . '</td>';
        echo '<td>' . s($request->course_name) . '</td>';
        echo '<td>' . s($request->teacher_firstname . ' ' . $request->teacher_lastname) . '</td>';
        
        // Original session
        echo '<td>';
        echo '<strong>' . date('Y-m-d', $request->original_begin_time) . '</strong><br>';
        echo '<small>' . date('H:i', $request->original_begin_time) . ' - ' . date('H:i', $request->original_end_time) . '</small>';
        echo '</td>';
        
        // Requested makeup
        echo '<td>';
        echo '<strong>' . date('Y-m-d', $request->makeup_start_time) . '</strong><br>';
        echo '<small>' . date('H:i', $request->makeup_start_time) . ' - ' . date('H:i', $request->makeup_end_time) . '</small>';
        echo '</td>';
        
        // Room
        echo '<td>';
        echo s($request->room_building) . '<br>';
        echo '<small>Floor ' . $request->room_floor . ', Room ' . $request->room_number . '</small>';
        echo '</td>';
        
        echo '<td><small>' . s(substr($request->reason, 0, 50)) . (strlen($request->reason) > 50 ? '...' : '') . '</small></td>';
        echo '<td>' . get_makeup_status_display($request->status) . '</td>';
        echo '<td><small>' . userdate($request->createdtime) . '</small></td>';
        echo '<td>';
        if ($request->approver_firstname) {
            echo '<small>' . s($request->approver_firstname . ' ' . $request->approver_lastname) . '</small>';
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td>' . get_makeup_action_buttons($request->id, $request->status) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

// JavaScript for confirmation
echo '<script>
function confirmAction(requestId, action) {
    var message = "";
    if (action === "approve") {
        message = "Are you sure you want to approve this makeup session request? This will create a new course section.";
    } else {
        message = "Are you sure you want to reject this makeup session request?";
    }
    
    if (confirm(message)) {
        // Create and submit form
        var form = document.createElement("form");
        form.method = "post";
        form.action = window.location.href;
        
        // Add hidden fields
        var sesskey = document.createElement("input");
        sesskey.type = "hidden";
        sesskey.name = "sesskey";
        sesskey.value = "' . sesskey() . '";
        form.appendChild(sesskey);
        
        var actionInput = document.createElement("input");
        actionInput.type = "hidden";
        actionInput.name = "action";
        actionInput.value = action;
        form.appendChild(actionInput);
        
        var requestIdInput = document.createElement("input");
        requestIdInput.type = "hidden";
        requestIdInput.name = "request_id";
        requestIdInput.value = requestId;
        form.appendChild(requestIdInput);
        
        // Submit form
        document.body.appendChild(form);
        form.submit();
    }
}
</script>';

echo $OUTPUT->footer();
?>
