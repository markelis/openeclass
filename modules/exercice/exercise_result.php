<?php
/*=============================================================================
       	GUnet eClass 2.0 
        E-learning and Course Management Program  
================================================================================
       	Copyright(c) 2003-2006  Greek Universities Network - GUnet
        A full copyright notice can be read in "/info/copyright.txt".
        
       	Authors:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
        	    Yannis Exidaridis <jexi@noc.uoa.gr> 
      		    Alexandros Diamantidis <adia@noc.uoa.gr> 

        For a full list of contributors, see "credits.txt".  
     
        This program is a free software under the terms of the GNU 
        (General Public License) as published by the Free Software 
        Foundation. See the GNU License for more details. 
        The full license can be read in "license.txt".
     
       	Contact address: GUnet Asynchronous Teleteaching Group, 
        Network Operations Center, University of Athens, 
        Panepistimiopolis Ilissia, 15784, Athens, Greece
        eMail: eclassadmin@gunet.gr
==============================================================================*/

include('exercise.class.php');
include('question.class.php');
include('answer.class.php');

// answer types
define('UNIQUE_ANSWER',	1);
define('MULTIPLE_ANSWER', 2);
define('FILL_IN_BLANKS', 3);
define('MATCHING', 4);

$require_current_course = TRUE;
$guest_allowed = true;
include '../../include/baseTheme.php';
$tool_content = "";

$nameTools = $langExercicesResult;
$navigation[]= array ("url"=>"exercice.php", "name"=> $langExercices);

// Destroy cookie
if (!setcookie("marvelous_cookie", "", time() - 3600, "/")) {
	header('Location: exercise_redirect.php');
	exit();
}
if (!setcookie("marvelous_cookie_control", "", time() - 3600, "/")) {
	header('Location: exercise_redirect.php');
	exit();
}

// latex support
include_once "$webDir"."/modules/latexrender/latex.php";
include('../../include/lib/textLib.inc.php');

$TBL_EXERCICE_QUESTION='exercice_question';
$TBL_EXERCICES='exercices';
$TBL_QUESTIONS='questions';
$TBL_REPONSES='reponses';

// if the above variables are empty or incorrect, stops the script
if(!is_array($exerciseResult) || !is_array($questionList) || !is_object($objExercise))
{
	$tool_content .= $langExerciseNotFound;
	draw($tool_content, 2, 'exercice');
	exit();
}

$exerciseTitle=$objExercise->selectTitle();
$exerciseDescription=$objExercise->selectDescription();
$exerciseDescription_temp = nl2br(make_clickable($exerciseDescription));

$tool_content .= "
      <table class=\"Exercise\" width=\"99%\">
      <thead>
      <tr>
        <td colspan=\"2\">
        <b>".stripslashes($exerciseTitle)."</b>
        <br/><br/>
        ".stripslashes($exerciseDescription_temp)."
        </td>
      </tr>
      </thead>
      </table>
	  
	<form method=\"GET\" action=\"exercice.php\">";

	$i=$totalScore=$totalWeighting=0;

	// for each question
	foreach($questionList as $questionId)
	{
		// gets the student choice for this question
		$choice=@$exerciseResult[$questionId];

		// creates a temporary Question object
		$objQuestionTmp=new Question();
		$objQuestionTmp->read($questionId);

		$questionName=$objQuestionTmp->selectTitle();
		$questionName=latex_content($questionName);
		$questionDescription=$objQuestionTmp->selectDescription();
		$questionDescription=latex_content($questionDescription);
        $questionDescription_temp = nl2br(make_clickable($questionDescription));
	
	
		$questionWeighting=$objQuestionTmp->selectWeighting();
		$answerType=$objQuestionTmp->selectType();

		// destruction of the Question object
		unset($objQuestionTmp);

		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
		{
			$colspan=4;
		}
		elseif($answerType == MATCHING)
		{
			$colspan=2;
		}
		else
		{
			$colspan=1;
		}
$iplus=$i+1;
$tool_content .= <<<cData

      <br/>
      <table width="99%" class="Question">
      <thead>
      <tr>
        <td colspan="${colspan}"><b><u>$langQuestion</u>: $iplus</b></td>
      </tr>
      <tr>
        <td colspan="${colspan}"><b>${questionName}</b><br/>
        <small>${questionDescription_temp}</small><br/><br/></td>
      </tr>
      </thead>
      <tbody>
cData;

		if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
		{
$tool_content .= <<<cData

      <tr>
        <td width="5%" align="center" style="background: #fff;"><b>${langChoice}</b></td>
        <td width="5%" align="center" style="background: #fff;"><b>${langExpectedChoice}</b></td>
        <td width="45%" align="center" style="background: #fff;"><b>${langAnswer}</b></td>
        <td width="45%" align="center" style="background: #fff;"><b>${langComment}</b></td>
      </tr>
cData;

	}
	elseif($answerType == FILL_IN_BLANKS)
	{

$tool_content .= <<<cData
      <tr>
        <td style="background: #fff;"><b>${langAnswer}</b></td>
      </tr>
cData;

	} else {
		
$tool_content .= <<<cData
      <tr>
        <td width="50%" style="background: #fff;"><b>${langElementList}</b></td>
        <td width="50%" style="background: #fff;"><b>${langCorrespondsTo}</b></td>
      </tr>
cData;

		}

		// construction of the Answer object
		$objAnswerTmp=new Answer($questionId);

		$nbrAnswers=$objAnswerTmp->selectNbrAnswers();
		$questionScore=0;

		for($answerId=1;$answerId <= $nbrAnswers;$answerId++) {
			$answer=$objAnswerTmp->selectAnswer($answerId);
			$answerComment=$objAnswerTmp->selectComment($answerId);
			$answerCorrect=$objAnswerTmp->isCorrect($answerId);
			$answerWeighting=$objAnswerTmp->selectWeighting($answerId);
			$answer=latex_content($answer);

			switch($answerType)
			{
				// for unique answer
				case UNIQUE_ANSWER :	$studentChoice=($choice == $answerId)?1:0;

						if($studentChoice) {
							$questionScore+=$answerWeighting;
							$totalScore+=$answerWeighting;
							}
						break;
				// for multiple answers
				case MULTIPLE_ANSWER :	$studentChoice=@$choice[$answerId];
						if($studentChoice) {
							$questionScore+=$answerWeighting;
							$totalScore+=$answerWeighting;
							}
						break;
				// for fill in the blanks
				case FILL_IN_BLANKS :	// splits text and weightings that are joined with the character '::'
						list($answer,$answerWeighting)=explode('::',$answer);
						// splits weightings that are joined with a comma
						$answerWeighting=explode(',',$answerWeighting);
						// we save the answer because it will be modified
						$temp=$answer;
						$answer='';
						$j=0;
						// the loop will stop at the end of the text
						while(1)
						{
						// quits the loop if there are no more blanks
						if(($pos = strpos($temp,'[')) === false) {
							// adds the end of the text
							$answer.=$temp;
							break;
						}
					// adds the piece of text that is before the blank and ended by [
						$answer.=substr($temp,0,$pos+1);
						$temp=substr($temp,$pos+1);
						// quits the loop if there are no more blanks
						if(($pos = strpos($temp,']')) === false) {
							// adds the end of the text
							$answer.=$temp;
							break;
						}
						$choice[$j]=trim(stripslashes($choice[$j]));
					// if the word entered by the student IS the same as the one defined by the professor
					if(strtolower(substr($temp,0,$pos)) == strtolower($choice[$j])) {
						// gives the related weighting to the student
						$questionScore+=$answerWeighting[$j];
						// increments total score
						$totalScore+=$answerWeighting[$j];
						// adds the word in green at the end of the string
						$answer.=$choice[$j];
					}
				// else if the word entered by the student IS NOT the same as the one defined by the professor
						elseif(!empty($choice[$j])) {
						// adds the word in red at the end of the string, and strikes it
						$answer.='<font color="red"><s>'.$choice[$j].'</s></font>';
						} else {
						// adds a tabulation if no word has been typed by the student
						$answer.='&nbsp;&nbsp;&nbsp;';
						}
						// adds the correct word, followed by ] to close the blank
						$answer.=' / <font color="green"><b>'.substr($temp,0,$pos).'</b></font>]';
						$j++;
						$temp=substr($temp,$pos+1);
						}
					break;
				// for matching
				case MATCHING :	if($answerCorrect) {
							if($answerCorrect == $choice[$answerId]) {
							$questionScore+=$answerWeighting;
							$totalScore+=$answerWeighting;
							$choice[$answerId]=$matching[$choice[$answerId]];
						}
						elseif(!$choice[$answerId]) {
							$choice[$answerId]='&nbsp;&nbsp;&nbsp;';
						} else {
							$choice[$answerId]='<font color="red"><s>'.$matching[$choice[$answerId]].'</s></font>';
							}
						}
						else {
							$matching[$answerId]=$answer;
						}
						break;
			}	// end switch()
			if($answerType != MATCHING || $answerCorrect)
			{
				if($answerType == UNIQUE_ANSWER || $answerType == MULTIPLE_ANSWER)
				{
$tool_content .= <<<cData
      <tr>
        <td width="5%" style="background: #fff;"><div align="center">
        <img src="../../template/classic/img/
cData;
	
	if ($answerType == UNIQUE_ANSWER)
		$tool_content .= "radio";
	else
		$tool_content .= "checkbox";
	
	if ($studentChoice)
		$tool_content .= "_on";
	else
		$tool_content .= '_off';
		
	$tool_content .= <<<cData
		.gif" border="0"></div>
        </td>
        <td width="5%" style="background: #fff;"><div align="center">
cData;

	if ($answerType == UNIQUE_ANSWER)
		$tool_content .= "<img src=\"../../template/classic/img/radio";
	else
		$tool_content .= "<img src=\"../../template/classic/img/checkbox";
	if ($answerCorrect)
		$tool_content .= "_on";
	else	
		$tool_content .= "_off";	
	$tool_content .= ".gif\" border=\"0\"></div>";	
  	$tool_content .= <<<cData
        </td>
        <td width="45%" style="background: #fff;">${answer}</td>
        <td width="45%" style="background: #fff;">
cData;

if($studentChoice) 
	$tool_content .= nl2br(make_clickable($answerComment)); 
else 
	$tool_content .= '&nbsp;'; 

  $tool_content .= "</td></tr>";

	} elseif($answerType == FILL_IN_BLANKS) {
			$tool_content .= "
      <tr>
        <td style=\"background: #fff;\">".nl2br($answer)."</td>
      </tr>";
	} else {

$tool_content .= <<<cData
      <tr>
        <td width="50%" style="background: #fff;">${answer}</td>
        <td width="50%" style="background: #fff;">${choice[$answerId]} / <font color="green"><b>${matching[$answerCorrect]}</b></font></td>
      </tr>
cData;

		}
	}
}	// end for()

$tool_content .= <<<cData
      <tr>
        <td colspan="${colspan}" class="score">
        ${langQuestionScore}: <b>${questionScore}/${questionWeighting}</b>
        </td>
      </tr>
      </tbody>
      </table>
      
cData;

	// destruction of Answer
		unset($objAnswerTmp);
		$i++;
		$totalWeighting+=$questionWeighting;
	}	// end foreach()

/////////////////////////////////////////////////////////////////////////////
// UPDATE results to DB
/////////////////////////////////////////////////////////////////////////////

$eid=$objExercise->selectId();
mysql_select_db($currentCourseID);
$sql="SELECT RecordStartDate FROM `exercise_user_record` WHERE eid='$eid' AND uid='$uid'";
$result=mysql_query($sql);
$attempt = count($result);
$row=mysql_fetch_array($result);
$RecordStartDate = ($RecordStartTime_temp = $row[count($result)-1]);
$RecordStartTime_temp = mktime(0, 0 , 0, substr($RecordStartTime_temp, 5,2), substr($RecordStartTime_temp, 8,2), substr($RecordStartTime_temp, 0,4));
$exerciseTimeConstrain=$objExercise->selectTimeConstrain();
$exerciseTimeConstrain = $exerciseTimeConstrain*60;
$RecordEndDate = ($SubmitDate = date("Y-m-d"));
$SubmitDate = mktime(0, 0, 0, substr($SubmitDate, 5,2), substr($SubmitDate, 8,2), substr($SubmitDate, 0,4));	
if (!$exerciseTimeConstrain) {
	$exerciseTimeConstrain = (7 * 24 * 60 * 60);
}
$OnTime = $RecordStartTime_temp + $exerciseTimeConstrain - $SubmitDate;

if (($OnTime > 0 or $is_adminOfCourse)) { // exercise time limit hasn't expired
	$sql="SELECT eurid FROM `exercise_user_record` WHERE eid='$eid' AND uid='$uid'";
	$result = mysql_query($sql);
	$row=mysql_fetch_array($result);
	$x = $row[count($result)-1];
	$eurid = $row[count($result)-1];
	// record end/results of exercise
	$sql="UPDATE `exercise_user_record` SET RecordEndDate='$RecordEndDate',TotalScore='$totalScore', TotalWeighting='$totalWeighting', attempt='$attempt' WHERE eurid='$eurid'";
	db_query($sql,$currentCourseID);
} else {  // not allowed begin again
	// if the object is not in the session
	if(!session_is_registered('objExercise')) {
		// construction of Exercise
		$objExercise=new Exercise();	
		// if the specified exercise doesn't exist or is disabled
		if(!$objExercise->read($exerciseId) && (!$is_allowedToEdit)) {
			$tool_content .= $langExerciseNotFound;
			draw($tool_content, 2, 'exercice');
			exit();	
		}
		// saves the object into the session
		session_register('objExercise');
    }

	$tool_content = "
      <table class=\"Exercise\" width=\"99%\">
      <thead>
      <tr>
        <td colspan=\"2\">
        <b>".stripslashes($exerciseTitle)."</b>
        <br/><br/>
        ".stripslashes($exerciseDescription_temp)."
        </td>
      </tr>
      </thead>
      </table>
      ";

    $tool_content .= <<<cData
      <br/>
      <table width="99%" class="Question">
      <thead>
      <tr>
        <td class="alert1">${langExerciseExpiredTime}</td>
      </tr>
      <tr>
        <td><br/><br/><br/><div align="center"><a href="exercice.php">${langBack}</a></div></td>
      </tr>
      </thead>
      </table>
cData;

draw($tool_content, 2, 'exercice');
exit();
}

$tool_content .= <<<cData
      <br/>
      <table width="99%" class="Exercise">
      <thead>
      <tr>
        <td class="score">
        ${langYourTotalScore}: <b>${totalScore}/${totalWeighting}</b>
        </td>
      </tr>
      </thead>
      </table>

      <br/>
      <div align="center">
        <input type="submit" value="${langFinish}">
      </div>


      <br/></form><br>
cData;
draw($tool_content, 2, 'exercice');
?>
