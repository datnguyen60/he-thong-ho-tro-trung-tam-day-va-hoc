<?php
// defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/dlog/lib.php');
require_once($CFG->dirroot . '/calendar/lib.php');  // Bao gồm thư viện calendar
require_once(__DIR__ . '/../../config.php');

dlog("load");

function local_course_schedule_before_footer(){

}