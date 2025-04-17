<?php
namespace theme_photo\output;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/calendar/renderer.php');

// class core_calendar_renderer extends \core_calendar_renderer {
//     public function render_event_item(\core_calendar\local\event\entities\event_interface $event) {
//         $data = $event->export_for_template($this->page->get_renderer('core_calendar'));
//         return $this->render_from_template('core_calendar/add_event_button', $data);
//     }
// }
