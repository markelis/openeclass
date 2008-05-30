<?php
/*===========================================================================
*              GUnet eClass 2.0
*       E-learning and Course Management Program
* ===========================================================================
*	Copyright(c) 2003-2006  Greek Universities Network - GUnet
*	A full copyright notice can be read in "/info/copyright.txt".
*
*  Authors:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*				Yannis Exidaridis <jexi@noc.uoa.gr>
*				Alexandros Diamantidis <adia@noc.uoa.gr>
*
*	For a full list of contributors, see "credits.txt".
*
*	This program is a free software under the terms of the GNU
*	(General Public License) as published by the Free Software
*	Foundation. See the GNU License for more details.
*	The full license can be read in "license.txt".
*
*	Contact address: 	GUnet Asynchronous Teleteaching Group,
*				Network Operations Center, University of Athens,
*				Panepistimiopolis Ilissia, 15784, Athens, Greece
*				eMail: eclassadmin@gunet.gr
============================================================================*/
/*
 * Groups Component
 *
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 * @version $Id$
 *
 * @abstract This module is responsible for the user groups of each lesson
 *
 */

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';
$require_prof = true;

include '../../include/baseTheme.php';
$nameTools = $langGroupMail;
$navigation[]= array ("url"=>"group.php", "name"=> $langGroupSpace,
"url"=>"group_space.php?userGroupId=$userGroupId", "name"=>$langGroupSpace);

include('../../include/sendMail.inc.php');
$tool_content = "";
$currentCourse=$dbname;

if ($is_adminOfCourse)  {
	if (isset($submit)) {
		$sql=mysql_query("SELECT user FROM `$dbname`.user_group WHERE team = '$userGroupId'");
		while ($userid = mysql_fetch_array($sql)) {
			mysql_select_db($mysqlMainDb);
			$m = mysql_fetch_array(mysql_query("SELECT DISTINCT email FROM user where user_id='$userid[0]'"));
			mysql_select_db($currentCourse);
			$prof = mysql_fetch_array(mysql_query("SELECT username, user_email FROM users WHERE user_id='1'"));
			$emailsubject = $intitule." - ".$subject;
			$emailbody = "$body_mail\n\n$prof[username]\n$langProfLesson\n";
			if (!send_mail($prof['username'], $prof['user_email'],
			'', $m[0], $emailsubject, $emailbody, $charset)) {
				$tool_content .= "<h4>$langMailError</h4>";
			}
		}
		$tool_content .= "<table width=\"99%\"><thead>
    		<tr><td class=\"success\">$langEmailSuccess</td></tr>
    		</thead></table><br />";
		$tool_content .= "<p align=\"right\"><a href=\"group.php\">$langBack</a></p>";
	} else {

		$tool_content .= <<<tCont

  <form action="$_SERVER[PHP_SELF]" method="post">
  <input type="hidden" name="userGroupId" value="$userGroupId">
    <table width="99%" class="FormData">
    <thead>
    <tr>
      <th width="220">&nbsp;</th>
      <td><b>$langTypeMessage</b></td>
    </tr>
    <tr>
      <th class="left">$langMailSubject:</th>
      <td><input type="text" name="subject" size="58" class="FormData_InputText"></input></td>
    </tr>
    <tr>
      <th class="left" valign="top">$langMailBody:</th>
      <td><textarea name="body_mail" rows="10" cols="73" class="FormData_InputText"></textarea></td>
    </tr>
    <tr>
      <th>&nbsp;</th>
      <td><input type="submit" name="submit" value="$langSend"></input></td>
    </tr>
    </thead>
    </table>
   <br />
   </form>


tCont;
}

}	// if prof

draw($tool_content, 2, 'group');
?>
