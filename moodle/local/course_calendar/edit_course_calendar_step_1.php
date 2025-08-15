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

require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/course_calendar/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');

try {
    // Yêu cầu người dùng đăng nhập
    require_login();
    require_capability('local/course_calendar:edit', context_system::instance()); // Kiểm tra quyền truy cập
    $PAGE->requires->css('/local/course_calendar/style/style.css');
    $PAGE->requires->js('/local/course_calendar/js/lib.js');
    $selected_courses_from_request = optional_param('selected_courses', null, PARAM_INT);
    $per_page = optional_param('perpage', 20, PARAM_INT);
    $current_page = optional_param('page', 0, PARAM_INT);


    // Khai báo các biến toàn cục
    global $PAGE, $OUTPUT, $DB, $USER;

    // $context = context_course::instance(SITEID); // Lấy ngữ cảnh của trang hệ thống
    $context = context_system::instance(); // Lấy ngữ cảnh của trang hệ thống
    // Đặt ngữ cảnh trang
    $PAGE->set_context($context);

    // Thiết lập trang Moodle
    // Đặt URL cho trang hiện tại
    $PAGE->set_url(new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', []));
    // Tiêu đề trang
    $PAGE->set_title(get_string('teaching_schedule_assignment_choose_course', 'local_course_calendar'));
    $PAGE->set_heading(get_string('teaching_schedule_assignment_choose_course', 'local_course_calendar'));

    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('course_calendar_title', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));


    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('teaching_schedule_assignment', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));

    // Thêm breadcrumb cho trang hiện tại
    $PAGE->navbar->add(get_string('teaching_schedule_assignment_choose_course', 'local_course_calendar'));

    // // add menu item to the settings navigation.
    // $settingsnav = $PAGE->settingsnav;
    // if (has_capability('local/course_calendar:edit', context_system::instance())) {
    //     if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
    //         $strfoo = get_string('edit_total_lesson_for_course', 'local_course_calendar');
    //         $url = new moodle_url('/local/course_calendar/edit_total_lesson_for_course.php', array('courseid' => $PAGE->course->id));
    //         $foonode = navigation_node::create(
    //             $strfoo,
    //             $url,
    //             navigation_node::NODETYPE_LEAF,
    //             get_string('edit_total_lesson_for_course', 'local_course_calendar'),
    //             'edit_total_lesson_for_course',
    //             new pix_icon('i/edit', $strfoo)
    //         );
    //         if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
    //             $foonode->make_active();
    //         }
    //         $settingnode->add_node($foonode);
    //     }
    // }

    echo $OUTPUT->header();

    // Nội dung trang của bạn
    echo $OUTPUT->box_start();
    $search_context = new stdClass();
    $search_context->method = 'get';
    $search_context->action = $PAGE->url; // Action URL for the search form
    $search_context->inputname = 'searchquery';
    $search_context->searchstring = get_string('searchitems', 'local_course_calendar'); // Placeholder text for the search input

    $search_query = optional_param('searchquery', '', PARAM_TEXT); // Get the search query from the URL parameters.
    $current_params = [];
    $current_params[] = ['name' => 'selected_courses', 'value' => optional_param('selected_courses', null, PARAM_INT)];
    $search_context->hiddenfields = $current_params;

    $search_context->value = $search_query; // Set the value of the search input to the current search query.
    $search_context->extraclasses = 'my-2'; // Additional CSS classes for styling
    $search_context->btnclass = 'btn-primary';

    // Renderer for template core
    $core_renderer = $PAGE->get_renderer('core');

    // Render search input
    echo $core_renderer->render_from_template('core/search_input', $search_context);

    // --- End code to render Search Input ---

    // Set default variable.
    $stt = 0;
    $courses = [];
    $total_records = 0;
    $offset = $current_page * $per_page;
    $params = [];

    // Get all children of current parent account.
    if (empty($search_query)) {
        $params = [];

        $total_count_sql = "SELECT count(*)
                            FROM mdl_course c
                            where c.id != 1
                            ORDER BY c.category, c.fullname ASC";
        $total_records = $DB->count_records_sql($total_count_sql, $params);

        $sql = "SELECT *
                FROM mdl_course c
                where c.id != 1
                ORDER BY c.category, c.fullname ASC";
        $courses = $DB->get_records_sql($sql, $params, $offset, $per_page);
    }

    // if parent use search input, we need to filter the children list.
    if (!empty($search_query)) {

        // Escape the search query to prevent SQL injection.
        $search_query = trim($search_query);
        $search_query = '%' . $DB->sql_like_escape($search_query) . '%';
        $params = [
            'searchparamcourseid' => $search_query,
            'searchparamcoursename' => $search_query
        ];

        $total_count_sql = "SELECT count(*)
                            FROM mdl_course c
                            where c.id != 1
                                and 
                                    (
                                        c.id like :searchparamcourseid 
                                        or c.fullname like :searchparamcoursename
                                        
                                    )
                            ORDER BY c.category, c.fullname ASC";

        $total_records = $DB->count_records_sql($total_count_sql, $params);
        // Process the search query.
        $sql = "SELECT *
                FROM mdl_course c
                where c.id != 1
                and (
                        c.id like :searchparamcourseid 
                        or c.fullname like :searchparamcoursename
                    )
                ORDER BY c.category, c.fullname ASC";

        $courses = $DB->get_records_sql($sql, $params, $offset, $per_page);
    }

    // Display children list of parent on screen.
    if (!$courses) {
        echo $OUTPUT->notification(get_string('no_course_found', 'local_course_calendar'), 'info');
    } else {
        // If there are children, display them in a table.
        // and parent does not need to search for children.
        echo html_writer::start_tag('form', ['action' => 'edit_course_calendar_step_2.php', 'method' => 'get']);


        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', []);
        if (!empty($search_query)) {
            $base_url->param('searchquery', $search_query);
        }

        if (isset($selected_courses_from_request)) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'selected_courses',
                'value' => $selected_courses_from_request,
            ]);
        }
        // Display the list of children in a table.
        $table = new html_table();
        $table->head = [
            html_writer::empty_tag('div'),
            get_string('stt', 'local_course_calendar'),
            get_string('course_category', 'local_course_calendar'),
            get_string('course_full_name', 'local_course_calendar'),
            get_string('start_date', 'local_course_calendar'),
            get_string('end_date', 'local_course_calendar'),
            get_string('user_created_course', 'local_course_calendar')
        ];
        $table->align = ['center', 'center', 'left', 'left', 'center', 'center', 'center'];
        foreach ($courses as $course) {
            // add no. for the table.
            $stt = $stt + 1;

            // You might want to add a link to course's profile overview and course detail.
            $course_detail_url = new moodle_url('/course/view.php', ['id' => $course->id]);

            // Get course category name.
            $course_category = $DB->get_record('course_categories', ['id' => $course->category]);

            // Get course creator's name.
            $course_creators = [];
            $course_creators_fullname = [];

            $sql = "SELECT user.id, user.firstname, user.lastname , course.fullname
                    from {user} user
                    join {role_assignments} ra on ra.userid = user.id
                    join {role} role on role.id = ra.roleid
                    join {context} context on context.id = ra.contextid
                    join {course} course on course.id = context.instanceid
                    where course.id != 1 
                        and course.id = :courseid
                        and (role.shortname = 'coursecreator' OR role.shortname = 'manager' or role.shortname = 'editingteacher')
                        and user.deleted = 0 
                        and user.suspended = 0
                        and context.contextlevel = 50 
                    ORDER BY user.id ASC";
            $params = ['courseid' => $course->id];
            $course_creators = $DB->get_records_sql($sql, $params);

            if (!empty($course_creators)) {
                foreach ($course_creators as $course_creator) {
                    // add to show course_creator full name.
                    $course_creator_profile_url = new moodle_url('/user/profile.php', ['id' => $course_creator->id]);
                    $course_creator_fullname = html_writer::link($course_creator_profile_url, format_string($course_creator->firstname) . " " . format_string($course_creator->lastname));

                    $course_creators_fullname[] = $course_creator_fullname;
                }
            }

            // Add the row to the table.
            // Use html_writer to create the avatar image and other fields.
            $table->data[] = [
                html_writer::empty_tag('input', [
                    'type' => 'radio',
                    'value' => $course->id,
                    'class' => 'select-radio',
                    'name' => 'selected_courses',
                ]),
                $stt,
                $course_category->name,
                html_writer::link($course_detail_url, format_string($course->fullname)),
                date('D, d-m-Y', $course->startdate),
                date('D, d-m-Y', $course->enddate),
                implode(', ', $course_creators_fullname),
            ];
        }
        echo html_writer::table($table);

        echo '<div class="d-flex justify-content-end align-items-center">';

        echo '<div class="me-2">';
        $back_url = new moodle_url('/local/course_calendar/index.php', []);
        echo '<div class="d-flex justify-content-end align-items-center">';
        echo '<div><a class="btn btn-secondary " href="' . $back_url->out() . '">Back</a></div>';
        echo '</div>';
        echo '</div>';

        echo '<div>';
        echo html_writer::empty_tag('input', array('class' => 'btn btn-primary form-submit', 'type' => 'submit', 'value' => get_string('next_step', 'local_course_calendar')));
        echo '</div>';
        echo '</div>';

        echo html_writer::end_tag('form');

        echo $OUTPUT->paging_bar($total_records, $current_page, $per_page, $base_url);

    }

    echo $OUTPUT->box_end();

    echo $OUTPUT->footer();

} catch (Exception $e) {
    dlog($e->getTrace());

    echo "<pre>";
    var_dump($e->getTrace());
    echo "</pre>";

    throw new \moodle_exception('error', 'local_course_calendar', '', null, $e->getMessage());
}
