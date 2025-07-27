<?php
defined('MOODLE_INTERNAL') || die();

$functions = array(
    'core_local_children_management_send_otp' => array(
        'classname' => 'local_children_management_external', // Lớp xử lý
        'methodname' => 'send_otp', // Phương thức trong lớp
        'classpath' => 'local/children_management/externallib.php', // Đường dẫn đến lớp
        'description' => 'Send OTP to user email',
        'type' => 'read', // Chỉ đọc, không thay đổi trạng thái quan trọng
        'ajax' => true, // Cho phép gọi qua AJAX
    )
);
