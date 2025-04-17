<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');
require_once($CFG->dirroot . '/calendar/externallib.php');
require_login();
global $DB;

$COURSE_ID = 2;

if (!$DB->record_exists('course', ['id' => $COURSE_ID])) {
    throw new moodle_exception("Course không tồn tại");
}
try{
    $events = core_calendar_external::get_calendar_events();
    dlog($events);

    // $newevent = calendar_event::create($event);
    // dlog("Đã tạo event với ID: {$newevent->id}");

    // echo "✅ Tạo event thành công: {$newevent->name}";
} catch (moodle_exception $e) {
    dlog("Lỗi ".$e->getTraceAsString());
    throw $e;
}
