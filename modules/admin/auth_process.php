<?php
/*========================================================================
*   Open eClass 2.3
*   E-learning and Course Management System
* ========================================================================
*  Copyright(c) 2003-2010  Greek Universities Network - GUnet
*  A full copyright notice can be read in "/info/copyright.txt".
*
*  Developers Group:	Costas Tsibanis <k.tsibanis@noc.uoa.gr>
*						Yannis Exidaridis <jexi@noc.uoa.gr>
*						Alexandros Diamantidis <adia@noc.uoa.gr>
*						Tilemachos Raptis <traptis@noc.uoa.gr>
*
*  For a full list of contributors, see "credits.txt".
*
*  Open eClass is an open platform distributed in the hope that it will
*  be useful (without any warranty), under the terms of the GNU (General
*  Public License) as published by the Free Software Foundation.
*  The full license can be read in "/info/license/license_gpl.txt".
*
*  Contact address: 	GUnet Asynchronous eLearning Group,
*  						Network Operations Center, University of Athens,
*  						Panepistimiopolis Ilissia, 15784, Athens, Greece
*  						eMail: info@openeclass.org
* =========================================================================*/

/*===========================================================================
	auth_process.php
	@last update: 27-06-2006 by Stratos Karatzidis
	@authors list: Karatzidis Stratos <kstratos@uom.gr>
		       Vagelis Pitsioygas <vagpits@uom.gr>
==============================================================================
        @Description: Platform Authentication Methods and their settings

 	This script tries to get the values of an authentication method, establish
 	a connectiond and with a test account successfully connect to the server.
 	Possible scenarios:
 	- The settings of the method are fine and the mechanism authenticates the
 	test account
 	- The settings of the method are fine, but the method does not work
 	with the test account
 	- The settings are wrong.

 	The admin can: - choose a method and define its settings

==============================================================================
*/

$require_admin = TRUE;
include '../../include/baseTheme.php';
include("../../include/CAS/CAS.php");
include_once '../auth/auth.inc.php';
$nameTools = $langAuthSettings;
$navigation[] = array("url" => "index.php", "name" => $langAdmin);
$navigation[] = array("url" => "auth.php", "name" => $langUserAuthentication);
$tool_content = "";

// get the values
$step = isset($_POST['step'])?$_POST['step']:'';
if((!empty($step)) && ($step=='1')) {
	$auth = isset($_POST['auth'])?$_POST['auth']:'';
	$auth_submit = isset($_POST['auth_submit'])?$_POST['auth_submit']:'';
	if ($auth==7) {
		$_SESSION['cas_do'] = true;
	}
} 
else {
	$auth = isset($_GET['auth'])?$_GET['auth']:'';
	if ($auth==7) {
		$_SESSION['cas_do'] = false;
	}
}

if (isset($_GET['ticket'])) {
	$_SESSION['cas_do'] = true;
}

if (isset($_SESSION['cas_warn'])) {
		$_SESSION['cas_do'] = false;
}

$imaphost = isset($_POST['imaphost'])?$_POST['imaphost']:'';
$imapinstructions = isset($_POST['imapinstructions'])?$_POST['imapinstructions']:'';

$pop3host = isset($_POST['pop3host'])?$_POST['pop3host']:'';
$pop3instructions = isset($_POST['pop3instructions'])?$_POST['pop3instructions']:'';

$ldaphost = isset($_POST['ldaphost'])? $_POST['ldaphost']: '';
$ldap_base = isset($_POST['ldap_base'])? $_POST['ldap_base']: '';
$ldapbind_dn = isset($_POST['ldapbind_dn'])? $_POST['ldapbind_dn']: '';
$ldapbind_pw = isset($_POST['ldapbind_pw'])? $_POST['ldapbind_pw']: '';
$ldap_login_attr = (isset($_POST['ldap_login_attr']) and
                    !empty($_POST['ldap_login_attr']))?
                        $_POST['ldap_login_attr']: 'uid';
$ldap_login_attr2 = isset($_POST['ldap_login_attr2'])?
                        $_POST['ldap_login_attr2']: '';
$ldapinstructions = isset($_POST['ldapinstructions'])?
                        $_POST['ldapinstructions']: '';

$dbhost = isset($_POST['dbhost'])?$_POST['dbhost']:'';
$dbtype = isset($_POST['dbtype'])?$_POST['dbtype']:'';
$dbname = isset($_POST['dbname'])?$_POST['dbname']:'';
$dbuser = isset($_POST['dbuser'])?$_POST['dbuser']:'';
$dbpass = isset($_POST['dbpass'])?$_POST['dbpass']:'';
$dbtable = isset($_POST['dbtable'])?$_POST['dbtable']:'';
$dbfielduser = isset($_POST['dbfielduser'])?$_POST['dbfielduser']:'';
$dbfieldpass = isset($_POST['dbfieldpass'])?$_POST['dbfieldpass']:'';
$dbinstructions = isset($_POST['dbinstructions'])?$_POST['dbinstructions']:'';;

$shibinstructions = isset($_POST['shibinstructions'])?$_POST['shibinstructions']:'';;

// set them only from _POST, otherwise they exist in _SESSION
// _POST is lost after we come back from CAS
if (isset($_POST['cas_host'])) $cas_host = $_POST['cas_host'];
if (isset($_POST['cas_port'])) $cas_port = intval($_POST['cas_port']);
if (isset($_POST['cas_context'])) $cas_context = $_POST['cas_context'];
if (isset($_POST['cas_cachain'])) $cas_cachain = $_POST['cas_cachain'];
if (isset($_POST['casinstructions'])) $casinstructions = $_POST['casinstructions'];
if (isset($_POST['casusermailattr'])) $casusermailattr = $_POST['casusermailattr'];
if (isset($_POST['casuserfirstattr'])) $casuserfirstattr = $_POST['casuserfirstattr'];
if (isset($_POST['casuserlastattr'])) $casuserlastattr = $_POST['casuserlastattr'];

$test_username = isset($_POST['test_username'])?$_POST['test_username']:'';
$test_password = isset($_POST['test_password'])?$_POST['test_password']:'';

// You have to logout from CAS and preferably close your browser
// to change CAS settings
if ((isset($_SESSION['cas_warn']) && $_SESSION['cas_warn'] == "yes" ) && ($auth==7)) {
	$tool_content .= "<p class=\"alert1\">$langCASnochange</p>";
	draw($tool_content, 3);
	exit();
}

if(((!empty($auth_submit)) && ($auth_submit==1)) || !empty($_SESSION['cas_do'])) {
 	if (!empty($_SESSION['cas_do']) && empty($_SESSION['cas_warn'])) {
		// save _POST to _SESSION
		if (isset($cas_host)) $_SESSION['cas_host'] = $cas_host;
		if (isset($cas_port)) $_SESSION['cas_port'] = $cas_port;
		if (isset($cas_context)) $_SESSION['cas_context'] = $cas_context;
		if (isset($cas_cachain)) $_SESSION['cas_cachain'] = $cas_cachain;
		if (isset($casinstructions)) $_SESSION['casinstructions'] = $casinstructions;
		if (isset($casusermailattr)) $_SESSION['casusermailattr'] = $casusermailattr;
		if (isset($casuserfirstattr)) $_SESSION['casuserfirstattr'] = $casuserfirstattr;
		if (isset($casuserlastattr)) $_SESSION['casuserlastattr'] = $casuserlastattr;
		
		// cas test new settings
		//cas_authenticate(7, true, $cas_host, $cas_port, $cas_context, $cas_cachain);
		cas_authenticate(7, true, $_SESSION['cas_host'], $_SESSION['cas_port'], $_SESSION['cas_context'], $_SESSION['cas_cachain']);
		if (phpCAS::checkAuthentication()) {
			$test_username = phpCAS::getUser();
			$cas_valid = true;
			$_SESSION['cas_warn'] = "yes";
		}
		else
			$cas_valid = false;
	}

	$submit = isset($_POST['submit'])?$_POST['submit']:'';
	// if form is submitted
	if( ((array_key_exists('submit', $_POST)) && (!empty($submit))) || $cas_valid ) {
		$tool_content .= "<br /><p>$langConnTest</p>";
		if ( ($auth == 6) || $cas_valid ) {
			$test_username = $test_password = " ";
		}
		if((!empty($test_username)) && (!empty($test_password))) {
			if ($cas_valid)
				$is_valid = true;
			else
				$is_valid = auth_user_login($auth, $test_username, $test_password);

			if($is_valid) {
				$auth_allow = 1;
				$tool_content .= "<table width=\"99%\"><tbody><tr>
				<td class=\"success\">$langConnYes</td></tr></tbody></table><br /><br />";
			} else {
				$tool_content .= "<table width=\"99%\"><tbody><tr><td class=\"caution\">$langConnNo";
				if (isset($GLOBALS['auth_errors'])) {
					$tool_content .= "<p>$GLOBALS[auth_errors]</p>";
				}
				$tool_content .= "</td></tr></tbody></table><br /><br />";
				$auth_allow = 0;
			}
		} else {
			$tool_content .= "<table width=\"99%\"><tbody><tr>
			<td class=\"caution\">$langWrongAuth</td></tr></tbody></table><br /><br />";
			$auth_allow = 0;
		}

		// when we come back from CAS
		if (isset($_SESSION['cas_do']) && $_SESSION['cas_do']==7) {
			$auth = 7;
		}

		// store the values - do the updates //
		if((!empty($auth_allow))&&($auth_allow==1)) {

			switch($auth) {
				case '1': $auth_default = 1;
					$auth_settings = "";
					$auth_instructions = "";
					break;
				case '2': $auth_default = 2;
					$auth_settings = "pop3host=".$pop3host;					
					$auth_instructions = $pop3instructions;
					break;
				case '3': $auth_default = 3;
					$auth_settings = "imaphost=".$imaphost;
					$auth_instructions = $imapinstructions;
					break;
				case '4': $auth_default = 4;
					$auth_settings = "ldaphost=".$ldaphost."|ldap_base=".$ldap_base."|ldapbind_dn=".$ldapbind_dn."|ldapbind_pw=".$ldapbind_pw."|ldap_login_attr=".$ldap_login_attr."|ldap_login_attr2=".$ldap_login_attr2;
					$auth_instructions = $ldapinstructions;
					break;
				case '5': 
					$auth_default = 5;
					$auth_settings = "dbhost=".$dbhost."|dbname=".$dbname."|dbuser=".$dbuser."|dbpass=".$dbpass."|dbtable=".$dbtable."|dbfielduser=".$dbfielduser."|dbfieldpass=".$dbfieldpass;
					$auth_instructions = $dbinstructions;
					break;
				case '6': if (isset($checkseparator) && $checkseparator == "on") {
						$auth_settings = $_POST['shibseparator'];
					} else {
						$auth_settings = 'shibboleth';
					}
					$auth_instructions = $shibinstructions;
					break;
				case '7': $auth_default = 7;
					$auth_settings = "cas_host=".$_SESSION['cas_host']."|cas_port=".$_SESSION['cas_port']."|cas_context=".$_SESSION['cas_context']."|cas_cachain=".$_SESSION['cas_cachain']."|casusermailattr=".$_SESSION['casusermailattr']."|casuserfirstattr=".$_SESSION['casuserfirstattr']."|casuserlastattr=".$_SESSION['casuserlastattr'];
					$auth_instructions = $_SESSION['casinstructions'];
					break;
				default:
					break;
			}

			$qry = "UPDATE auth SET auth_settings='".$auth_settings."',
					auth_instructions='".$auth_instructions."',
					auth_default=1 
				WHERE auth_id=".$auth;
			$sql2 = db_query($qry, $db); // do the update as the default method
			if($sql2) {
				if(mysql_affected_rows($db)==1) {
					$tool_content .= "<p class=\"alert1\">$langHasActivate</p>";
				} else {
					$tool_content .= "<p class=\"alert1\">$langAlreadyActiv</p>";
				}
			} else {
				$tool_content .= "<p class=\"alert1\">$langErrActiv</p>";
			}
		}
	}
}
else
{
	// Display the form 
	//if(isset($auth) and $auth != 6 and $auth != 7) {
	if(isset($auth) and $auth != 6 and $auth != 7) {
		$auth_data = get_auth_settings($auth);
	}
	$tool_content .= "<form name='authmenu' method='post' action='$_SERVER[PHP_SELF]'>
	<table width='99%' class='FormData' align='left'><tbody><tr>
	<th width='220'>
	<input type=\"hidden\" name=\"auth_submit\" value=\"1\" />
	<input type=\"hidden\" name=\"auth\" value=\"".htmlspecialchars($auth)."\" />
	<input type=\"hidden\" name=\"step\" value=\"1\" />
	</th>
	<td><b>".get_auth_info($auth)."</b></td>
	</tr>";
	
	switch($auth) {
		case 2: include_once '../auth/methods/pop3form.php';
			break;
		case 3: include_once '../auth/methods/imapform.php';
			break;
		case 4: include_once '../auth/methods/ldapform.php';
			break;
		case 5: include_once '../auth/methods/db/dbform.php';
			break;
		case 6: include_once '../auth/methods/shibform.php';
			break;
		case 7: include_once '../auth/methods/casform.php';
			break;
		default:
			break;
	}

	if ($auth != 6 && $auth !=7) { 
		$tool_content .= "<tr><td colspan='2'>&nbsp;</td></tr>";
		$tool_content .= "<tr><th>&nbsp;</th><td>$langTestAccount</td></tr>
		<tr><th class='left'>$langUsername: </th>
		<td><input size='30' class='FormData_InputText' type='text' name='test_username' value='".$test_username."'></td></tr>
		<tr><th class='left'>$langPass: </th>
		<td><input size='30' class='FormData_InputText' type='password' name='test_password' value='".$test_password."'></td></tr>";
	}
	$tool_content .= "<tr><th>&nbsp;</th><td><input type='submit' name='submit' value='$langModify'></td></tr>";
	$tool_content .= "</table></form>";
}

draw($tool_content, 3);
?>
