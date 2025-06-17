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
function local_children_management_extend_navigation_user($navigation, $user, $context) {
    if (has_capability('local/children_management:view', $context)) {
        $url = new moodle_url('/local/children_management/index.php', ['id' => $user->id]);
        $navigation->add(
            get_string('children_management_title', 'local_children_management'),
            $url,
            navigation_node::TYPE_SETTING,
            null,
            null,
            new pix_icon('i/children', '')
        );
    }
}
