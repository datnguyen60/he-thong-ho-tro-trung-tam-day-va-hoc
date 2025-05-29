<?php
require('../../config.php');

$id = required_param('id', PARAM_INT);
$courseid = required_param('courseid', PARAM_INT); // id của course

$schedule = $DB->get_record('course_schedule', ['id' => $id], '*', MUST_EXIST);

if (!$courseid && !empty($schedule->sectionid)) {
    $courseid = $DB->get_field('course_sections', 'course', ['id' => $schedule->sectionid]);
}
if ($courseid) {
    $course = get_course($courseid);
    require_login($course);
    $context = context_course::instance($courseid);
    require_capability('moodle/course:update', $context);
} else {
    require_login();
    require_capability('moodle/site:config', context_system::instance());
}

// Đánh dấu iscancel = 1
$DB->set_field('course_schedule', 'iscancel', 1, ['id' => $id]);

redirect(
    new moodle_url('/local/course_schedule/index.php', ['id' => $courseid]),
    'Đã hủy buổi học thành công!',
    2
);