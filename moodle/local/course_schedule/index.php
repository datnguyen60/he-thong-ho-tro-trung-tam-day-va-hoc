<?php
require('../../config.php');
try {
    $courseid = required_param('id', PARAM_INT);
    $course = get_course($courseid);
    require_login($course);

    $context = context_course::instance($courseid);
    $PAGE->set_url(new moodle_url('/mod/course_schedule/index.php', ['courseid' => $courseid]));
    $PAGE->set_context($context);
    $PAGE->set_title('Thời khóa biểu');
    $PAGE->set_heading(format_string($course->fullname));

    echo $OUTPUT->header();

    // Kiểm tra quyền creator (có thể thay bằng capability nếu dùng)
    $iscreator = has_capability('moodle/course:update', $context);

    // Nút tạo thời khóa biểu
    if ($iscreator) {
        $url = new moodle_url('/mod/course_schedule/schedule_form.php', ['courseid' => $courseid]);
        echo $OUTPUT->single_button($url, 'Tạo thời khóa biểu mới', 'get');
    }

    // Lấy danh sách các event là thời khóa biểu
    $events = $DB->get_records('event', [
        'courseid' => $courseid,
        'eventtype' => 'course_schedule'
    ], 'timestart ASC');

    if (!$events) {
        echo $OUTPUT->notification('Chưa có buổi học nào được tạo.', 'info');
    } else {
        echo html_writer::start_tag('div', ['class' => 'schedule-list']);
        echo html_writer::tag('h3', 'Danh sách các buổi học');

        foreach ($events as $event) {
            $status = 'upcoming';
            $color = '#ccc';

            // Check attendance_records
            $record = $DB->get_record('attendance_records', [
                'eventid' => $event->id,
                'userid' => $USER->id
            ]);

            if ($record) {
                if ($record->status === 'taught') {
                    $status = 'taught';
                    $color = '#28a745';
                } else if ($record->status === 'absent') {
                    $status = 'absent';
                    $color = '#dc3545';
                }
            } else if ($event->timestart > time()) {
                $status = 'upcoming';
                $color = '#ccc';
            } else {
                $status = 'past';
                $color = '#ffc107';
            }

            // Hiển thị khối buổi học
            echo html_writer::start_tag('div', ['style' => "background-color:$color; padding:10px; margin:10px; border-radius:6px"]);
            echo html_writer::link(
                new moodle_url('/mod/course_schedule/view.php', ['id' => $event->id]),
                format_string($event->name) . ' - ' . userdate($event->timestart)
            );
            echo html_writer::end_tag('div');
        }

        echo html_writer::end_tag('div');
    }

    echo html_writer::tag('hr', '');
    echo html_writer::tag('p', '<strong>Chú thích:</strong> 
        <span style="color:#28a745">■ Đã dạy</span> - 
        <span style="color:#dc3545">■ Nghỉ</span> - 
        <span style="color:#ccc">■ Chưa diễn ra</span> - 
        <span style="color:#ffc107">■ Diễn ra nhưng chưa ghi nhận');

    // Footer
    echo $OUTPUT->footer();
} catch (Exception $e) {
    echo '<pre>';
    print_r($e->getTrace());
    echo '</pre>';
}
