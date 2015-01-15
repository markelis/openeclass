<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2013  Greek Universities Network - GUnet
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


// Check if user is administrator and if yes continue
// Othewise exit with appropriate message
$require_admin = true;
require_once '../../include/baseTheme.php';
require_once 'modules/auth/auth.inc.php';
$nameTools = $langAutoJudge;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);

$available_themes = active_subdirs("$webDir/template", 'theme.html');

// Scan the connectors directory and locate the appropriate classes
$connectorFiles = array_diff(scandir('modules/work/connectors'), array('..', '.'));
$connectorClasses = array();
foreach($connectorFiles as $curFile) {
    require_once('modules/work/connectors/'.$curFile);
    $tokens = token_get_all(file_get_contents('modules/work/connectors/'.$curFile));
    $class_token = false;
    foreach ($tokens as $token) {
      if (is_array($token)) {
        if ($token[0] == T_CLASS) {
           $class_token = true;
        } else if ($class_token && $token[0] == T_STRING) {
           if(strpos($token[1], 'AutoJudgeConnector') === false) {
            $connectorClasses[] = $token[1];
           }
           $class_token = false;
        }
      }
    }
}

// Save new auto_judge.php
if (isset($_POST['submit'])) {
    set_config('autojudge_connector', $_POST['formconnector']);
    foreach($connectorClasses as $curConnectorClass) {
        $connector = new $curConnectorClass();
        foreach($connector->getConfigFields() as $curField => $curLabel) {
            set_config($curField, $_POST['form'.$curField]);
        }
    }

    // Display result message
    $tool_content .= "<div class='alert alert-success'>$langAutoJudgeUpdated</div>";
} // end of if($submit)
// Display auto_judge.php edit form
else {
    $connectorOptions = array_map(function($connectorClass) {
        $connector = new $connectorClass();
        $selected = q(get_config('autojudge_connector')) == $connectorClass ? " selected='selected'" : '';
        return "<option value='$connectorClass'$selected>".$connector->getName()."</option>";
    }, $connectorClasses);
    $tool_content .= "<form action='$_SERVER[SCRIPT_NAME]' method='post'>
                <fieldset><legend>$langBasicCfgSetting</legend>
	 <table class='tbl' width='100%'>
         <tr>
            <th width='200' class='left'><b>$langAutoJudgeConnector</b></th>
            <td><select name='formconnector'>".implode('', $connectorOptions)."</select></td>
         </tr>";
    foreach($connectorClasses as $curConnectorClass) {
        $connector = new $curConnectorClass();
        $tool_content .= "
        <tr class='connector-config connector-$curConnectorClass' style='display: none;'>
            <th width='200' class='left'><b>$langAutoJudgeSupportedLanguages</b></th>
            <td>".implode(', ', array_keys($connector->getSupportedLanguages()))."</td>
        </tr>
        <tr class='connector-config connector-$curConnectorClass' style='display: none;'>
            <th width='200' class='left'><b>$langAutoJudgeSupportsInput</b></th>
            <td>".($connector->supportsInput() ? $langCMeta['true'] : $langCMeta['false'])."</td>
        </tr>";
        foreach($connector->getConfigFields() as $curField => $curLabel) {
            $tool_content .= "
              <tr class='connector-config connector-$curConnectorClass' style='display: none;'>
                <th width='200' class='left'><b>$curLabel</b></th>
                <td><input class='FormData_InputText' type='text' name='form$curField' size='40' value='" . q(get_config($curField)) . "'></td>
              </tr>";
        }
    }
    $tool_content .= "</table></fieldset>";
    $tool_content .= "<input class='btn btn-primary' type='submit' name='submit' value='$langModify'> </form>";

    $head_content .= "
        <script type='text/javascript'>
        function update_connector_config_visibility() {
            $('tr.connector-config').hide();
            $('tr.connector-config input').removeAttr('required');
            $('tr.connector-'+$('select[name=\"formconnector\"]').val()).show();
            $('tr.connector-'+$('select[name=\"formconnector\"]').val()+' input').attr('required', 'required');
        }
        $(document).ready(function() {
            $('select[name=\"formconnector\"]').change(function() {
                update_connector_config_visibility();
            });
            update_connector_config_visibility();
        });
        </script>";
}

// Display link to index.php
$tool_content .= action_bar(array(
    array('title' => $langBack,
        'url' => "index.php",
        'icon' => 'fa-reply',
        'level' => 'primary-label')));
draw($tool_content, 3, null, $head_content);

