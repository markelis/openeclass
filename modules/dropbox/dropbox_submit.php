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
 * @file dropbox_submit.php
 * @brief Handles actions submitted from the main dropbox page
 */

$require_login = TRUE;
$require_current_course = TRUE;
$guest_allowed = FALSE;
include '../../include/baseTheme.php';
require_once 'include/lib/forcedownload.php';
require_once 'include/lib/fileUploadLib.inc.php';
require_once 'include/sendMail.inc.php';

$dropbox_dir = $webDir."/courses/".$course_code."/dropbox";
// get dropbox quotas from database
$d = mysql_fetch_array(db_query("SELECT dropbox_quota FROM course WHERE code = '$course_code'"));
$diskQuotaDropbox = $d['dropbox_quota'];
$nameTools = $langDropBox;

/**
 * ========================================
 * PREVENT RESUBMITING
 * ========================================
 * This part checks if the $dropbox_unid var has the same ID
 * as the session var $dropbox_uniqueid that was registered as a session
 * var before.
 * The resubmit prevention only works with GET requests, because it gives some annoying
 * behaviours with POST requests.
 */

if (isset($_POST['dropbox_unid'])) {
	$dropbox_unid = $_POST['dropbox_unid'];
} elseif (isset($_GET['dropbox_unid']))
{
	$dropbox_unid = $_GET['dropbox_unid'];
} else {
	header("Location: $urlServer");
}

if (isset($_SESSION["dropbox_uniqueid"]) && isset($_GET["dropbox_unid"]) && $dropbox_unid == $_SESSION["dropbox_uniqueid"]) {

	if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on") {
		$mypath = "https";
	} else {
		$mypath = "http";
	}
	$mypath=$mypath."://".$_SERVER['HTTP_HOST'].dirname($_SERVER['SCRIPT_NAME'])."/index.php?course=$course_code";

	header("Location: $mypath");
}

$dropbox_uniqueid = $dropbox_unid;
$_SESSION['dropbox_uniqueid'] = $dropbox_uniqueid;

require_once("dropbox_class.inc.php");

/*
 form submission
*/
if (isset($_POST["submitWork"])) {
	$error = FALSE;
	$errormsg = '';
	if (!isset($_POST['description'])) {
		$error = TRUE;
		$errormsg = $langBadFormData;
	}
     /*
     * --------------------------------------
     *     FORM SUBMIT : UPLOAD NEW FILE
     * --------------------------------------
     */
	if (!$error) {
		$cwd = getcwd();
		if (is_dir($dropbox_dir)) {
			$dropbox_space = dir_total_space($dropbox_dir);
		}
		$dropbox_filename = php2phps($_FILES['file']['name']);
		$dropbox_filesize = $_FILES['file']['size'];
		$dropbox_filetype = $_FILES['file']['type'];
		$dropbox_filetmpname = $_FILES['file']['tmp_name'];
		
		validateUploadedFile($_FILES['file']['name'], 1);

		if ($dropbox_filesize + $dropbox_space > $diskQuotaDropbox)
		{
			$errormsg = $langNoSpace;
			$error = TRUE;
		} elseif (!is_uploaded_file($dropbox_filetmpname)) // check user found : no clean error msg.
		{
			die ($langBadFormData);
		}

		// set title
		$dropbox_title = $dropbox_filename;
		$format = get_file_extension($dropbox_filename);
		$dropbox_filename = safe_filename($format);		
		$newWorkRecipients = $_POST["recipients"];
		
		//After uploading the file, create the db entries
		if (!$error) {
			$subject_dropbox = "$logo - $langNewDropboxFile";
			$c = course_code_to_title($course_code);
			$filename_final = $dropbox_dir . '/' . $dropbox_filename;
			move_uploaded_file($dropbox_filetmpname, $filename_final) or die($langUploadError);
			@chmod($filename_final, 0644);
			new Dropbox_SentWork($uid, $dropbox_title, $_POST['description'], $dropbox_filename, $dropbox_filesize, $newWorkRecipients);
			if (isset($_POST['mailing']) and $_POST['mailing']) {	// send mail to recipients of dropbox file
				foreach($newWorkRecipients as $userid) {
                                        if (get_user_email_notification($userid, $course_id)) {
                                                $linkhere = "&nbsp;<a href='${urlServer}modules/profile/emailunsubscribe.php?cid=$course_id'>$langHere</a>.";
                                                $unsubscribe = "<br /><br />".sprintf($langLinkUnsubscribe, $title);
                                                $body_dropbox_message = "$langInCourses '".q($c)."' $langDropboxMailNotify <br /><br />$gunet<br /><a href='$urlServer'>$urlServer</a> $unsubscribe$linkhere";
                                                $plain_body_dropbox_message = "$langInCourses '$c' $langDropboxMailNotify \n\n$gunet\n<a href='$urlServer'>$urlServer</a> $unsubscribe$linkhere";
                                                $emailaddr = uid_to_email($userid);
                                                send_mail_multipart('', '', '', $emailaddr, $subject_dropbox, $plain_body_dropbox_message, $body_dropbox_message, $charset);
                                        }
				}
			}
		}
		chdir ($cwd);
	} //end if(!$error)
	if (!$error) {
		$tool_content .= "<p class='success'>$langdocAdd<br />
		<a href='index.php?course=$course_code'>$langBack</a></p><br/>";
	} else {
		$tool_content .= "<p class='caution'>$errormsg<br /><br />
		<a href='index.php?course=$course_code'>$langBack</a><br/>";
	}
}

/*
 * ========================================
 * DELETE RECEIVED OR SENT FILES
 * ========================================
 * - DELETE ALL RECEIVED FILES
 * - DELETE 1 RECEIVED FILE
 * - DELETE ALL SENT FILES
 * - DELETE 1 SENT FILE
 */
if (isset($_GET['deleteReceived']) or isset($_GET['deleteSent'])) {
	
        $dropbox_person = new Dropbox_Person($uid, $is_editor);	
	if (isset($_GET['deleteReceived']))
	{
		if ($_GET["deleteReceived"] == "all") {
			$dropbox_person->deleteAllReceivedWork( );
		} elseif (is_numeric( $_GET["deleteReceived"])) {
			$dropbox_person->deleteReceivedWork($_GET['deleteReceived']);
		}		
	} else {
		if ($_GET["deleteSent"] == "all") {
			$dropbox_person->deleteAllSentWork( );
		} elseif (is_numeric($_GET["deleteSent"])) {
			$dropbox_person->deleteSentWork($_GET['deleteSent']);
		}		
	}        
        $tool_content .= "<p class='success'>$langDelF<br />
	<a href='index.php?course=$course_code'>$langBack</a></p><br/>";
	
} elseif (isset($_GET['AdminDeleteSent'])) {        
        $dropbox_person = new Dropbox_Person($uid, $is_editor);
        $dropbox_person ->deleteWork($_GET['AdminDeleteSent']);        

        $tool_content .= "<p class='success'>$langDelF<br />
	<a href='index.php?course=$course_code'>$langBack</a></p><br/>";
}

draw($tool_content, 2, null, $head_content);
