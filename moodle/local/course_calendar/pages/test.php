<?php
// Debug đường dẫn
echo "Current directory: " . __DIR__ . "<br>";
echo "Config path: " . __DIR__ . '/../../../config.php' . "<br>";
echo "Config exists: " . (file_exists(__DIR__ . '/../../../config.php') ? 'YES' : 'NO') . "<br>";

require_once(__DIR__ . '/../../../config.php');
require_login();

$context = context_system::instance();
$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/course_calendar/pages/test.php'));
$PAGE->set_title('Test Page');
$PAGE->set_heading('Test Page');

echo $OUTPUT->header();
echo $OUTPUT->heading('Test Page - Working!');

echo '<p>User ID: ' . $USER->id . '</p>';
echo '<p>Current time: ' . date('Y-m-d H:i:s') . '</p>';

// Test xem có table absence_request không
global $DB;
try {
    $count = $DB->count_records('local_course_calendar_absence_request');
    echo '<p>Absence requests table exists with ' . $count . ' records</p>';
} catch (Exception $e) {
    echo '<p>Absence requests table does not exist: ' . $e->getMessage() . '</p>';
}

echo $OUTPUT->footer();
