<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/children_management/classes/form/children_form.php');
require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context); // Chỉnh sửa tùy quyền phù hợp
$PAGE->set_url(new moodle_url('/local/children_management/index.php'));
$PAGE->set_context($context);
$PAGE->set_title('Quản lý trẻ em');
$PAGE->set_heading('Quản lý trẻ em');
$mform = new \local_children_management\form\children_form();
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