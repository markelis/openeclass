<?php

/* ========================================================================
 * Open eClass 
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
 * ======================================================================== 
 */

session_start();
require_once '../clouddrive.php';
$drive = CloudDriveManager::getSessionDrive();

$url = trim(addslashes(array_key_exists('url', $_POST) ? $_POST['url'] : $drive->getDefaultURL()));
$username = trim(addslashes(array_key_exists('username', $_POST) ? $_POST['username'] : ""));
$password = array_key_exists('password', $_POST) ? $_POST['password'] : "";

if ($drive->checkCredentials($url, $username, $password)) {
    header('Location: ' . '../popup.php?' . $drive->getDriveDefaultParameter() . "&" . $drive->getCallbackName() . '=' . $drive->encodeCredentials($url, $username, $password));
    die();
}

echo '<form action="credential_auth.php?' . $drive->getDriveDefaultParameter() . '" method="POST">';
echo '<div>URL <input type="url" id="url" name="url" value="' . $url . '"></div>';
echo '<div>Username <input type="text" id="username" name="username" value="' . $username . '"></div>';
echo '<div>Password <input type="password" id="password" name="password"></div>';
echo '<div><input type="submit" value="Submit">';
echo '</form>';
