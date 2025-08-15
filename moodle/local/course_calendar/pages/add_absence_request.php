<?php
require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/add_absence_request.php'));
$PAGE->set_title('Add Absence Request');
// $PAGE->set_heading('Add New Absence Request');


$userid = $USER->id;

global $DB;

// Get filter parameters
$view_mode = optional_param('view', 'list', PARAM_ALPHA); // list or calendar
$filter_course = optional_param('course_filter', 0, PARAM_INT);
$filter_start = optional_param('date_start', date('Y-m-d'), PARAM_TEXT);
$filter_end = optional_param('date_end', date('Y-m-d', strtotime('+1 month')), PARAM_TEXT);

// Convert date filters to timestamps
$start_timestamp = strtotime($filter_start . ' 00:00:00');
$end_timestamp = strtotime($filter_end . ' 23:59:59');

// Build SQL based on filters
$current_time = time();
$where_conditions = [
    'cs.visible = 1', 
    'cs.is_cancel = 0',  // Chỉ show buổi học chưa bị cancel
    'cs.class_end_time > :current_time',  // Chỉ show buổi học chưa kết thúc
    'cs.class_begin_time >= :start_time', 
    'cs.class_begin_time <= :end_time'
];
$params = [
    'userid' => $userid, 
    'current_time' => $current_time,
    'start_time' => $start_timestamp, 
    'end_time' => $end_timestamp
];

if ($filter_course > 0) {
    $where_conditions[] = 'cs.courseid = :courseid';
    $params['courseid'] = $filter_course;
}

// Query chỉ lấy course sections từ courses mà user được enrol
// Kiểm tra user có enrolment trong course đó không
// Loại trừ các buổi đã có absence request pending hoặc approved
$sql = "SELECT cs.id, cs.courseid, cs.class_begin_time, cs.class_end_time, c.fullname,
               ar.id as existing_request_id, ar.status as request_status
        FROM {local_course_calendar_course_section} cs
        JOIN {course} c ON c.id = cs.courseid
        JOIN {enrol} e ON e.courseid = c.id
        JOIN {user_enrolments} ue ON ue.enrolid = e.id
        LEFT JOIN {local_course_calendar_absence_request} ar ON ar.course_section_id = cs.id 
                  AND ar.teacher_id = :userid2 AND ar.status IN (0, 1)
        WHERE ue.userid = :userid AND " . implode(' AND ', $where_conditions) . "
        ORDER BY cs.class_begin_time ASC";

// Add userid2 parameter to avoid duplicate parameter names
$params['userid2'] = $userid;
$sections = $DB->get_records_sql($sql, $params);

// Get courses for filter dropdown - chỉ courses mà user được enrol
$courses_sql = "SELECT DISTINCT c.id, c.fullname
                FROM {local_course_calendar_course_section} cs
                JOIN {course} c ON c.id = cs.courseid
                JOIN {enrol} e ON e.courseid = c.id
                JOIN {user_enrolments} ue ON ue.enrolid = e.id
                WHERE ue.userid = :userid AND cs.visible = 1
                ORDER BY c.fullname";
$all_courses = $DB->get_records_sql($courses_sql, ['userid' => $userid]);

// Xử lý form submission

if ($_SERVER['REQUEST_METHOD'] === 'POST' && confirm_sesskey()) {
    $reason = required_param('reason', PARAM_TEXT);
    $sectionid = required_param('section_id', PARAM_INT);

    if (!empty($reason) && !empty($sectionid)) {
        // Kiểm tra xem đã có absence request cho buổi này chưa
        $existing_request = $DB->get_record('local_course_calendar_absence_request', [
            'teacher_id' => $userid,
            'course_section_id' => $sectionid,
            'status' => 0  // Pending
        ]);
        
        if ($existing_request) {
            $errormsg = 'You already have a pending absence request for this session.';
        } else {
            // Kiểm tra xem buổi học có hợp lệ không (chưa kết thúc, chưa cancel)
            $section = $DB->get_record('local_course_calendar_course_section', [
                'id' => $sectionid,
                'visible' => 1,
                'is_cancel' => 0
            ]);
            
            if (!$section || $section->class_end_time <= time()) {
                $errormsg = 'Cannot request absence for this session. It may be completed or cancelled.';
            } else {
                $record = new stdClass();
                $record->teacher_id = $userid;
                $record->reason = $reason;
                $record->status = 0; // Pending
                $record->createdtime = time();
                $record->modifiedtime = time();
                $record->course_section_id = $sectionid;

                try {
                    $DB->insert_record('local_course_calendar_absence_request', $record);
                    redirect(new moodle_url('/local/course_calendar/pages/my_absence_requests.php'),
                            'Absence request submitted successfully!', null, \core\output\notification::NOTIFY_SUCCESS);
                } catch (Exception $e) {
                    $errormsg = 'Database error: ' . $e->getMessage();
                }
            }
        }
    } else {
        $errormsg = 'Please fill in all required fields.';
    }
}

echo $OUTPUT->header();
echo $OUTPUT->heading('Add New Absence Request');

// Hiển thị lỗi nếu có
if (isset($errormsg)) {
    echo $OUTPUT->notification($errormsg, 'error');
}

// Filter and View Controls
echo '<div class="card mb-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">View Options & Filters</h5>';
echo '</div>';
echo '<div class="card-body">';

// View Mode Toggle
echo '<div class="row mb-3">';
echo '<div class="col-md-6">';
echo '<label class="form-label">View Mode:</label><br>';
$list_active = ($view_mode === 'list') ? 'btn-primary' : 'btn-outline-primary';
$calendar_active = ($view_mode === 'calendar') ? 'btn-primary' : 'btn-outline-primary';
echo '<div class="btn-group" role="group">';
echo '<a href="?view=list&course_filter=' . $filter_course . '&date_start=' . $filter_start . '&date_end=' . $filter_end . '" class="btn ' . $list_active . '">List View</a>';
echo '<a href="?view=calendar&course_filter=' . $filter_course . '&date_start=' . $filter_start . '&date_end=' . $filter_end . '" class="btn ' . $calendar_active . '">Calendar View</a>';
echo '</div>';
echo '</div>';
echo '</div>';

// Filters Form
echo '<form method="get" class="row g-3">';
echo '<input type="hidden" name="view" value="' . $view_mode . '">';

echo '<div class="col-md-4">';
echo '<label for="course_filter" class="form-label">Filter by Course:</label>';
echo '<select name="course_filter" id="course_filter" class="form-control">';
echo '<option value="0">All Courses</option>';
foreach ($all_courses as $course) {
    $selected = ($filter_course == $course->id) ? 'selected' : '';
    echo '<option value="' . $course->id . '" ' . $selected . '>' . s($course->fullname) . '</option>';
}
echo '</select>';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label for="date_start" class="form-label">Start Date:</label>';
echo '<input type="date" name="date_start" id="date_start" class="form-control" value="' . $filter_start . '">';
echo '</div>';

echo '<div class="col-md-3">';
echo '<label for="date_end" class="form-label">End Date:</label>';
echo '<input type="date" name="date_end" id="date_end" class="form-control" value="' . $filter_end . '">';
echo '</div>';

echo '<div class="col-md-2">';
echo '<label class="form-label">&nbsp;</label><br>';
echo '<button type="submit" class="btn btn-success">Apply Filters</button>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

// Display sections based on view mode
if ($view_mode === 'calendar') {
    // Calendar View
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5 class="mb-0">Calendar View - Select a Session to Request Absence</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    if (empty($sections)) {
        echo '<p class="text-center">No sessions found for the selected period.</p>';
    } else {
        // Group sections by date
        $sections_by_date = [];
        foreach ($sections as $section) {
            $date_key = date('Y-m-d', $section->class_begin_time);
            if (!isset($sections_by_date[$date_key])) {
                $sections_by_date[$date_key] = [];
            }
            $sections_by_date[$date_key][] = $section;
        }
        
        echo '<div class="calendar-grid" style="display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #ddd;">';
        
        // Calendar header
        $days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        foreach ($days as $day) {
            echo '<div class="calendar-header" style="background: #f8f9fa; padding: 10px; text-align: center; font-weight: bold;">' . $day . '</div>';
        }
        
        // Calculate calendar start (first day of the month containing start date)
        $calendar_start = strtotime(date('Y-m-01', $start_timestamp));
        $first_day_week = date('w', $calendar_start);
        
        // Fill empty cells before first day
        for ($i = 0; $i < $first_day_week; $i++) {
            echo '<div class="calendar-cell" style="background: #fff; padding: 5px; min-height: 100px; border: 1px solid #eee;"></div>';
        }
        
        // Generate calendar days
        $current_date = $calendar_start;
        $end_of_month = strtotime(date('Y-m-t', $end_timestamp));
        
        while ($current_date <= $end_of_month) {
            $date_key = date('Y-m-d', $current_date);
            $day_num = date('j', $current_date);
            
            echo '<div class="calendar-cell" style="background: #fff; padding: 5px; min-height: 100px; border: 1px solid #eee; vertical-align: top;">';
            echo '<div style="font-weight: bold; margin-bottom: 5px;">' . $day_num . '</div>';
            
            if (isset($sections_by_date[$date_key])) {
                foreach ($sections_by_date[$date_key] as $section) {
                    $time_range = date('H:i', $section->class_begin_time) . '-' . date('H:i', $section->class_end_time);
                    
                    // Check if session has existing request
                    $has_request = !empty($section->existing_request_id);
                    $request_status = $has_request ? $section->request_status : null;
                    
                    // Set style based on request status
                    $bg_color = '#e3f2fd'; // Default blue
                    $cursor = 'pointer';
                    $onclick = "selectSession(" . $section->id . ", '" . addslashes($section->fullname . ' - ' . $time_range) . "')";
                    $status_text = '';
                    
                    if ($has_request) {
                        if ($request_status == 0) { // Pending
                            $bg_color = '#fff3cd'; // Yellow
                            $status_text = ' (Pending)';
                            $cursor = 'not-allowed';
                            $onclick = "alert('You already have a pending request for this session.')";
                        } else if ($request_status == 1) { // Approved
                            $bg_color = '#d4edda'; // Green
                            $status_text = ' (Approved)';
                            $cursor = 'not-allowed';
                            $onclick = "alert('You already have an approved request for this session.')";
                        }
                    }
                    
                    echo '<div class="session-item" data-section-id="' . $section->id . '" style="background: ' . $bg_color . '; margin: 2px 0; padding: 3px; border-radius: 3px; font-size: 0.8em; cursor: ' . $cursor . ';" onclick="' . $onclick . '">';
                    echo '<div style="font-weight: bold; pointer-events: none;">' . s(substr($section->fullname, 0, 15)) . '...' . $status_text . '</div>';
                    echo '<div style="pointer-events: none;">' . $time_range . '</div>';
                    echo '</div>';
                }
            }
            
            echo '</div>';
            $current_date = strtotime('+1 day', $current_date);
        }
        
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
    
} else {
    // List View
    echo '<div class="card">';
    echo '<div class="card-header">';
    echo '<h5 class="mb-0">List View - Select a Session to Request Absence</h5>';
    echo '</div>';
    echo '<div class="card-body">';
    
    if (empty($sections)) {
        echo '<p>No sessions found for the selected period.</p>';
    } else {
        echo '<div class="table-responsive">';
        echo '<table class="table table-striped">';
        echo '<thead><tr><th>Course</th><th>Date</th><th>Time</th><th>Action</th></tr></thead>';
        echo '<tbody>';
        
        foreach ($sections as $section) {
            // Check if session has existing request
            $has_request = !empty($section->existing_request_id);
            $request_status = $has_request ? $section->request_status : null;
            
            $row_class = '';
            $action_button = '';
            
            if ($has_request) {
                if ($request_status == 0) { // Pending
                    $row_class = 'table-warning';
                    $action_button = '<span class="badge badge-warning">Pending Request</span>';
                } else if ($request_status == 1) { // Approved
                    $row_class = 'table-success';
                    $action_button = '<span class="badge badge-success">Approved Request</span>';
                }
            } else {
                $action_button = '<button class="btn btn-sm btn-primary" onclick="selectSession(' . $section->id . ', \'' . addslashes($section->fullname . ' - ' . date('Y-m-d H:i', $section->class_begin_time)) . '\')">Select</button>';
            }
            
            echo '<tr class="' . $row_class . '">';
            echo '<td>' . s($section->fullname) . '</td>';
            echo '<td>' . date('Y-m-d', $section->class_begin_time) . '</td>';
            echo '<td>' . date('H:i', $section->class_begin_time) . ' - ' . date('H:i', $section->class_end_time) . '</td>';
            echo '<td>' . $action_button . '</td>';
            echo '</tr>';
        }
        
        echo '</tbody>';
        echo '</table>';
        echo '</div>';
    }
    
    echo '</div>';
    echo '</div>';
}


// Absence Request Form
echo '<div class="card mt-4">';
echo '<div class="card-header">';
echo '<h5 class="mb-0">Submit Absence Request</h5>';
echo '</div>';
echo '<div class="card-body">';

// Form thêm yêu cầu nghỉ
echo '<form method="post" action="" class="mform">';
echo '<input type="hidden" name="sesskey" value="' . sesskey() . '">';

// Hidden field for selected section
echo '<input type="hidden" name="section_id" id="selected_section_id" value="">';

// Display selected session
echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break">Selected Session <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<div id="selected_session_display" class="alert alert-info" style="display: none;"></div>';
echo '<p id="no_selection" class="text-muted">Please select a session from the calendar or list above.</p>';
echo '</div>';
echo '</div>';

echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0">';
echo '<label class="d-inline word-break" for="id_reason">Reason for absence <span class="text-danger">*</span></label>';
echo '</div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<textarea name="reason" id="id_reason" class="form-control" rows="4" cols="50" required>';
echo isset($_POST['reason']) ? s($_POST['reason']) : '';
echo '</textarea>';
echo '</div>';
echo '</div>';

echo '<div class="form-group row fitem">';
echo '<div class="col-md-3 col-form-label d-flex pb-0 pr-md-0"></div>';
echo '<div class="col-md-9 form-inline align-items-start felement">';
echo '<input type="submit" class="btn btn-primary" value="Submit Request" id="submit_btn" disabled>';
echo ' <a href="' . new moodle_url('/local/course_calendar/pages/my_absence_requests.php') . '" class="btn btn-secondary ml-2">Cancel</a>';
echo '</div>';
echo '</div>';

echo '</form>';
echo '</div>';
echo '</div>';

// JavaScript for session selection
echo '<script>
function selectSession(sectionId, sessionLabel) {
    document.getElementById("selected_section_id").value = sectionId;
    document.getElementById("selected_session_display").innerHTML = sessionLabel;
    document.getElementById("selected_session_display").style.display = "block";
    document.getElementById("no_selection").style.display = "none";
    document.getElementById("submit_btn").disabled = false;
    
    // Remove previous selections (reset all to original style)
    var prevSelected = document.querySelectorAll(".session-item");
    prevSelected.forEach(function(el) {
        el.classList.remove("session-selected");
        el.style.background = "#e3f2fd";
        el.style.color = "inherit";
    });
    
    // Find the correct session item by data-section-id
    var currentSession = document.querySelector(".session-item[data-section-id=\"" + sectionId + "\"]");
    if (currentSession) {
        currentSession.classList.add("session-selected");
        currentSession.style.background = "#4caf50";
        currentSession.style.color = "white";
    }
}
</script>';

echo $OUTPUT->footer();
