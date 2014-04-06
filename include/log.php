<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
 * A full copyright notice can be read in "/info/copyright.txt".
 * For a full list of contributors, see "credits.txt".
 *
 * Open eClass is an open platform distributed in the hope that it will
 * be useful (without any warranty), under the terms of the GNU (General
 * Public License) as published by the Free Software Foundation.
 * The full license can be read in "/info/license/license_gpl.txt".
 *
 * Contact address: GUnet Asynchronous eLearning Group,
 *                  Network Operations Center, University of Athens,
 *                  Panepistimiopolis Ilissia, 15784, Athens, Greece
 *                  e-mail: info@openeclass.org
 * ======================================================================== */

/**
 * @file log.php
 * @author Yannis Exidaridis <jexi@noc.uoa.gr>
 * @brief defines class Log for logging actions
 */
define('LOG_INSERT', 1);
define('LOG_MODIFY', 2);
define('LOG_DELETE', 3);
define('LOG_PROFILE', 4);
define('LOG_CREATE_COURSE', 5);
define('LOG_DELETE_COURSE', 6);
define('LOG_MODIFY_COURSE', 7);
define('LOG_LOGIN_FAILURE', 8);
define('LOG_DELETE_USER', 9);

define('LOGS_PER_PAGE', 15);

class Log {

    /**
     * record users actions
     * @param type $course_id
     * @param type $module_id
     * @param type $action_type
     * @param type $details
     * @return none;
     */
    public static function record($course_id, $module_id, $action_type, $details) {

        // check `config` values for logging
        if (get_config('disable_log_actions')) {
            return;
        } else {
            if (get_config('disable_log_system_actions') and $module_id == 0) {
                return;
            } elseif (get_config('disable_log_course_actions')) {
                return;
            }
        }

        if (!isset($_SESSION['uid'])) { // it is used only when logging login failures
            $userid = 0;
        } else {
            $userid = $_SESSION['uid']; // in all other cases
        }
        db_query("INSERT INTO log SET
                                user_id = $userid,
                                course_id = $course_id,
                                module_id = $module_id,
                                details = " . quote(serialize($details)) . ",
                                action_type = $action_type,
                                ts = NOW(),
                                ip = '$_SERVER[SERVER_ADDR]'");
        return;
    }

    /**
     * display users logging        
     * Note: $module_id = $course_id = 0 means other logging (e.g. modify user profile, course creation etc.)
     * @param int $course_id (-1 means all courses)
     * @param type $user_id (-1 means all users)
     * @param int $module_id (-1 means all modules)
     * @param type $logtype (-1 means logtypes)
     * @param type $date_from
     * @param type $date_now
     * @param type script_page
     * @param type $limit (used for paging)
     * @param type $page_link (used for paging)
     * @return none        
     */
    public function display($course_id, $user_id, $module_id, $logtype, $date_from, $date_now, $script_page, $limit, $page_link) {

        global $tool_content, $modules;
        global $langNoUsersLog, $langDate, $langUser, $langAction, $langDetail,
        $langCourse, $langModule, $langAdminUsers, $langExternalLinks;

        $q1 = $q2 = $q3 = $q4 = '';

        if ($user_id != -1) {
            $q1 = "AND user_id = $user_id"; // display specific user
        }

        if ($logtype > 0) {
            $q3 = "AND action_type = $logtype"; // specific course logging
            if ($logtype > 3) { // specific system logging
                $module_id = $course_id = 0;
            }
        } elseif ($logtype == -2) { // display all system logging
            $q2 = "AND module_id = 0";
            $q4 = "AND course_id = 0";
        }

        if ($module_id > 0) {
            $q2 = "AND module_id = $module_id"; // display specific module
        } elseif ($module_id == -1) { // display all course module logging
            $q2 = "AND module_id > 0"; // but exclude system logging
        }

        if ($course_id > 0) {
            $q4 = "AND course_id = $course_id"; // display specific course
        } elseif ($course_id == -1) { // display all course logging
            $q4 = "AND course_id > 0"; // but exclude system logging
        }
        // count logs
        $num_of_logs = mysql_num_rows(db_query("SELECT * FROM log WHERE ts BETWEEN '$date_from' AND '$date_now' $q1 $q2 $q3 $q4"));
        // fetch logs
        $sql = db_query("SELECT user_id, course_id, module_id, details, action_type, ts FROM log
                                WHERE ts BETWEEN '$date_from' AND '$date_now'
                                $q1 $q2 $q3 $q4
                                ORDER BY ts DESC LIMIT $limit, " . LOGS_PER_PAGE . "");
        if ($num_of_logs > 0) {
            if ($course_id > 0) {
                $tool_content .= "<div class='info'>$langCourse: " . course_id_to_title($course_id) . "</div>";
            }
            if ($module_id > 0) {
                if ($module_id == MODULE_ID_USERS) {
                    $tool_content .= "<div class='info'>$langModule: " . $langAdminUsers . "</div>";
                } elseif ($module_id == MODULE_ID_TOOLADMIN) {
                    $tool_content .= "<div class='info'>$langModule: " . $langExternalLinks . "</div>";
                } else {
                    $tool_content .= "<div class='info'>$langModule: " . $modules[$module_id]['title'] . "</div>";
                }
            }
            // log paging
            if ($num_of_logs > LOGS_PER_PAGE) {
                $tool_content .= show_paging($limit, LOGS_PER_PAGE, $num_of_logs, $script_page, $page_link);
            }
            $tool_content .= "<table class='tbl'>";
            // log header
            $tool_content .= "<tr><th>$langDate</th><th>$langUser</th>";
            if ($course_id == -1) {
                $tool_content .= "<th>$langCourse</th>";
            }
            if ($module_id == -1) {
                $tool_content .= "<th>$langModule</th>";
            }
            $tool_content .= "<th>$langAction</th><th>$langDetail</th>";
            $tool_content .= "</tr>";
            // display logs
            while ($r = mysql_fetch_array($sql)) {
                $tool_content .= "<tr>";
                $tool_content .= "<td>" . nice_format($r['ts'], true) . "</td>";
                if (($r['user_id'] == 0) or ($logtype == LOG_DELETE_USER)) { // login failures or delete user
                    $tool_content .= "<td>&nbsp;&nbsp;&mdash;&mdash;&mdash;</td>";
                } else {
                    $tool_content .= "<td>" . display_user($r['user_id'], false, false) . "</td>";
                }
                if ($course_id == -1) { // all courses
                    $tool_content .= "<td>" . course_id_to_title($r['course_id']) . "</td>";
                }
                if ($module_id == -1) { // all modules                                                
                    $mid = $r['module_id'];
                    if ($mid == MODULE_ID_USERS) {
                        $tool_content .= "<td>" . $langAdminUsers . "</td>";
                    } elseif ($mid == MODULE_ID_TOOLADMIN) {
                        $tool_content .= "<td>" . $langExternalLinks . "</td>";
                    } else {
                        $tool_content .= "<td>" . $modules[$mid]['title'] . "</td>";
                    }
                }
                $tool_content .= "<td>" . $this->get_action_names($r['action_type']) . "</td>";
                if ($course_id == 0 or $module_id == 0) { // system logging
                    $tool_content .= "<td>" . $this->other_action_details($r['action_type'], $r['details']) . "</td>";
                } else { // course logging
                    $tool_content .= "<td>" . $this->course_action_details($r['module_id'], $r['details']) . "</td>";
                }
                $tool_content .= "</tr>";
            }
            $tool_content .= "</table>";
        } else {
            $tool_content .= "<div class='alert1'>$langNoUsersLog</div>";
        }
        return;
    }

    /**
     * @brief move logs from table `log` to table `log_archive`
     * @return none
     */
    public static function rotate() {

        $date = get_config('log_expire_interval');
        // move records in table `log_archive`
        $sql = db_query("INSERT INTO log_archive (user_id, course_id, module_id, details, action_type, ts, ip)
                                SELECT user_id, course_id, module_id, details, action_type, ts, ip FROM log
                                WHERE DATE_SUB(CURDATE(),interval $date month) > ts");

        // delete previous records from `log`
        if ($sql) {
            db_query("DELETE FROM log WHERE date_sub(CURDATE(),interval $date month) > ts");
        }
        return;
    }

    /**
     * @brief purge logs from table `logs_archive`
     * @return none
     */
    public static function purge() {

        $date = get_config('log_purge_interval');
        $sql = db_query("DELETE FROM log_archive WHERE DATE_SUB(CURDATE(),interval $date month) > ts");

        return;
    }

    /**
     * 
     * @global type $langUnknownModule
     * @param type $module_id
     * @param type $details
     * @return type
     * drive to appropriate subsystem for displaying details
     */
    private function course_action_details($module_id, $details) {

        global $langUnknownModule;

        switch ($module_id) {
            case MODULE_ID_AGENDA: $content = $this->agenda_action_details($details);
                break;
            case MODULE_ID_LINKS: $content = $this->link_action_details($details);
                break;
            case MODULE_ID_DOCS: $content = $this->document_action_details($details);
                break;
            case MODULE_ID_ANNOUNCE: $content = $this->announcement_action_details($details);
                break;
            case MODULE_ID_ASSIGN: $content = $this->assignment_action_details($details);
                break;
            case MODULE_ID_VIDEO: $content = $this->video_action_details($details);
                break;
            case MODULE_ID_DROPBOX: $content = $this->dropbox_action_details($details);
                break;
            case MODULE_ID_GROUPS: $content = $this->group_action_details($details);
                break;
            case MODULE_ID_DESCRIPTION: $content = $this->description_action_details($details);
                break;
            case MODULE_ID_GLOSSARY: $content = $this->glossary_action_details($details);
                break;
            case MODULE_ID_LP: $content = $this->lp_action_details($details);
                break;
            case MODULE_ID_EXERCISE: $content = $this->exercise_action_details($details);
                break;
            case MODULE_ID_WIKI: $content = $this->wiki_action_details($details);
                break;
            case MODULE_ID_USERS: $content = $this->course_user_action_details($details);
                break;
            case MODULE_ID_TOOLADMIN: $content = $this->external_link_action_details($details);
                break;
            default: $content = $langUnknownModule;
                break;
        }
        return $content;
    }

    /**
     * 
     * @global type $langUnknownAction
     * @param type $logtype
     * @param type $details
     * @return \type
     * drive to appropriate subsystems for displaying results
     */
    private function other_action_details($logtype, $details) {

        global $langUnknownAction;

        switch ($logtype) {
            case LOG_CREATE_COURSE: $content = $this->create_course_action_details($details);
                break;
            case LOG_DELETE_COURSE: $content = $this->delete_course_action_details($details);
                break;
            case LOG_MODIFY_COURSE: $content = $this->modify_course_action_details($details);
                break;
            case LOG_PROFILE: $content = $this->profile_action_details($details);
                break;
            case LOG_LOGIN_FAILURE: $content = $this->login_failure_action_details($details);
                break;
            case LOG_DELETE_USER: $content = $this->delete_user_action_details($details);
                break;
            default: $content = $langUnknownAction;
                break;
        }
        return $content;
    }

    /**
     * display action details while creating course
     * @global type $langTitle
     * @param type $details
     * @return string
     */
    private function create_course_action_details($details) {

        global $langTitle;

        $details = unserialize($details);

        $content = "$langTitle &laquo;" . $details['title'] . "&raquo;";
        $content .= "&nbsp;(" . $details['code'] . ")";

        return $content;
    }

    /**
     * display action details while deleting course
     * @global type $langTitle
     * @param type $details
     * @return string
     */
    private function delete_course_action_details($details) {

        global $langTitle;

        $details = unserialize($details);

        $content = "$langTitle &laquo;" . $details['title'] . "&raquo;";
        $content .= "&nbsp;(" . $details['code'] . ")";

        return $content;
    }

    private function modify_course_action_details($details) {

        global $langTitle;

        $details = unserialize($details);

        $content = "$langTitle &laquo;" . $details['title'] . "&raquo;";
        $content .= "&nbsp;(" . $details['public_code'] . ")";

        return $content;
    }

    private function profile_action_details($details) {

        global $lang_username, $langAm, $langChangePass, $langUpdateImage,
        $langType, $langDelImage, $langPersoDetails;

        $details = unserialize(($details));
        $content = '';

        if (!empty($details['modifyprofile'])) {
            $content .= "$langPersoDetails<br />$lang_username&nbsp;&laquo;" . $details['username'] . "&raquo;&nbsp;email&nbsp;&laquo;" . $details['email'] . "&raquo;&nbsp;";
            if (!empty($details['am'])) {
                $content .= "&nbsp;($langAm: " . $details['am'];
            }
            $content .= ")";
        }
        if (!empty($details['pass_change'])) {
            $content .= "$langChangePass";
        }
        if (!empty($details['addimage'])) {
            $content .= "$langUpdateImage&nbsp;($langType: " . $details['imagetype'] . ")";
        }
        if (!empty($details['deleteimage'])) {
            $content .= "$langDelImage";
        }
        /* if (!empty($details['deleteuser'])) {
          $content .= "$langUnregUser <br />&nbsp;&laquo;$langName".$details['name']."&raquo;&nbsp;$lang_username&nbsp;&laquo;".$details['username']."&raquo;";
          } */

        return $content;
    }

    /**
     * display login failures details
     * @global type $lang_username
     * @global type $langPassword
     * @param type $details
     * @return type
     */
    private function login_failure_action_details($details) {

        global $lang_username, $langPassword;

        $details = unserialize($details);

        $content = "$lang_username&nbsp;&laquo;$details[uname]&raquo;&nbsp;$langPassword&nbsp;&laquo;$details[pass]&raquo;";

        return $content;
    }

    private function delete_user_action_details($details) {
        global $lang_username, $langName;

        $details = unserialize($details);

        $content = "$lang_username&nbsp;&laquo;$details[username]&raquo;&nbsp;$langName&nbsp;&laquo;$details[name]&raquo;";

        return $content;
    }

    /**
     * display action details in video
     * @global type $langTitle
     * @global type $langDescription
     * @param type $details
     * @return string
     */
    private function video_action_details($details) {

        global $langTitle, $langDescription;

        $details = unserialize($details);
        $content = "$langTitle  &laquo" . $details['title'] . "&raquo";
        if (!empty($details['description'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langDescription &laquo" . $details['description'] . "&raquo";
        }
        if (!empty($details['url'])) {
            $content .= "&nbsp;&mdash;&nbsp; URL &laquo" . $details['url'] . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in assignments
     * @global type $langTitle
     * @global type $langDescription
     * @global type $m
     * @param type $details
     * @return string
     */
    private function assignment_action_details($details) {

        global $langTitle, $langDescription, $m;

        $details = unserialize($details);
        $content = "$langTitle  &laquo" . $details['title'] . "&raquo";
        if (!empty($details['description'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langDescription &laquo" . $details['description'] . "&raquo";
        }
        if (!empty($details['filename'])) {
            $content .= "&nbsp;&mdash;&nbsp; " . $m['filename'] . " &laquo" . $details['filename'] . "&raquo";
        }
        if (!empty($details['comments'])) {
            $content .= "&nbsp;&mdash;&nbsp; " . $m['comments'] . " &laquo" . $details['comments'] . "&raquo";
        }
        if (!empty($details['grade'])) {
            $content .= "&nbsp;&mdash;&nbsp; " . $m['grade'] . " &laquo" . $details['grade'] . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in announcements
     * @global type $langTitle
     * @global type $langContent
     * @param type $details
     * @return string
     */
    private function announcement_action_details($details) {

        global $langTitle, $langContent;

        $details = unserialize($details);
        $content = "$langTitle &laquo" . $details['title'] .
                "&raquo&nbsp;&mdash;&nbsp; $langContent &laquo" . $details['content'] . "&raquo";
        return $content;
    }

    /**
     * display action details in agenda
     * @global type $langTitle
     * @global type $langContent
     * @global type $langDuration
     * @global type $langhours
     * @global type $langDate
     * @param type $details
     * @return string
     */
    private function agenda_action_details($details) {

        global $langTitle, $langContent, $langDuration, $langhours, $langDate;

        $details = unserialize($details);
        $date = $details['date'];

        $content = "$langTitle &laquo" . $details['title'] .
                "&raquo&nbsp;&mdash;&nbsp; $langContent &laquo" . $details['content'] . "&raquo
                             &nbsp;&mdash;&nbsp;$langDate: " . nice_format($date, true) . "
                             &nbsp;&mdash;&nbsp;$langDuration: " . $details['duration'] . " $langhours";
        return $content;
    }

    /**
     * display action details in link
     * @global type $langTitle
     * @global type $langDescription
     * @global type $langCategoryName
     * @param type $details
     * @return string
     */
    private function link_action_details($details) {

        global $langTitle, $langDescription, $langCategoryName;

        $details = unserialize($details);
        $content = '';
        if (!empty($details['url'])) {
            $content .= "URL: " . $details['url'];
        }
        if (!empty($details['category'])) {
            $content .= " $langCategoryName &laquo" . $details['category'] . "&raquo";
        }
        if (!empty($details['title'])) {
            $content .= " &mdash; $langTitle &laquo" . $details['title'] . "&raquo";
        }
        if (!empty($details['description'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langDescription &laquo" . $details['description'] . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in documents
     * @global type $langFileName
     * @global type $langComments
     * @global type $langTitle
     * @global type $langRename
     * @global type $langMove
     * @global type $langTo
     * @global type $langIn
     * @param type $details
     * @return string         
     */
    private function document_action_details($details) {

        global $langFileName, $langComments, $langTitle, $langRename, $langMove, $langTo, $langIn;

        $details = unserialize($details);

        $content = "$langFileName &laquo" . $details['filename'] . "&raquo";
        if (!empty($details['title'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langTitle &laquo" . $details['title'] . "&raquo";
        }
        if (!empty($details['comment'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langComments &laquo" . $details['comment'] . "&raquo";
        }
        if (!empty($details['newfilename'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langRename $langIn &laquo" . $details['newfilename'] . "&raquo";
        }
        if (!empty($details['newpath'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langMove $langTo &laquo" . $details['newpath'] . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in dropbox
     * @param type $details
     * @return string
     */
    private function dropbox_action_details($details) {

        global $langFileName, $langSubject, $langMessage;

        $details = unserialize($details);
        
        $content = "$langSubject &laquo" . q($details['subject']) . "&raquo";
        if (!empty($details['filename'])) {
            $content .= "&nbsp;&mdash;&nbsp;$langFileName &laquo" . $details['filename'] . "&raquo";
        }
        if (!empty($details['body'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langMessage &laquo" . standard_text_escape($details['body']) . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in groups
     * @global type $langGroup
     * @global type $langNewUser
     * @global type $langInGroup
     * @param type $details
     * @return string
     */
    private function group_action_details($details) {

        global $langGroup, $langNewUser, $langInGroup;

        $details = unserialize($details);

        if (!empty($details['uid'])) {
            $content = "$langNewUser &laquo" . display_user($details['uid'], false, false) . "&raquo $langInGroup &laquo" . $details['name'] . "&raquo";
        } else {
            $content = "$langGroup &laquo" . $details['name'] . "&raquo";
        }

        return $content;
    }

    /**
     * display action details in course description
     * @global type $langTitle
     * @global type $langContent
     * @param type $details
     * @return string
     */
    private function description_action_details($details) {

        global $langTitle, $langContent;

        $details = unserialize($details);

        $content = "$langTitle  &laquo" . $details['title'] . "&raquo";
        $content .= "&nbsp;&mdash;&nbsp; $langContent &laquo" . ellipsize($details['content'], 100) . "&raquo";

        return $content;
    }

    /**
     * display action details in glossary
     * @global type $langGlossaryTerm
     * @global type $langGlossaryDefinition
     * @global type $langGlossaryURL
     * @global type $langCategoryNotes
     * @param type $details
     * @return string
     */
    private function glossary_action_details($details) {

        global $langGlossaryTerm, $langGlossaryDefinition, $langGlossaryURL, $langCategoryNotes;

        $details = unserialize($details);

        $content = "$langGlossaryTerm &laquo" . $details['term'] . "&raquo";
        if (!empty($details['definition'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langGlossaryDefinition &laquo" . ellipsize($details['definition'], 100) . "&raquo";
        }
        if (!empty($details['url'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langGlossaryURL &laquo" . $details['url'] . "&raquo";
        }
        if (!empty($details['notes'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langCategoryNotes &laquo" . $details['notes'] . "&raquo";
        }

        return $content;
    }

    /**
     * display action details in learning path
     * @global type $langLearnPath
     * @global type $langComments
     * @param type $details
     * @return string
     */
    private function lp_action_details($details) {

        global $langLearnPath, $langComments;

        $details = unserialize($details);

        $content = "$langLearnPath &laquo" . $details['name'] . "&raquo";
        if (!empty($details['comment'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langComments &laquo" . ellipsize($details['comment'], 100) . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in exercises
     * @global type $langTitle
     * @global type $langDescription
     * @param type $details
     * @return string
     */
    private function exercise_action_details($details) {

        global $langTitle, $langDescription;

        $details = unserialize($details);

        $content = "$langTitle &laquo" . $details['title'] . "&raquo";
        if (!empty($details['description'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langDescription &laquo" . ellipsize($details['description'], 100) . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in wiki
     * @global type $langTitle
     * @global type $langDescription
     * @param type $details
     * @return string
     */
    private function wiki_action_details($details) {

        global $langTitle, $langDescription;

        $details = unserialize($details);

        $content = "$langTitle &laquo" . $details['title'] . "&raquo";
        if (!empty($details['description'])) {
            $content .= "&nbsp;&mdash;&nbsp; $langDescription &laquo" . ellipsize($details['description'], 100) . "&raquo";
        }
        return $content;
    }

    /**
     * display action details in course users administration
     * @global type $langUnCourse
     * @global type $langOfUser
     * @global type $langToUser
     * @global type $langOneByOne
     * @global type $langGiveRightAdmin
     * @global type $langGiveRightΕditor
     * @global type $langGiveRightTutor
     * @global type $langRemoveRightAdmin
     * @global type $langRemoveRightEditor
     * @global type $langRemoveRightAdmin
     * @param type $details
     * @return string
     */
    private function course_user_action_details($details) {

        global $langUnCourse, $langOfUser, $langToUser, $langNewUser,
        $langGiveRightAdmin, $langGiveRightEditor, $langGiveRightTutor,
        $langRemoveRightAdmin, $langRemoveRightEditor, $langRemoveRightAdmin;

        $details = unserialize($details);

        switch ($details['right']) {
            case '+5': $content = $langNewUser;
                break;
            case '0': $content = "$langUnCourse $langOfUser";
                break;
            case '+1': $content = "$langGiveRightAdmin $langToUser";
                break;
            case '+2': $content = "$langGiveRightΕditor $langToUser";
                break;
            case '+3': $content = "$langGiveRightTutor $langToUser";
                break;
            case '-1': $content = "$langRemoveRightAdmin $langToUser";
                break;
            case '-2': $content = "$langRemoveRightEditor $langToUser";
                break;
            case '-3': $content = "$langRemoveRightAdmin $langToUser";
                break;
        }
        $content .= "&nbsp;&laquo" . display_user($details['uid'], false, false) . "&raquo";

        return $content;
    }

    /**
     * display action details in external links
     * @global type $langLinkName
     * @param type $details
     * @return string
     */
    private function external_link_action_details($details) {

        global $langLinkName;

        $details = unserialize($details);

        $content = "URL: " . $details['link'];
        $content .= " &mdash; $langLinkName &laquo" . $details['name_link'] . "&raquo";

        return $content;
    }

    /**
     * @global type $langInsert
     * @global type $langModify
     * @global type $langDelete
     * @global type $langModProfile
     * @global type $langFinalize
     * @global type $langCourseDel
     * @global type $langUnknownAction
     * @param type $action_type
     * @return type (real action names)
     */
    private function get_action_names($action_type) {

        global $langInsert, $langModify, $langDelete, $langModProfile, $langLoginFailures,
        $langFinalize, $langCourseDel, $langCourseInfoEdit, $langUnregUsers, $langUnknownAction;

        switch ($action_type) {
            case LOG_INSERT: return $langInsert;
            case LOG_MODIFY: return $langModify;
            case LOG_DELETE: return $langDelete;
            case LOG_PROFILE: return $langModProfile;
            case LOG_CREATE_COURSE: return $langFinalize;
            case LOG_DELETE_COURSE: return $langCourseDel;
            case LOG_MODIFY_COURSE: return $langCourseInfoEdit;
            case LOG_LOGIN_FAILURE: return $langLoginFailures;
            case LOG_DELETE_USER: return $langUnregUsers;
            default: return $langUnknownAction;
        }
    }

}
