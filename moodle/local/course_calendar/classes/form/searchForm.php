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

class searchForm extends \moodleform
{
    /**
     * Elements of the test form.
     */
    public function definition()
    {
        $mform = $this->_form;
        $required = optional_param('required', false, PARAM_BOOL);
        $help = optional_param('help', false, PARAM_BOOL);
        $mixed = optional_param('mixed', false, PARAM_BOOL);


    }

    public function validation($data, $files)
    {

    }
}