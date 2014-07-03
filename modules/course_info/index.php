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
if (isset($_GET['from_home']) and ($_GET['from_home'] == TRUE) and isset($_GET['cid'])) {
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

$user = new User();
$course = new Course();
$tree = new Hierarchy();

load_js('jquery');
load_js('jquery-ui');
load_js('jstree');
load_js('pwstrength.js');
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

$nameTools = $langModifInfo;

// if the course is opencourses certified, disable visibility choice in form
$isOpenCourseCertified = ($creview = Database::get()->querySingle("SELECT is_certified FROM course_review WHERE course_id = ?d", $course_id)) ? $creview->is_certified : false;
$disabledVisibility = ($isOpenCourseCertified) ? " disabled='disabled' " : '';


if (isset($_POST['submit'])) {
    if (empty($_POST['title'])) {
        $tool_content .= "<p class='caution'>$langNoCourseTitle</p>
                                  <p>&laquo; <a href='$_SERVER[SCRIPT_NAME]?course=$course_code'>$langAgain</a></p>";
    } else {        
        // update course settings
        if (isset($_POST['formvisible']) and ($_POST['formvisible'] == '1' or $_POST['formvisible'] == '2')) {
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

        // Check if the teacher is allowed to create in the departments he chose
        if (!$deps_valid) {
            $tool_content .= "<p class='caution'>$langCreateCourseNotAllowedNode</p>
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
                                password = ?s
                            WHERE id = ?d", $_POST['title'], $_POST['fcode'], $_POST['course_keywords'], $_POST['formvisible'], 
                                            $course_license, $_POST['titulary'], $session->language, $password, $course_id);            
            $course->refresh($course_id, $departments);

            Log::record(0, 0, LOG_MODIFY_COURSE, array('title' => $_POST['title'],
                'public_code' => $_POST['fcode'],
                'visible' => $_POST['formvisible'],
                'prof_names' => $_POST['titulary'],
                'lang' => $language));

            $tool_content .= "<p class='success'>$langModifDone</p>
                            <p>&laquo; <a href='" . $_SERVER['SCRIPT_NAME'] . "?course=$course_code'>$langBack</a></p>
                            <p>&laquo; <a href='{$urlServer}courses/$course_code/index.php'>$langBackCourse</a></p>";
        }
    }
} else {
    $tool_content .= "
	<div id='operations_container'>
	  <ul id='opslist'>
	    <li><a href='archive_course.php?course=$course_code'>$langBackupCourse</a></li>
	    <li><a href='delete_course.php?course=$course_code'>$langDelCourse</a></li>
	    <li><a href='refresh_course.php?course=$course_code'>$langRefreshCourse</a></li>";
    if (get_config('course_metadata'))
        $tool_content .= "<li><a href='../course_metadata/index.php?course=$course_code'>$langCourseMetadata</a></li>";
    if (get_config('opencourses_enable') && $is_opencourses_reviewer)
        $tool_content .= "<li><a href='../course_metadata/control.php?course=$course_code'>$langCourseMetadataControlPanel</a></li>";
    $tool_content .= "
	  </ul>
	</div>";
         
    $c = Database::get()->querySingle("SELECT title, keywords, visible, public_code, prof_names, lang,
                	       course_license, password, id
                      FROM course WHERE code = ?s", $course_code);    
    $title = q($c->title);
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
        $license_checked[$id] = ($course_license == $id)? ' checked': '';
        if ($id and $id < 10) {
            $cc_license[$id] = $l_info['title'];
        }
    }

    $tool_content .="
	<form method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' onsubmit='return validateNodePickerForm();'>
	<fieldset>
	<legend>$langCourseIden</legend>
	<table class='tbl' width='100%'>
	    <tr>
		<th width='170'>$langCode:</th>
		<td><input type='text' name='fcode' value='$public_code' size='60' /></td>
	    </tr>
	    <tr>
		<th>$langCourseTitle:</th>
		<td><input type='text' name='title' value='$title' size='60' /></td>
	    </tr>
	    <tr>
		<th>$langTeachers:</th>
		<td><input type='text' name='titulary' value='$titulary' size='60' /></td>
	    </tr>
	    <tr>
                <th>$langFaculty:</th>
                <td>";
            $allow_only_defaults = ( get_config('restrict_teacher_owndep') && !$is_admin ) ? true : false;
            list($js, $html) = $tree->buildCourseNodePicker(array('defaults' => $course->getDepartmentIds($c->id), 'allow_only_defaults' => $allow_only_defaults));
            $head_content .= $js;
            $tool_content .= $html;
            @$tool_content .= "</td></tr>
	    <tr>
		<th>$langCourseKeywords</th>
		<td><input type='text' name='course_keywords' value='$course_keywords' size='60' /></td>
	    </tr>
	    </table>
         </fieldset>";
        if ($isOpenCourseCertified) {
            $tool_content .= "<input type='hidden' name='course_license' value='$course_license'>";
        }
         $tool_content .= "<fieldset>
        <legend>$langOpenCoursesLicense</legend>
            <table class='tbl' width='100%'>
            <tr><td colspan='2'><input type='radio' name='l_radio' value='0'$license_checked[0]$disabledVisibility>
            {$license[0]['title']}
            </td>
            </tr>
            <tr><td colspan='2'><input type='radio' name='l_radio' value='10'$license_checked[10]$disabledVisibility>
            {$license[10]['title']}
            </td>
            </tr>
            <tr><td colspan='2'><input id='cc_license' type='radio' name='l_radio' value='cc'$cc_checked$disabledVisibility>
                $langCMeta[course_license]
            </td>
            </tr>
            <tr id = 'cc'><td>
                ".selection($cc_license, 'cc_use', $course_license, $disabledVisibility)."
             </td></tr>
             </table>
        </fieldset>
	<fieldset>
	<legend>$langConfidentiality</legend>
	    <table class='tbl' width='100%'>
            <tr>		            
		<th width='170'>$langOptPassword</th>
                <td colspan='2'><input id='coursepassword' type='text' name='password' value='$password' autocomplete='off' /></td>
	    </tr>            
	    <tr>
		<th width='170'><img src='$themeimg/lock_open.png' alt='$m[legopen]' title='$m[legopen]' width='16' height='16' />&nbsp;$m[legopen]:</th>
		<td width='1'><input id='courseopen' type='radio' name='formvisible' value='2' $visibleChecked[2] $disabledVisibility /></td>
		<td class='smaller'>$langPublic</td>
	    </tr>
	    <tr>
		<th><img src='$themeimg/lock_registration.png' alt='$m[legrestricted]' title='$m[legrestricted]' width='16' height='16' />&nbsp;$m[legrestricted]:</th>
		<td><input id='coursewithregistration' type='radio' name='formvisible' value='1' $visibleChecked[1] $disabledVisibility /></td>
		<td class='smaller'>$langPrivOpen</td>
	    </tr>	    
	    <tr>
		<th><img src='$themeimg/lock_closed.png' alt='$m[legclosed]' title='$m[legclosed]' width='16' height='16' />&nbsp;$m[legclosed]:</th>
		<td><input id='courseclose' type='radio' name='formvisible' value='0' $visibleChecked[0] $disabledVisibility /></td>
		<td class='smaller'>$langPrivate</td>
	    </tr>
             <tr>
		<th><img src='$themeimg/lock_inactive.png' alt='$m[linactive]' title='$m[linactive]' width='16' height='16' />&nbsp;$m[linactive]:</th>
		<td><input id='courseinactive' type='radio' name='formvisible' value='3' $visibleChecked[3] $disabledVisibility /></td>
		<td class='smaller'>$langCourseInactive</td>
	    </tr>
	    </table>
	</fieldset>

	<fieldset>
	    <legend>$langLanguage</legend>
	    <table class='tbl'>
	    <tr>
		<th width='170'>$langOptions:</th>
		<td width='1'>";
    $language = $c->lang;
    $tool_content .= lang_select_options('localize');
    $tool_content .= "
	        </td>
	        <td class='smaller'>$langTipLang</td>
	    </tr>
	</table>
	</fieldset>
	<p class='right'><input type='submit' name='submit' value='$langSubmit' /></p>
	</form>";
}

draw($tool_content, 2, null, $head_content);
