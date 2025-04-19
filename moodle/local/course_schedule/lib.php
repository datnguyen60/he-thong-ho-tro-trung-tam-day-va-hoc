<?php
// defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/dlog/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');  // Bao gồm thư viện calendar
require_once(__DIR__ . '/../../config.php');

function local_course_schedule_extend_navigation_course(navigation_node $navigation, stdClass $course, context_course $context) {
    // if (has_capability('moodle/course:view', $context)) {

        $url = new moodle_url('/local/course_schedule/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('pluginname', 'local_course_schedule'), // Tên menu
            $url,
            navigation_node::TYPE_CUSTOM,
            null,
            'course_schedule',
            new pix_icon('i/calendar', '')
        );
    // }
}