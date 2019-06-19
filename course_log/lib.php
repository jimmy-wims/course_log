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
 * Public API of the log coursereport.
 *
 * Defines the APIs used by log coursereports
 *
 * @package    coursereport_course_log
 * @copyright  1999 onwards Martin Dougiamas (http://dougiamas.com)
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;


/**
 * Extend the navigation bar
 *
 * @global stdClass $USER
 * @param stdClass $nav
 * @param stdClass $course
 * @return array with two elements $all, $today
 */
function course_log_report_extend_navigation($nav, $course) {
    $url = new moodle_url('/course/report/course_log/index.php', array('id' => $course->id));
    $nav->add(get_string('title', 'coursereport_course_log'), $url);
}


/**
 * Is current user allowed to access this coursereport
 *
 * @access private defined in lib.php for performance reasons
 * @global stdClass $USER
 * @param stdClass $user
 * @param stdClass $course
 * @return array with two elements $all, $today
 */
function coursereport_course_log_can_access_user_coursereport($user, $course) {
    global $USER;

    $coursecontext = context_course::instance($course->id);
    $personalcontext = context_user::instance($user->id);

    if ($user->id == $USER->id) {
        if ($course->showcoursereports and (is_viewing($coursecontext, $USER) or is_enrolled($coursecontext, $USER))) {
            return array(true, true);
        }
    } else if (has_capability('moodle/user:viewuseractivitiescoursereport', $personalcontext)) {
        if ($course->showcoursereports and (is_viewing($coursecontext, $user) or is_enrolled($coursecontext, $user))) {
            return array(true, true);
        }
    }

    // Check if $USER shares group with $user (in case separated groups are enabled and 'moodle/site:accessallgroups' is disabled).
    if (!groups_user_groups_visible($course, $user->id)) {
        return array(false, false);
    }

    $today = false;
    $all = false;

    if (has_capability('coursereport/course_log:viewtoday', $coursecontext)) {
        $today = true;
    }
    if (has_capability('coursereport/course_log:view', $coursecontext)) {
        $all = true;
    }

    return array($all, $today);
}


/**
 * Return a list of page types
 *
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array a list of page types
 */
function coursereport_course_log_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = array(
        '*'                => get_string('page-x', 'pagetype'),
        'coursereport-*'         => get_string('page-coursereport-x', 'pagetype'),
        'coursereport-log-*'     => get_string('page-coursereport-log-x',  'coursereport_course_log'),
        'coursereport-log-index' => get_string('page-coursereport-log-index',  'coursereport_course_log'),
        'coursereport-log-user'  => get_string('page-coursereport-log-user',  'coursereport_course_log')
    );
    return $array;
}


