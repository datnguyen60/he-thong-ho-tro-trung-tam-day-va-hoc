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
 * Callback implementations for local_children_management
 *
 * @package    local_children_management
 * @copyright  2025 Võ Mai Phương <vomaiphuonghhvt@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Extend the user navigation to add a link to the children management page.
 * @param mixed $navigation The user navigation object to extend.
 * @param mixed $user The user object for the current user.
 * @param mixed $context The context object for the current user.
 * @return void
 */
// function local_children_management_extend_navigation(global_navigation $navigation) {
//     global $USER;
//     if (has_capability('local/children_management:view', context_system::instance())) {
//         $string = get_string('children_management_title', 'local_children_management');
//         $url = new moodle_url('/local/children_management/index.php', ['id' => $USER->id]);
//         $navigation->add(
//             get_string('children_management_title', 'local_children_management'),
//             $url,
//             navigation_node::TYPE_CONTAINER,
//             null,
//             null,
//             new pix_icon('i/children', $string)
//         );
//         
//     }
// }

function local_children_management_extend_settings_navigation($settingsnav, $context) {
    global $CFG, $PAGE;

    // Only add this settings item on non-site course pages.
    if (!$PAGE->course or $PAGE->course->id == 1) {
        return;
    }

    // Only let users with the appropriate capability see this settings item.
    if (!has_capability('local/children_management:view', context_system::instance())) {
        return;
    }

    if ($settingnode = $settingsnav->find('courseadmin', navigation_node::TYPE_COURSE)) {
        $strfoo = get_string('children_management_title', 'local_children_management');
        $url = new moodle_url('/local/children_management/index.php', array('id' => $PAGE->course->id));
        $foonode = navigation_node::create(
            $strfoo,
            $url,
            navigation_node::NODETYPE_LEAF,
            'children_management',
            'children_management',
            new pix_icon('t/addcontact', $strfoo)
        );
        if ($PAGE->url->compare($url, URL_MATCH_BASE)) {
            $foonode->make_active();
        }
        $settingnode->add_node($foonode);
    }
}

// Hàm gửi email OTP
function local_children_management_send_otp_email(string $recipientemail, string $otpcode): bool {
    global $CFG, $DB;

    // Lấy thông tin người nhận
    $recipient = $DB->get_record('user', ['email' => $recipientemail]);
    if (!$recipient) {
        // Hoặc tạo một user tạm thời nếu email không khớp user nào trong Moodle
        // Hoặc đơn giản là không gửi
        debugging('OTP email recipient user not found: ' . $recipientemail, DEBUG_NORMAL);
        return false;
    }

    $site = get_site();
    $subject = get_string('otpsubject', 'local_children_management', $site->fullname); // Tiêu đề email
    $fullmessagehtml = html_writer::tag('p', get_string('otpemailmessage', 'local_children_management', $otpcode)); // Nội dung HTML
    $fullmessage = $fullmessagehtml; // Nội dung văn bản thuần

    $emailresult = email_to_user(
        $recipient,
        $recipient, // From user (có thể là admin hoặc user mặc định của Moodle)
        $subject,
        $fullmessage,
        $fullmessagehtml
    );

    return $emailresult;
}