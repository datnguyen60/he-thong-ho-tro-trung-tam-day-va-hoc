<?php
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/calendar/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');

require_login();
global $DB;

// Ghi log
dlog("Bắt đầu tạo event");

$COURSE_ID = 6;

if (!$DB->record_exists('course', ['id' => $COURSE_ID])) {
    throw new moodle_exception("Course không tồn tại");
}
try{
    $event = new stdClass();
    $event->name         = 'Buổi học thứ 3';
    $event->description  = 'Buổi học về chủ đề Arrays';
    $event->format       = 1;
    $event->courseid     = $COURSE_ID;
    $event->groupid      = 0;
    $event->userid       = 0;
    $event->repeatid     = 0;
    $event->modulename   = '';
    $event->instance     = 0;
    $event->eventtype    = 'course_schedule';
    $event->timestart    = strtotime('2025-04-18 09:00:00');
    $event->timeduration = 3600;
    $event->timesort     = $event->timestart;
    $event->visible      = 1;
    $event->uuid         = \core\uuid::generate();
    $event->sequence     = 1;
    $event->timemodified = time();
    $event->location     = ''; // optional, để null cũng được
    $event->priority     = 0;  // optional
    $event->subscriptionid = null; // optional
    $event->categoryid   = 0;  // nếu không dùng danh mục

    $eventid = $DB->insert_record('event', $event);
    dlog("✅ Đã insert thành công, event ID = $eventid");


    // $newevent = calendar_event::create($event);
    // dlog("Đã tạo event với ID: {$newevent->id}");

    // echo "✅ Tạo event thành công: {$newevent->name}";
} catch (moodle_exception $e) {
    dlog("Lỗi ".$e->getTraceAsString());
    throw $e;
}
