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
 * TODO describe file index
 *
 * @package    local_children_management
 * @copyright  2025 Võ Mai Phương <vomaiphuonghhvt@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../config.php');
require_once($CFG->dirroot . '/local/children_management/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');

try {
    $id = required_param('id', PARAM_INT);
    $user = $DB->get_record('user', ['id' => $id], '*', MUST_EXIST);
    require_login($user);

    $url = new moodle_url('/local/children_management/index.php', []);
    $PAGE->set_url($url);
    $PAGE->set_context(context_system::instance());
    $PAGE->set_title(get_string('children_management_title', 'local_children_management'));
    $PAGE->set_heading(get_string('children_management_heading', 'local_children_management'));
    echo $OUTPUT->header();

    $parentid = $user->id;
    $sql = "SELECT student.*
            FROM {children_and_parent_information} student
            WHERE student.parentid = :parentid";
    $students = $DB->get_records_sql($sql, ['parentid' => $parentid]);

    if (!$students) {
        echo $OUTPUT->notification(get_string('no_children_found', 'local_children_management'), 'info');
    } else {
        echo html_writer::start_tag('div', ['class' => 'children-list']);
        echo html_writer::tag('h3', get_string('children_list', 'local_children_management'));

        $table = new html_table();
        $table->head = [
            get_string('id', 'local_children_management'),
            get_string('fullname', 'local_children_management'),
            get_string('email', 'local_children_management'),
            get_string('actions', 'local_children_management'),
        ];
        $table->align = ['center', 'left', 'left', 'center'];
        foreach ($students as $student) {
            $profileurl = new moodle_url('/user/profile.php', ['id' => $student->id]);
            // You might want to add a link to student's course overview etc.
            $actions = html_writer::link($profileurl, get_string('view_profile', 'local_children_management'));
            // Add more links here, e.g., link to student's courses (more complex)

            $table->data[] = [
                $student->id,
                format_string($student->fullname),
                $student->email,
                $actions,
            ];
        }
        echo html_writer::table($table);
        echo html_writer::end_tag('div');
    }

    // Add a button to add a new child.
    $addchildurl = new moodle_url('/local/children_management/add_child.php');
    echo $OUTPUT->single_button($addchildurl, get_string('add_child', 'local_children_management'), 'get', ['class' => 'btn btn-primary mt-3']);

    // Add a button to manage children.
    $managechildrenurl = new moodle_url('/local/children_management/manage_children.php');
    echo $OUTPUT->single_button($managechildrenurl, get_string('manage_children', 'local_children_management'), 'get', ['class' => 'btn btn-secondary mt-3']);

    // Add a button to view parent information.
    $parentinfo = new moodle_url('/local/children_management/parent_info.php');
    echo $OUTPUT->single_button($parentinfo, get_string('view_parent_info', 'local_children_management'), 'get', ['class' => 'btn btn-info mt-3']);

    // Add a button to view all children.
    $viewallchildren = new moodle_url('/local/children_management/view_all_children.php');
    echo $OUTPUT->single_button($viewallchildren, get_string('view_all_children', 'local_children_management'), 'get', ['class' => 'btn btn-success mt-3']);

    // Add a button to view parent-child relationships.
    $viewrelationships = new moodle_url('/local/children_management/view_relationships.php');
    echo $OUTPUT->single_button($viewrelationships, get_string('view_relationships', 'local_children_management'), 'get', ['class' => 'btn btn-warning mt-3']);

    // Add a button to view child schedules.
    $viewschedules = new moodle_url('/local/children_management/view_schedules.php');
    echo $OUTPUT->single_button($viewschedules, get_string('view_child_schedules', 'local_children_management'), 'get', ['class' => 'btn btn-info mt-3']);

    // Add a button to view child grades.
    $viewgrades = new moodle_url('/local/children_management/view_grades.php');
    echo $OUTPUT->single_button($viewgrades, get_string('view_child_grades', 'local_children_management'), 'get', ['class' => 'btn btn-secondary mt-3']);

    // Add a button to view child attendance.
    $viewattendance = new moodle_url('/local/children_management/view_attendance.php');
    echo $OUTPUT->single_button($viewattendance, get_string('view_child_attendance', 'local_children_management'), 'get', ['class' => 'btn btn-primary mt-3']);

    // Add a button to view child behavior records.
    $viewbehavior = new moodle_url('/local/children_management/view_behavior.php');
    echo $OUTPUT->single_button($viewbehavior, get_string('view_child_behavior', 'local_children_management'), 'get', ['class' => 'btn btn-danger mt-3']);

    // Add a button to view child health records.
    $viewhealth = new moodle_url('/local/children_management/view_health.php');
    echo $OUTPUT->single_button($viewhealth, get_string('view_child_health', 'local_children_management'), 'get', ['class' => 'btn btn-success mt-3']);

    // Add a button to view child extracurricular activities.
    $viewactivities = new moodle_url('/local/children_management/view_activities.php');
    echo $OUTPUT->single_button($viewactivities, get_string('view_child_activities', 'local_children_management'), 'get', ['class' => 'btn btn-info mt-3']);

    // Add a button to view child academic performance.
    $viewperformance = new moodle_url('/local/children_management/view_performance.php');
    echo $OUTPUT->single_button($viewperformance, get_string('view_child_performance', 'local_children_management'), 'get', ['class' => 'btn btn-secondary mt-3']);

    // Add a button to view child feedback.
    $viewfeedback = new moodle_url('/local/children_management/view_feedback.php');
    echo $OUTPUT->single_button($viewfeedback, get_string('view_child_feedback', 'local_children_management'), 'get', ['class' => 'btn btn-warning mt-3']);

    // Add a button to view child reports.
    $viewreports = new moodle_url('/local/children_management/view_reports.php');
    echo $OUTPUT->single_button($viewreports, get_string('view_child_reports', 'local_children_management'), 'get', ['class' => 'btn btn-dark mt-3']);

    // Add a button to view child notifications.
    $viewnotifications = new moodle_url('/local/children_management/view_notifications.php');
    echo $OUTPUT->single_button($viewnotifications, get_string('view_child_notifications', 'local_children_management'), 'get', ['class' => 'btn btn-light mt-3']);

    // Add a button to view child events.
    $viewevents = new moodle_url('/local/children_management/view_events.php');
    echo $OUTPUT->single_button($viewevents, get_string('view_child_events', 'local_children_management'), 'get', ['class' => 'btn btn-primary mt-3']);

    // Add a button to view child assignments.
    $viewassignments = new moodle_url('/local/children_management/view_assignments.php');
    echo $OUTPUT->single_button($viewassignments, get_string('view_child_assignments', 'local_children_management'), 'get', ['class' => 'btn btn-secondary mt-3']);

    // Add a button to view child projects.
    $viewprojects = new moodle_url('/local/children_management/view_projects.php');
    echo $OUTPUT->single_button($viewprojects, get_string('view_child_projects', 'local_children_management'), 'get', ['class' => 'btn btn-success mt-3']);

    // Add a button to view child resources.
    $viewresources = new moodle_url('/local/children_management/view_resources.php');
    echo $OUTPUT->single_button($viewresources, get_string('view_child_resources', 'local_children_management'), 'get', ['class' => 'btn btn-info mt-3']);

    echo $OUTPUT->footer();
} catch (Exception $e) {
    dlog($e->getTrace());
}
