<?php
require('../../config.php');
require_once($CFG->libdir.'/formslib.php');

$courseid = required_param('id', PARAM_INT);
debugging('courseid: ' . $courseid, DEBUG_DEVELOPER);
require_login($courseid);
$context = context_course::instance($courseid);
require_capability('moodle/course:update', $context);

class schedule_form extends moodleform {
    function definition() {
        $mform = $this->_form;
        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->setDefault('id', $this->_customdata['id']);

        $mform->addElement('text', 'name', 'Tên buổi học');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Bắt buộc nhập', 'required');

        $mform->addElement('date_selector', 'classdate', 'Ngày học');
        $mform->addRule('classdate', 'Bắt buộc nhập', 'required');

        $mform->addElement('date_time_selector', 'classbegintime', 'Giờ bắt đầu');
        $mform->addRule('classbegintime', 'Bắt buộc nhập', 'required');

        $mform->addElement('date_time_selector', 'classendtime', 'Giờ kết thúc');
        $mform->addRule('classendtime', 'Bắt buộc nhập', 'required');

        $mform->addElement('textarea', 'description', 'Mô tả', 'wrap="virtual" rows="3" cols="40"');
        $mform->setType('description', PARAM_TEXT);

       

        $mform->addElement('select', 'repeat_type', 'Lặp lại', [
            '' => 'Không lặp lại',
            'day' => 'Mỗi n ngày',
            'week' => 'Mỗi n tuần',
            'month' => 'Mỗi n tháng'
        ]);
        $mform->setDefault('repeat_type', '');

        $mform->addElement('text', 'repeat_every', 'Số lần lặp (n)');
        $mform->setType('repeat_every', PARAM_INT);
        $mform->setDefault('repeat_every', 1);

        $mform->addElement('text', 'repeat_count', 'Số lần tạo');
        $mform->setType('repeat_count', PARAM_INT);
        $mform->setDefault('repeat_count', 1);

        $mform->addElement('submit', 'submitbutton', 'Lưu');
    }
}

$mform = new schedule_form(null, ['id' => $courseid]);

if ($mform->is_cancelled()) {
    redirect(new moodle_url('/local/course_schedule/index.php', ['id' => $courseid]));
} else if ($data = $mform->get_data()) {
    $repeat_type = $data->repeat_type;
    $repeat_every = max(1, intval($data->repeat_every));
    $repeat_count = max(1, intval($data->repeat_count));

    $base_date = $data->classdate;
    $base_begintime = $data->classbegintime;
    $base_endtime = $data->classendtime;

    for ($i = 0; $i < $repeat_count; $i++) {
        $record = new stdClass();
        $record->sectionid = 0; // Hoặc lấy từ form nếu có
        $record->classdate = userdate($base_date, '%Y-%m-%d');
        $record->classbegintime = $base_begintime;
        $record->classendtime = $base_endtime;
        $record->createtime = time();
        $record->lastmodifytime = time();
        $record->createuserid = $USER->id;
        $record->modifyuserid = $USER->id;
        $record->ismakeup = 0;
        $record->iscancel = 0;
        $DB->insert_record('course_schedule', $record);

        // Lặp lại ngày nếu cần
        if ($repeat_type == 'day') {
            $base_date = strtotime("+{$repeat_every} days", $base_date);
        } else if ($repeat_type == 'week') {
            $base_date = strtotime("+{$repeat_every} weeks", $base_date);
        } else if ($repeat_type == 'month') {
            $base_date = strtotime("+{$repeat_every} months", $base_date);
        }
    }

    redirect(new moodle_url('/local/course_schedule/index.php', ['id' => $courseid]), 'Đã tạo buổi học mới!', 2);
}

$PAGE->set_url(new moodle_url('/local/course_schedule/schedule_form.php', ['id' => $courseid]));
$PAGE->set_context($context);
$PAGE->set_title('Tạo buổi học mới');
$PAGE->set_heading('Tạo buổi học mới');

echo $OUTPUT->header();
echo $OUTPUT->heading('Tạo buổi học mới');
$mform->display();
echo $OUTPUT->footer();