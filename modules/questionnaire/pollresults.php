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

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Questionnaire';

require_once '../../include/baseTheme.php';
require_once 'include/libchart/classes/libchart.php';

$nameTools = $langPollCharts;
$navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langQuestionnaire);

$questions = array();
$answer_total = 0;

if(!isset($_GET['pid']) || !is_numeric($_GET['pid'])) die();
	$pid = intval($_GET['pid']);
	$current_poll = db_query("SELECT * FROM poll WHERE course_id = $course_id AND pid='$pid' ORDER BY pid", $mysqlMainDb);
	$thePoll = mysql_fetch_array($current_poll);

	$tool_content .= "
        <p class='sub_title1'>$langSurvey</p>
	<table class='tbl_border'>
	<tr>
	  <th width='150'>$langTitle:</th>
	  <td>" . $thePoll["name"] . "</td>
	</tr>
	<tr>
	  <th>$langPollCreation:</th>
	  <td>".nice_format(date("Y-m-d H:i", strtotime($thePoll["creation_date"])), true)."</td>
	</tr>
	<tr>
	  <th>$langPollStart:</th>
	  <td>".nice_format(date("Y-m-d H:i", strtotime($thePoll["start_date"])), true)."</td>
	</tr>
	<tr>
	  <th>$langPollEnd:</th>
	  <td>".nice_format(date("Y-m-d H:i", strtotime($thePoll["end_date"])), true)."</td>
	</tr>
	</table>
	<p class='sub_title1'>$langAnswers</p>";
	$tool_content .= "<table class='tbl'>";

	$questions = db_query("SELECT * FROM poll_question WHERE pid=$pid");
	while ($theQuestion = mysql_fetch_array($questions)) {
		$tool_content .= "
                <tr>
                  <td width='50'><b>$langQuestion:</b></td>
                  <td>$theQuestion[question_text]</td>
                </tr>
                <tr>
                  <td>&nbsp;</td>
                  <td>";
                if ($theQuestion['qtype'] == 'multiple') {
                        $answers = db_query("SELECT COUNT(aid) AS count, aid, poll_question_answer.answer_text AS answer
                                FROM poll_answer_record LEFT JOIN poll_question_answer
                                ON poll_answer_record.aid = poll_question_answer.pqaid
                                WHERE qid = $theQuestion[pqid] GROUP BY aid", $mysqlMainDb);
                        $answer_counts = array();
                        $answer_text = array();
                        while ($theAnswer = mysql_fetch_array($answers)) {
                                $answer_counts[] = $theAnswer['count'];
                                $answer_total += $theAnswer['count'];
                                if ($theAnswer['aid'] < 0) {
                                        $answer_text[] = $langPollUnknown;
                                } else {
                                        $answer_text[] = $theAnswer['answer'];
                                }
                        }
                        $chart = new PieChart(500, 300);
                        $dataSet = new XYDataSet();
                        $chart->setTitle('');
                        foreach ($answer_counts as $i => $count) {
                                $percentage = 100 * ($count / $answer_total);
                                $label = sprintf("$answer_text[$i] (%2.1f%%)", $percentage);
                                $dataSet->addPoint(new Point($label, $percentage));
                        }
                        $chart->setDataSet($dataSet);
                        $chart_path = '/courses/'.$course_code.'/temp/chart_'.md5(serialize($chart)).'.png';
                        $chart->render($webDir.$chart_path);
                        $tool_content .= '<img src="'.$urlServer.$chart_path.'" /><br />';
                } else {
                        $answers = db_query("SELECT answer_text, user_id FROM poll_answer_record
                                        WHERE qid = $theQuestion[pqid]", $mysqlMainDb);
                        $tool_content .= '<dl>';
                        $answer_total = mysql_num_rows($answers);
                        while ($theAnswer = mysql_fetch_array($answers)) {
                                $tool_content .= "<dt><u>$langUser</u>: <dd>" . q(uid_to_name($theAnswer['user_id'])) . "</dd></dt> <dt><u>$langAnswer</u>: <dd>$theAnswer[answer_text]</dd></dt>";
                        }
                        $tool_content .= '</dl><br />';
                }
                $tool_content .= "</td></tr>";
         }
        $tool_content .= "<tr><th colspan='2'>$langPollTotalAnswers: $answer_total</th></tr>
	</table>";
// display page
draw($tool_content, 2);
