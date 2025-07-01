<?php
class block_course_schedule extends block_base {
    public function init() {
        $this->title = get_string('pluginname', 'block_course_schedule');
    }

    public function get_content() {
        global $USER, $DB;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass();

        // Lấy danh sách courseid mà user đang tham gia
        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname');
        if (empty($courses)) {
            $this->content->text = get_string('noschedule', 'block_course_schedule');
            return $this->content;
        }
        $courseids = array_keys($courses);

        // Lấy lịch theo các courseid này
        list($sql, $params) = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED, 'cid');
        $schedules = $DB->get_records('course_schedule', null, 'classdate ASC, classbegintime ASC');

        if (!$schedules) {
            $this->content->text = get_string('noschedule', 'block_course_schedule');
            return $this->content;
        }

        $html = '<table class="table"><tr><th>Khóa học</th><th>Ngày</th><th>Bắt đầu</th><th>Kết thúc</th></tr>';
        foreach ($schedules as $s) {
            $coursename = isset($courses[$s->courseid]) ? $courses[$s->courseid]->fullname : '';
            $html .= "<tr>
                <td>{$coursename}</td>
                <td>{$s->classdate}</td>
                <td>{$s->classbegintime}</td>
                <td>{$s->classendtime}</td>
            </tr>";
        }
        $html .= '</table>';
        $this->content->text = $html;
        return $this->content;
    }
}