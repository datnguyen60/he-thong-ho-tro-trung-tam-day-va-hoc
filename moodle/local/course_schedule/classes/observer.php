<?php

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/dlog/lib.php');
class local_course_schedule_observer {

    public static function course_created(\core\event\course_created $event) {
        global $DB, $USER;
        dlog('course_created');

        $courseid = $event->objectid;
        $userid = $USER->id;

        // Lưu vào bảng course_creators (bạn phải tạo bảng này nhé).
        $record = new stdClass();
        $record->courseid = $courseid;
        $record->userid = $userid;
        $record->timecreated = time();

        $DB->insert_record('course_creator', $record);
    }
}
