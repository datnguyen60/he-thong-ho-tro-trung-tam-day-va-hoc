<?php
namespace local_course_schedule\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/externallib.php');

use core_calendar\external\event_exporter;
use core_calendar\local\event\container as event_container;
use external_api;
use external_function_parameters;
use external_value;
use context_user;
use context_system;
use moodle_exception;
use required_capability_exception;

class calendar extends external_api {
    public static function get_calendar_event_by_id_parameters() {
        return new external_function_parameters([
            'eventid' => new external_value(PARAM_INT, 'Event ID')
        ]);
    }

    public static function get_calendar_event_by_id($eventid) {
        global $PAGE, $USER;

        $params = self::validate_parameters(self::get_calendar_event_by_id_parameters(), ['eventid' => $eventid]);
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        $eventvault = event_container::get_event_vault();
        $event = $eventvault->get_event_by_id($params['eventid']);

        if (!$event) {
            $syscontext = context_system::instance();
            throw new required_capability_exception($syscontext, 'moodle/course:view', 'nopermissions', 'error');
        }

        $mapper = event_container::get_event_mapper();
        if (!calendar_view_event_allowed($mapper->from_event_to_legacy_event($event))) {
            throw new moodle_exception('nopermissiontoviewcalendar', 'error');
        }

        $cache = new \core_calendar\external\events_related_objects_cache([$event]);
        $relatedobjects = [
            'context' => $cache->get_context($event),
            'course'  => $cache->get_course($event),
        ];

        $exporter = new event_exporter($event, $relatedobjects);
        $renderer = $PAGE->get_renderer('core_calendar');
        $data = $exporter->export($renderer);
        dlog("hello baby");
        // ✅ Gắn thêm label nếu eventtype là "course_schedule"
        if ($event->get('eventtype') === 'course_schedule') {
            $data->label = 'Thời khóa biểu';
        }

        return ['event' => $data, 'warnings' => []];
    }

    public static function get_calendar_event_by_id_returns() {
        return new \external_single_structure([
            'event' => event_exporter::get_read_structure(),
            'warnings' => new \external_warnings(),
        ]);
    }
}
