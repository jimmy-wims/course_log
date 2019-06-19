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
 * Table log for displaying logs.
 *
 * @package    coursereport_course_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

/**
 * Table log class for displaying logs.
 *
 * @package    coursereport_course_log
 * @copyright  2014 Rajesh Taneja <rajesh.taneja@gmail.com>
 * @copyright  2019 Jimmy WIMS
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class coursereport_course_log_table_log extends table_sql {

    /** @var array list of user fullnames shown in coursereport */
    private $userfullnames = array();

    /** @var array list of context name shown in coursereport */
    private $contextname = array();

    /** @var stdClass filters parameters */
    private $filterparams;

    /**
     * Sets up the table_log parameters.
     *
     * @param string $uniqueid unique id of form.
     * @param stdClass $filterparams (optional) filter params.
     *     - int courseid: id of course
     *     - int|string modid: Module id or "site_errors" to view site errors
     *     - int groupid: Group id
     *     - \core\log\sql_reader logreader: reader from which data will be fetched.
     *     - string action: view action
     *     - int date: Date from which logs to be viewed.
     */
    public function __construct($uniqueid, $filterparams = null) {
        parent::__construct($uniqueid);

        $this->set_attribute('class', 'coursereportlog generaltable generalbox');
        $this->filterparams = $filterparams;
        // Add course column if logs are displayed for site.
        $cols = array();
        $headers = array();
     

        $this->define_columns(array_merge($cols, array('time', 'fullnameuser', 'group','context', 'component',
                'eventname', 'description')));
        $this->define_headers(array_merge($headers, array(
                get_string('time'),
                get_string('nameuser','coursereport_course_log'),
                get_string('group'),
                get_string('eventcontext', 'coursereport_course_log'),
                get_string('eventcomponent', 'coursereport_course_log'),
                get_string('eventname'),
                get_string('description'),
                )
            ));
        $this->collapsible(false);
        $this->sortable(false);
        $this->pageable(true);
    }

    /**
     * Generate the time column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the time column
     */
    public function col_time($event) {

        if (empty($this->download)) {
            $dateformat = get_string('strftimedatetime', 'core_langconfig');
        } else {
            $dateformat = get_string('strftimedatetimeshort', 'core_langconfig');
        }
        return userdate($event->timecreated, $dateformat);
    }

    /**
     * Generate the group column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the group column
     */
    public function col_group($event) {
        $groups = groups_get_user_groups($event->courseid, $event->userid);
        $groupname="";
        if(isset($groups[0][0]))
        {
            for($i=0;$i<count($groups);$i++) 
            {
                for($j=0;$j<count($groups[$i]);$j++)
                {
                    if(isset($groups[$i][$j]))
                        $groupname = $groupname . groups_get_group_name($groups[$i][$j]) . ", ";  
                }
            }    
            $groupname=substr($groupname,0,-2);
            return $groupname;
        }
        return get_string('nogroup','coursereport_course_log');
    }


    /**
     * Generate the username column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the username column
     */
    public function col_fullnameuser($event) {
        // Get extra event data for origin and realuserid.
       global $DB;

       $user = $DB->get_record_sql('SELECT firstname,lastname FROM {user} WHERE id='.$event->userid.';');
       return $user->firstname . " " . $user->lastname; 
    }


    /**
     * Generate the context column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the context column
     */
    public function col_context($event) {
        // Add context name.
        if ($event->contextid) {
            // If context name was fetched before then return, else get one.
            if (isset($this->contextname[$event->contextid])) {
                return $this->contextname[$event->contextid];
            } else {
                $context = context::instance_by_id($event->contextid, IGNORE_MISSING);
                if ($context) {
                    $contextname = $context->get_context_name(true);
                    if (empty($this->download) && $url = $context->get_url()) {
                        $contextname = html_writer::link($url, $contextname);
                    }
                } else {
                    $contextname = get_string('other');
                }
            }
        } else {
            $contextname = get_string('other');
        }

        $this->contextname[$event->contextid] = $contextname;
        return $contextname;
    }

    /**
     * Generate the component column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the component column
     */
    public function col_component($event) {
        // Component.
        $componentname = $event->component;
        if (($event->component === 'core') || ($event->component === 'legacy')) {
            return  get_string('coresystem');
        } else if (get_string_manager()->string_exists('pluginname', $event->component)) {
            return get_string('pluginname', $event->component);
        } else {
            return $componentname;
        }
    }

    /**
     * Generate the event name column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the event name column
     */
    public function col_eventname($event) {
        // Event name.
        
            $eventname = $event->get_name();
        
        // Only encode as an action link if we're not downloading.
        if (($url = $event->get_url()) && empty($this->download)) {
            $eventname = $this->action_link($url, $eventname, 'action');
        }
        return $eventname;
    }

    /**
     * Generate the description column.
     *
     * @param stdClass $event event data.
     * @return string HTML for the description column
     */
    public function col_description($event) {
        global$DB;

        //remplace the user id by the name of the user
        $description=$event->get_description();
        $description = explode("' ",$description);
        $desc="";
        for($i=0;$i<count($description);$i++)
        {
            if(preg_match('`user`',$description[$i]))
            {
                $id = $description[$i][strlen($description[$i])-1];
                $sql = "SELECT firstname,lastname FROM mdl_user WHERE id=?";
                $result = $DB->get_records_sql($sql, array($id),IGNORE_MISSING);

                foreach ($result as $key) {
                    $description[$i]=substr($description[$i],0,-1);
                    $user=explode('with',$description[$i]);
                    $description[$i]=$user[0]."'$key->firstname $key->lastname";
                }
            }
            $desc = $desc . $description[$i]."' ";
        }
        $desc=substr($desc,0,-2);
        return $desc;
    }

    /**
     * Method to create a link with popup action.
     *
     * @param moodle_url $url The url to open.
     * @param string $text Anchor text for the link.
     * @param string $name Name of the popup window.
     *
     * @return string html to use.
     */
    protected function action_link(moodle_url $url, $text, $name = 'popup') {
        global $OUTPUT;
        $link = new action_link($url, $text, new popup_action('click', $url, $name, array('height' => 440, 'width' => 700)));
        return $OUTPUT->render($link);
    }

    /**
     * Helper function which is used by build logs to get course module sql and param.
     *
     * @return array sql and param for action.
     */
    public function get_cm_sql() {
        $joins = array();
        $params = array();

      
            $joins[] = "contextinstanceid = :contextinstanceid";
            $joins[] = "contextlevel = :contextmodule";
            $params['contextinstanceid'] = $this->filterparams->modid;
            $params['contextmodule'] = CONTEXT_MODULE;
        

        $sql = implode(' AND ', $joins);
        return array($sql, $params);
    }

    /**
     * Query the reader. Store results in the object for use by build_table.
     *
     * @param int $pagesize size of page for paginated displayed table.
     * @param bool $useinitialsbar do you want to use the initials bar.
     */
    public function query_db($pagesize, $useinitialsbar = true) {
        global $DB;

        $joins = array();
        $params = array();

        //show only the course and student logs 
        $joins[] = "(mdl_logstore_standard_log.userid in ( SELECT ra.userid FROM mdl_role_assignments AS ra LEFT JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid LEFT JOIN mdl_role AS r ON ra.roleid = r.id LEFT JOIN mdl_context AS c ON c.id = ra.contextid LEFT JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id WHERE r.shortname='student' AND e.courseid=".$this->filterparams->courseid."))";
        
        $joins[] = "component!='tool_usertours'";
        $joins[] = "NOT (action='viewed' and component='core' or action='updated' and component='core' or component='mod_quiz' and action='viewed' )";

        $groupid = 0;
        $groupid = $this->filterparams->groupid;
        $joins[] = "courseid = :courseid";
        $params['courseid'] = $this->filterparams->courseid;

        if (!empty($this->filterparams->siteerrors)) {
            $joins[] = "( action='error' OR action='infected' OR action='failed' )";
        }

        if (!empty($this->filterparams->modid)) {
            list($actionsql, $actionparams) = $this->get_cm_sql();
            $joins[] = $actionsql;
            $params = array_merge($params, $actionparams);
        }

        // Getting all members of a group.
        if ($groupid and empty($this->filterparams->userid)) {
            if ($gusers = groups_get_members($groupid)) {
                $gusers = array_keys($gusers);
                $joins[] = 'userid IN (' . implode(',', $gusers) . ')';
            } else {
                $joins[] = 'userid = 0'; // No users in groups, so we want something that will always be false.
            }
        } else if (!empty($this->filterparams->userid)) {
            $joins[] = "userid = :userid";
            $params['userid'] = $this->filterparams->userid;
        }

        if (!empty($this->filterparams->date)) {
            $joins[] = "timecreated > :date AND timecreated < :enddate";
            $params['date'] = $this->filterparams->date;
            $params['enddate'] = $this->filterparams->date + DAYSECS; // Show logs only for the selected date.
        }

        if(!empty($this->filterparams->component!=='')){
            $joins[] = "component = :comp";
            //Show logs only for the selected component
            switch ($this->filterparams->component) {
                case 0:
                    $params['comp'] = "core";
                    break;
                case 1:
                    $params['comp'] = "mod_quiz";
                    break;
                case 2:
                    $params['comp'] = "mod_assign";
                    break;
                case 3:
                    $params['comp'] = "mod_resource";
                    break;
            }
        }

        $selector = implode(' AND ', $joins);

        if (!$this->is_downloading()) {
            $total = $this->filterparams->logreader->get_events_select_count($selector, $params);
            $this->pagesize($pagesize, $total);
        } else {
            $this->pageable(false);
        }

        // Get the users and course data.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
            $this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

        // Update list of users which will be displayed on log page.
        // $this->update_users_used();

        // Get the events. Same query than before; even if it is not likely, logs from new users
        // may be added since last query so we will need to work around later to prevent problems.
        // In almost most of the cases this will be better than having two opened recordsets.
        $this->rawdata = $this->filterparams->logreader->get_events_select_iterator($selector, $params,
            $this->filterparams->orderby, $this->get_page_start(), $this->get_page_size());

    }
}
