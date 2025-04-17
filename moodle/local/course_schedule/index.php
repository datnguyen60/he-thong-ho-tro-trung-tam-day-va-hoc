<?php
require('../../config.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context); // Chỉnh sửa tùy quyền phù hợp

$PAGE->set_url(new moodle_url('/local/course_schedule/index.php'));
$PAGE->set_context($context);
$PAGE->set_title('Tạo thời khóa biểu');
$PAGE->set_heading('Tạo thời khóa biểu');


