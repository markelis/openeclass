<?php
/* ========================================================================
 * Open eClass 3.0 
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2014  Greek Universities Network - GUnet
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
 * @file new.php
 * @brief Create / edit HTML document
 */

if (!defined('COMMON_DOCUMENTS')) {
    $require_current_course = true;
    $require_login = true;
}

require_once "../../include/baseTheme.php";
require_once "modules/document/doc_init.php";

load_js('tools.js');

$uploadPath = $editPath = false;
if (isset($_GET['uploadPath'])) {
    $uploadPath = q($_GET['uploadPath']);
} elseif (isset($_GET['editPath'])) {
    $editPath = q($_GET['editPath']);
    $uploadPath = dirname($editPath);
}

if (defined('EBOOK_DOCUMENTS')) {
    $navigation[] = array('url' => 'edit.php?course=' . $course_code . '&amp;id=' . $ebook_id, 'name' => $langEBookEdit);
} 

$backUrl = documentBackLink($uploadPath);

if ($can_upload) {
    $navigation[] = array('url' => $backUrl, 'name' => $pageName);
    if ($editPath) {
        $pageName = $langEditDoc;
        $info = Database::get()->querySingle("SELECT * FROM document WHERE $group_sql AND path = ?s", $editPath);
        $htmlFileName = '<p class="form-control-static">'.q($info->filename).'</p>';
        $htmlTitle = ' value="' . q($info->title) . '"';
        $fileContent = file_get_contents($basedir . $info->path);
        $htmlPath = "<input type='hidden' name='editPath' value='$editPath'>";
    } else {
        $pageName = $langCreateDoc;
        $htmlFileName = "<input type='text' class='form-control' id='file_name' name='file_name'>";
        $htmlTitle = '';
        $fileContent = '';
        $htmlPath = "<input type='hidden' name='uploadPath' value='$uploadPath'>";
    }
    if(isset($_GET['ebook_id'])){
        $sections = Database::get()->queryArray("SELECT id, public_id, title FROM ebook_section
                           WHERE ebook_id = ?d
                           ORDER BY CONVERT(public_id, UNSIGNED), public_id", $_GET['ebook_id']);
        $section_id = '';
        if ($editPath) {
            $section = Database::get()->querySingle("SELECT section_id 
                FROM ebook_subsection WHERE file_id = ?d", $info->id);
            if($section){
                $section_id = $section->section_id;
            }
        }else {
            if(count($sections)){
                $section_id =  $sections[0]->id;
            }   
        }
        $sections_array = array('' => '---');
        foreach ($sections as $sid => $section){
            $sid = $section->id;
            $qsid = q($section->public_id);
            $sections_array[$sid] = $qsid . '. ' . ellipsize($section->title, 25);
        }
        
        $ebook_section_select = "
                <div class='form-group'>
                    <label for='section' class='col-sm-2 control-label'>$langSection:</label>
                    <div class='col-sm-10'>
                    " . selection($sections_array, 'section_id', $section_id, 'class="form-control"') . "
                    </div>                        
                </div>
                ";
    } else {
        $ebook_section_select = "";
    }
    $action = defined('COMMON_DOCUMENTS')? 'commondocs': 'document';
    $tool_content .= action_bar(array(
                                array('title' => $langBack,
                                      'url' => '#',
                                      'icon' => 'fa-reply',
                                      'level' => 'primary-label',
                                      'class' => 'back_btn')
                            ),false);
    $tool_content .= "<div class='form-wrapper'>";
    $tool_content .= "<form class='form-horizontal' role='form' action='$upload_target_url' method='post'>
    $htmlPath
    $group_hidden_input
    $ebook_section_select    

              <div class='form-group'>
        <label for='file_name' class='col-sm-2 control-label'>$langFileName:</label>
        <div class='col-sm-10'>
          $htmlFileName
        </div>
      </div>
	
        <div class='form-group'>
        <label for='file_title' class='col-sm-2 control-label'>$langTitle:</label>
        <div class='col-sm-10'>
          <input type='text' class='form-control' id='file_title' name='file_title'$htmlTitle>
        </div>
      </div>
      <div class='form-group'>
        <label for='file_title' class='col-sm-2 control-label'>$langContent:</label>
        <div class='col-sm-10'>"
          . rich_text_editor('file_content', 20, 40, $fileContent) .
        "</div>
      </div>
	  
	<div class='form-group'>
        <div class='col-xs-offset-2 col-xs-10'>
          <button type='submit' value='" . $langSubmit . "' class='btn btn-primary'>
            $langSubmit
          </button>
        </div>
      </div>
</form></div>";
} else {
	$tool_content .= "<div class='alert alert-danger'>$langNotAllowed</div>";
}

draw($tool_content, 
    defined('COMMON_DOCUMENTS')? 3: 2,
    null, $head_content);
