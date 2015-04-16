<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2015  Greek Universities Network - GUnet
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


// Disable modules admin page

$require_admin = true;
require_once '../../include/baseTheme.php';

$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);
$pageName = $langDisableModules;

if (isset($_POST['submit'])) {
    Database::get()->query('DELETE FROM module_disable');
    if (isset($_POST['moduleDisable'])) {
        $optArray = implode(', ', array_fill(0, count($_POST['moduleDisable']), '(?d)'));
        Database::get()->query('INSERT INTO module_disable (module_id) VALUES ' . $optArray,
            array_keys($_POST['moduleDisable']));
    }
    Session::Messages($langWikiEditionSucceed, 'alert-success');
    redirect_to_home_page('modules/admin/modules.php');
} else {
    $disabled = array();
    foreach (Database::get()->queryArray('SELECT module_id FROM module_disable') as $item) {
        $disabled[] = $item->module_id;
    }
    $tool_content .= action_bar(array(
        array('title' => $langBack,
              'url' => $urlAppend . 'modules/admin/index.php',
              'icon' => 'fa-reply',
              'level' => 'primary-label')), false) .
        "<div class='alert alert-warning'>$langDisableModulesHelp</div>
         <div class='form-wrapper'>
           <form class='form-horizontal' role='form' action='modules.php' method='post'>";

    foreach ($modules as $mid => $minfo) {
        $checked = in_array($mid, $disabled)? ' checked': '';
        $icon = $minfo['image'];
        if (isset($theme_settings['icon_map'][$icon])) {
            $icon = $theme_settings['icon_map'][$icon];
        }
        $icon = icon($icon);
        $tool_content .= "
           <div class='form-group'>
             <div class='col-xs-1'>
               <input class='form-control' type='checkbox' name='moduleDisable[$mid]' id='id_$mid' value='1'$checked>
             </div>
             <label class='col-xs-11 control-label' for='id_$mid'><div class='text-left'>" .
                $icon . '&nbsp;' . q($minfo['title']) . "</div></label>
           </div>";
    }
    $tool_content .= "
           <div class='form-group'>
             <div class='col-xs-11 col-xs-offset-1'>
               <input class='btn btn-primary' type='submit' name='submit' value='" . q($langSubmitChanges) . "'>
             </div>
           </div>
         </form>
       </div>";
}

draw($tool_content, 3, null, $head_content);
