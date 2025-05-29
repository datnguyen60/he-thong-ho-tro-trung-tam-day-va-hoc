<?php
require('../../config.php');
require_once($CFG->dirroot . '/local/dlog/lib.php');

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
        'Ngày học',
        'Giờ bắt đầu',
        'Giờ kết thúc',
        'Trạng thái'
    ];
    $table->align = ['center', 'center', 'center', 'center', 'center'];
    $i = 1;
    foreach ($schedules as $schedule) {
        // Trạng thái
        $timestart = strtotime($schedule->classdate . ' ' . $schedule->classbegintime);
        if ($timestart > time()) {
            $status = '<span style="color:#007bff;font-weight:bold;"><i class="fa fa-clock-o"></i> Chưa diễn ra</span>';
        } else {
            $status = '<span style="color:#ffc107;font-weight:bold;"><i class="fa fa-check-circle"></i> Đã diễn ra</span>';
        }
        $table->data[] = [
            $i++,
            $schedule->classdate,
            $schedule->classbegintime,
            $schedule->classendtime,
            $status
        ];
    }
    echo html_writer::table($table);
    echo html_writer::end_tag('div');
}

    // Chú thích
    echo html_writer::tag('hr', '');
    echo html_writer::div(
        '<strong>Chú thích:</strong> 
        <span style="color:#007bff">■ Chưa diễn ra</span> - 
        <span style="color:#ffc107">■ Đã diễn ra</span>',
        'mt-3'
    );

    echo $OUTPUT->footer();
} catch (Exception $e) {
    echo '<pre>';
    print_r($e->getTrace());
    echo '</pre>';
}