<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/course_schedule/classes/form/schedule_form.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context); // Chỉnh sửa tùy quyền phù hợp

$PAGE->set_url(new moodle_url('/local/course_schedule/index.php'));
$PAGE->set_context($context);
$PAGE->set_title('Tạo thời khóa biểu');
$PAGE->set_heading('Tạo thời khóa biểu');

$mform = new \local_course_schedule\form\schedule_form();

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/my'));
} else if ($data = $mform->get_data()) {
    // Tạm thời, just debug
    echo $OUTPUT->header();
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    echo $OUTPUT->footer();
    die;
}

echo $OUTPUT->header();
$mform->display();
echo $OUTPUT->footer();
