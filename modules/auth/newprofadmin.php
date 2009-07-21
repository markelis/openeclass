<?
/*========================================================================
*   Open eClass 2.1
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2008  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:    Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*                       Yannis Exidaridis <jexi@noc.uoa.gr>
*                       Alexandros Diamantidis <adia@noc.uoa.gr>
*                       Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address:     GUnet Asynchronous eLearning Group,
*                       Network Operations Center, University of Athens,
*                       Panepistimiopolis Ilissia, 15784, Athens, Greece
*                       eMail: info@openeclass.org
* =========================================================================*/


$require_admin = TRUE;
include '../../include/baseTheme.php';
include '../../include/sendMail.inc.php';
$nameTools = $langProfReg;
$navigation[] = array("url" => "../admin/index.php", "name" => $langAdmin);

// Initialise $tool_content
$tool_content = "";

$submit = isset($_POST['submit'])?$_POST['submit']:'';
if($submit) {
		
	// register user
	$nom_form = isset($_POST['nom_form'])?$_POST['nom_form']:'';
	$prenom_form = isset($_POST['prenom_form'])?$_POST['prenom_form']:'';
	$uname = isset($_POST['uname'])?$_POST['uname']:'';
	$password = isset($_POST['password'])?$_POST['password']:'';
	$email_form = isset($_POST['email_form'])?$_POST['email_form']:'';
	$depid = intval(isset($_POST['department'])?$_POST['department']: 0);
	$proflanguage = isset($_POST['language'])?$_POST['language']:'';
	if (!isset($native_language_names[$proflanguage])) {
		$proflanguage = langname_to_code($language);
	}

	// check if user name exists
	$username_check = mysql_query("SELECT username FROM `$mysqlMainDb`.user WHERE username=".autoquote($uname));
	$user_exist = (mysql_num_rows($username_check) > 0);

	// check if there are empty fields
	if (empty($nom_form) or empty($prenom_form) or empty($password) or empty($department) or empty($uname) or (empty($email_form))) {
		$tool_content .= "<p class=\"caution_small\">$langEmptyFields</p>
			<br><br><p align=\"right\"><a href='$_SERVER[PHP_SELF]'>$langAgain</a></p>";
	} elseif ($user_exist) {
		$tool_content .= "<p class=\"caution_small\">$langUserFree</p>
			<br><br><p align=\"right\"><a href='$_SERVER[PHP_SELF]'>$langAgain</a></p>";
	} elseif(!email_seems_valid($email_form)) {
		$tool_content .= "<p class=\"caution_small\">$langEmailWrong.</p>
			<br><br><p align=\"right\"><a href='$_SERVER[PHP_SELF]'>$langAgain</a></p>";
	} else {
		$expires_at = time() + $durationAccount;
		$password_encrypted = md5($password);
		$inscr_user=db_query("INSERT INTO `$mysqlMainDb`.user
				(nom, prenom, username, password, email, statut, department, registered_at, expires_at,lang)
				VALUES (" .
				autoquote($nom_form) . ', ' .
				autoquote($prenom_form) . ', ' .
				autoquote($uname) . ", '$password_encrypted', " .
				autoquote($email_form) .
				", 1, $depid, NOW(), '$expires_at', '$proflanguage')");
		$last_id = mysql_insert_id();

		// close request
	  	$rid = intval($_POST['rid']);
  	  	db_query("UPDATE prof_request set status = '2',date_closed = NOW() WHERE rid = '$rid'");
	       	$tool_content .= "<p class=\"success_small\">$profsuccess</p><br><br><p align=\"right\"><a href='../admin/listreq.php'>$langBackRequests</a></p>";
		
		// send email
		$emailsubject = "$langYourReg $siteName $langAsProf";
		
$emailbody = "
$langDestination $prenom_form $nom_form

$langYouAreReg $siteName $langAsProf, $langSettings $uname
$langPass : $password
$langAddress $siteName $langIs: $urlServer
$langProblem

$administratorName $administratorSurname
$langManager $siteName
$langTel $telephone
$langEmail : $emailAdministrator
";
		
		send_mail($siteName, $emailAdministrator, '', $email_form, $emailsubject, $emailbody, $charset);

		}

} else {
	if (isset($id)) { // if we come from prof request
		$res = mysql_fetch_array(db_query("SELECT profname,profsurname, profuname, profemail, proftmima, lang 
			FROM prof_request WHERE rid='$id'"));
		$ps = $res['profsurname'];
		$pn = $res['profname'];
		$pu = $res['profuname'];
		$pe = $res['profemail'];
		$pt = $res['proftmima'];
		$lang = $res['lang'];
		
		// if not submit then display the form
		if (isset($lang)) {
			$lang = langname_to_code($language);
		}
	}

	$tool_content .= "<form action=\"$_SERVER[PHP_SELF]\" method=\"post\">
	<table width=\"99%\" align=\"left\" class=\"FormData\">
	<tbody><tr>
	<th width=\"220\">&nbsp;</th>
	<td><b>$langNewProf</b></td>
	</tr>
	<tr>
	<th class='left'><b>".$langSurname."</b></th>
	<td><input class='FormData_InputText' type=\"text\" name=\"nom_form\" value=\"".@$ps."\" >&nbsp;(*)</td>
	</tr>
	<tr>
	<th class='left'><b>".$langName."</b></th>
	<td><input class='FormData_InputText' type=\"text\" name=\"prenom_form\" value=\"".@$pn."\">&nbsp;(*)</td>
	</tr>
	<tr>
	<th class='left'><b>".$langUsername."</b></th>
	<td><input class='FormData_InputText' type=\"text\" name=\"uname\" value=\"".@$pu."\">&nbsp;(*)</td>
	</tr>
	<tr>
	<th class='left'><b>".$langPass."&nbsp;:</b></th>
	<td><input class='FormData_InputText' type=\"text\" name=\"password\" value=\"".create_pass(5)."\"></td>
	</tr>
	<tr>
	<th class='left'><b>".$langEmail."</b></th>
	<td><input class='FormData_InputText' type=\"text\" name=\"email_form\" value=\"".@$pe."\">&nbsp;(*)</b></td>
	</tr>
	<tr>
	<th class='left'>".$langDepartment.":</th>
	<td>";
	
	$dep = array();
	$deps = db_query("SELECT id, name FROM faculte order by id");
	while ($n = mysql_fetch_array($deps)) {
		$dep[$n['id']] = $n['name'];
	}
	if (isset($pt)) {
		$tool_content .= selection ($dep, 'department', $pt);
	} else {
		$tool_content .= selection ($dep, 'department');
	}
	$tool_content .= "</td>
	</tr>
		<tr>
	<th class='left'>$langLanguage</th>
	<td>";
		$tool_content .= lang_select_options('language');
		$tool_content .= "</td>
	</tr>
	<tr>
	<th>&nbsp;</th>
	<td><input type=\"submit\" name=\"submit\" value=\"".$langSubmit."\" >
		<input type=\"hidden\" name=\"auth\" value=\"1\" >&nbsp;
		<small>".$langRequiredFields."</small></td>
	</tr>
	<input type='hidden' name='rid' value='".@$id."'>
	</tbody>
	</table>
	</form>";
	
	$tool_content .= "
	<br />
	<p align=\"right\"><a href=\"../admin/index.php\">$langBack</p>";
}
draw($tool_content, 3, 'auth');
?>
