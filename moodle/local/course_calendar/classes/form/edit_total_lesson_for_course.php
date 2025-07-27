<?php

namespace local_course_calendar\form;

// moodleform is defined in formslib.php
require_once("$CFG->libdir/formslib.php");

class edit_total_lesson_for_course_form extends \moodleform {
    // Add elements to form.
    public function definition() {
        // A reference to the form is stored in $this->form.
        // A common convention is to store it in a variable, such as `$mform`.
        $mform = $this->_form; // Don't forget the underscore!

        // Get the courseid from the URL.
        $courseid = optional_param('courseid', 0, PARAM_INT);

        // Add a hidden field to store the courseid.
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);
        // Add elements to your form.
        $mform->addElement('text', 'total_lesson_for_course', get_string('total_lesson_for_course', 'local_course_calendar'));

        // Set type of element.
        $mform->setType('total_lesson_for_course', PARAM_INT);
        $mform->addRule('total_lesson_for_course', 'Total lesson number for course is missing.','required', null, 'client');

        // Add elements to your form.
        $mform->addElement('text', 'total_section_for_course', get_string('total_section_for_course', 'local_course_calendar'));

        // Set type of element.
        $mform->setType('total_section_for_course', PARAM_INT);
        $mform->addRule('total_section_for_course', 'Total section number for course is missing.','required', null, 'client');
        
        // Add elements to your form.
        $mform->addElement('text', 'total_chapter_for_course', get_string('total_chapter_for_course', 'local_course_calendar'));

        // Set type of element.
        $mform->setType('total_chapter_for_course', PARAM_INT);
        $mform->addRule('total_chapter_for_course', 'Total chapter number for course is missing.','required', null, 'client');
        
        $this->add_action_buttons();
        
    }

    // Custom validation should be added here.
    function validation($data, $files) {
        return [];
    }
}
