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

/**
 * Version information for local_course_calendar
 *
 * @package    local_course_calendar
 * @copyright  2025 Võ Mai Phương <vomaiphuonghhvt@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


require('../../config.php');
require_once($CFG->dirroot . '/local/course_calendar/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');
require_once($CFG->dirroot . '/local/course_calendar/classes/form/edit_total_lesson_for_course.php');

try {
    require_login();

    $courseid = optional_param('courseid', 1, PARAM_INT);
    if ($courseid <= 1) {
        throw new \moodle_exception('invalidcourseid ' . $courseid, 'local_course_calendar');
    }

    require_capability('local/course_calendar:edit_total_lesson_for_course', context_course::instance($courseid));
    $PAGE->set_context(context_course::instance($courseid));
    $PAGE->set_url(new moodle_url('/local/course_calendar/edit_total_lesson_for_course.php', ['courseid' => $courseid]));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('edit_total_lesson_for_course', 'local_course_calendar'));
    $PAGE->set_heading(get_string('edit_total_lesson_for_course', 'local_course_calendar'));
    $PAGE->requires->css('/local/course_calendar/style/style.css');

    // Instantiate the myform form from within the plugin.
    $mform = new \local_course_calendar\form\edit_total_lesson_for_course_form();
    $toform = '';

    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.
        redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Cancelled edit total lesson information for course.', 0, \core\output\notification::NOTIFY_ERROR);
    } else if ($fromform = $mform->get_data()) {

        global $PAGE, $OUTPUT, $DB, $USER;
        $courseid = $fromform->courseid;

        if ($courseid <= 1) {
            throw new \moodle_exception('invalidcourseid ' . $courseid, 'local_course_calendar');
        }

        // When the form is submitted, and the data is successfully validated,
        // the `get_data()` function will return the data posted in the form.

        // find children information from user table in system.
        // if have student information then insert information to children_and_parent_information table
        // if not information about student then return add new child fail because don't have this account in system

        // Check and search student information 
        // prepare search condition
        $total_lesson_for_course = $fromform->total_lesson_for_course;
        $total_section_for_course = $fromform->total_section_for_course;
        $total_chapter_for_course = $fromform->total_chapter_for_course;
        $courseid_search_query = $courseid;

        $params = [
            'search_param_courseid' => $courseid_search_query
        ];

        // Process the search query.
        $sql = "SELECT c.id
            FROM {course} c
            WHERE   (
                        c.id = :search_param_courseid
                    )";

        $course = $DB->get_record_sql($sql, $params);

        if (!$course) {

            redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Error: This course with course ID: ' . $courseid_search_query . ' was not found.', 0, \core\output\notification::NOTIFY_ERROR);
        } else {

            // If have information about student. Add this information to children_and_parent_information table
            $data = new stdClass();
            $data->courseid = $courseid;
            $data->total_course_section = $total_section_for_course;
            $data->total_course_lesson = $total_lesson_for_course;
            $data->total_course_chapter = $total_chapter_for_course;
            $data->created_user_id = $USER->id;
            $data->modified_user_id = $USER->id;
            $data->createtime = time();
            $data->lastmodifytime = time();

            if ($DB->insert_record('local_course_calendar_total_course_lesson', $data)) {
                redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Add new total lesson number for course with course ID: ' . $courseid_search_query . ' successfully', 0, \core\output\notification::NOTIFY_SUCCESS);

            } else {
                redirect(new moodle_url('/course/view.php', ['id' => $courseid]), 'Error: Add new total lesson number for course with course ID: ' . $courseid_search_query . ' failed', 0, \core\output\notification::NOTIFY_ERROR);
            }
        }

    } else {
        // This branch is executed if the form is submitted but the data doesn't
        // validate and the form should be redisplayed or on the first display of the form.

        // Set anydefault data (if any).
        $mform->set_data($toform);

        // Display the form.
    }

    echo $OUTPUT->header();

    $mform->display();

    echo $OUTPUT->footer();

} catch (Exception $e) {
    echo "<pre>";
    var_dump($e->getTrace());
    echo "</pre>";

    throw new \moodle_exception('error', 'local_course_calendar', '', null, $e->getMessage());
}
