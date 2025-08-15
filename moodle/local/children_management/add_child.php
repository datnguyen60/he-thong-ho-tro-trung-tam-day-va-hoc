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
require_once($CFG->dirroot .'/local/children_management/classes/form/add_child_form.php');

try {
    require_login();
    require_capability('local/children_management:edit', context_system::instance());
    
    global $CFG, $USER, $DB, $PAGE;
    
    $PAGE->set_url(new moodle_url('/local/children_management/add_child.php', []));
    $PAGE->set_context(context_system::instance());
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('children_management_title', 'local_children_management'));
    $PAGE->set_heading(get_string('children_management_add_child_form_heading', 'local_children_management'));
    $PAGE->requires->css('/local/children_management/style/style.css');
    
    
    // Instantiate the myform form from within the plugin.
    $mform = new \local_children_management\form\add_child_form();
    $toform = '';
    
    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.
        redirect( new moodle_url('/local/children_management/index.php'));
    } else if ($fromform = $mform->get_data()) {
        // When the form is submitted, and the data is successfully validated,
        // the `get_data()` function will return the data posted in the form.
        
        // find children information from user table in system.
        // if have student information then insert information to children_and_parent_information table
        // if not information about student then return add new child fail because don't have this account in system
        
        // Check and search student information 
        // prepare search condition
        $studentid_search_query = trim($fromform->studentid);
        $username_search_query = trim($fromform->username);
        $firstname_search_query = trim($fromform->firstname);
        $lastname_search_query = trim($fromform->lastname);
        $email_search_query = trim($fromform->email);
        $phone_search_query = trim($fromform->phone);
        
        $params = [
            'searchparamid' => $studentid_search_query
        ];

        // Process the search query.
        $sql = "SELECT user.id,
                    user.username,
                    user.firstname,
                    user.lastname,
                    user.email,
                    user.phone1,
                    user.phone2
            FROM {user} user
            WHERE   (
                        user.id = :searchparamid
                    )";

        $students = $DB->get_record_sql($sql, $params);
        
        if (!$students) {
            redirect(new moodle_url('/local/children_management/add_child.php', []), 'error: This student with student ID: ' . $studentid_search_query . ' was not found.', 0, \core\output\notification::NOTIFY_ERROR);
        } else {
            
            // If have information about student. Add this information to children_and_parent_information table
            $data = new stdClass();
            $data->parentid = $USER->id;
            $data->childrenid = $fromform->studentid;
            $data->createtime = time();
            $data->lastmodifytime = time();
    
            if ($DB->insert_record('children_and_parent_information', $data)) {
                redirect(new moodle_url('/local/children_management/index.php', []), 'Add new children with children ID: '. $studentid_search_query .' successfully', 0, \core\output\notification::NOTIFY_SUCCESS);

            } else {
                redirect(new moodle_url('/local/children_management/add_child.php', []), 'Error: Add new children with children ID: '. $studentid_search_query .' failed', 0, \core\output\notification::NOTIFY_ERROR);
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
    $PAGE->requires->js_call_amd('local_children_management/otp_handler', 'init', [], null, true); // true để đảm bảo nó được thêm vào cuối body

    echo $OUTPUT->footer();
    
} catch (Exception $e) {
    echo "<pre>";
        var_dump($e->getTrace());
    echo "</pre>";
    
    throw new \moodle_exception('error', 'local_children_management', '', null, $e->getMessage());
}
