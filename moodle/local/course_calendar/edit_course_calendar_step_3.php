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
// Include necessary Moodle libraries.
require_once(__DIR__ . '/../../config.php');
require_once($CFG->dirroot . '/local/course_calendar/lib.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');
require_once($CFG->dirroot . '/local/course_calendar/classes/form/chooseTimeForClassSection.php');

try {
    // Yêu cầu người dùng đăng nhập
    require_login();
    require_capability('local/course_calendar:edit', context_system::instance()); // Kiểm tra quyền truy cập
    $PAGE->requires->css('/local/course_calendar/style/style.css');
    $PAGE->requires->js('/local/course_calendar/js/lib.js');
    $per_page = optional_param('perpage', 20, PARAM_INT);
    $current_page = optional_param('page', 0, PARAM_INT);
    try {
        $courses = required_param('selected_courses', PARAM_INT);
    } catch (Exception $e) {
        dlog($e->getTrace());
        $params = [];
        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', $params);
        redirect($base_url, "You must select one course.", 0, \core\output\notification::NOTIFY_ERROR);
    }

    try {
        $teachers = required_param_array('selected_teachers', PARAM_INT);
    } catch (Exception $e) {
        dlog($e->getTrace());
        $params = [];
        if (isset($courses)) {
            $params['selected_courses'] = $courses;
        }
        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_2.php', $params);
        redirect($base_url, "You must select at least one teacher.", 0, \core\output\notification::NOTIFY_ERROR);
    }


    // Khai báo các biến toàn cục
    global $PAGE, $OUTPUT, $DB, $USER;

    // $context = context_course::instance(SITEID); // Lấy ngữ cảnh của trang hệ thống
    $context = context_system::instance(); // Lấy ngữ cảnh của trang hệ thống
    // Đặt ngữ cảnh trang
    $PAGE->set_context($context);

    // Thiết lập trang Moodle
    // Đặt URL cho trang hiện tại
    $PAGE->set_url(new moodle_url('/local/course_calendar/edit_course_calendar_step_3.php', []));
    $PAGE->set_pagelayout('standard');
    // Tiêu đề trang
    $PAGE->set_title(get_string('teaching_schedule_assignment_choose_address_and_time', 'local_course_calendar'));
    $PAGE->set_heading(get_string('teaching_schedule_assignment_choose_address_and_time', 'local_course_calendar'));

    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('course_calendar_title', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));


    // Thêm một breadcrumb cho các link khác.
    $PAGE->navbar->add(get_string('teaching_schedule_assignment', 'local_course_calendar'), new moodle_url('/local/course_calendar/index.php', []));

    // Thêm breadcrumb cho trang hiện tại
    $PAGE->navbar->add(get_string('teaching_schedule_assignment_choose_course', 'local_course_calendar'), new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', []));

    // Thêm breadcrumb cho trang hiện tại
    $PAGE->navbar->add(get_string('teaching_schedule_assignment_choose_teacher', 'local_course_calendar'), new moodle_url('/local/course_calendar/edit_course_calendar_step_2.php', []));

    // Thêm breadcrumb cho trang hiện tại
    $PAGE->navbar->add(get_string('teaching_schedule_assignment_choose_address_and_time', 'local_course_calendar'));

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

    // form to search time
    $start_class_time = time();
    $end_class_time = $start_class_time + 90 * 60;

    $mform = new \local_course_calendar\form\chooseTimeForClassSection();

    // Form processing and displaying is done here.
    if ($mform->is_cancelled()) {
        // If there is a cancel element on the form, and it was pressed,
        // then the `is_cancelled()` function will return true.
        // You can handle the cancel operation here.

    } else if ($fromform = $mform->get_data()) {

        global $PAGE, $OUTPUT, $DB, $USER;
        $start_class_time = $fromform->starttime;
        $end_class_time = $fromform->endtime;
        $selected_teachers = $fromform->selected_teachers;
        $selected_courses = $fromform->selected_courses;
        $search_query = $fromform->searchquery;
        $SESSION->start_class_time = $start_class_time;
        $SESSION->end_class_time = $end_class_time;

        $params = [];
        if (isset($selected_courses)) {
            $params['selected_courses'] = $selected_courses;
        }

        if (!empty($selected_teachers) and isset($selected_teachers)) {
            foreach ($selected_teachers as $teacherid) {
                // Add hidden input for each selected teacher.
                $params['selected_teachers[]'] = $teacherid;
            }
        }

        if (!empty($start_class_time) and !empty($end_class_time)) {
            $params['starttime'] = $start_class_time;
            $params['endtime'] = $end_class_time;
        }

        if (!empty($search_query)) {
            $params['searchquery'] = $search_query;
        }

        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_3.php', $params);


        redirect(
            $base_url,
            get_string('find_available_room', 'local_course_calendar'),
            0,
            \core\output\notification::NOTIFY_SUCCESS
        );

    } else {
        // default data for the form
        $data = new stdClass();
        if (isset($SESSION->start_class_time) and isset($SESSION->end_class_time)) {
            $start_class_time = $SESSION->start_class_time;
            $end_class_time = $SESSION->end_class_time;
            unset($SESSION->start_class_time);
            unset($SESSION->end_class_time);
        }

        $data->starttime = $start_class_time;
        $data->endtime = $end_class_time;
        $data->selected_teachers = required_param_array('selected_teachers', PARAM_INT);
        $data->selected_courses = required_param('selected_courses', PARAM_INT);
        $data->searchquery = optional_param('searchquery', '', PARAM_TEXT);
        $mform->set_data($data);
    }

    echo $OUTPUT->header();

    // Nội dung trang của bạn
    echo $OUTPUT->box_start();

    $mform->display();

    // search box
    $search_context = new stdClass();
    $search_context->method = 'get';
    $search_context->action = $PAGE->url; // Action URL for the search form
    $search_context->inputname = 'searchquery';
    $search_context->searchstring = get_string('searchitems', 'local_course_calendar'); // Placeholder text for the search input

    $search_query = optional_param('searchquery', '', PARAM_TEXT); // Get the search query from the URL parameters.
    $current_params = [];
    $current_params[] = ['name' => 'selected_courses', 'value' => required_param('selected_courses', PARAM_INT)];
    if (!empty($teachers) and isset($teachers)) {
        foreach ($teachers as $teacherid) {
            // Add hidden input for each selected teacher.
            $current_params[] = ['name' => 'selected_teachers[]', 'value' => $teacherid];
        }
    }
    if (!empty($start_class_time) and !empty($end_class_time)) {
        $current_params[] = ['name' => 'starttime', 'value' => $start_class_time];
        $current_params[] = ['name' => 'endtime', 'value' => $end_class_time];
    }
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
    $available_room_address = [];
    $total_records = 0;
    $offset = $current_page * $per_page;
    $params = [];

    // Get all room available of central.
    if (empty($search_query)) {
        $params = [
            'search_param_start_class_time' => $start_class_time,
            'search_param_end_class_time' => $end_class_time,
            'search_param_start_class_time_th1' => $start_class_time,
            'search_param_end_class_time_th1' => $end_class_time,
        ];

        $total_count_sql = "SELECT count(DISTINCT cr.id)
                            from {local_course_calendar_course_room} cr
                            left join {local_course_calendar_course_section} cs on cr.id = cs.course_room_id
                            where :search_param_start_class_time <= :search_param_end_class_time
                                and 
                                (
                                    (cs.class_end_time <= :search_param_start_class_time_th1)
                                    or (:search_param_end_class_time_th1 <= cs.class_begin_time) 
                                    or (cs.class_begin_time is null and cs.class_end_time is null)
                                )";

        $total_records = $DB->count_records_sql($total_count_sql, $params);

        $sql = "SELECT distinct cr.id AS room_id, -- ĐẶT CỘT ID CỦA BẢNG CHÍNH (cr) LÀM CỘT ĐẦU TIÊN VÀ ĐẢM BẢO NÓ DUY NHẤT
                        cr.room_number,
                        cr.room_floor,
                        cr.room_building,
                        cr.ward_address,
                        cr.district_address,
                        cr.province_address,
                        cr.room_online_url
                from {local_course_calendar_course_room} cr
                left join {local_course_calendar_course_section} cs on cr.id = cs.course_room_id
                where :search_param_start_class_time <= :search_param_end_class_time
                    and 
                    (
                        (cs.class_end_time <= :search_param_start_class_time_th1)
                        or (:search_param_end_class_time_th1 <= cs.class_begin_time) 
                        or (cs.class_begin_time is null and cs.class_end_time is null)
                    )
                order by cr.id asc";
        $available_room_address = $DB->get_records_sql($sql, $params, $offset, $per_page);
    }

    // if parent use search input, we need to filter the children list.
    if (!empty($search_query)) {

        // Escape the search query to prevent SQL injection.
        $search_query = trim($search_query);
        $search_query = '%' . $DB->sql_like_escape($search_query) . '%';
        $params = [
            'search_param_start_class_time' => $start_class_time,
            'search_param_end_class_time' => $end_class_time,
            'search_param_start_class_time_th1' => $start_class_time,
            'search_param_end_class_time_th1' => $end_class_time,
            'search_param_room_building' => $search_query,
            'search_param_room_number' => $search_query,
            'search_param_room_floor' => $search_query,
            'search_param_ward_address' => $search_query,
            'search_param_district_address' => $search_query,
            'search_param_province_address' => $search_query,
        ];

        $total_count_sql = "SELECT count(DISTINCT cr.id)
                            from {local_course_calendar_course_room} cr
                            left join {local_course_calendar_course_section} cs on cr.id = cs.course_room_id
                            where :search_param_start_class_time <= :search_param_end_class_time
                            and 
                                (
                                    (cs.class_end_time <= :search_param_start_class_time_th1)
                                    or (:search_param_end_class_time_th1 <= cs.class_begin_time) 
                                    or (cs.class_begin_time is null and cs.class_end_time is null)
                                )
                            AND 
                                (
                                    cr.room_building like :search_param_room_building 
                                    or cr.room_number like :search_param_room_number
                                    or cr.room_floor like :search_param_room_floor
                                    or cr.ward_address like :search_param_ward_address
                                    or cr.district_address like :search_param_district_address
                                    or cr.province_address like :search_param_province_address
                                )";

        $total_records = $DB->count_records_sql($total_count_sql, $params);
        // Process the search query.
        $sql = "SELECT distinct cr.id AS room_id, -- ĐẶT CỘT ID CỦA BẢNG CHÍNH (cr) LÀM CỘT ĐẦU TIÊN VÀ ĐẢM BẢO NÓ DUY NHẤT
                        cr.room_number,
                        cr.room_floor,
                        cr.room_building,
                        cr.ward_address,
                        cr.district_address,
                        cr.province_address,
                        cr.room_online_url
                from {local_course_calendar_course_room} cr
                left join {local_course_calendar_course_section} cs on cr.id = cs.course_room_id
                where :search_param_start_class_time <= :search_param_end_class_time
                and 
                    (
                        (cs.class_end_time <= :search_param_start_class_time_th1)
                        or (:search_param_end_class_time_th1 <= cs.class_begin_time) 
                        or (cs.class_begin_time is null and cs.class_end_time is null)
                    )
                AND
                    (
                        cr.room_building like :search_param_room_building 
                        or cr.room_number like :search_param_room_number
                        or cr.room_floor like :search_param_room_floor
                        or cr.ward_address like :search_param_ward_address
                        or cr.district_address like :search_param_district_address
                        or cr.province_address like :search_param_province_address
                    )
                ORDER BY cr.id ASC";
        $available_room_address = $DB->get_records_sql($sql, $params, $offset, $per_page);
    }

    // Xóa các phòng không có sẵn trong khoảng thời gian đã chọn.
    get_empty_rooms($available_room_address, $start_class_time, $end_class_time);

    // Display children list of parent on screen.
    if (!$available_room_address) {
        echo $OUTPUT->notification(get_string('no_address_and_time_found', 'local_course_calendar'), 'info');
    } else {
        // If there are children, display them in a table.
        // and parent does not need to search for children.
        echo html_writer::start_tag('form', ['action' => 'process_edit_course_calendar_after_step_3.php', 'method' => 'get']);

        $params = [];
        if (isset($courses)) {
            $params['selected_courses'] = $courses;
        }

        if (!empty($teachers) and isset($teachers)) {
            foreach ($teachers as $teacherid) {
                // Add hidden input for each selected teacher.
                $params['selected_teachers[]'] = $teacherid;
            }
        }

        if (!empty($start_class_time) and !empty($end_class_time)) {
            $params['starttime'] = $start_class_time;
            $params['endtime'] = $end_class_time;
        }

        if (!empty($search_query)) {
            $params['searchquery'] = $search_query;
        }

        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_3.php', $params);


        // Display the list of children in a table.
        $table = new html_table();
        $table->head = [
            html_writer::empty_tag('div'),
            get_string('stt', 'local_course_calendar'),
            get_string('building', 'local_course_calendar'),
            get_string('floor', 'local_course_calendar'),
            get_string('room', 'local_course_calendar'),
            get_string('address', 'local_course_calendar')
        ];
        $table->align = ['center', 'center', 'left', 'left', 'left', 'left'];
        foreach ($available_room_address as $room_address) {
            // add no. for the table.
            $stt = $stt + 1;

            // Add the row to the table.
            // Use html_writer to create the avatar image and other fields.
            $table->data[] = [
                html_writer::empty_tag('input', [
                    'type' => 'radio',
                    'value' => $room_address->room_id,
                    'class' => 'select-radio',
                    'name' => 'selected_room_addresses',
                ]),

                $stt,
                $room_address->room_building,
                $room_address->room_floor,
                $room_address->room_number,
                $room_address->ward_address . ', ' . $room_address->district_address . ', ' . $room_address->province_address

            ];
        }
        echo html_writer::table($table);

        if (isset($courses)) {
            // Add hidden input for each selected course.
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'selected_courses',
                'value' => $courses,
            ]);
        }

        if (isset($teachers)) {
            foreach ($teachers as $teacherid) {
                // Add hidden input for each selected teacher.
                echo html_writer::empty_tag('input', [
                    'type' => 'hidden',
                    'name' => 'selected_teachers[]',
                    'value' => $teacherid,
                ]);
            }
        }

        // Add hidden inputs for start and end class time.
        if (isset($start_class_time) && isset($end_class_time)) {
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'starttime',
                'value' => $start_class_time,
            ]);
            echo html_writer::empty_tag('input', [
                'type' => 'hidden',
                'name' => 'endtime',
                'value' => $end_class_time,
            ]);
        }

        echo '<div class="d-flex justify-content-end align-items-center">';
        echo '<div class="me-2">';
        $params = [];
        if (isset($courses)) {
            $params['selected_courses'] = $courses;
        }

        if (!empty($teachers) and isset($teachers)) {
            foreach ($teachers as $teacherid) {
                // Add hidden input for each selected teacher.
                $params['selected_teachers[]'] = $teacherid;
            }
        }

        $back_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_2.php', $params);
        echo '<div class="d-flex justify-content-end align-items-center">';
        echo '<div><a class="btn btn-secondary " href="' . $back_url->out() . '">Back</a></div>';
        echo '</div>';
        echo '</div>';

        echo '<div>';
        echo html_writer::empty_tag('input', array('class' => 'btn btn-primary form-submit', 'type' => 'submit', 'value' => get_string('save_changes', 'local_course_calendar')));
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
