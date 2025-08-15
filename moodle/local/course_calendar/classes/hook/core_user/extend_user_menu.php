<?php
namespace local_course_calendar\hook\core_user;

use core_user\output\user_menu;
use moodle_url;
use lang_string;

class extend_user_menu {
    public static function callback(user_menu $menu): void {
        // Add a new menu item to the user dropdown
        $url = new moodle_url('/local/course_calendar/pages/my_absence_requests.php');
        $menu->add(
            'my_absence_requests', // Unique key
            $url,
            get_string('menuitemtitle', 'local_course_calendar'), // Title of the menu item
        );
    }
}
