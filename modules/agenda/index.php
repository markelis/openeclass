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

/**
 * @description: agenda module
 * @file: index.php
 */
$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Agenda';
$guest_allowed = true;

require_once '../../include/baseTheme.php';
require_once 'include/lib/textLib.inc.php';
require_once 'include/action.php';
require_once 'include/log.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'modules/search/agendaindexer.class.php';
ModalBoxHelper::loadModalBox();

$action = new action();
$action->record(MODULE_ID_AGENDA);

$dateNow = date("j-n-Y / H:i", time());

$nameTools = $langAgenda;

if (isset($_GET['id'])) {
    $id = intval($_GET['id']);
}

load_js('tools.js');
load_js('bootstrap-datetimepicker');
load_js('bootstrap-timepicker');
 
$head_content .= "
<script type='text/javascript'>
$(function() {
    $('#startdatecal, #enddatecal').datetimepicker({
        format: 'dd-mm-yyyy hh:ii', pickerPosition: 'bottom-left', 
        language: '".$language."',
        autoclose: true
    });    
    $('#durationcal').timepicker({showMeridian: false, minuteStep: 1, defaultTime: false });
});
</script>";

if ($is_editor and (isset($_GET['addEvent']) or isset($_GET['id']))) {

    //--if add event
    $head_content .= <<<hContent
<script type="text/javascript">
function checkrequired(which, entry) {
	var pass=true;
	if (document.images) {
		for (i=0;i<which.length;i++) {
			var tempobj=which.elements[i];
			if (tempobj.name == entry) {
				if (tempobj.type=="text"&&tempobj.value=='') {
					pass=false;
					break;
		  		}
	  		}
		}
	}
	if (!pass) {
		alert("$langEmptyAgendaTitle");
		return false;
	} else {
		return true;
	}
}
</script>
hContent;
}

$result = Database::get()->queryArray("SELECT id FROM agenda WHERE course_id = ?d", $course_id);
    if (count($result) > 1) {
        if (isset($_GET["sens"]) && $_GET["sens"] == "d") {
            $sens = " DESC"; 
        } else {
            $sens = " ASC";    
        }
    }
    
// display action bar
if (isset($_GET['addEvent']) or isset($_GET['edit'])) {
    $tool_content .= action_bar(array(
            array('title' => $langBack,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code",
                  'icon' => 'fa-reply',
                  'level' => 'primary',
                  'show' => $is_editor)));
} else {
    $tool_content .= action_bar(array(
            array('title' => $langAddEvent,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;addEvent=1",
                  'icon' => 'fa-plus-circle',
                  'level' => 'primary-label',
                  'button-class' => 'btn-success',
                  'show' => $is_editor),
            array('title' => $langOldToNew,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;sens=",
                  'icon' => 'fa-arrows-v',
                  'level' => 'primary-label',
                  'show' => count($result) and (isset($_GET['sens']) and $_GET['sens'] == "d")),
            array('title' => $langOldToNew,
                  'url' => "$_SERVER[SCRIPT_NAME]?course=$course_code&amp;sens=d",
                  'icon' => 'fa-arrows-v',
                  'level' => 'primary-label',
                  'show' => count($result) and (!isset($_GET['sens']) or (isset($_GET['sens']) and $_GET['sens'] != "d")))
        ));                        
}

if ($is_editor) {
    $agdx = new AgendaIndexer();
    // modify visibility
    if (isset($_GET['mkInvisibl']) and $_GET['mkInvisibl'] == true) {
        Database::get()->query("UPDATE agenda SET visible = 0 WHERE course_id = ?d AND id = ?d", $course_id, $id);
        $agdx->store($id);
    } elseif (isset($_GET['mkVisibl']) and ( $_GET['mkVisibl'] == true)) {
        Database::get()->query("UPDATE agenda SET visible = 1 WHERE course_id = ?d AND id = ?d", $course_id, $id);
        $agdx->store($id);
    }
    if (isset($_POST['submit'])) {
        register_posted_variables(array('startdate' => true, 'event_title' => true, 'content' => true, 'duration' => true));
        $content = purify($content);
        $startDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['startdate']);
        $startdate = $startDate_obj->format('Y-m-d H:i');
        if (isset($_POST['id']) and !empty($_POST['id'])) {  // update event
            $id = $_POST['id'];                        
            Database::get()->query("UPDATE agenda SET title = ?s, content = ?s, start = ?t, duration = ?s
                        WHERE course_id = ?d AND id = ?d", $event_title, $content, $startdate, $duration, $course_id, $id);            
            $agdx->store($id);
            $txt_content = ellipsize(canonicalize_whitespace(strip_tags($content)), 50, '+');
            Log::record($course_id, MODULE_ID_AGENDA, LOG_MODIFY, array('id' => $id,
                                                                   'date' => $startdate,
                                                                   'duration' => $duration,
                                                                   'title' => $event_title,
                                                                   'content' => $txt_content));
        } else {
            $period = '';            
            if(isset($_POST['frequencyperiod']) and isset($_POST['frequencynumber']) and isset($_POST['enddate'])) {
                $period = 'P' . $_POST['frequencynumber'] . $_POST['frequencyperiod'];
                if (!empty($_POST['enddate'])) {                    
                    $endDate_obj = DateTime::createFromFormat('d-m-Y H:i', $_POST['enddate']);                    
                    $enddate = $endDate_obj->format('Y-m-d H:i');
                } else {
                    $enddate = '0000-00-00 00:00';
                }                
            }
            $id = Database::get()->query("INSERT INTO agenda "
                        ."SET course_id = ?d, "
                        ."title = ?s, "
                        ."content = ?s, " 
                        ."start = ?t, "                                             
                        ."duration = ?t, "
                        ."recursion_period = ?t, "
                        ."recursion_end = ?t, " 
                        ."visible = 1", $course_id, $event_title, $content, $startdate, $duration, $period, $enddate)->lastInsertID;
            
            if(isset($id) && !is_null($id)) {
                $agdx->store($id);
                $txt_content = ellipsize(canonicalize_whitespace(strip_tags($content)), 50, '+');
                Log::record($course_id, MODULE_ID_AGENDA, LOG_INSERT, array('id' => $id,
                                                            'date' => $startdate,
                                                            'duration' => $duration,
                                                            'title' => $event_title,
                                                            'content' => $txt_content));
                Database::get()->query("UPDATE agenda SET source_event_id = id WHERE id = ?d",$id);
                if(!empty($period) && !empty($enddate)) {                    
                    $sourceevent = $id;
                    $interval = new DateInterval($period);
                    $startdatetime = new DateTime($startdate);                    
                    $enddatetime = new DateTime($enddate);
                    $newdate = date_add($startdatetime, $interval);
                    while($newdate <= $enddatetime)
                    {
                        $neweventid = Database::get()->query("INSERT INTO agenda "
                                . "SET course_id = ?d, content = ?s, title = ?s, start = ?t, duration = ?t, visible = 1,"
                                . "recursion_period = ?s, recursion_end = ?t, "
                                . "source_event_id = ?d", 
                        $course_id, purify($content), $event_title, $newdate->format('Y-m-d H:i'), $duration, $period, $enddate, $sourceevent)->lastInsertID;
                        $agdx->store($id);
                        $txt_content = ellipsize(canonicalize_whitespace(strip_tags($content)), 50, '+');
                        Log::record($course_id, MODULE_ID_AGENDA, LOG_INSERT, array('id' => $neweventid,
                                                                                    'date' => $newdate->format('Y-m-d H:i'),
                                                                                    'duration' => $duration,
                                                                                    'title' => $event_title,
                                                                                    'content' => $txt_content));
                        
                        $newdate = date_add($startdatetime, $interval);
                    }
                }
            }
        }        
        $tool_content .= "<div class='alert alert-success text-center' role='alert'>$langStoredOK</div>";        
    } elseif (isset($_GET['delete']) && $_GET['delete'] == 'yes') {
        $row = Database::get()->querySingle("SELECT title, content, start, duration
                                        FROM agenda WHERE id = ?d", $id);
        $txt_content = ellipsize(canonicalize_whitespace(strip_tags($row->content)), 50, '+');
        Database::get()->query("DELETE FROM agenda WHERE course_id = ?d AND id = ?d", $course_id, $id);
        $agdx->remove($id);
        Log::record($course_id, MODULE_ID_AGENDA, LOG_DELETE, array('id' => $id,
                                                                    'date' => $row->start,
                                                                    'duration' => $row->duration,
                                                                    'title' => $row->title,
                                                                    'content' => $txt_content));
        $tool_content .= "<div class='alert alert-success text-center' role='alert'>$langDeleteOK</div><br>";        
    }

    if (isset($_GET['addEvent']) or isset($_GET['edit'])) {
        $nameTools = $langAddEvent;
        $navigation[] = array("url" => $_SERVER['SCRIPT_NAME'] . "?course=$course_code", "name" => $langAgenda);
        if (isset($id) && $id) {
            $myrow = Database::get()->querySingle("SELECT id, title, content, start, duration FROM agenda WHERE course_id = ?d AND id = ?d", $course_id, $id);
            if ($myrow) {
                $id = $myrow->id;
                $event_title = $myrow->title;
                $content = $myrow->content;
                $startdate = date('d-m-Y H:i', strtotime($myrow->start));
                $duration = $myrow->duration;
            }
        } else {
            $id = $content = $duration = '';
            $startdate = date('d-m-Y H:i', strtotime('now'));
            $enddate = date('d-m-Y H:i', strtotime('now +1 week'));
        }    
        $tool_content .= "<div class='form-wrapper'>";
        $tool_content .= "<form class='form-horizontal' role='form' method='post' action='$_SERVER[SCRIPT_NAME]?course=$course_code' onsubmit='return checkrequired(this, \"event_title\");'>
            <input type='hidden' name='id' value='$id'>";
        @$tool_content .= "
            <div class='form-group'>
                <label for='Title' class='col-sm-2 control-label'>$langTitle: </label>
                <div class='col-sm-10'>
                    <input type='text' class='form-control' id='event_title' name='event_title' placeholder='$langTitle' value='" . q($event_title) . "'>
                </div>
            </div>
            <div class='input-append date form-group' id='startdatecal' data-date='$langDate' data-date-format='dd-mm-yyyy'>
                <label for='DateStart' class='col-sm-2 control-label'>$langDate :</label>
                <div class='col-xs-10 col-sm-9'>        
                    <input name='startdate' id='startdate' type='text' value = '" .$startdate . "'>
                </div>
                <div class='col-xs-2 col-sm-1'>  
                    <span class='add-on'><i class='fa fa-times'></i></span>
                    <span class='add-on'><i class='fa fa-calendar'></i></span>
                </div>
            </div>
            <div class='input-append bootstrap-timepicker form-group' id='durationcal'>
                <label for='Duration' class='col-sm-2 control-label'>$langDuration <small>$langInHour</small></label>
                <div class='col-xs-10 col-sm-9'>
                    <input name='duration' id='duration' type='text' class='input-small' value='" . $duration . "'>
                </div>
                <div class='col-xs-2 col-sm-1'>
                    <span class='add-on'><i class='icon-time'></i></span>
                </div>
            </div>";
        if(!isset($_GET['edit'])) {
            $tool_content .= "<div class='form-group'>
                                    <label for='Repeat' class='col-sm-2 control-label'>$langRepeat $langEvery</label>
                                <div class='col-sm-2'>
                                    <select class='form-control' name='frequencynumber'>
                                    <option value='0'>$langSelectFromMenu</option>";
            for($i = 1;$i<10;$i++) {
                $tool_content .= "<option value=\"$i\">$i</option>";
            }
            $tool_content .= "</select></div>";            
            $tool_content .= "<div class='col-sm-2'>
                        <select class='form-control' name='frequencyperiod'>
                            <option value=\"D\">$langSelectFromMenu...</option>
                            <option value=\"D\">$langDays</option>
                            <option value=\"W\">$langWeeks</option>
                            <option value=\"M\">$langMonthsAbstract</option>
                        </select>
                        </div>
                    </div>";
            $tool_content .= "<div class='input-append date form-group' id='enddatecal' data-date='$langDate' data-date-format='dd-mm-yyyy'>
                <label for='Enddate' class='col-sm-2 control-label'>$langUntil :</label>
                    <div class='col-xs-10 col-sm-9'>
                        <input name='enddate' id='enddate' type='text' value = '" . $enddate . "'>
                    </div>
                    <div class='col-xs-2 col-sm-1'>  
                        <span class='add-on'><i class='fa fa-times'></i></span>
                        <span class='add-on'><i class='fa fa-calendar'></i></span>
                    </div>
                </div>";
        }
                
        $tool_content .= "<div class='form-group'>
                        <label for='Detail' class='col-sm-2 control-label'>$langDetail :</label>
                        <div class='col-sm-10'>" . rich_text_editor('content', 4, 20, $content) . "</div>
                      </div>            
                      <div class='form-group'>
                        <div class='col-sm-offset-2 col-sm-10'>
                            <input type='submit' class='btn btn-default' name='submit' value='$langAddModify'>
                        </div>
                      </div>                
            </form></div>";
    }
}

/* ---------------------------------------------
 *  End  of  prof only
 * ------------------------------------------- */
if (!isset($_GET['sens'])) {
    $sens = " ASC";
}
if ($is_editor) {
    $result = Database::get()->queryArray("SELECT id, title, content, start, duration, visible FROM agenda WHERE course_id = ?d
		ORDER BY start " . $sens, $course_id);
} else {
    $result = Database::get()->queryArray("SELECT id, title, content, start, duration, visible FROM agenda WHERE course_id = ?d
		AND visible = 1 ORDER BY start " . $sens, $course_id);
}

if (count($result) > 0) {
    $barMonth = '';
    $nowBarShowed = false;
    $tool_content .= "<div class='table-responsive'><table class='table-default'>
                    <tr><th class='left'>$langEvents</th>";
    if ($is_editor) {
        $tool_content .= "<th class='text-center'>" . icon('fa-gears') . "</th>";
    } else {
        $tool_content .= "<th width='50'>&nbsp;</th>";
    }
    $tool_content .= "</tr>";

    foreach ($result as $myrow) {
        $content = standard_text_escape($myrow->content);
        $d = strtotime($myrow->start);
        if (!$nowBarShowed) {
            // Following order
            if ((($d > time()) and ($sens == " ASC")) or ( ($d < time()) and ( $sens == " DESC "))) {
                if ($barMonth != date("m", time())) {
                    $barMonth = date("m", time());
                    $tool_content .= "<tr>";
                    // current month
                    $tool_content .= "<td colspan='2' class='monthLabel'>" . $langCalendar . "&nbsp;<b>" . ucfirst(claro_format_locale_date("%B %Y", time())) . "</b></td>";
                    $tool_content .= "</tr>";
                }
                $nowBarShowed = TRUE;
                $tool_content .= "<tr>";
                $tool_content .= "<td colspan='2' class='today'>$langDateNow $dateNow</td>";
                $tool_content .= "</tr>";
            }
        }
        if ($barMonth != date("m", $d)) {
            $barMonth = date("m", $d);
            // month LABEL
            $tool_content .= "<tr>";            
            $tool_content .= "<td colspan='2' class='monthLabel'>";            
            $tool_content .= "<div align='center'>" . $langCalendar . "&nbsp;<b>" . ucfirst(claro_format_locale_date("%B %Y", $d)) . "</b></div></td>";
            $tool_content .= "</tr>";
        }
         
        $classvis = '';         
        if ($is_editor) {
            if ($myrow->visible == 0) {
                $classvis = 'class = "not_visible"';
            }
        }
        $tool_content .= "<tr $classvis>";
        if ($is_editor) {
            $tool_content .= "<td>";
        } else {
            $tool_content .= "<td colspan='2'>";
        }

        $tool_content .= "<span class='day'>" . ucfirst(claro_format_locale_date($dateFormatLong, $d)) . "</span> ($langHour: " . ucfirst(date('H:i', $d)) . ")";
        if ($myrow->duration != '') {
            if ($myrow->duration == 1) {
                $message = $langHour;
            } else {
                $message = $langHours;
            }
            $msg = "($langDuration: " . q($myrow->duration) . " $message)";
        } else {
            $msg = '';
        }
        $tool_content .= "<br><b>";
        if ($myrow->title == '') {
            $tool_content .= $langAgendaNoTitle;
        } else {
            $tool_content .= q($myrow->title);
        }
        $tool_content .= " $msg $content</b></td>";

        if ($is_editor) {
            $tool_content .= "<td class='option-btn-cell'>";
            $tool_content .= action_button(array(
                    array('title' => $langModify,
                          'url' => "?course=$course_code&amp;id=$myrow->id&amp;edit=true",
                          'icon' => 'fa-edit'),
                    array('title' => $langDelete,
                          'url' => "?course=$course_code&amp;id=$myrow->id&amp;delete=yes",
                          'icon' => 'fa-times',
                          'class' => 'delete',
                          'confirm' => $langConfirmDelete),
                    array('title' => $langVisible,
                          'url' => "?course=$course_code&amp;id=$myrow->id" . ($myrow->visible? "&amp;mkInvisibl=true" : "&amp;mkVisibl=true"),
                          'icon' => $myrow->visible ? 'fa-eye' : 'fa-eye-slash')
                ));
           $tool_content .= "</td>";                                 
        }
        $tool_content .= "</tr>";
    }
    $tool_content .= "</table></div>";
} else {
    $tool_content .= "<p class='alert alert-warning text-center'>$langNoEvents</p>";
}
add_units_navigation(TRUE);

draw($tool_content, 2, null, $head_content);
