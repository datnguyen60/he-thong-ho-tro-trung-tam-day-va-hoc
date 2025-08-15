<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/my_absence_requests.php'));
$PAGE->set_title('Absence Requests Management');
$PAGE->set_heading('My Absence Requests');

$userid = $USER->id;

// Handle withdraw request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $action = optional_param('action', '', PARAM_ALPHA);
    $request_id = optional_param('request_id', 0, PARAM_INT);
    
    if ($action === 'withdraw' && $request_id > 0) {
        // Verify that this request belongs to current user and is still pending
        $request = $DB->get_record('local_course_calendar_absence_request', [
            'id' => $request_id,
            'teacher_id' => $userid,
            'status' => 0  // Only pending requests can be withdrawn
        ]);
        
        if ($request) {
            try {
                $DB->delete_record('local_course_calendar_absence_request', ['id' => $request_id]);
                redirect(new moodle_url('/local/course_calendar/pages/my_absence_requests.php'),
                        'Absence request withdrawn successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
            } catch (Exception $e) {
                $errormsg = 'Error withdrawing request: ' . $e->getMessage();
            }
        } else {
            $errormsg = 'Cannot withdraw this request. It may not exist or is no longer pending.';
        }
    }
}

/**
 * Fetch all absence requests of the given teacher.
 *
 * @param int $teacherId
 * @return array
 */
function fetch_absence_requests(int $teacherId): array {
    global $DB;

    // Kiểm tra xem table có tồn tại không
    try {
        // Query chỉ lấy absence requests từ courses mà user được enrol
        $sql = "SELECT ar.id, ar.teacher_id, ar.reason, ar.status, ar.createdtime, 
                       c.fullname as course_name, cs.class_begin_time, cs.class_end_time
                  FROM {local_course_calendar_absence_request} ar
                  JOIN {local_course_calendar_course_section} cs ON cs.id = ar.course_section_id
                  JOIN {course} c ON c.id = cs.courseid
                  JOIN {enrol} e ON e.courseid = c.id
                  JOIN {user_enrolments} ue ON ue.enrolid = e.id
                 WHERE ar.teacher_id = :teacherid AND ue.userid = :userid
              ORDER BY ar.createdtime DESC";

        return $DB->get_records_sql($sql, ['teacherid' => $teacherId, 'userid' => $teacherId]);
    } catch (Exception $e) {
        // Nếu table không tồn tại, return empty array
        return [];
    }
}

/**
 * Convert status code to readable string.
 *
 * @param int $status
 * @return string
 */
function get_status_text(int $status): string {
    switch ($status) {
        case 0:
            return 'Pending';
        case 1:
            return 'Approved';
        case 2:
            return 'Rejected';
        default:
            return 'Unknown';
    }
}

/**
 * Get approver's full name if exists.
 *
 * @param stdClass $record
 * @return string
 */
function get_approver_name(stdClass $record): string {
    // Đơn giản hóa vì chưa có approver data
    return isset($record->approver_id) && $record->approver_id ? 'Admin' : '-';
}

echo $OUTPUT->header();

// Display error if any
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

// Thêm nút tạo yêu cầu mới
$addurl = new moodle_url('/local/course_calendar/pages/add_absence_request.php');
echo $OUTPUT->single_button($addurl, 'Add New Absence Request', 'get', ['class' => 'btn mb-3']);

// Thêm nút xem makeup requests
echo '<div class="mb-3">';
echo '<a href="' . new moodle_url('/local/course_calendar/pages/my_makeup_requests.php') . '" class="btn btn-info">View My Makeup Requests</a>';
echo '</div>';

$requests = fetch_absence_requests($userid);

if (empty($requests)) {
    echo $OUTPUT->notification('You have not submitted any absence requests.', 'info');
} else {
    $table = new html_table();
    $table->head = ['ID', 'Course', 'Session', 'Reason', 'Status', 'Requested Date', 'Actions'];
    $table->data = [];

    foreach ($requests as $request) {
        $session_info = date('Y-m-d', $request->class_begin_time) . ' ' . 
                       date('H:i', $request->class_begin_time) . '-' . 
                       date('H:i', $request->class_end_time);
        
        // Create withdraw button for pending requests and makeup button for approved requests
        $actions = '';
        if ($request->status == 0) { // Pending
            $actions = '<form method="post" style="display: inline;" onsubmit="return confirm(\'Are you sure you want to withdraw this request?\')">';
            $actions .= '<input type="hidden" name="sesskey" value="' . sesskey() . '">';
            $actions .= '<input type="hidden" name="action" value="withdraw">';
            $actions .= '<input type="hidden" name="request_id" value="' . $request->id . '">';
            $actions .= '<button type="submit" class="btn btn-sm btn-danger">Withdraw</button>';
            $actions .= '</form>';
        } else if ($request->status == 1) { // Approved
            $makeup_url = new moodle_url('/local/course_calendar/pages/request_makeup_session.php', ['absence_id' => $request->id]);
            $actions = '<a href="' . $makeup_url . '" class="btn btn-sm btn-success">Request Makeup</a>';
        } else {
            $actions = '-';
        }
        
        $table->data[] = [
            $request->id,
            s($request->course_name),
            $session_info,
            s($request->reason),
            get_status_text((int)$request->status),
            userdate($request->createdtime),
            $actions
        ];
    }

    echo html_writer::table($table);
}

echo $OUTPUT->footer();
