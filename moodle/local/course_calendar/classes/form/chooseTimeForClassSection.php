<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.
namespace local_course_calendar\form;

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");
require_once($CFG->dirroot . '/local/course_calendar/lib.php');

class chooseTimeForClassSection extends \moodleform
{
    /**
     * Elements of the test form.
     */
    public function definition()
    {
        $duration = 90 * 60;
        $mform = $this->_form;

        // Get the courseid from the URL.
        $courses = optional_param('selected_courses', null, PARAM_INT);

        // Add a hidden field to store the courseid.

        $mform->addElement('hidden', 'selected_courses', $courses);
        $mform->setType('selected_courses', PARAM_INT);

        // Get the teachers from the URL.
        $teachers = optional_param_array('selected_teachers', [], PARAM_INT);

        // Add a hidden field to store the courseid.
        foreach ($teachers as $teacher) {
            $mform->addElement('hidden', 'selected_teachers[]', $teacher);
            $mform->setType('selected_teachers[]', PARAM_INT);
        }

        // Get the search query from the URL parameters.
        $search_query = optional_param('searchquery', '', PARAM_TEXT);
        $mform->addElement('hidden', 'searchquery', $search_query);
        $mform->setType('searchquery', PARAM_TEXT);

        $mform->addElement('date_time_selector', 'starttime', 'Thời gian bắt đầu');
        $mform->setDefault('starttime', time()); // Mặc định là thời gian hiện tại
        $mform->addRule('starttime', get_string('required', 'moodle'), 'required', null, 'client');

        // Thời gian kết thúc (End Date and Time)
        $mform->addElement('date_time_selector', 'endtime', 'Thời gian kết thúc');
        // Mặc định là 1 giờ sau thời gian bắt đầu (hoặc bất kỳ khoảng thời gian hợp lý nào)
        $mform->setDefault('endtime', time() + $duration); // HOURSECS = 3600 giây (1 giờ)
        $mform->addRule('endtime', get_string('required', 'moodle'), 'required', null, 'client');

        $this->add_action_buttons(false, get_string('findroom', 'local_course_calendar'));
    }

    public function validation($data, $files)
    {
        $class_duration = 90 * 60;
        $time_slot = 45 * 60;
        $errors = parent::validation($data, $files);
        if (isset($data['starttime']) && isset($data['endtime'])) {
            if ($data['endtime'] < $data['starttime']) {
                $errors['endtime'] = get_string('endtimemustbeafterstarttime', 'local_course_calendar');
            }

            if ($data['endtime'] - $data['starttime'] < $time_slot) {
                $errors['endtime'] = get_string('classdurationmustbegreaterthantimeslot', 'local_course_calendar');
            }
        }

        return $errors;
    }
}