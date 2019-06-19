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
 * Log coursereport renderer.
 *
 * @package    coursereport_course_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Report log renderer's for printing coursereports.
 *
 * @package    coursereport_course_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursereport_course_log_renderer extends plugin_renderer_base {

    /**
     * Render log coursereport page.
     *
     * @param coursereport_course_log_renderable $coursereportlog object of coursereport_course_log.
     */
    protected function render_coursereport_course_log(coursereport_course_log_renderable $coursereportlog) {
        if (empty($coursereportlog->selectedlogreader)) {
            echo $this->output->notification(get_string('nologreaderenabled', 'coursereport_course_log'), 'notifyproblem');
            return;
        }   
        $this->coursereport_selector_form($coursereportlog);
        $coursereportlog->tablelog->out($coursereportlog->perpage, true);
    }

    /**
     * Prints/return reader selector
     *
     * @param coursereport_course_log_renderable $coursereportlog log coursereport.
     */
    public function reader_selector(coursereport_course_log_renderable $coursereportlog) {
        $readers = $coursereportlog->get_readers(true);
        if (empty($readers)) {
            $readers = array(get_string('nologreaderenabled', 'coursereport_course_log'));
        }
        $url = fullclone ($coursereportlog->url);
        $url->remove_params(array('logreader'));
        $select = new single_select($url, 'logreader', $readers, $coursereportlog->selectedlogreader, null);
        $select->set_label(get_string('selectlogreader', 'coursereport_course_log'));
        echo $this->output->render($select);
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param coursereport_course_log_renderable $coursereportlog log coursereport.
     */
    public function coursereport_selector_form(coursereport_course_log_renderable $coursereportlog) {
        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $coursereportlog->url, 'method' => 'get'));
        echo html_writer::start_div();
            echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'id','value' => $_GET['id']));

            // Add group selector.
            $groups = $coursereportlog->get_group_list();
            if (!empty($groups)) {
                echo html_writer::label(get_string('selectagroup'), 'menugroup', false, array('class' => 'accesshide'));
                echo html_writer::select($groups, "group", $coursereportlog->groupid, get_string("allgroups"));
            }

            // Add component selector
            $components = $coursereportlog->get_components_options();
            echo html_writer::label(get_string('eventcomponent','coursereport_course_log'), 'menucomponent', false, array('class' => 'accesshide'));
            echo html_writer::select($components, "component", $coursereportlog->component, get_string('allcomponent','coursereport_course_log'));

            // Add activity selector.
            $activities = $coursereportlog->get_activities_list();
            echo html_writer::label(get_string('activities'), 'menumodid', false, array('class' => 'accesshide'));
            echo html_writer::select($activities, "modid", $coursereportlog->modid, get_string("allactivities"));
            
            //Add date selector
            if($coursereportlog->date!=0)
                echo html_writer::empty_tag('input', array('type' => 'date', 'name' => 'date', 'value' => "".date('Y-m-d', $coursereportlog->date)."","style" => "height: 6%"));
            else
            {
                echo html_writer::empty_tag('input', array('type' => 'date', 'name' => 'date', 'value' => 'dd-mm-aaaa',"style" => "height: 6%"));
            }
            
            //add submit button
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('gettheselogs'),
                        'class' => 'btn btn-secondary'));
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
}
