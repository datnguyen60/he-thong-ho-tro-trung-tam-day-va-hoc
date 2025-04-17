<?php
namespace local_course_schedule\form;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/formslib.php");

class schedule_form extends \moodleform {
    public function definition() {
        $mform = $this->_form;

        // Course selection (optional, nếu chỉ dùng trong 1 course thì bỏ)
        $mform->addElement('select', 'courseid', get_string('course'), $this->get_courses());
        $mform->setType('courseid', PARAM_INT);
        $mform->addRule('courseid', null, 'required');

        // Schedule title
        $mform->addElement('text', 'title', get_string('title', 'local_course_schedule'));
        $mform->setType('title', PARAM_TEXT);
        $mform->addRule('title', null, 'required');

        // Start date
        $mform->addElement('date_selector', 'startdate', get_string('startdate', 'local_course_schedule'));

        // Start time
        $mform->addElement('time_selector', 'starttime', get_string('starttime', 'local_course_schedule'));

        // Duration in minutes
        $mform->addElement('text', 'duration', get_string('duration', 'local_course_schedule'));
        $mform->setType('duration', PARAM_INT);

        // Days of week
        $days = [
            'mon' => get_string('monday', 'local_course_schedule'),
            'tue' => get_string('tuesday', 'local_course_schedule'),
            'wed' => get_string('wednesday', 'local_course_schedule'),
            'thu' => get_string('thursday', 'local_course_schedule'),
            'fri' => get_string('friday', 'local_course_schedule'),
            'sat' => get_string('saturday', 'local_course_schedule'),
            'sun' => get_string('sunday', 'local_course_schedule'),
        ];
        $mform->addElement('checkboxes', 'days', get_string('repeatdays', 'local_course_schedule'), $days);

        // End date
        $mform->addElement('date_selector', 'enddate', get_string('enddate', 'local_course_schedule'));

        // Submit
        $this->add_action_buttons(true, get_string('createschedule', 'local_course_schedule'));
    }

    private function get_courses() {
        global $DB;
        $courses = $DB->get_records_menu('course', null, 'fullname', 'id, fullname');
        return $courses;
    }
}
