<?php
defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php'); // Cần include externallib.php
require_once(__DIR__ . '/lib.php'); // Cần include lib.php để gọi hàm gửi email

class local_children_management_external extends external_api {

    public static function send_otp_parameters() {
        return new external_function_parameters(
            array(
                'email' => new external_value(PARAM_EMAIL, 'The email address to send OTP to.')
            )
        );
    }

    public static function send_otp($email) {
        global $CFG, $DB, $USER;

        self::validate_parameters(self::send_otp_parameters(), array('email' => $email));

        // Kiểm tra quyền hạn của người dùng hiện tại
        // Ví dụ: người dùng phải có quyền addchild
        $context = context_system::instance(); // Hoặc context phù hợp
        require_capability('local/children_management:addchild', $context);

        // Gọi hàm gửi OTP từ lib.php
        $success = local_children_management_send_otp_email($email, local_children_management_generate_otp($email, $USER->id));

        if ($success) {
            return (object)['status' => 'success', 'message' => get_string('otpsent', 'local_children_management')];
        } else {
            // Nên trả về lỗi cụ thể hơn nếu có thể
            throw new moodle_exception('failedtosendotp', 'local_children_management');
        }
    }

    // Hàm phụ trợ để tạo OTP và lưu vào DB (nếu chưa có trong lib.php)
    private static function local_children_management_generate_otp(string $email, int $userid): string {
        global $DB;
        $otp_code = random_int(100000, 999999);
        $otp_expires_in = 300; // 5 minutes
        $current_time = time();

        $otp_record = new stdClass();
        $otp_record->userid = $userid;
        $otp_record->email = $email;
        $otp_record->otpcode = $otp_code;
        $otp_record->timecreated = $current_time;
        $otp_record->timeexpires = $current_time + $otp_expires_in;
        $otp_record->isused = 0;

        $DB->insert_record('local_children_management_otps', $otp_record);
        return (string)$otp_code;
    }
}
