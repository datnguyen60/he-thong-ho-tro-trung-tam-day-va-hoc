<?php
require_once(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php'); // Chứa hàm gửi email
require_once($CFG->libdir . '/moodlelib.php'); // Chứa các hàm tiện ích Moodle

header('Content-Type: application/json'); // Trả về JSON

// Đảm bảo request là POST và có đủ dữ liệu
if (!confirm_sesskey()) {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => get_string('invalidrequest', 'moodle')]);
    die();
}

$email = required_param('email', PARAM_EMAIL); // Lấy email từ request AJAX
// Bạn có thể cần lấy userid từ $USER->id nếu OTP liên quan đến người dùng đang đăng nhập

$response = new stdClass();
$response->status = 'success';
$response->message = get_string('otpsent', 'local_children_management'); // Thông báo chung, không tiết lộ OTP

try {
    // 1. Tạo OTP code (ví dụ: 6 chữ số ngẫu nhiên)
    $otp_code = random_int(100000, 999999);
    $otp_expires_in = 300; // OTP hết hạn sau 300 giây (5 phút)
    $current_time = time();

    // 2. Lưu OTP vào database (bảng mdl_local_children_management_otps)
    $otp_record = new stdClass();
    $otp_record->userid = $USER->id; // Gán ID người dùng hiện tại
    $otp_record->email = $email;
    $otp_record->otpcode = $otp_code;
    $otp_record->timecreated = $current_time;
    $otp_record->timeexpires = $current_time + $otp_expires_in;
    $otp_record->isused = 0; // Chưa sử dụng

    $DB->insert_record('local_children_management_otps', $otp_record);

    // 3. Gửi email OTP
    // Bạn cần một hàm để gửi email trong lib.php
    local_children_management_send_otp_email($email, $otp_code);

} catch (Exception $e) {
    // Xử lý lỗi (ví dụ: lỗi gửi email, lỗi DB)
    $response->status = 'error';
    $response->message = get_string('failedtosendotp', 'local_children_management');
    // Log lỗi chi tiết hơn nếu cần: error_log($e->getMessage());
}

echo json_encode($response);
die();
