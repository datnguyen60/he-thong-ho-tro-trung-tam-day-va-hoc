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
    require_capability('local/course_calendar:view', context_system::instance()); // Kiểm tra quyền truy cập

    // Khai báo các biến toàn cục
    global $PAGE, $OUTPUT, $DB, $USER;

    // $context = context_course::instance(SITEID); // Lấy ngữ cảnh của trang hệ thống
    $context = context_system::instance(); // Lấy ngữ cảnh của trang hệ thống
    // Đặt ngữ cảnh trang
    $PAGE->set_context($context);

    // Thiết lập trang Moodle
    // Đặt URL cho trang hiện tại
    $PAGE->set_url(new moodle_url('/local/course_calendar/index.php', []));
    // Tiêu đề trang
    $PAGE->set_title(get_string('course_calendar_title', 'local_course_calendar'));
    $PAGE->set_heading(get_string('course_list', 'local_course_calendar'));

    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('course_calendar_title', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));


    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('teaching_schedule_assignment', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));

    // Thêm breadcrumb cho trang hiện tại
    $PAGE->navbar->add(get_string('course_list', 'local_course_calendar'));

    // // add menu item to the settings navigation.
    // $settingsnav = $PAGE->settingsnav;
    // if (has_capability('local/course_calendar:edit', context_system::instance())) {
    //     if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
    //         $strfoo = get_string('edit_total_lesson_for_course', 'local_course_calendar');
    //         $url = new moodle_url('/local/course_calendar/edit_total_lesson_for_course.php', array('courseid' => 1));
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

    // Add a button to add a new course schedule.
    $add_new_course_schedule = new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', []);
    echo '<div class="d-flex justify-content-end align-items-center">';
    echo '<div><a class="btn btn-primary " href="' . $add_new_course_schedule->out() . '">+ Add new schedule</a></div>';
    echo '</div>';


    // Nội dung trang của bạn
    echo $OUTPUT->box_start();
    $search_context = new stdClass();
    $search_context->method = 'get'; // Method for the search form
    $search_context->action = $PAGE->url; // Action URL for the search form
    $search_context->inputname = 'searchquery';
    $search_context->searchstring = get_string('searchitems', 'local_course_calendar'); // Placeholder text for the search input

    $search_query = optional_param('searchquery', '', PARAM_TEXT); // Get the search query from the URL parameters.

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

    $per_page = optional_param('perpage', 20, PARAM_INT);
    $current_page = optional_param('page', 0, PARAM_INT);
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
        echo html_writer::start_tag('div');

        $base_url = new moodle_url('/local/course_calendar/index.php', []);
        if (!empty($search_query)) {
            $base_url->param('searchquery', $search_query);
        }

        // Display the list of children in a table.
        $table = new html_table();
        $table->head = [
            get_string('stt', 'local_course_calendar'),
            // get_string('course_avatar', 'local_course_calendar'),
            get_string('course_full_name', 'local_course_calendar'),
            get_string('student_number', 'local_course_calendar'),
            get_string('chapter_number', 'local_course_calendar'),
            get_string('lesson_number', 'local_course_calendar'),
            get_string('section_number', 'local_course_calendar'),
            get_string('actions', 'local_course_calendar'),
        ];
        $table->align = ['center', 'left', 'center', 'center', 'center', 'center', 'center'];
        foreach ($courses as $course) {
            // add no. for the table.
            $stt = $stt + 1;

            // You might want to add a link to course's profile overview and course detail.
            $course_detail_url = new moodle_url('/course/view.php', ['id' => $course->id]);

            $edit_course_schedule_action = null;
            $view_course_detail_action = null;
            // If the user has permission to edit the course, add an edit link.
            if (has_capability('local/course_calendar:edit', context_system::instance())) {
                $edit_schedule_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_2.php', ['courseid' => $course->id]);
                $edit_course_schedule_action = $OUTPUT->action_icon(
                    $edit_schedule_url,
                    new pix_icon('i/edit', get_string('edit_schedule', 'local_course_calendar'))
                );
            }

            $view_course_detail_action = $OUTPUT->action_icon(
                $course_detail_url,
                new pix_icon('i/hide', get_string('view_course_detail', 'local_course_calendar'))
            );

            // Count student number in this course.
            $sql = "SELECT count(*)
                    from {user} user
                    join {role_assignments} ra on ra.userid = user.id
                    join {role} role on role.id = ra.roleid
                    join {context} context on context.id = ra.contextid
                    join {course} course on course.id = context.instanceid
                    where course.id != 1 and course.id = :courseid
                            and role.shortname = 'student'
                            and context.contextlevel = 50 
                    ORDER BY course.id ASC";

            // get total lesson, chapter, section of this course.
            $sql_total_lesson_of_course = "SELECT *
                                            FROM {local_course_calendar_total_course_lesson} c
                                            where c.courseid != 1 and c.courseid = :courseid
                                            ORDER BY c.createtime DESC";

            $params = ['courseid' => $course->id];

            $total_students = $DB->count_records_sql($sql, $params);

            // get record latest containing total lesson, chapter, section of this course.
            $records = $DB->get_records_sql($sql_total_lesson_of_course, $params);

            $total_lesson_of_course = 0;
            $total_chapter_of_course = 0;
            $total_section_of_course = 0;

            foreach ($records as $record) {
                // get total lesson for this course.
                $total_lesson_of_course = $record->total_course_lesson;

                // get total chapter for this course.
                $total_chapter_of_course = $record->total_course_chapter;

                // get total section for this course.
                $total_section_of_course = $record->total_course_section;

                break; // We only need the first record.                
            }


            // Add the row to the table.
            // Use html_writer to create the avatar image and other fields.
            $table->data[] = [
                $stt,
                html_writer::link($course_detail_url, format_string($course->fullname)),
                $total_students,
                $total_chapter_of_course,
                $total_lesson_of_course,
                $total_section_of_course,
                $view_course_detail_action . ' ' . $edit_course_schedule_action
            ];
        }
        echo html_writer::table($table);

        echo $OUTPUT->paging_bar($total_records, $current_page, $per_page, $base_url);

        echo html_writer::end_tag('div');
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
