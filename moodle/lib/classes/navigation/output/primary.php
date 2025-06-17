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

namespace core\navigation\output;

use renderable;
use renderer_base;
use templatable;
use custom_menu;

/**
 * Primary navigation renderable
 *
 * This file combines primary nav, custom menu, lang menu and
 * usermenu into a standardized format for the frontend
 *
 * @package     core
 * @category    navigation
 * @copyright   2021 onwards Peter Dias
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class primary implements renderable, templatable {
    /** @var \moodle_page $page the moodle page that the navigation belongs to */
    private $page = null;

    /**
     * primary constructor.
     * @param \moodle_page $page
     */
    public function __construct($page) {
        $this->page = $page;
    }

    /**
     * Combine the various menus into a standardized output.
     *
     * @param renderer_base|null $output
     * @return array
     */
    public function export_for_template(?renderer_base $output = null): array {
        if (!$output) {
            $output = $this->page->get_renderer('core');
        }

        $menudata = (object) $this->merge_primary_and_custom($this->get_primary_nav(), $this->get_custom_menu($output));
        $moremenu = new \core\navigation\output\more_menu($menudata, 'navbar-nav', false);
        $mobileprimarynav = $this->merge_primary_and_custom($this->get_primary_nav(), $this->get_custom_menu($output), true);

        $languagemenu = new \core\output\language_menu($this->page);

        return [
            'mobileprimarynav' => $mobileprimarynav,
            'moremenu' => $moremenu->export_for_template($output),
            'lang' => !isloggedin() || isguestuser() ? $languagemenu->export_for_template($output) : [],
            'user' => $this->get_user_menu($output),
        ];
    }

    /**
     * Get the primary nav object and standardize the output
     *
     * @param \navigation_node|null $parent used for nested nodes, by default the primarynav node
     * @return array
     */
    protected function get_primary_nav($parent = null): array {
        if ($parent === null) {
            $parent = $this->page->primarynav;
        }
        $nodes = [];
        foreach ($parent->children as $node) {
            $children = $this->get_primary_nav($node);
            $activechildren = array_filter($children, function($child) {
                return !empty($child['isactive']);
            });
            if ($node->preceedwithhr && count($nodes) && empty($nodes[count($nodes) - 1]['divider'])) {
                $nodes[] = ['divider' => true];
            }
            $nodes[] = [
                'title' => $node->get_title(),
                'url' => $node->action(),
                'text' => $node->text,
                'icon' => $node->icon,
                'isactive' => $node->isactive || !empty($activechildren),
                'key' => $node->key,
                'children' => $children,
                'haschildren' => !empty($children) ? 1 : 0,
            ];
        }

        return $nodes;
    }

    /**
     * Custom menu items reside on the same level as the original nodes.
     * Fetch and convert the nodes to a standardised array.
     *
     * @param renderer_base $output
     * @return array
     */
    protected function get_custom_menu(renderer_base $output): array {
        global $CFG, $DB, $USER;

        if(isloggedin()) {
            $systemcontext = \context_system::instance();
            $usercontext = \context_user::instance($USER->id);
            
            /* 
                check current user is admin or not
                this is most quickly way to check if the current user is admin or not
            */ 

            $isadmin = is_siteadmin();

            /* 
                check if the user has the capability 'local/children_management:view' and not an admin 
                display the custom menu item for children management
            */

            if(has_capability('local/children_management:view', $systemcontext, $USER) 
                && !$isadmin
            ) {
                $stringlang1 = get_string('children_management', 'local_children_management');
                $stringURL1 = $CFG->wwwroot . '/local/children_management/index.php';
                $CFG->custommenuitems .= "$stringlang1 | $stringURL1";
            }
            

            /*
                Debugging for parent account. This parent account is Trinh Dinh Dung and when i logged in as this parent account,
                i dont see the custom menu items - the children management menu item.
                This is because the parent account does not have the capability 'local/children_management:view'.
                So, i will add a debug code to show the current course context and the roles and capabilities of the current user.
                This will help me to understand why the custom menu items are not showing up for this parent account.
                
                when i check variable systemcontext, i see that the context level is 10 (system context) and the instance id is 1.
                This means that the current user is in the system context and this account haven't capability 'local/children_management:view'.
            */

            // else {
            //     echo '<pre>';
            //     echo '<h2>Debugging Current Course Context:</h2>';
            //     var_dump($systemcontext);
            //     var_dump($usercontext);
            //     echo '</pre>';

            //     echo '<pre>'; // Bắt đầu thẻ <pre> để định dạng output

            //     global $USER, $DB; // Đảm bảo $USER và $DB có sẵn

            //     // --- PHẦN 1: HIỂN THỊ CÁC VAI TRÒ ĐƯỢC GÁN CHO NGƯỜI DÙNG HIỆN TẠI ---
            //     echo '<h2>1. Current User Roles:</h2>';

            //     // Lấy tất cả các vai trò được gán cho người dùng hiện tại ở tất cả các ngữ cảnh
            //     // Moodle có hàm get_user_roles_in_context() hoặc get_user_roles()
            //     // Tuy nhiên, để debug nhanh, việc truy vấn trực tiếp mdl_role_assignments cũng hữu ích.

            //     $roles_assigned = $DB->get_records_sql("
            //         SELECT
            //             ra.id AS assignment_id,
            //             r.name AS role_name,
            //             r.archetype,
            //             c.contextlevel,
            //             c.instanceid,
            //             c.id AS context_id,
            //             CASE
            //                 WHEN c.contextlevel = 10 THEN 'System'
            //                 WHEN c.contextlevel = 20 THEN (SELECT name FROM {course_categories} WHERE id = c.instanceid)
            //                 WHEN c.contextlevel = 30 THEN (SELECT fullname FROM {course} WHERE id = c.instanceid)
            //                 WHEN c.contextlevel = 40 THEN (SELECT CONCAT(firstname, ' ', lastname) FROM {user} WHERE id = c.instanceid)
            //                 ELSE 'Other Context'
            //             END AS context_description
            //         FROM
            //             {role_assignments} ra
            //         JOIN
            //             {role} r ON ra.roleid = r.id
            //         JOIN
            //             {context} c ON ra.contextid = c.id
            //         WHERE
            //             ra.userid = :userid
            //         ORDER BY c.contextlevel, r.name
            //     ", ['userid' => $USER->id]);

            //     if ($roles_assigned) {
            //         echo "<ul>";
            //         foreach ($roles_assigned as $assignment) {
            //             echo "<li><strong>Role:</strong> {$assignment->role_name} ";
            //             echo "(Archetype: {$assignment->archetype}) ";
            //             echo "in <strong>Context:</strong> {$assignment->context_description} ";
            //             echo "(Context ID: {$assignment->context_id}, Level: {$assignment->contextlevel}, Instance ID: {$assignment->instanceid})</li>";
            //         }
            //         echo "</ul>";
            //     } else {
            //         echo "<p>No roles assigned to the current user.</p>";
            //     }

            //     // --- PHẦN 2: HIỂN THỊ CÁC CAPABILITY CỦA NGƯỜI DÙNG HIỆN TẠI TRONG NGỮ CẢNH CỤ THỂ ---
            //     // Thường thì bạn sẽ kiểm tra trong CONTEXT_SYSTEM hoặc CONTEXT_COURSE
            //     echo '<h2>2. Capabilities of Current User in System Context:</h2>';

            //     // Lấy ngữ cảnh hệ thống
            //     $systemcontext = \context_system::instance();

            //     // Lấy tất cả các capability đã được Moodle định nghĩa
            //     $allcapabilities = $DB->get_records('capabilities', null, 'name ASC', 'name');

            //     if ($allcapabilities) {
            //         echo "<ul>";
            //         foreach ($allcapabilities as $cap) {
            //             $capabilityname = $cap->name;
            //             // Kiểm tra xem người dùng có capability này trong ngữ cảnh hệ thống hay không
            //             $hascap = has_capability($capabilityname, $systemcontext, $USER);
            //             if ($hascap) {
            //                 echo "<li><strong style='color: green;'>&#10004; {$capabilityname}</strong></li>"; // Dấu tích xanh nếu có
            //             } else {
            //                 // echo "<li><span style='color: red;'>&#10006; {$capabilityname}</span></li>"; // Dấu X đỏ nếu không có
            //             }
            //         }
            //         echo "</ul>";
            //     } else {
            //         echo "<p>No capabilities found in Moodle.</p>";
            //     }


            //     // --- PHẦN 3: HIỂN THỊ CÁC CAPABILITY CỦA NGƯỜI DÙNG HIỆN TẠI TRONG NGỮ CẢNH NGƯỜI DÙNG (USER CONTEXT) ---
            //     // Rất quan trọng nếu bạn đang debug vai trò Parent/Child
            //     echo '<h2>3. Capabilities of Current User in THEIR OWN User Context:</h2>';

            //     $usercontext = \context_user::instance($USER->id);

            //     if ($allcapabilities) { // Dùng lại danh sách capabilities đã lấy
            //         echo "<ul>";
            //         foreach ($allcapabilities as $cap) {
            //             $capabilityname = $cap->name;
            //             $hascap = has_capability($capabilityname, $usercontext, $USER);
            //             if ($hascap) {
            //                 echo "<li><strong style='color: green;'>&#10004; {$capabilityname}</strong></li>";
            //             } else {
            //                 // echo "<li><span style='color: red;'>&#10006; {$capabilityname}</span></li>";
            //             }
            //         }
            //         echo "</ul>";
            //     }

            //     echo '</pre>'; // Kết thúc thẻ <pre>
            // }
        }
        
        
        // Early return if a custom menu does not exists.
        if (empty($CFG->custommenuitems)) {
            return [];
        }

        $custommenuitems = $CFG->custommenuitems;
        $currentlang = current_language();
        $custommenunodes = custom_menu::convert_text_to_menu_nodes($custommenuitems, $currentlang);
        $nodes = [];
        foreach ($custommenunodes as $node) {
            $nodes[] = $node->export_for_template($output);
        }

        return $nodes;
    }

    /**
     * When defining custom menu items, the active flag is not obvserved correctly. Therefore, the merge of the primary
     * and custom navigation must be handled a bit smarter. Change the "isactive" flag of the nodes (this may set by
     * default in the primary nav nodes but is entirely missing in the custom nav nodes).
     * Set the $expandedmenu argument to true when the menu for the mobile template is build.
     *
     * @param array $primary
     * @param array $custom
     * @param bool $expandedmenu
     * @return array
     */
    protected function merge_primary_and_custom(array $primary, array $custom, bool $expandedmenu = false): array {
        if (empty($custom)) {
            return $primary; // No custom nav, nothing to merge.
        }
        // Remember the amount of primary nodes and whether we changed the active flag in the custom menu nodes.
        $primarylen = count($primary);
        $changed = false;
        foreach (array_keys($custom) as $i) {
            if (!$changed) {
                if ($this->flag_active_nodes($custom[$i], $expandedmenu)) {
                    $changed = true;
                }
            }
            $primary[] = $custom[$i];
        }
        // In case some custom node is active, mark all primary nav elements as inactive.
        if ($changed) {
            for ($i = 0; $i < $primarylen; $i++) {
                $primary[$i]['isactive'] = false;
            }
        }
        return $primary;
    }

    /**
     * Recursive checks if any of the children is active. If that's the case this node (the parent) is active as
     * well. If the node has no children, check if the node itself is active. Use pass by reference for the node
     * object because we actively change/set the "isactive" flag inside the method and this needs to be kept at the
     * callers side.
     * Set $expandedmenu to true, if the mobile menu is done, in this case the active flag gets the node that is
     * actually active, while the parent hierarchy of the active node gets the flag isopen.
     *
     * @param object $node
     * @param bool $expandedmenu
     * @return bool
     */
    protected function flag_active_nodes(object $node, bool $expandedmenu = false): bool {
        global $FULLME;
        $active = false;
        foreach (array_keys($node->children ?? []) as $c) {
            if ($this->flag_active_nodes($node->children[$c], $expandedmenu)) {
                $active = true;
            }
        }
        // One of the children is active, so this node (the parent) is active as well.
        if ($active) {
            if ($expandedmenu) {
                $node->isopen = true;
            } else {
                $node->isactive = true;
            }
            return true;
        }

        // By default, the menu item node to check is not active.
        $node->isactive = false;

        // Check if the node url matches the called url. The node url may omit the trailing index.php, therefore check
        // this as well.
        if (empty($node->url)) {
            // Current menu node has no url set, so it can't be active.
            return false;
        }
        $nodeurl = parse_url($node->url);
        $current = parse_url($FULLME ?? '');

        $pathmatches = false;

        // Check for same host names before comparing the path.
        $currenthost = array_key_exists('host', $current) ? strtolower($current['host']) : '';
        $nodehost = array_key_exists('host', $nodeurl) ? strtolower($nodeurl['host']) : '';
        if ($currenthost !== $nodehost) {
            return false;
        }
        // Exact match of the path of node and current url.
        $nodepath = $nodeurl['path'] ?? '/';
        $currentpath = $current['path'] ?? '/';
        if ($nodepath === $currentpath) {
            $pathmatches = true;
        }
        // The current url may be trailed by a index.php, otherwise it's the same as the node path.
        if (!$pathmatches && $nodepath . 'index.php' === $currentpath) {
            $pathmatches = true;
        }
        // No path did match, so the node can't be active.
        if (!$pathmatches) {
            return false;
        }
        // We are here because the path matches, so now look at the query string.
        $nodequery = $nodeurl['query'] ?? '';
        $currentquery = $current['query'] ?? '';
        // If the node has no query string defined, then the patch match is sufficient.
        if (empty($nodeurl['query'])) {
            $node->isactive = true;
            return true;
        }
        // If the node contains a query string then also the current url must match this query.
        if ($nodequery === $currentquery) {
            $node->isactive = true;
        }
        return $node->isactive;
    }

    /**
     * Get/Generate the user menu.
     *
     * This is leveraging the data from user_get_user_navigation_info and the logic in $OUTPUT->user_menu()
     *
     * @param renderer_base $output
     * @return array
     */
    public function get_user_menu(renderer_base $output): array {
        global $CFG, $USER, $PAGE;
        require_once($CFG->dirroot . '/user/lib.php');

        $usermenudata = [];
        $submenusdata = [];
        $info = user_get_user_navigation_info($USER, $PAGE);
        if (isset($info->unauthenticateduser)) {
            $info->unauthenticateduser['content'] = get_string($info->unauthenticateduser['content']);
            $info->unauthenticateduser['url'] = get_login_url();
            return (array) $info;
        }
        // Gather all the avatar data to be displayed in the user menu.
        $usermenudata['avatardata'][] = [
            'content' => $info->metadata['useravatar'],
            'classes' => 'current'
        ];
        $usermenudata['userfullname'] = $info->metadata['realuserfullname'] ?? $info->metadata['userfullname'];

        // Logged in as someone else.
        if ($info->metadata['asotheruser']) {
            $usermenudata['avatardata'][] = [
                'content' => $info->metadata['realuseravatar'],
                'classes' => 'realuser'
            ];
            $usermenudata['metadata'][] = [
                'content' => get_string('loggedinas', 'moodle', $info->metadata['userfullname']),
                'classes' => 'viewingas'
            ];
        }

        // Gather all the meta data to be displayed in the user menu.
        $metadata = [
            'asotherrole' => [
                'value' => 'rolename',
                'class' => 'role role-##GENERATEDCLASS##',
            ],
            'userloginfail' => [
                'value' => 'userloginfail',
                'class' => 'loginfailures',
            ],
            'asmnetuser' => [
                'value' => 'mnetidprovidername',
                'class' => 'mnet mnet-##GENERATEDCLASS##',
            ],
        ];
        foreach ($metadata as $key => $value) {
            if (!empty($info->metadata[$key])) {
                $content = $info->metadata[$value['value']] ?? '';
                $generatedclass = strtolower(preg_replace('#[ ]+#', '-', trim($content)));
                $customclass = str_replace('##GENERATEDCLASS##', $generatedclass, ($value['class'] ?? ''));
                $usermenudata['metadata'][] = [
                    'content' => $content,
                    'classes' => $customclass
                ];
            }
        }

        $modifiedarray = array_map(function($value) {
            $value->divider = $value->itemtype == 'divider';
            $value->link = $value->itemtype == 'link';
            if (isset($value->pix) && !empty($value->pix)) {
                $value->pixicon = $value->pix;
                unset($value->pix);
            }
            return $value;
        }, $info->navitems);

        // Include the language menu as a submenu within the user menu.
        $languagemenu = new \core\output\language_menu($this->page);
        $langmenu = $languagemenu->export_for_template($output);
        if (!empty($langmenu)) {
            $languageitems = $langmenu['items'];
            // If there are available languages, generate the data for the the language selector submenu.
            if (!empty($languageitems)) {
                $langsubmenuid = uniqid();
                // Generate the data for the link to language selector submenu.
                $language = (object) [
                    'itemtype' => 'submenu-link',
                    'submenuid' => $langsubmenuid,
                    'title' => get_string('language'),
                    'divider' => false,
                    'submenulink' => true,
                ];

                // Place the link before the 'Log out' menu item which is either the last item in the menu or
                // second to last when 'Switch roles' is available.
                $menuposition = count($modifiedarray) - 1;
                if (has_capability('moodle/role:switchroles', $PAGE->context)) {
                    $menuposition = count($modifiedarray) - 2;
                }
                array_splice($modifiedarray, $menuposition, 0, [$language]);

                // Generate the data for the language selector submenu.
                $submenusdata[] = (object)[
                    'id' => $langsubmenuid,
                    'title' => get_string('languageselector'),
                    'items' => $languageitems,
                ];
            }
        }

        // Add divider before the last item.
        $modifiedarray[count($modifiedarray) - 2]->divider = true;
        $usermenudata['items'] = $modifiedarray;
        $usermenudata['submenus'] = array_values($submenusdata);

        return $usermenudata;
    }
}
