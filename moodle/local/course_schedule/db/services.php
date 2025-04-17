<?php
$functions = array(
    'local_course_schedule_get_calendar_event_by_id' => array(
        'classname'   => 'local_course_schedule\external\calendar',
        'methodname'  => 'get_calendar_event_by_id',
        'description' => 'Custom get calendar event by id (override core)',
        'type'        => 'read',
        'ajax'        => true,
    ),
);