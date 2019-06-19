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
use core\log\manager;

/**
 * Report log renderable class.
 *
 * @package    coursereport_course_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursereport_course_log_renderable implements renderable {
    /** @var manager log manager */
    protected $logmanager;

    /** @var string selected log reader pluginname */
    public $selectedlogreader = null;

    /** @var int page number */
    public $page;

    /** @var int perpage records to show */
    public $perpage;

    /** @var stdClass course record */
    public $course;

    /** @var moodle_url url of coursereport page */
    public $url;

    /** @var int selected date from which records should be displayed */
    public $date;

    /** @var int selected moduleid */
    public $modid;

    /** @var string selected log format */
    public $logformat;

    /** @var string order to sort */
    public $order;

    /** @var int group id */
    public $groupid;

    /** @var table_log table log which will be used for rendering logs */
    public $tablelog;

    /** @var component selected component from wich records should be displayed*/
    public $component;

    /**
     * Constructor.
     *
     * @param string $logreader (optional)reader pluginname from which logs will be fetched.
     * @param stdClass|int $course (require) course record or id
     * @param int|string $modid (optional) id of acivitie
     * @param int $groupid (optional) groupid of user.
     * @param moodle_url|string $url (require) page url.
     * @param int $date date (optional) timestamp of start of the day for which logs will be displayed.
     * @param int component (optional) component for which logs will be displayed
     * @param string $logformat log format.
     * @param int $page (optional) page number.
     * @param int $perpage (optional) number of records to show per page.
     * @param string $order (optional) sortorder of fetched records
     */
    public function __construct($logreader = "", $course, $modid = 0, $groupid = 0, $url, $date = 0,$component = '', $logformat='showashtml', $page = 0, $perpage, $order) {

        global $PAGE;

        // Use first reader as selected reader, if not passed.
        if (empty($logreader)) {
            $readers = $this->get_readers();
            if (!empty($readers)) {
                reset($readers);
                $logreader = key($readers);
            } else {
                $logreader = null;
            }
        }
       
        $url = new moodle_url($url);

        $this->selectedlogreader = $logreader;
        $url->param('logreader', $logreader);

        $this->course = $course;
        $this->date = $date;
        $this->page = $page;
        $this->perpage = $perpage;
        $this->url = $url;
        $this->order = $order;
        $this->modid = $modid;
        $this->groupid = $groupid;
        $this->logformat = $logformat;
        $this->component = $component;
    }

    /**
     * Get a list of enabled sql_reader objects/name
     *
     * @param bool $nameonly if true only reader names will be returned.
     * @return array core\log\sql_reader object or name.
     */
    public function get_readers($nameonly = false) {
        if (!isset($this->logmanager)) {
            $this->logmanager = get_log_manager();
        }

        $readers = $this->logmanager->get_readers('core\log\sql_reader');
        if ($nameonly) {
            foreach ($readers as $pluginname => $reader) {
                $readers[$pluginname] = $reader->get_name();
            }
        }
        return $readers;
    }


    /**
     * Return list of component options.
     *
     * @return array component options.
     */
    public function get_components_options() {
        global $DB;
        $sql ='SELECT DISTINCT component from mdl_logstore_standard_log';
        $components = array();
        $result = $DB->get_records_sql($sql);
        foreach ($result as $res) {
            if( $res->component=='mod_resource' || $res->component=='mod_quiz' || $res->component=='mod_assign' || ($res->component=='core'))
                $components[]=get_string($res->component,'coursereport_course_log');
        }
        return $components;
     }



    /**
     * Helper function to return list of activities to show in selection filter.
     *
     * @return array list of activities.
     */
    public function get_activities_list() {
        $activities = array();

        $modinfo = get_fast_modinfo($this->course);
        if (!empty($modinfo->cms)) {
            $section = 0;
            $thissection = array();
            foreach ($modinfo->cms as $cm) {
                if (!$cm->uservisible || !$cm->has_view()) {
                    continue;
                }
                if ($cm->sectionnum > 0 and $section <> $cm->sectionnum) {
                    $activities[] = $thissection;
                    $thissection = array();
                }
                $section = $cm->sectionnum;
                $modname = strip_tags($cm->get_formatted_name());
                if (core_text::strlen($modname) > 55) {
                    $modname = core_text::substr($modname, 0, 50)."...";
                }
                if (!$cm->visible) {
                    $modname = "(".$modname.")";
                }
                $key = get_section_name($this->course, $cm->sectionnum);
                if (!isset($thissection[$key])) {
                    $thissection[$key] = array();
                }
                $thissection[$key][$cm->id] = $modname;
            }
            if (!empty($thissection)) {
                $activities[] = $thissection;
            }
        }
        return $activities;
    }
    
    /**
     * Return list of groups.
     *
     * @return array list of groups.
     */
    public function get_group_list() {

        $groups = array();
        if ($cgroups = groups_get_all_groups($this->course->id)) {
            foreach ($cgroups as $cgroup) {
                $groups[$cgroup->id] = $cgroup->name;
            }
        }
        return $groups;
    }

    /**
     * Setup table log.
     */
    public function setup_table() {
        $readers = $this->get_readers();

        $filter = new \stdClass();
        if (!empty($this->course)) {
            $filter->courseid = $this->course->id;
        } else {
            $filter->courseid = 0;
        }
        $filter->modid = $this->modid;
        $filter->groupid = $this->groupid;
        $filter->logreader = $readers[$this->selectedlogreader];
        $filter->date = $this->date;
        $filter->component = $this->component;
        $filter->orderby = $this->order;
        // If showing site_errors.
        if ('site_errors' === $this->modid) {
            $filter->siteerrors = true;
            $filter->modid = 0;
        }

        $this->tablelog = new coursereport_course_log_table_log('coursereport_course_log', $filter);
        $this->tablelog->define_baseurl($this->url);
        $this->tablelog->is_downloadable(true);
        $this->tablelog->show_download_buttons_at(array(TABLE_P_BOTTOM));
    }

    /**
     * Download logs in specified format.
     */
    public function download() {
        $filename = 'logs_' . userdate(time(), get_string('backupnameformat', 'langconfig'), 99, false);
        if ($this->course->id !== SITEID) {
            $courseshortname = format_string($this->course->shortname, true,
                    array('context' => context_course::instance($this->course->id)));
            $filename = clean_filename('logs_' . $courseshortname . '_' . userdate(time(),
                    get_string('backupnameformat', 'langconfig'), 99, false));
        }
        $this->tablelog->is_downloading($this->logformat, $filename);
        $this->tablelog->out($this->perpage, false);
    }
}
