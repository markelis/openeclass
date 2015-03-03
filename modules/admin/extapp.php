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

// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = true;
require_once '../../include/baseTheme.php';
require_once 'extconfig/externals.php';

$toolName = $langExtAppConfig;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
load_js('tools.js');
load_js('validation.js');
$available_themes = active_subdirs("$webDir/template", 'theme.html');

$shouldEdit = isset($_GET['edit']);
$shouldUpdate = isset($_GET['update']);
$appName = $shouldEdit ? $_GET['edit'] : ($shouldUpdate ? $_GET['update'] : null);

$tool_content .= action_bar(array(
        array('title' => $langBack,
            'url' => "index.php",
            'icon' => 'fa-reply',
            'level' => 'primary-label')));

if ($appName) {
    $navigation[] = array('url' => 'extapp.php', 'name' => $langExtAppConfig);
    $app = ExtAppManager::getApp($appName);
    $pageName = $langModify . ' ' . $app->getDisplayName();
    $tool_content .= action_bar(array(
        array('title' => $langBack,
            'url' => 'extapp.php',
            'icon' => 'fa-reply',
            'level' => 'primary-label')));

    if ($shouldUpdate) {
        $result = $app->storeParams();
        if ($result) {
            Session::Messages($result, 'alert-danger');
        } else {
            Session::Messages($langFileUpdatedSuccess, 'alert-success');
        }
        redirect_to_home_page('modules/admin/extapp.php?edit=' . $appName);
    }
    if ($shouldEdit) {
        $tool_content .= "\n<div class='form-wrapper'>\n";
        $tool_content .= "  <form class='form-horizontal' role='form' action='extapp.php?update=" . $appName . "' method='post'>\n";
        $tool_content .= "    <fieldset>\n";

        foreach ($app->getParams() as $param) {
            $tool_content .= "      <div class='form-group'>\n";
            $tool_content .= "        <label for='" . $param->name() . "' class='col-sm-2 control-label'>" . $param->display() . "</label>\n";
            $tool_content .= "        <div class='col-sm-10'><input class='FormData_InputText form-control' type='text' name='" . $param->name() . "' value='" . $param->value() . "'></div>";
            $tool_content .= "      </div>\n";
        }

        $tool_content .= "      <div class='col-sm-offset-2 col-sm-10'><input class='btn btn-primary' type='submit' name='submit' value='$langModify'></div>\n";
        $tool_content .= "    </fieldset>\n";
        $tool_content .= "  </form>\n</div>\n";
        $tool_content.=$app->getLongDescription();
    }
} else {
    $tool_content .="<table class=\"table-default dataTable no-footer\">\n";
    $tool_content.="<thead class='list-header'><td>$langExtAppName</td><td>$langExtAppDescription</td></thead>\n";
    $tool_content.="\n";
    /*foreach (ExtAppManager::getApps() as $app) {
        $tool_content .="<tr>\n";
        // WARNING!!!! LEAVE THE SIZE OF THE IMAGE TO BE DOUBLE THE SIZE OF THE ACTUAL PNG FILE, TO SUPPORT HDPI DISPLAYS!!!!
        $tool_content .= "<td style=\"width:90px;\"><a href=\"extapp.php?edit=" . $app->getName() . "\"'><img height=\"50\" width=\"89\" src=\"" . $app->getAppIcon() . "\"/></a></td>\n";
        $tool_content .= "<td style=\"vertical-align:middle; text-align:center; width:1px;\"><a href=\"extapp.php?edit=" . $app->getName() . "\"'>" . $app->getDisplayName() . "</a></td>\n";
        $tool_content .= "<td>" . $app->getShortDescription() . "</td>\n";
        $tool_content .="</tr>\n";
    }*/
    $tool_content .="<tr>\n";
    $tool_content .= "<td style=\"width:90px; padding: 20px;\"><a href=\"bbbmoduleconf.php\"><img class=\"img-responsive\" src=\"../../template/icons/bigbluebutton.png\"/>BigBlueButton</a></td>\n";
    $tool_content .= "<td style=\"padding: 20px;\"><p>Το υποσύστημα <b>Τηλεσυνεργασίας</b> της πλατφόρμας Open eClass υποστηρίζεται λειτουργικά από το ανοικτό λογισμικό <a href=\"http://www.bigbluebutton.org/\" target=\"_blank\">BigBlueButton</a> το οποίο αποτελεί  ένα πλήρες σύστημα άμεσης web-based επικοινωνίας και συνεργασίας εκπαιδευτών και εκπαιδευόμενων.</p><p>Το BigBlueButton ανήκει στις εφαρμογές τηλεδιάσκεψης / σύγχρονης τηλεκπαίδευσης που δεν απαιτούν την εγκατάσταση πρόσθετου υλικού (Software ή Hardware). Η εφαρμογή εκτελείται απευθείας από τον πλοηγό διαδικτύου (Internet Explorer, Firefox, Chrome, Safari, κ.α) με χρήση του Adobe Flash Player. Για τη σύνδεση του Open eClass με μία εγκατεστημένη πλατφόρμα BigBlueButton επιλέξτε <a href=\"bbbmoduleconf.php\">Ρυθμίσεις</a>.</p></td>\n";
    $tool_content .="</tr>\n";

    $tool_content.="</table>\n";
}

draw($tool_content, 3, null, $head_content);
