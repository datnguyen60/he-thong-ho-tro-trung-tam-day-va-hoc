<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');
$PAGE->requires->js(new moodle_url('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.js'));
$PAGE->requires->css(new moodle_url('https://cdn.jsdelivr.net/npm/fullcalendar@6.1.11/index.global.min.css'));

try {
    $id = required_param('id', PARAM_INT);
    $course = get_course($id);
    require_login($course);

    $context = context_course::instance($id);
    $PAGE->set_url(new moodle_url('/local/course_schedule/index.php', ['id' => $id]));
    $PAGE->set_context($context);
    $PAGE->set_title('Thời khóa biểu');
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();

    // Kiểm tra quyền creator (có thể thay bằng capability nếu dùng)
    $iscreator = has_capability('moodle/course:update', $context);

    // Nút tạo thời khóa biểu
    if ($iscreator) {
        $url = new moodle_url('/local/course_schedule/schedule_form.php', ['id' => $id]);
        echo $OUTPUT->single_button($url, 'Tạo buổi học mới', 'get', ['class' => 'mb-3']);
    }

    // Lấy danh sách các event là thời khóa biểu
    $schedules = $DB->get_records('course_schedule', null, 'classdate ASC, classbegintime ASC');

    if (!$schedules) {
        echo $OUTPUT->notification('Chưa có buổi học nào được tạo.', 'info');
    } else {
        echo html_writer::start_tag('div', ['class' => 'schedule-list']);
        echo html_writer::tag('h3', 'Danh sách thời khóa biểu');

        $table = new html_table();
        $table->head = [
            'STT',
            'Giờ bắt đầu',
            'Giờ kết thúc',
            'Trạng thái'
        ];
        $table->align = ['center', 'center', 'center', 'center'];
        $i = 1;
        $now = time();
        $events = [];
        $sections = $DB->get_records('course_sections', ['course' => $id]);

        foreach ($schedules as $schedule) {
            $sectionname = '';
            if (!empty($sections[$schedule->sectionid])) {
                $section = $sections[$schedule->sectionid];
                $sectionname = $section->name ? format_string($section->name) : ('Section ' . $section->section);
            } else {
                $sectionname = 'Section ?';
            }

            if (!empty($schedule->iscancel)) {
                $color = '#dc3545'; // Đã hủy
                $status = 'Đã hủy';
            } else if (!empty($schedule->ismakeup)) {
                $color = '#6f42c1'; // Buổi bù
                $status = 'Buổi bù';
            } else if ($now < $schedule->classbegintime) {
                $color = '#007bff'; // Chưa bắt đầu
                $status = 'Chưa bắt đầu';
            } else if ($now >= $schedule->classbegintime && $now < $schedule->classendtime) {
                $color = '#28a745'; // Đang diễn ra
                $status = 'Đang diễn ra';
            } else {
                $color = '#ffc107'; // Đã kết thúc
                $status = 'Đã kết thúc';
            }
            $events[] = [
                'id' => $schedule->id,
                'courseid' => $id,
                'title' => $sectionname,
                'start' => date('c', $schedule->classbegintime),
                'end'   => date('c', $schedule->classendtime),
                'color' => $color,
                'description' => $schedule->description ?? '',
                'status' => $status,
                'iscancel' => !empty($schedule->iscancel)
            ];
        }
        $events_json = json_encode($events);
        echo '<div id="calendar" style="max-width:900px;margin:0 auto 30px auto;height:700px;"></div>';
        echo "<script>var COURSE_ID = " . (int)$id . ";</script>";
        // Modal HTML
        echo '
        <div id="eventModal" style="display:none;position:fixed;top:20%;left:50%;transform:translate(-50%,0);background:#fff;padding:24px;z-index:9999;box-shadow:0 2px 8px rgba(0,0,0,0.2);border-radius:8px;min-width:320px;">
            <div id="eventModalContent"></div>
            <div id="eventModalActions" style="margin-top:16px;"></div>
        </div>
        ';

        echo "<script>
        document.addEventListener('DOMContentLoaded', function() {
            var calendarEl = document.getElementById('calendar');
            var calendar = new FullCalendar.Calendar(calendarEl, {
                initialView: 'dayGridMonth',
                locale: 'vi',
                events: $events_json,
                eventClick: function(info) {
                    var e = info.event;
                    var props = e.extendedProps;
                    var html = '<h3>' + e.title + '</h3>';
                    html += '<p><b>Bắt đầu:</b> ' + e.start.toLocaleString('vi-VN') + '</p>';
                    html += '<p><b>Kết thúc:</b> ' + e.end.toLocaleString('vi-VN') + '</p>';
                    html += '<p><b>Trạng thái:</b> ' + (props.status || '') + '</p>';
                    if (props.description) {
                        html += '<p><b>Mô tả:</b> ' + props.description + '</p>';
                    }
                    document.getElementById('eventModalContent').innerHTML = html;
                    // Nút hủy (chỉ hiện nếu chưa hủy)
                    var actions = '';
                    if (!props.iscancel && (props.status === 'Chưa bắt đầu' || props.status === 'Đang diễn ra')) {
                        actions = '<button class=\"btn btn-danger\" onclick=\"cancelSchedule(' + e.id + ')\">Hủy buổi học</button>';
                    } else if (props.iscancel) {
                        actions = '<span style=\"color:#dc3545;font-weight:bold;\">Buổi học đã bị hủy</span>';
                    }
                    actions += ' <button class=\"btn btn-secondary\" style=\"margin-left:8px;\" onclick=\"document.getElementById(\\'eventModal\\').style.display=\\'none\\'\">Đóng</button>';
                    document.getElementById('eventModalActions').innerHTML = actions;
                    document.getElementById('eventModal').style.display = 'block';
                }
            });
            calendar.render();
        });

        // Gọi AJAX hoặc chuyển trang để hủy buổi học
        function cancelSchedule(id) {
            if (!confirm('Bạn chắc chắn muốn hủy buổi học này?')) return;
            // Đơn giản nhất: chuyển trang đến 1 URL xử lý hủy
            window.location.href = '/local/course_schedule/cancel.php?id=' + id + '&courseid=' + COURSE_ID;
        }
        </script>";
        echo '<style>
        .fc-event-title, .fc-event-title-container {
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 120px; /* hoặc giá trị phù hợp với giao diện của bạn */
            display: block;
        }
        .fc-event {
            min-width: 0;
        }
        </style>';
    }

    // Chú thích
    echo html_writer::tag('hr', '');
    echo html_writer::div(
        '<strong>Chú thích:</strong> 
        <span style="color:#007bff">■ Chưa diễn ra</span> - 
        <span style="color:#28a745">■ Đang diễn ra</span> -
        <span style="color:#ffc107">■ Đã diễn ra</span> -
        <span style="color:#dc3545">■ Đã hủy</span> -
        <span style="color:#6f42c1">■ Buổi bù</span>',
        'mt-3'
    );

    echo $OUTPUT->footer();
} catch (Exception $e) {
    echo '<pre>';
    print_r($e->getTrace());
    echo '</pre>';
}