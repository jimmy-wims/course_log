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
 * Displays different views of the logs.
 *
 * @package    coursereport_course_log
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require('../../../config.php');
require_once($CFG->dirroot.'/course/lib.php');
require_once($CFG->libdir.'/adminlib.php');
require_once($CFG->dirroot.'/lib/tablelib.php');


$id          = optional_param('id', 0, PARAM_INT);// Course ID.
$group       = optional_param('group', 0, PARAM_INT); // Group to display.
$date        = optional_param('date', 0, PARAM_ALPHANUMEXT); // Date to display.
$component   = optional_param('component','', PARAM_ALPHANUMEXT);
$modid       = optional_param('modid', 0, PARAM_ALPHANUMEXT); // Module id or 'site_errors'.
$page        = optional_param('page', '0', PARAM_INT);     // Which page to show.
$logformat   = optional_param('download', '', PARAM_ALPHA);
$logreader      = optional_param('logreader', '', PARAM_COMPONENT); // Reader which will be used for logsdisplay.

$params = array();

$id = $_GET['id'];
$params['id'] = $id;
if ($group !== 0) {
    $params['group'] = $group;
}
if ($date !== 0) {
    $date=strtotime($date);
    $params['date'] = $date;
}
if($component!=='')
{
    $params['component'] = $component;
}
if ($modid !== 0) {
    $params['modid'] = $modid;
}
if ($page !== '0') {
    $params['page'] = $page;
}
if ($logformat !== '') {
    $params['download'] = $logformat;
}
if ($logreader !== '') {
    $params['logreader'] = $logreader;
}
//url of the page
$url = new moodle_url("/course/report/course_log/index.php", $params);

$PAGE->set_url('/course/report/course_log/index.php', array('id' => $id));

//Current course
$course = null;
$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
require_login($course);
$context = context_course::instance($course->id);

//set the title and the header of the page
$PAGE->set_title($course->shortname .': '. get_string('logs'));
$PAGE->set_heading($course->fullname);

if(has_capability('coursereport/course_log:view', $PAGE->context))
{
    //creation of the table
    $coursereportlog = new coursereport_course_log_renderable($logreader, $course, $modid, $group, $url, $date,$component, $logformat, $page, 15, 'timecreated DESC');
    $output = $PAGE->get_renderer('coursereport_course_log');

    //table display
    $coursereportlog->setup_table();

    if (empty($logformat)) {
        echo $output->header();
        echo $output->render($coursereportlog);
    } else { //if the user want to download the table
        ob_clean();
        \core\session\manager::write_close();
        $coursereportlog->download();
        exit();
    }

    echo $output->footer();
}
else
{
    //if the user is a student the plugin will display an error message
    echo $OUTPUT->header();
         echo "<font size='1'><table class='xdebug-error xe-parse-error' dir='ltr' border='1' cellspacing='0' cellpadding='1'>
        <tr><th align='left' bgcolor='#f57900' colspan='5'><span style='background-color: #cc0000; color: #fce94f; font-size: x-large;'>( ! )</span><b>".get_string('access','local_course_group')."</b></th></tr>
        </table></font>";
    echo $OUTPUT->footer();
}
