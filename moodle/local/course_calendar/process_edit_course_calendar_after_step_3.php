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

try {
    // Yêu cầu người dùng đăng nhập
    require_login();
    require_capability('local/course_calendar:edit', context_system::instance()); // Kiểm tra quyền truy cập
    $PAGE->requires->css('/local/course_calendar/style/style.css');
    // Khai báo các biến toàn cục
    global $PAGE, $OUTPUT, $DB, $USER;

    $context = context_system::instance(); // Lấy ngữ cảnh của trang hệ thống
    // Đặt ngữ cảnh trang
    $PAGE->set_context($context);

    // Thiết lập trang Moodle
    // Đặt URL cho trang hiện tại
    $PAGE->set_url(new moodle_url('/local/course_calendar/process_edit_course_calendar_after_step_3.php', []));
    // Tiêu đề trang
    $PAGE->set_title(get_string('teaching_schedule_assignment_processing_title', 'local_course_calendar'));
    $PAGE->set_heading(get_string('teaching_schedule_assignment_processing_heading', 'local_course_calendar'));



    try {
        // courses is array with format [courseid, courseid, courseid,....]
        $courses = required_param('selected_courses', PARAM_INT);
    } catch (Exception $e) {
        dlog($e->getTrace());
        $params = [];
        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_1.php', $params);
        redirect($base_url, "You must select one course.", 0, \core\output\notification::NOTIFY_ERROR);
    }

    try {
        // teachers is array with format [teacherid, teacherid, teacherid,....]
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

    // room_address is roomid.
    try {
        $room_addresses = required_param('selected_room_addresses', PARAM_INT);

    } catch (Exception $e) {
        dlog($e->getTrace());
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
        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_3.php', $params);
        redirect($base_url, "You must select one room.", 0, \core\output\notification::NOTIFY_ERROR);
    }

    try {
        // start_time and endtime is Unix timestamp. It is an integer number.
        $start_time = required_param('starttime', PARAM_INT);
        $end_time = required_param('endtime', PARAM_INT);

    } catch (Exception $e) {
        dlog($e->getTrace());
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
        $base_url = new moodle_url('/local/course_calendar/edit_course_calendar_step_3.php', $params);
        redirect($base_url, "You must select start time and end time and must be press find room button.", 0, \core\output\notification::NOTIFY_ERROR);
    }

    create_manual_calendar($courses, $teachers, $room_addresses, $start_time, $end_time);
    echo $OUTPUT->header();
    // $calendar = create_automatic_calendar_by_genetic_algorithm();
    // $timeTable = new TimetableGenerator();
    // $timeTable = $timeTable->create_automatic_calendar_by_recursive_swap_algorithm();
    // $calendar = $timeTable->format_time_table($timeTable->get_time_slot_array());

    // $available_rooms = $DB->get_records('local_course_calendar_course_room');

    // $number_room = count($available_rooms);
    // $number_class_sessions = count(STT_CLASS_SESSIONS);
    // $number_day = count(DATES);

    // // --- In bảng thời khóa biểu ---
    // echo "<!DOCTYPE html>";
    // echo "<html lang='vi'>";
    // echo "<head>";
    // echo "    <meta charset='UTF-8'>";
    // echo "    <meta name='viewport' content='width=device-width, initial-scale=1.0'>";
    // echo "    <title>Bảng Thời Khóa Biểu</title>";
    // echo "    <style>";
    // echo "        table {";
    // echo "            width: 100%;";
    // echo "            border-collapse: collapse;";
    // echo "            margin-bottom: 20px;";
    // echo "        }";
    // echo "        th, td {";
    // echo "            border: 1px solid #ccc;";
    // echo "            padding: 8px;";
    // echo "            text-align: center;";
    // echo "            vertical-align: top;"; // Căn trên cho nội dung dài
    // echo "        }";
    // echo "        th {";
    // echo "            background-color: #f2f2f2;";
    // echo "        }";
    // echo "        .room-header {";
    // echo "            background-color: #e0e0e0;";
    // echo "            font-weight: bold;";
    // echo "        }";
    // echo "        .day-header {";
    // echo "            background-color: #f9f9f9;";
    // echo "            font-weight: bold;";
    // echo "        }";
    // echo "    </style>";
    // echo "</head>";
    // echo "<body>";

    // echo "<h2>Thời Khóa Biểu</h2>";

    // echo "<table>";
    // // Hàng tiêu đề cho các ngày
    // echo "<thead>";
    // echo "<tr>";
    // echo "<th>Phòng / Ngày</th>"; // Góc trên bên trái
    // for ($j = 0; $j < $number_day; $j++) {
    //     switch ($j) {
    //         case 0:
    //             echo "<th class='day-header'>Thứ 2" . "</th>";
    //             break;
    //         case 1:
    //             echo "<th class='day-header'>Thứ 3" . "</th>";

    //             break;
    //         case 2:
    //             echo "<th class='day-header'>Thứ 4" . "</th>";

    //             break;
    //         case 3:
    //             echo "<th class='day-header'>Thứ 5" . "</th>";

    //             break;
    //         case 4:
    //             echo "<th class='day-header'>Thứ 6" . "</th>";

    //             break;
    //         case 5:
    //             echo "<th class='day-header'>Thứ 7" . "</th>";
    //             break;
    //         case 6:
    //             echo "<th class='day-header'>CN" . "</th>";
    //             break;

    //     }

    // }
    // echo "</tr>";
    // echo "</thead>";

    // // Nội dung bảng
    // echo "<tbody>";
    // for ($i = 0; $i < $number_room; $i++) {
    //     echo "<tr>";
    //     echo "<td class='room-header'>Phòng " . ($i + 1) . "</td>"; // Cột đầu tiên là tên phòng
    //     for ($j = 0; $j < $number_day; $j++) {
    //         echo "<td>";
    //         // Duyệt qua các buổi học trong ngày và phòng hiện tại
    //         if (!empty($calendar) and !empty($calendar[$i][$j])) {
    //             $tiet = 1;
    //             foreach ($calendar[$i][$j] as $k => $session_data) {
    //                 // Hiển thị nội dung buổi học.
    //                 // Bạn có thể format lại ở đây để hiển thị thông tin chi tiết hơn.
    //                 if (isset($session_data->courseid)) {
    //                     echo "<div>" . "Tiết " . $tiet . "</div>";
    //                     // echo "<div>" ."courseid: " . $session_data->courseid . "</div>";
    //                     echo "<div>" . $session_data->course_name . "</div>";
    //                 }
    //                 if ($k < $number_class_sessions - 1) {
    //                     echo "<hr style='border-top: 1px dashed #eee; margin: 5px 0;'>"; // Đường kẻ phân cách các buổi
    //                 }
    //                 $tiet++;
    //             }
    //         } else {
    //             echo "<i>(Chưa có dữ liệu)</i>"; // Hiển thị khi không có dữ liệu
    //         }
    //         echo "</td>";
    //     }
    //     echo "</tr>";
    // }
    // echo "</tbody>";
    // echo "</table>";

    // echo "</body>";
    // echo "</html>";


    echo $OUTPUT->footer();

} catch (Exception $e) {
    dlog($e->getTrace());

    echo "<pre>";
    var_dump($e->getTrace());
    echo "</pre>";

    throw new \moodle_exception('error', 'local_course_calendar', '', null, $e->getMessage());
}
