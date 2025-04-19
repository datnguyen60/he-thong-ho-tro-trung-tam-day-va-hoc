<?php

defined('MOODLE_INTERNAL') || die();

$observers = [
    [
        'eventname'   => '\core\event\course_created',
        'callback'    => 'local_course_schedule_observer::course_created',
        'includefile' => '/local/course_schedule/classes/observer.php',
        'internal'    => false,
        'priority'    => 9999,
    ],
];
