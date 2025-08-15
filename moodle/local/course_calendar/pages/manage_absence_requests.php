<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/manage_absence_requests.php'));
$PAGE->set_title('Manage Absence Requests');
$PAGE->set_heading('Manage Absence Requests');

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
    
    if (in_array($action, ['approve', 'reject'])) {
        $new_status = ($action === 'approve') ? 1 : 2;
        
        try {
            // Start transaction to ensure data consistency
            $transaction = $DB->start_delegated_transaction();
            
            // Update absence request status
            $update_record = new stdClass();
            $update_record->id = $request_id;
            $update_record->status = $new_status;
            $update_record->approver_id = $userid;
            $update_record->modifiedtime = time();
            $DB->update_record('local_course_calendar_absence_request', $update_record);
            
            // If approved, also mark the course section as cancelled
            if ($action === 'approve') {
                // Get the course section ID from the absence request
                $absence_request = $DB->get_record('local_course_calendar_absence_request', 
                    ['id' => $request_id], 'course_section_id');
                
                if ($absence_request) {
                    $section_update = new stdClass();
                    $section_update->id = $absence_request->course_section_id;
                    $section_update->is_cancel = 1;
                    $section_update->modifiedtime = time();
                    $section_update->modified_user_id = $userid;
                    $section_update->reason = 'Approved absence request #' . $request_id;
                    
                    $DB->update_record('local_course_calendar_course_section', $section_update);
                }
            }
            
            // Commit the transaction
            $transaction->allow_commit();
            
            $message = ($action === 'approve') ? 'Request approved successfully and session marked as cancelled!' : 'Request rejected successfully!';
            redirect(new moodle_url('/local/course_calendar/pages/manage_absence_requests.php'), 
                    $message, null, \core\output\notification::NOTIFY_SUCCESS);
        } catch (Exception $e) {
            // Rollback transaction on error
            $transaction->rollback($e);
            $errormsg = 'Database error: ' . $e->getMessage();
        }
    }
}

// Build SQL query to get absence requests for courses where user is manager
// Manager là người tạo ra course sections, hoặc có role trong course
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

// Query to get absence requests from courses where current user has manager role
// This checks if user has manager role (roleId = 1) in the course
$sql = "SELECT ar.id, ar.teacher_id, ar.reason, ar.status, ar.createdtime, ar.modifiedtime, ar.approver_id,
               cs.class_begin_time, cs.class_end_time,
               c.id as courseid, c.fullname as course_name,
               t.firstname as teacher_firstname, t.lastname as teacher_lastname,
               app.firstname as approver_firstname, app.lastname as approver_lastname
        FROM {local_course_calendar_absence_request} ar
        JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
        JOIN {course} c ON c.id = cs.courseid
        JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
        JOIN {role_assignments} ra ON ra.contextid = ctx.id
        JOIN {user} t ON t.id = ar.teacher_id
        LEFT JOIN {user} app ON app.id = ar.approver_id
        WHERE ra.userid = :managerid AND ra.roleid = 1 AND " . implode(' AND ', $where_conditions) . "
        ORDER BY ar.createdtime DESC";

$requests = $DB->get_records_sql($sql, $params);

// Get only courses where current user has manager role (roleId = 1)
$courses_sql = "SELECT DISTINCT c.id, c.fullname
                FROM {local_course_calendar_course_section} cs
                JOIN {course} c ON c.id = cs.courseid
                JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = 50
                JOIN {role_assignments} ra ON ra.contextid = ctx.id
                WHERE ra.userid = :userid AND ra.roleid = 1
                ORDER BY c.fullname";
$managed_courses = $DB->get_records_sql($courses_sql, ['userid' => $userid]);

/**
 * Get status display text
 */
function get_status_display($status) {
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
function get_action_buttons($request_id, $status) {
    if ($status == 0) { // Pending
        $approve_btn = '<form method="post" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to approve this request?\')">';
        $approve_btn .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        $approve_btn .= '<input type="hidden" name="action" value="approve">';
        $approve_btn .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
        $approve_btn .= '<button type="submit" class="btn btn-sm btn-success">Approve</button>';
        $approve_btn .= '</form> ';
        
        $reject_btn = '<form method="post" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to reject this request?\')">';
        $reject_btn .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
        $reject_btn .= '<input type="hidden" name="action" value="reject">';
        $reject_btn .= '<input type="hidden" name="request_id" value="' . $request_id . '">';
        $reject_btn .= '<button type="submit" class="btn btn-sm btn-danger">Reject</button>';
        $reject_btn .= '</form>';
        
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
echo '<h5 class="mb-0">Absence Requests (' . count($requests) . ')</h5>';
echo '</div>';
echo '<div class="card-body">';

if (empty($requests)) {
    echo '<p class="text-center">No absence requests found for the selected criteria.</p>';
} else {
    echo '<div class="table-responsive">';
    echo '<table class="table table-striped table-hover">';
    echo '<thead class="table-dark">';
    echo '<tr>';
    echo '<th>ID</th>';
    echo '<th>Course</th>';
    echo '<th>Teacher</th>';
    echo '<th>Session Date/Time</th>';
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
        echo '<td>';
        echo '<strong>' . date('Y-m-d', $request->class_begin_time) . '</strong><br>';
        echo '<small>' . date('H:i', $request->class_begin_time) . ' - ' . date('H:i', $request->class_end_time) . '</small>';
        echo '</td>';
        echo '<td><small>' . s(substr($request->reason, 0, 50)) . (strlen($request->reason) > 50 ? '...' : '') . '</small></td>';
        echo '<td>' . get_status_display($request->status) . '</td>';
        echo '<td><small>' . userdate($request->createdtime) . '</small></td>';
        echo '<td>';
        if ($request->approver_firstname) {
            echo '<small>' . s($request->approver_firstname . ' ' . $request->approver_lastname) . '</small>';
        } else {
            echo '-';
        }
        echo '</td>';
        echo '<td>' . get_action_buttons($request->id, $request->status) . '</td>';
        echo '</tr>';
    }
    
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
}

echo '</div>';
echo '</div>';

echo $OUTPUT->footer();
