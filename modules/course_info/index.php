<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2014  Greek Universities Network - GUnet
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

// if we come from the home page
if (isset($_GET['from_home']) and ( $_GET['from_home'] == TRUE) and isset($_GET['cid'])) {
    session_start();
    $_SESSION['dbname'] = $_GET['cid'];
}

$require_current_course = true;
$require_course_admin = true;
$require_help = true;
$helpTopic = 'Infocours';
require_once '../../include/baseTheme.php';
require_once 'include/log.php';
require_once 'include/lib/user.class.php';
require_once 'include/lib/course.class.php';
require_once 'include/lib/hierarchy.class.php';
require_once 'include/course_settings.php';
require_once 'modules/sharing/sharing.php';

$user = new User();
$course = new Course();
$tree = new Hierarchy();

load_js('jquery-ui');
load_js('jstree');
load_js('pwstrength.js');

//Datepicker
load_js('tools.js');
load_js('jquery-ui-timepicker-addon.min.js');

$head_content .= <<<hContent
<script type="text/javascript">
/* <![CDATA[ */

function deactivate_input_password () {
        $('#coursepassword').attr('disabled', 'disabled');
        $('#coursepassword').addClass('invisible');
}

function activate_input_password () {
        $('#coursepassword').removeAttr('disabled', 'disabled');
        $('#coursepassword').removeClass('invisible');
}

function displayCoursePassword() {

        if ($('#courseclose,#courseiactive').is(":checked")) {
                deactivate_input_password ();
        } else {
                activate_input_password ();
        }
}
    var lang = {
hContent;
$head_content .= "pwStrengthTooShort: '" . js_escape($langPwStrengthTooShort) . "', ";
$head_content .= "pwStrengthWeak: '" . js_escape($langPwStrengthWeak) . "', ";
$head_content .= "pwStrengthGood: '" . js_escape($langPwStrengthGood) . "', ";
$head_content .= "pwStrengthStrong: '" . js_escape($langPwStrengthStrong) . "'";
$head_content .= <<<hContent
    };

    function showCCFields() {
        $('#cc').show();
    }
    function hideCCFields() {
        $('#cc').hide();
    }
        
    $(document).ready(function() {
        
        $('input[name=start_date]').datepicker({
            dateFormat: 'yy-mm-dd',
            onSelect: function (date) {
                var date2 = $('input[name=start_date]').datepicker('getDate');
                if($('input[name=start_date]').datepicker('getDate')>$('input[name=finish_date]').datepicker('getDate')){
                    date2.setDate(date2.getDate() + 7);
                    $('input[name=finish_date]').datepicker('setDate', date2);
                    $('input[name=finish_date]').datepicker('option', 'minDate', date2);
                }else{
                    $('input[name=finish_date]').datepicker('option', 'minDate', date2);
                }
            }
        });
        
        $('input[name=finish_date]').datepicker({
            dateFormat: 'yy-mm-dd', 
            onClose: function () {
                var dt1 = $('input[name=start_date]').datepicker('getDate');
                var dt2 = $('input[name=finish_date]').datepicker('getDate');
                if (dt2 <= dt1) {
                    var minDate = $('input[name=finish_date]').datepicker('option', 'minDate');
                    $('input[name=finish_date]').datepicker('setDate', minDate);
            }
        }
        });
        
        if($('input[name=start_date]').datepicker("getDate") === null){
            $('input[name=start_date]').datepicker("setDate", new Date());
            var date2 = $('input[name=start_date]').datepicker('getDate');
            date2.setDate(date2.getDate() + 7);
            $('input[name=finish_date]').datepicker('setDate', date2);
            $('input[name=finish_date]').datepicker('option', 'minDate', date2);
        }else{
            var date2 = $('input[name=finish_date]').datepicker('getDate');
            $('input[name=finish_date]').datepicker('option', 'minDate', date2);
        }
        
        if($('input[name=finish_date]').datepicker("getDate") === null){
            $('input[name=finish_date]').datepicker("setDate", 7);
        }
        
        $('#weeklyDates').hide();
        
        $('input[name=view_type]').change(function () {
            if ($('#weekly').is(":checked")) {
                $('#weeklyDates').show();
            } else {
                $('#weeklyDates').hide();
            }
        }).change();
        
        $('#password').keyup(function() {
            $('#result').html(checkStrength($('#password').val()))
        });
        
        displayCoursePassword();
        
        $('#courseopen').click(function(event) {
                activate_input_password();
        });
        $('#coursewithregistration').click(function(event) {
                activate_input_password();
        });
        $('#courseclose').click(function(event) {
                deactivate_input_password();
        });
        $('#courseinactive').click(function(event) {
                deactivate_input_password();
        });
        
        $('input[name=l_radio]').change(function () {
            if ($('#cc_license').is(":checked")) {
                showCCFields();
            } else {
                hideCCFields();
            }
        }).change();
    });

/* ]]> */
</script>
hContent;

$nameTools = $langCourseInfo;

// if the course is opencourses certified, disable visibility choice in form
$isOpenCourseCertified = ($creview = Database::get()->querySingle("SELECT is_certified FROM course_review WHERE course_id = ?d", $course_id)) ? $creview->is_certified : false;
$disabledVisibility = ($isOpenCourseCertified) ? " disabled='disabled' " : '';


if (isset($_POST['submit'])) {
    if (empty($_POST['title'])) {
        $tool_content .= "<div class='alert alert-danger'>$langNoCourseTitle</div>
                                  <p>&laquo; <a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langAgain</a></p>";
    } else {
        // update course settings
        if (isset($_POST['formvisible']) and ( $_POST['formvisible'] == '1' or $_POST['formvisible'] == '2')) {
            $password = $_POST['password'];
        } else {
            $password = "";
        }
        // if it is opencourses certified keeep the current course_license
        if (isset($_POST['course_license'])) {
            $course_license = $_POST['course_license'];
        }
        // update course_license
        if (isset($_POST['l_radio'])) {
            $l = $_POST['l_radio'];
            switch ($l) {
                case 'cc':
                    if (isset($_POST['cc_use'])) {
                        $course_license = intval($_POST['cc_use']);
                    }
                    break;
                case '10':
                    $course_license = 10;
                    break;
                default:
                    $course_license = 0;
                    break;
            }
        }

        // disable visibility if it is opencourses certified
        if (get_config('opencourses_enable') && $isOpenCourseCertified) {
            $_POST['formvisible'] = '2';
        }

        $departments = isset($_POST['department']) ? $_POST['department'] : array();
        $deps_valid = true;

        foreach ($departments as $dep) {
            if (get_config('restrict_teacher_owndep') && !$is_admin && !in_array($dep, $user->getDepartmentIds($uid)))
                $deps_valid = false;
        }

        //===================course format and start and finish date===============
        //check if there is a start and finish date if weekly selected
        if ($_POST['view_type'] || $_POST['start_date'] || $_POST['finish_date']) {
            if (!$_POST['start_date']) {
                //if no start date do not allow weekly view and show alert message
                $view_type = 'units';
                $_POST['start_date'] = '0000-00-00';
                $_POST['finish_date'] = '0000-00-00';
                $noWeeklyMessage = 1;
            } else { //if there is start date create the weeks from that start date
                //Number of the previous week records for this course
                $previousWeeks = Database::get()->queryArray("SELECT id FROM course_weekly_view WHERE course_id = ?d", $course_id);
                //count of previous weeks
                if ($previousWeeks) {
                    foreach ($previousWeeks as $previousWeek) {
                        //array to hold all the previous records
                        $previousWeeksArray[] = $previousWeek->id;
                    }
                    $countPreviousWeeks = count($previousWeeksArray);
                } else {
                    $countPreviousWeeks = 0;
                }

                //counter for the new records
                $cnt = 1;

                //counter for the old records
                $cntOld = 0;

                $noWeeklyMessage = 0;

                $view_type = $_POST['view_type'];
                $begin = new DateTime($_POST['start_date']);

                //check if there is no end date
                if ($_POST['finish_date'] == "" || $_POST['finish_date'] == '0000-00-00') {
                    $end = new DateTime($begin->format("Y-m-d"));
                    ;
                    $end->add(new DateInterval('P26W'));
                } else {
                    $end = new DateTime($_POST['finish_date']);
                }

                $daterange = new DatePeriod($begin, new DateInterval('P1W'), $end);


                foreach ($daterange as $date) {
                    //===============================
                    //new weeks
                    //get the end week day
                    $endWeek = new DateTime($date->format("Y-m-d"));
                    $endWeek->modify('+6 day');

                    //value for db
                    $startWeekForDB = $date->format("Y-m-d");

                    if ($endWeek->format("Y-m-d") < $end->format("Y-m-d")) {
                        $endWeekForDB = $endWeek->format("Y-m-d");
                    } else {
                        $endWeekForDB = $end->format("Y-m-d");
                    }
                    //================================
                    //update the DB or insert new weeks
                    if ($cnt <= $countPreviousWeeks) {
                        //update the weeks in DB
                        Database::get()->query("UPDATE course_weekly_view SET start_week = ?t, finish_week = ?t WHERE course_id = ?d AND id = ?d", $startWeekForDB, $endWeekForDB, $course_id, $previousWeeksArray[$cntOld]);
                        //update the cntOLD records
                        $cntOld++;
                    } else {
                        //create the weeks in DB
                        Database::get()->query("INSERT INTO course_weekly_view (course_id, start_week, finish_week) VALUES (?d, ?t, ?t)", $course_id, $startWeekForDB, $endWeekForDB);
                    }
                    //update the counter
                    $cnt++;
                }
                //check if left from the previous weeks and they are out of the new period
                //if so delete them
                if (--$cnt < $countPreviousWeeks) {
                    $week2delete = $countPreviousWeeks - $cnt;
                    for ($i = 0; $i < $week2delete; $i++) {
                        Database::get()->query("DELETE FROM course_weekly_view WHERE id = ?d", $previousWeeksArray[$cntOld]);
                        $cntOld++;
                    }
                }
            }
        }
        //=======================================================
        // Check if the teacher is allowed to create in the departments he chose
        if (!$deps_valid) {
            $tool_content .= "<div class='alert alert-danger'>$langCreateCourseNotAllowedNode</div>
                                      <p>&laquo; <a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langAgain</a></p>";
        } else {
            Database::get()->query("UPDATE course
                            SET title = ?s,
                                public_code = ?s,
                                keywords = ?s,
                                visible = ?d,
                                course_license = ?d,
                                prof_names = ?s,
                                lang = ?s,
                                password = ?s,
                                view_type = ?s,
                                start_date = ?t,
                                finish_date = ?t
                            WHERE id = ?d", $_POST['title'], $_POST['fcode'], $_POST['course_keywords'], $_POST['formvisible'], $course_license, $_POST['titulary'], $session->language, $password, $view_type, $_POST['start_date'], $_POST['finish_date'], $course_id);
            $course->refresh($course_id, $departments);

            Log::record(0, 0, LOG_MODIFY_COURSE, array('title' => $_POST['title'],
                'public_code' => $_POST['fcode'],
                'visible' => $_POST['formvisible'],
                'prof_names' => $_POST['titulary'],
                'lang' => $session->language));

            $tool_content .= "<div class='alert alert-success'>$langModifDone</div>";

            if ($noWeeklyMessage) {
                $tool_content .= "<div class='alert alert-warning'>$langCourseWeeklyFormatNotice</div>";
            }

            $tool_content .= "<p>&laquo; <a href='" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code'>$langBack</a></p>
                            <p>&laquo; <a href='{$urlServer}courses/$course_code/index.php'>$langBackCourse</a></p>";
        }

        if (isset($_POST['s_radio'])) {
            setting_set(SETTING_COURSE_SHARING_ENABLE, $_POST['s_radio'], $course_id);
        }

        if (isset($_POST['r_radio'])) {
            setting_set(SETTING_COURSE_RATING_ENABLE, $_POST['r_radio'], $course_id);
        }
        if (isset($_POST['ran_radio'])) {
            setting_set(SETTING_COURSE_ANONYMOUS_RATING_ENABLE, $_POST['ran_radio'], $course_id);
        }
        if (isset($_POST['c_radio'])) {
            setting_set(SETTING_COURSE_COMMENT_ENABLE, $_POST['c_radio'], $course_id);
        }
    }
} else {
    $tool_content .= "
	<div id='operations_container'>" .
            action_bar(array(
                array('title' => $langDelCourse,
                    'url' => "delete_course.php?course=$course_code",
                    'icon' => 'fa-times',
                    'button-class' => 'btn-danger',
                    'level' => 'primary-label'),
                array('title' => $langBackupCourse,
                    'url' => "archive_course.php?course=$course_code",
                    'icon' => 'fa-archive',
                    'level' => 'primary'),
                array('title' => $langRefreshCourse,
                    'url' => "refresh_course.php?course=$course_code",
                    'icon' => 'fa-refresh',
                    'level' => 'primary'),
                array('title' => $langCourseMetadata,
                    'url' => "../course_metadata/index.php?course=$course_code",
                    'icon' => 'fa-file-text',
                    'level' => 'primary',
                    'show' => get_config('course_metadata')
                ),
                array('title' => $langCourseMetadataControlPanel,
                    'url' => "../course_metadata/control.php?course=$course_code",
                    'icon' => 'fa-list',
                    'show' => get_config('opencourses_enable') && $is_opencourses_reviewer,
                    'level' => 'primary'),
            )) .
            "</div>";

    $c = Database::get()->querySingle("SELECT title, keywords, visible, public_code, prof_names, lang,
                	       course_license, password, id, view_type, start_date, finish_date
                      FROM course WHERE code = ?s", $course_code);
    $title = $c->title;
    $visible = $c->visible;
    $visibleChecked = array(COURSE_CLOSED => '', COURSE_REGISTRATION => '', COURSE_OPEN => '', COURSE_INACTIVE => '');
    $visibleChecked[$visible] = " checked='checked'";
    $public_code = q($c->public_code);
    $titulary = q($c->prof_names);
    $languageCourse = $c->lang;
    $course_keywords = q($c->keywords);
    $password = q($c->password);
    $course_license = $c->course_license;
    if ($course_license > 0 and $course_license < 10) {
        $cc_checked = ' checked';
    } else {
        $cc_checked = '';
    }
    foreach ($license as $id => $l_info) {
        $license_checked[$id] = ($course_license == $id) ? ' checked' : '';
        if ($id and $id < 10) {
            $cc_license[$id] = $l_info['title'];
        }
    }

    $tool_content .= "<div class='form-wrapper'>
	<form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' onsubmit='return validateNodePickerForm();'>
	<fieldset><div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langCourseIden</label></div>
	<div class='form-group'>
            <label for='Code' class='col-sm-2 control-label'>$langCode</label>
            <div class='col-sm-10'>
                <input type='text' name='fcode' value='$public_code' size='60' />
            </div>
        </div>
        <div class='form-group'>	    
            <label for='Title' class='col-sm-2 control-label'>$langCourseTitle:</label>
            <div class='col-sm-10'>
		<input type='text' name='title' value='" . q($title) . "' size='60' />
	    </div>
        </div>
        <div class='form-group'>
            <label for='Teacher' class='col-sm-2 control-label'>$langTeachers:</label>
            <div class='col-sm-10'>
		<input type='text' name='titulary' value='$titulary' size='60' />
	    </div>
        </div>
        <div class='form-group'>
	    <label for='Faculty' class='col-sm-2 control-label'>$langFaculty:</label>
            <div class='col-sm-10'>";
        $allow_only_defaults = ( get_config('restrict_teacher_owndep') && !$is_admin ) ? true : false;
        list($js, $html) = $tree->buildCourseNodePicker(array('defaults' => $course->getDepartmentIds($c->id), 'allow_only_defaults' => $allow_only_defaults));
        $head_content .= $js;
        $tool_content .= $html;
        @$tool_content .= "</div></div>
	    <div class='form-group'>
		<label for='Keywords' class='col-sm-2 control-label'>$langCourseKeywords</label>
		<div class='col-sm-10'>
                    <input type='text' name='course_keywords' value='$course_keywords' size='60' />
                </div>
	    </div>
            
        <div class='form-wrapper'>
	    <div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langCourseFormat</label></div>
            <div class='form-group'>
            <label for='simple' class='col-sm-3'>$langCourseSimpleFormat</label>
                <div class='col-sm-9'>
                    <input type='radio' name='view_type' value='simple' ";
        if ($c->view_type == "simple") {
            $tool_content .= " checked ";
        }
        $tool_content .= "id='simple'></div></div>
            <div class='form-group'>
            <label for='units' class='col-sm-3'>$langWithCourseUnits</label>
            <div class='col-sm-9'>
            
                <input type='radio' name='view_type' value='units'";
        if ($c->view_type == "units") {
            $tool_content .= " checked ";
        }
        $tool_content .= "id='units'></div></div>
            <div class='form-group'>
            <label for='weekly' class='col-sm-3'>$langCourseWeeklyFormat</label>
                <div class='col-sm-9'>
                    <input type='radio' name='view_type' value='weekly' ";
        if ($c->view_type == "weekly") {
            $tool_content .= " checked ";
        }
        $tool_content .= "id='weekly'></div>
            </div>
            <div class='form-group'>
                <div class='col-sm-10' id='weeklyDates'>
                        $langStartDate 
                        <input class='dateInForm' type='text' name='start_date' value='";
    if ($c->start_date != "0000-00-00") {
        $tool_content .= $c->start_date;
    }
    $tool_content .= "' readonly='true'>                        
                        $langDuration
                        <input class='dateInForm' type='text' name='finish_date' value='";
    if ($c->finish_date != "0000-00-00") {
        $tool_content .= $c->finish_date;
    }
    $tool_content .= "' readonly='true'>
                    </div>
                </div>
            </div>";

    if ($isOpenCourseCertified) {
        $tool_content .= "<input type='hidden' name='course_license' value='$course_license'>";
    }
    $language = $c->lang;
    $tool_content .= "<div class='form-wrapper'>        
            <div class='form-group'>
                <label for='License' class='col-sm-offset-4 col-sm-8'>$langOpenCoursesLicense</label>
            </div>
            <div class='form-group'>
            <label for='simple' class='col-sm-3'>{$license[0]['title']}</label>
                <div class='col-sm-9'>
                    <input type='radio' name='l_radio' value='0'$license_checked[0]$disabledVisibility>                
                </div>
            </div>
            <div class='form-group'>
            <label for='simple' class='col-sm-3'>{$license[10]['title']}</label>
                <div class='col-sm-9'>
                    <input type='radio' name='l_radio' value='10'$license_checked[10]$disabledVisibility>                
                </div>
            </div>
            <div class='form-group'>
            <label for='simple' class='col-sm-3'>$langCMeta[course_license]</label>
                <div class='col-sm-9'>
                    <input id='cc_license' type='radio' name='l_radio' value='cc'$cc_checked$disabledVisibility>                    
                </div>
            </div>
            <div class='form-group'>
                <div class='col-sm-10' id = 'cc'>            
                    " . selection($cc_license, 'cc_use', $course_license, $disabledVisibility) . "
                </div>
            </div>
        </div>
        
        <div class='form-wrapper'>
	<div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langConfidentiality</label></div>
	    <div class='form-group'>
		<label for='Pass' class='col-sm-3 control-label'>$langOptPassword</label>
                <div class='col-sm-9'>
                    <input id='coursepassword' type='text' name='password' value='$password' autocomplete='off' />
                </div>
	    </div>
	    <div class='form-group'>
            <label for='Public' class='col-sm-3 control-label'>$langOpenCourse</label>
            <div class='col-sm-9 radio'><label><input id='courseopen' type='radio' name='formvisible' value='2' $visibleChecked[2] $disabledVisibility> $langPublic</label></div>
	    </div>
	    <div class='form-group'>
            <label for='PrivateOpen' class='col-sm-3 control-label'>$langRegCourse</label>	
            <div class='col-sm-9 radio'><label><input id='coursewithregistration' type='radio' name='formvisible' value='1' $visibleChecked[1] $disabledVisibility> $langPrivOpen</label></div>
        </div>
	    <div class='form-group'>
            <label for='PrivateClosed' class='col-sm-3 control-label'>$langClosedCourse</label>
            <div class='col-sm-9 radio'><label><input id='courseclose' type='radio' name='formvisible' value='0' $visibleChecked[0] $disabledVisibility> $langClosedCourseShort</label></div>
	   </div>
       <div class='form-group'>
            <label for='Inactive' class='col-sm-3 control-label'>$langInactiveCourse</label>
            <div class='col-sm-9 radio'><label><input id='courseinactive' type='radio' name='formvisible' value='3' $visibleChecked[3] $disabledVisibility> $langCourseInactiveShort</label></div>
	    </div>
	</div>

	<div class='form-wrapper'>
	    <div class='form-group'>
            <label for='Options' class='col-sm-3 control-label'>$langLanguage</label>
            <div class='col-sm-9'>" . lang_select_options('localize') . "</div>	        
	    </div>";

    if (!is_sharing_allowed($course_id)) {
        $radio_dis = ' disabled';
        if (!get_config('enable_social_sharing_links')) {
            $sharing_dis_label = $langSharingDisAdmin;
        }
        if (course_status($course_id) != COURSE_OPEN) {
            $sharing_dis_label = $langSharingDisCourse;
        }        
    } else {
        $radio_dis = '';
        $sharing_dis_label = '';
    }

    if (setting_get(SETTING_COURSE_SHARING_ENABLE, $course_id) == 1) {
        $checkSharingDis = '';
        $checkSharingEn = 'checked';
    } else {
        $checkSharingDis = 'checked';
        $checkSharingEn = '';
    }

    if (setting_get(SETTING_COURSE_RATING_ENABLE, $course_id) == 1) {
        $checkRatingDis = '';
        $checkRatingEn = 'checked';
    } else {
        $checkRatingDis = 'checked';
        $checkRatingEn = '';
    }

    $tool_content .= "
                <div class='form-group'>
                    <label class='col-sm-3'>$langSharing</label>
                    <div class='col-sm-9 radio'>
                        <label><input type='radio' value='1' name='s_radio' $checkSharingEn $radio_dis> $langSharingEn</label>
                        <label><input type='radio' value='0' name='s_radio' $checkSharingDis $radio_dis> $langSharingDis</label>
                        <label>$sharing_dis_label</label>
                    </div>
                </div>
                <div class='form-group'>
                    <label class='col-sm-3'>$langRating</label>
                    <div class='col-sm-9 radio'>
                        <label><input type='radio' value='1' name='r_radio' $checkRatingEn> $langRatingEn</label>
                        <label><input type='radio' value='0' name='r_radio' $checkRatingDis> $langRatingDis</label>
                    </div>
                </div>
            </div>";

    if (course_status($course_id) != COURSE_OPEN) {
        $radio_dis = ' disabled';
        $rating_dis_label = $langRatingAnonDisCourse;
    } else {
        $radio_dis = '';
        $rating_dis_label = '';
    }

    if (setting_get(SETTING_COURSE_ANONYMOUS_RATING_ENABLE, $course_id) == 1) {
        $checkDis = '';
        $checkEn = 'checked ';
    } else {
        $checkDis = 'checked ';
        $checkEn = '';
    }
    
    $tool_content .= "<div class='form-wrapper'><div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langAnonymousRating</label></div>
            <div class='form-group'>
            <label for='rating_anon_en' class='col-sm-3'>$langRatingAnonEn</label>
            <div class='col-sm-9'><input type='radio' value='1' name='ran_radio' $checkEn $radio_dis></div>
            </div>
            <div class='form-group'>
                <label for='rating_anon_dis' class='col-sm-3'>$langRatingAnonDis</label>
            <div class='col-sm-9'><input type='radio' value='0' name='ran_radio' $checkDis $radio_dis></div>
        </div>
<div class='form-group'><label class='col-sm-12'>$rating_dis_label</label></div>        
</div>";

    $tool_content .= "</fieldset>";

    if (setting_get(SETTING_COURSE_COMMENT_ENABLE, $course_id) == 1) {
        $checkDis = "";
        $checkEn = "checked ";
    } else {
        $checkDis = "checked ";
        $checkEn = "";
    }
    $tool_content .= "<div class='form-wrapper'><div class='form-group'><label class='col-sm-offset-4 col-sm-8'>$langCommenting</label></div>
            <div class='form-group'>
            <label for='comments-_en' class='col-sm-3'>$langCommentsEn</label>
            <div class='col-sm-9'><input type='radio' value='1' name='c_radio' $checkEn /></div>
            </div>
            <div class='form-group'>
            <label for='comments_dis' class='col-sm-3'>$langCommentsDis</label>
            <div class='col-sm-9'><input type='radio' value='0' name='c_radio' $checkDis /></div>
        </div></div>";
    
    $tool_content .= "<p class='pull-right'><input class='btn btn-primary' type='submit' name='submit' value='$langSubmit' /></p>";
    $tool_content .= "</form></div>";
}

draw($tool_content, 2, null, $head_content);
