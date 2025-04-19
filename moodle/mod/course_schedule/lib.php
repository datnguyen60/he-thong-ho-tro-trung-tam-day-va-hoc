<?php
defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/local/dlog/lib.php');

function mod_course_schedule_calendar_event_hook(MoodleQuickForm $form, \calendar_event $event = null) {
    dlog("hehe");
    $eventtype = $form->getElement('eventtype');
    if ($eventtype) {
        $options = $eventtype->getOptions();
        if (!isset($options['external'])) {
            $options['external'] = get_string('eventtype_external', 'mod_course_schedule');
            $eventtype->load($options);
        }
    }
}
