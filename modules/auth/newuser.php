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
 * @file newuser.php
 * @brief user registration process
 */

require_once '../../include/baseTheme.php';
require_once 'include/sendMail.inc.php';
require_once 'include/phpass/PasswordHash.php';
require_once 'modules/auth/auth.inc.php';

require_once 'include/lib/user.class.php';
require_once 'include/lib/hierarchy.class.php';

$display_captcha = get_config("display_captcha") && function_exists('imagettfbbox');

$tree = new Hierarchy();
$userObj = new User();

load_js('jstree3d');
load_js('pwstrength.js');
$head_content .= <<<hContent
<script type="text/javascript">
/* <![CDATA[ */

    var lang = {
hContent;
$head_content .= "pwStrengthTooShort: '" . js_escape($langPwStrengthTooShort) . "', ";
$head_content .= "pwStrengthWeak: '" . js_escape($langPwStrengthWeak) . "', ";
$head_content .= "pwStrengthGood: '" . js_escape($langPwStrengthGood) . "', ";
$head_content .= "pwStrengthStrong: '" . js_escape($langPwStrengthStrong) . "'";
$head_content .= <<<hContent
    };

    $(document).ready(function() {
        $('#password').keyup(function() {
            $('#result').html(checkStrength($('#password').val()))
        });
    });

/* ]]> */
</script>
hContent;

$pageName = $langUserDetails;
$navigation[] = array("url" => "registration.php", "name" => $langNewUser);

$user_registration = get_config('user_registration');
$eclass_stud_reg = get_config('eclass_stud_reg'); // student registration via eclass

if (!$user_registration or $eclass_stud_reg != 2) {
    $tool_content .= "<div class='alert alert-info'>$langStudentCannotRegister</div>";
    draw($tool_content, 0);
    exit;
}

if(!empty($_GET['provider_id'])) $provider_id = @q($_GET['provider_id']); else $provider_id = '';

//check if it's valid and the provider enabled in the db
if(is_numeric(@$_GET['auth']) && @$_GET['auth'] > 7 && @$_GET['auth'] < 14) {
    $provider_name = $auth_ids[$_GET['auth']];
    $result = Database::get()->querySingle("SELECT auth_enabled FROM auth WHERE auth_name = ?s", $provider_name);
    if($result->auth_enabled != 1) {
        $provider_name = "";
        $provider_id = "";
    }
} else $provider_name = "";

//authenticate user via hybridauth if requested by URL
$user_data = '';
if(!empty($provider_name)) {
    require_once 'modules/auth/methods/hybridauth/config.php';
    require_once 'modules/auth/methods/hybridauth/Hybrid/Auth.php';
    $config = get_hybridauth_config();
    
    $hybridauth = new Hybrid_Auth( $config );
    $allProviders = $hybridauth->getProviders();
    $warning = '';
    
    //additional layer of checks to verify that the provider is valid via hybridauth middleware
    if(count($allProviders) && array_key_exists(ucfirst($provider_name), $allProviders)) { 
        try {
            // create an instance for Hybridauth with the configuration file path as parameter
            $hybridauth = new Hybrid_Auth($config);
    
            // try to authenticate the selected $provider
            $adapter = $hybridauth->authenticate(strtolower($provider_name));
    
            // grab the user profile and check if the provider_uid
            $user_data = $adapter->getUserProfile();
            if($user_data->identifier) {
                $result = Database::get()->querySingle("SELECT " . strtolower($provider_name) . "_uid FROM user WHERE " . strtolower($provider_name) . "_uid = ?s", $user_data->identifier);
                if($result) $registration_errors[] = $langProviderError9; //the provider user id already exists the the db. show an error.
                    else $provider_id = $user_data->identifier; 
            }
        } catch(Exception $e) {
            // In case we have errors 6 or 7, then we have to use Hybrid_Provider_Adapter::logout() to
            // let hybridauth forget all about the user so we can try to authenticate again.

            // Display the recived error,
            // to know more please refer to Exceptions handling section on the userguide
            switch($e->getCode()) {
                case 0 : $warning = "<p class='alert alert-info'>$langProviderError1</p>"; break;
                case 1 : $warning = "<p class='alert alert-info'>$langProviderError2</p>"; break;
                case 2 : $warning = "<p class='alert alert-info'>$langProviderError3</p>"; break;
                case 3 : $warning = "<p class='alert alert-info'>$langProviderError4</p>"; break;
                case 4 : $warning = "<p class='alert alert-info'>$langProviderError5</p>"; break;
                case 5 : $warning = "<p class='alert alert-info'>$langProviderError6</p>"; break;
                case 6 : $warning = "<p class='alert alert-info'>$langProviderError7</p>"; $adapter->logout(); break;
                case 7 : $warning = "<p class='alert alert-info'>$langProviderError8</p>"; $adapter->logout(); break;
            }
        }
    }
}

// display form
if (!isset($_POST['submit'])) {
    if (get_config('email_required')) {
        $email_message = $langCompulsory;
    } else {
        $email_message = $langOptional;
    }
    if (get_config('am_required')) {
        $am_message = $langCompulsory;
    } else {
        $am_message = $langOptional;
    }
    
    if (@count($registration_errors) != 0) {
        // errors exist (from hybridauth) - show message
        $tool_content .= "<div class='alert alert-danger'>";
        foreach ($registration_errors as $error) {
            $tool_content .= " $error";
        }
        $tool_content .= "</div>";
        $provider_name = '';
        $provider_id ='';
    }
    
    $tool_content .= action_bar(array(
                    array('title' => $langBack,
                        'url' => "{$urlAppend}modules/auth/registration.php",
                        'icon' => 'fa-reply',
                        'level' => 'primary-label')), false);
    @$tool_content .= "<div class='form-wrapper'>
            <form class='form-horizontal' role='form' action='$_SERVER[SCRIPT_NAME]' method='post' onsubmit='return validateNodePickerForm();'>
            <fieldset>
            <div class='form-group'>
                <label for='Name' class='col-sm-2 control-label'>$langName:</label>
                <div class='col-sm-10'>
                  <input class='form-control' type='text' name='givenname_form' size='30' maxlength='50' value='" . $user_data->firstName . "' placeholder='$langName'>
                </div>
            </div>
            <div class='form-group'>
                <label for='SurName' class='col-sm-2 control-label'>$langSurname:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='surname_form' size='30' maxlength='100' value='" . $user_data->lastName . "' placeholder='$langSurname'>
                </div>
            </div>
            <div class='form-group'>
                <label for='UserName' class='col-sm-2 control-label'>$langUsername:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='uname' value='" . q(str_replace(' ', '', $user_data->displayName)) . "' size='30' maxlength='30'  autocomplete='off' placeholder='$langUserNotice'>
                </div>
            </div>
            <div class='form-group'>
                <label for='UserPass' class='col-sm-2 control-label'>$langPass:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='password' name='password1' size='30' maxlength='30' autocomplete='off'  id='password' placeholder='$langUserNotice'><span id='result'></span>
                </div>
            </div>
            <div class='form-group'>
              <label for='UserPass2' class='col-sm-2 control-label'>$langConfirmation:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='password' name='password' size='30' maxlength='30' autocomplete='off'/>
                </div>
            </div>
            <div class='form-group'>
                <label for='UserEmail' class='col-sm-2 control-label'>$langEmail:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='email' size='30' maxlength='100' value='" . $user_data->email . "' placeholder='$email_message'>
                </div>
            </div>
            <div class='form-group'>
                <label for='UserAm' class='col-sm-2 control-label'>$langAm:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='am' size='20' maxlength='20' value='" . q($_GET['am']) . "' placeholder='$am_message'>
                </div>
            </div>
            <div class='form-group'>
                <label for='UserPhone' class='col-sm-2 control-label'>$langPhone:</label>
                <div class='col-sm-10'>
                    <input class='form-control' type='text' name='phone' size='20' maxlength='20' value='" . $user_data->phone . "' placeholder = '$langOptional'>
                </div>
            </div>
            <div class='form-group'>
              <label for='UserFac' class='col-sm-2 control-label'>$langFaculty:</label>
                <div class='col-sm-10'>";
            list($js, $html) = $tree->buildUserNodePicker();
            $head_content .= $js;
            $tool_content .= $html;
            $tool_content .= "</div>
            </div>
            <div class='form-group'>
              <label for='UserLang' class='col-sm-2 control-label'>$langLanguage:</label>
              <div class='col-sm-10'>";
            $tool_content .= lang_select_options('localize', "class='form-control'");
            $tool_content .= "</div>
            </div>";
            if ($display_captcha) {
                $tool_content .= "<div class='form-group'>
                      <div class='col-sm-offset-2 col-sm-10'><img id='captcha' src='{$urlAppend}include/securimage/securimage_show.php' alt='CAPTCHA Image' /></div><br>
                      <label for='Captcha' class='col-sm-2 control-label'>$langCaptcha:</label>
                      <div class='col-sm-10'><input type='text' name='captcha_code' maxlength='6'/></div>
                    </div>";
            }

        //check if provider_id from an authenticated user and a valid provider name are set so as to show the relevant form
        if(!empty($provider_name) && !empty($provider_id)) {
            $tool_content .= "<div class='form-group'>
              <label for='UserLang' class='col-sm-2 control-label'>$langProviderConnectWith:</label>
              <div class='col-sm-10'>
                <img src='$themeimg/" . q($provider_name) . ".png' alt='" . q($provider_name) . "' />&nbsp;" . q(ucfirst($provider_name)) . "<br /><small>$langProviderConnectWithTooltip</small>
              </div>
              <div class='col-sm-offset-2 col-sm-10'>
                <input type='hidden' name='provider' value='" . $provider_name . "' />
                <input type='hidden' name='provider_id' value='" . $provider_id . "' />
              </div>
              </div>";
        }
        $tool_content .= "<div class='form-group'><div class='col-sm-offset-2 col-sm-10'>
                    <input class='btn btn-primary' type='submit' name='submit' value='" . q($langRegistration) . "' />
              </div>
            </div>";
        
      $tool_content .= "  </fieldset>
      </form>
      </div>";

} else {
    if (get_config('email_required')) {
        $email_arr_value = true;
    } else {
        $email_arr_value = false;
    }
    if (get_config('am_required')) {
        $am_arr_value = true;
    } else {
        $am_arr_value = false;
    }
    $missing = register_posted_variables(array('uname' => true,
        'surname_form' => true,
        'givenname_form' => true,
        'password' => true,
        'password1' => true,
        'email' => $email_arr_value,
        'phone' => false,
        'am' => $am_arr_value));

    if (!isset($_POST['department'])) {
        $departments = array();
        $missing = false;
    } else {
        $departments = $_POST['department'];
    }

    $registration_errors = array();
    // check if there are empty fields
    if (!$missing) {
        $registration_errors[] = $langFieldsMissing;
    } else {
        $uname = canonicalize_whitespace($uname);
        // check if the username is already in use
        $username_check = Database::get()->querySingle("SELECT username FROM user WHERE username = ?s", $uname);
        if ($username_check) {
            $registration_errors[] = $langUserFree;
        }
        if ($display_captcha) {
            // captcha check
            require_once 'include/securimage/securimage.php';
            $securimage = new Securimage();
            if ($securimage->check($_POST['captcha_code']) == false) {
                $registration_errors[] = $langCaptchaWrong;
            }
        }
    }
    if (!empty($email) and !email_seems_valid($email)) {
        $registration_errors[] = $langEmailWrong;
    } else {
        $email = mb_strtolower(trim($email));
    }
    if ($password != $_POST['password1']) { // check if the two passwords match
        $registration_errors[] = $langPassTwice;
    }

    //validate HybridAuth provider and user id and check if it's already in the db (shouldn't be
    //because the user would be logged in the system rather than redirected here)
    //check if there are any available alternative providers for authentication
    if(!empty($_POST['provider_id'])) {
        require_once 'modules/auth/methods/hybridauth/config.php';
        require_once 'modules/auth/methods/hybridauth/Hybrid/Auth.php';
        $config = get_hybridauth_config();
        
        $hybridauth = new Hybrid_Auth( $config );
        $allProviders = $hybridauth->getProviders();
        $provider = '';
        $warning = '';
        
        //check if $_POST['provider'] is valid and enabled
        if(count($allProviders) && array_key_exists(ucfirst($_POST['provider']), $allProviders)) $provider = strtolower($_POST['provider']);        
        if(!empty($_POST['provider_id']) && !empty($provider)) { //if(!empty($provider), it means the provider is existent and valid - it's checked above
            try {
                // create an instance for Hybridauth with the configuration file path as parameter
                $hybridauth = new Hybrid_Auth($config);
        
                // try to authenticate the selected $provider
                $adapter = $hybridauth->authenticate($provider);
                    
                // grab the user profile and check if the provider_uid
                $user_data = $adapter->getUserProfile();
                if($user_data->identifier) {
                    $result = Database::get()->querySingle("SELECT " . $provider . "_uid FROM user WHERE " . $provider . "_uid = ?s", $user_data->identifier);
                    if($result) $registration_errors[] = $langProviderError; //the provider user id already exists the the db. show an error.
                } 
                    
            } catch(Exception $e) {
                // In case we have errors 6 or 7, then we have to use Hybrid_Provider_Adapter::logout() to
                // let hybridauth forget all about the user so we can try to authenticate again.
        
                // Display the recived error,
                // to know more please refer to Exceptions handling section on the userguide
                switch($e->getCode()) {
                    case 0 : $warning = "<p class='alert1'>$langProviderError1</p>"; break;
                    case 1 : $warning = "<p class='alert1'>$langProviderError2</p>"; break;
                    case 2 : $warning = "<p class='alert1'>$langProviderError3</p>"; break;
                    case 3 : $warning = "<p class='alert1'>$langProviderError4</p>"; break;
                    case 4 : $warning = "<p class='alert1'>$langProviderError5</p>"; break;
                    case 5 : $warning = "<p class='alert1'>$langProviderError6</p>"; break;
                    case 6 : $warning = "<p class='alert1'>$langProviderError7</p>"; $adapter->logout(); break;
                    case 7 : $warning = "<p class='alert1'>$langProviderError8</p>"; $adapter->logout(); break;
                }
            }
        } else $registration_errors[] = $langProviderError . ': ' . $warning; //error. the provider is not valid or not enabled
    }

    if (count($registration_errors) == 0) {
        if (get_config('email_verification_required') && !empty($email)) {
            $verified_mail = 0;
            $vmail = TRUE;
        } else {
            $verified_mail = 2;
            $vmail = FALSE;
        }
        
        $hasher = new PasswordHash(8, false);
        $password_encrypted = $hasher->HashPassword($password);

        //check if hybridauth provider and provider user id is used (the validity of both is checked on a previous step in this script)
        if(empty($provider) && empty($_POST['provider_id'])) {
            $q1 = Database::get()->query("INSERT INTO user (surname, givenname, username, password, email,
                                     status, am, phone, registered_at, expires_at,
                                     lang, verified_mail, whitelist, description)
                          VALUES (?s, ?s, ?s, '$password_encrypted', ?s, " . USER_STUDENT . ", ?s, ?s, " . DBHelper::timeAfter() . ",
                                  " . DBHelper::timeAfter(get_config('account_duration')) . ", ?s, $verified_mail, '', '')", 
                                $surname_form, $givenname_form, $uname, $email, $am, $phone, $language);
        } else {
            $q1 = Database::get()->query("INSERT INTO user (surname, givenname, username, password, email,
                    status, am, phone, registered_at, expires_at,
                    lang, verified_mail, whitelist, description, " . $provider . "_uid)
                    VALUES (?s, ?s, ?s, '$password_encrypted', ?s, " . USER_STUDENT . ", ?s, ?s, " . DBHelper::timeAfter() . ",
                                  " . DBHelper::timeAfter(get_config('account_duration')) . ", ?s, $verified_mail, '', '', ?s)",
                    $surname_form, $givenname_form, $uname, $email, $am, $phone, $language, $user_data->identifier);
        }
        
        $last_id = $q1->lastInsertID;
        $userObj->refresh($last_id, $departments);
        user_hook($last_id);

        if ($vmail) {
            $hmac = token_generate($uname . $email . $last_id);
        }

        $emailsubject = "$langYourReg $siteName";
        $telephone = get_config('phone');
        $administratorName = get_config('admin_name');
        $emailhelpdesk = get_config('email_helpdesk');
        $emailbody = "$langDestination $givenname_form $surname_form\n" .
                "$langYouAreReg $siteName $langSettings $uname\n" .
                "$langPass: $password\n$langAddress $siteName: " .
                "$urlServer\n" .
                ($vmail ? "\n$langMailVerificationSuccess.\n$langMailVerificationClick\n$urlServer" . "modules/auth/mail_verify.php?h=" . $hmac . "&id=" . $last_id . "\n" : "") .
                "$langProblem\n$langFormula\n" .
                "$administratorName\n" .
                "$langManager $siteName \n$langTel $telephone\n" .
                "$langEmail: $emailhelpdesk";

        // send email to user
        if (!empty($email)) {
            send_mail('', '', '', $email, $emailsubject, $emailbody, $charset);
            $user_msg = $langPersonalSettings;
        } else {
            $user_msg = $langPersonalSettingsLess;
        }

        // verification needed
        if ($vmail) {
            $user_msg .= "$langMailVerificationSuccess: <strong>$email</strong>";
        }
        // login user
        else {
            $myrow = Database::get()->querySingle("SELECT id, surname, givenname FROM user WHERE id = ?d", $last_id);
            $uid = $myrow->id;
            $surname = $myrow->surname;
            $givenname = $myrow->givenname;

            Database::get()->query("INSERT INTO loginout (loginout.id_user, loginout.ip, loginout.when, loginout.action)
                             VALUES (?d, ?s, NOW(), 'LOGIN')", $uid, $_SERVER['REMOTE_ADDR']);
            $_SESSION['uid'] = $uid;
            $_SESSION['status'] = USER_STUDENT;
            $_SESSION['givenname'] = $givenname_form;
            $_SESSION['surname'] = $surname_form;
            $_SESSION['uname'] = $uname;
            $session->setLoginTimestamp();
            $tool_content .= "<p>$langDear " . q("$givenname_form $surname_form") . ",</p>";
        }
        // user msg
        $tool_content .= "<div class='alert alert-success'><p>$user_msg</p></div>";

        // footer msg
        if (!$vmail) {
            $tool_content .= "<p>$langPersonalSettingsMore</p>";
        } else {
            $tool_content .=
                    "<p>$langMailVerificationSuccess2.
                                 <br /><br />$click <a href='$urlServer'
                                 class='mainpage'>$langHere</a> $langBackPage</p>";
        }
    } else {
        // errors exist - registration failed
        $tool_content .= "<div class='alert alert-danger'>";
        foreach ($registration_errors as $error) {
            $tool_content .= " $error";
        }
        $tool_content .= "</div><p><a href='$_SERVER[SCRIPT_NAME]?" .
                'givenname_form=' . urlencode($givenname_form) .
                '&amp;surname_form=' . urlencode($surname_form) .
                '&amp;uname=' . urlencode($uname) .
                '&amp;email=' . urlencode($email) .
                '&amp;am=' . urlencode($am) .
                '&amp;phone=' . urlencode($phone) .
                "'>$langAgain</a></p>";
    }
} // end of registration

draw($tool_content, 0, null, $head_content);
