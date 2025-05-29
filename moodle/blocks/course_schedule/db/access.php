<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'block/course_schedule:addinstance' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_BLOCK,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ],
    ],
];
