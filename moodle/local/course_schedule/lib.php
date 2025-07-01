<?php

function local_course_schedule_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('moodle/course:update', $context)) {
        $url = new moodle_url('/local/course_schedule/index.php', ['id' => $course->id]);
        $navigation->add(
            get_string('manageschedule', 'local_course_schedule'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/calendar', '')
        );
    }
}