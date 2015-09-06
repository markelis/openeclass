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
 * @file group_creation.php
 * @brief create users group
 */

$require_current_course = TRUE;
$require_help = TRUE;
$helpTopic = 'Group';
$require_editor = true;

require_once '../../include/baseTheme.php';
$toolName = $langGroups;
$pageName = $langNewGroupCreate;
$navigation[] = array("url" => "index.php?course=$course_code", "name" => $langGroups);

$tool_content .= action_bar(array(
    array(
        'title' => $langBack,
        'level' => 'primary-label',
        'icon' => 'fa-reply',
        'url' => "index.php?course=$course_code"
    )
));
$group_max_value = Session::has('group_max') ? Session::get('group_max') : 8;
$group_quantity_value = Session::has('group_quantity') ? Session::get('group_quantity') : 1;
$tool_content .= " 
    <div class='form-wrapper'>
        <form class='form-horizontal' role='form' method='post' action='index.php?course=$course_code'>
        <fieldset>
        <div class='form-group".(Session::getError('group_quantity') ? " has-error":"")."'>
            <label for='group_quantity' class='col-sm-2 control-label'>$langNewGroups:</label>
            <div class='col-sm-10'>
                <input name='group_quantity' type='text' class='form-control' id='group_quantity' value='$group_quantity_value' placeholder='$langNewGroups'>
                <span class='help-block'>".Session::getError('group_quantity')."</span>    
            </div>
        </div>
        <div class='form-group".(Session::getError('group_max') ? " has-error":"")."'>
            <label for='group_max' class='col-sm-2 control-label'>$langNewGroupMembers:</label>
            <div class='col-sm-10'>
                <input name='group_max' type='text' class='form-control' id='group_max' value='$group_max_value' placeholder='$langNewGroupMembers'>
                <span class='help-block'>".(Session::getError('group_max') ?: "$langMax $langPlaces")."</span>
            </div>
        </div>
		<div class='form-group'>
            <label for='selectcategory' class='col-sm-2 control-label'>$langCategory:</label>
            <div class='col-sm-3'>
                <select class='form-control' name='selectcategory' id='selectcategory'>
                <option value='0'>--</option>";
        if ($social_bookmarks_enabled) {
            $tool_content .= "<option value='" . getIndirectReference(-2) . "'";
            if (isset($category) and -2 == $category) {
                $tool_content .= " selected='selected'";
            }
            $tool_content .= ">$langSocialCategory</option>";
        }
        $resultcategories = Database::get()->queryArray("SELECT * FROM group_category WHERE course_id = ?d ORDER BY `order`", $course_id);
        foreach ($resultcategories as $myrow) {
            $tool_content .= "<option value='" . getIndirectReference($myrow->id) . "'";
            if (isset($category) and $myrow->id == $category) {
                $tool_content .= " selected='selected'";
            }
            $tool_content .= '>' . q($myrow->name) . "</option>";
        }
        $tool_content .= "
            </select>
            </div>
        </div>
        <div class='form-group'>
        <div class='col-sm-10 col-sm-offset-2'>
            <input class='btn btn-primary' type='submit' value='$langCreate' name='creation'>
            <a class='btn btn-default' href='index.php?course=$course_code'>$langCancel</a>
        </div>
        </div>
        </fieldset>
        </form>
    </div>";
}
else{
	if ($is_editor) {
		$tool_content_tutor = "<select name='tutor[]' multiple id='select-tutor' class='form-control'>\n";
		$q = Database::get()->queryArray("SELECT user.id AS user_id, surname, givenname,
                                   user.id IN(SELECT user_id FROM group_members
                                                              WHERE is_tutor = 1) AS is_tutor
                              FROM course_user, user 
                              WHERE course_user.user_id = user.id AND
                                    course_user.tutor = 1 AND
                                    course_user.course_id = ?d
                              ORDER BY surname, givenname, user_id", $course_id);
		foreach ($q as $row) {
			$selected = $row->is_tutor ? ' selected="selected"' : '';
			$tool_content_tutor .= "<option value='$row->user_id'$selected>" . q($row->surname) .
					' ' . q($row->givenname) . "</option>\n";
    }
		$tool_content_tutor .= '</select>';
	} else {
		$tool_content_tutor = display_user($tutors);
	}	
$tool_content .= "<div class='form-wrapper'>
        <form class='form-horizontal' role='form' method='post' action='index.php?course=$course_code&amp;group=1'>
        <fieldset>    
        <div class='form-group".(Session::getError('name') ? " has-error" : "")."'>
            <label class='col-sm-2 control-label'>$langGroupName:</label>
            <div class='col-sm-10'>
                <input class='form-control' type=text name='name' size='40'>
                <span class='help-block'>".Session::getError('name')."</span>
            </div>
        </div>
        <div class='form-group'>
          <label class='col-sm-2 control-label'>$langDescription $langOptional:</label>
          <div class='col-sm-10'><textarea class='form-control' name='description' rows='2' cols='60'></textarea></div>
        </div>
        <div class='form-group".(Session::getError('maxStudent') ? " has-error" : "")."'>
            <label class='col-sm-2 control-label'>$langMax $langGroupPlacesThis:</label>
            <div class='col-sm-10'>
                <input class='form-control' type=text name='maxStudent' size=2>
                <span class='help-block'>".Session::getError('maxStudent')."</span>
            </div>
              
        </div>
		<div class='form-group'>
          <label class='col-sm-2 control-label'>$langGroupTutor:</label>
          <div class='col-sm-10'>
              $tool_content_tutor
          </div>
        </div>
        <div class='form-group'>
            <label class='col-sm-2 control-label'>$langGroupMembers:</label>
        <div class='col-sm-10'>
            <div class='table-responsive'>
                <table class='table-default'>
                    <thead>
                        <tr class='title1'>
                          <th>$langNoGroupStudents</th>
                          <th width='100' class='text-center'>$langMove</th>
                          <th class='right'>$langGroupMembers</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                          <td>
                            <select class='form-control' id='users_box' name='nogroup[]' size='15' multiple>
                              
                            </select>
                          </td>
                          <td class='text-center'>
                              <div class='form-group'>
                                  <input class='btn btn-default' type='button' onClick=\"move('users_box','members_box')\" value='   &gt;&gt;   ' />
                              </div>
                              <div class='form-group'>
                                  <input class='btn btn-default' type='button' onClick=\"move('members_box','users_box')\" value='   &lt;&lt;   ' />
                              </div>    
                          </td>
                          <td class='text-right'>
                            <select class='form-control' id='members_box' name='ingroup[]' size='15' multiple>

                            </select>
                          </td>
                        </tr>
                    </tbody>
                </table>
            </div>
      </div>
    </div>
}
else{
	if ($is_editor) {
		$tool_content_tutor = "<select name='tutor[]' multiple id='select-tutor' class='form-control'>\n";
		$q = Database::get()->queryArray("SELECT user.id AS user_id, surname, givenname,
                                   user.id IN(SELECT user_id FROM group_members
                                                              WHERE is_tutor = 1) AS is_tutor
                              FROM course_user, user 
                              WHERE course_user.user_id = user.id AND
                                    course_user.tutor = 1 AND
                                    course_user.course_id = ?d
                              ORDER BY surname, givenname, user_id", $course_id);
		foreach ($q as $row) {
			$selected = $row->is_tutor ? ' selected="selected"' : '';
			$tool_content_tutor .= "<option value='$row->user_id'$selected>" . q($row->surname) .
					' ' . q($row->givenname) . "</option>\n";
    }
		$tool_content_tutor .= '</select>';
	} else {
		$tool_content_tutor = display_user($tutors);
	}	
$tool_content .= "<div class='form-wrapper'>
        <form class='form-horizontal' role='form' method='post' action='index.php?course=$course_code&amp;group=1'>
        <fieldset>    
        <div class='form-group".(Session::getError('name') ? " has-error" : "")."'>
            <label class='col-sm-2 control-label'>$langGroupName:</label>
            <div class='col-sm-10'>
                <input class='form-control' type=text name='name' size='40'>
                <span class='help-block'>".Session::getError('name')."</span>
            </div>
        </div>
        <div class='form-group'>
          <label class='col-sm-2 control-label'>$langDescription $langOptional:</label>
          <div class='col-sm-10'><textarea class='form-control' name='description' rows='2' cols='60'></textarea></div>
        </div>
        <div class='form-group".(Session::getError('maxStudent') ? " has-error" : "")."'>
            <label class='col-sm-2 control-label'>$langMax $langGroupPlacesThis:</label>
            <div class='col-sm-10'>
                <input class='form-control' type=text name='maxStudent' size=2>
                <span class='help-block'>".Session::getError('maxStudent')."</span>
            </div>
              
        </div>
		<div class='form-group'>
          <label class='col-sm-2 control-label'>$langGroupTutor:</label>
          <div class='col-sm-10'>
              $tool_content_tutor
          </div>
        </div>
        <div class='form-group'>
            <label class='col-sm-2 control-label'>$langGroupMembers:</label>
        <div class='col-sm-10'>
            <div class='table-responsive'>
                <table class='table-default'>
                    <thead>
                        <tr class='title1'>
                          <th>$langNoGroupStudents</th>
                          <th width='100' class='text-center'>$langMove</th>
                          <th class='right'>$langGroupMembers</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                          <td>
                            <select class='form-control' id='users_box' name='nogroup[]' size='15' multiple>
                              
                            </select>
                          </td>
                          <td class='text-center'>
                              <div class='form-group'>
                                  <input class='btn btn-default' type='button' onClick=\"move('users_box','members_box')\" value='   &gt;&gt;   ' />
                              </div>
                              <div class='form-group'>
                                  <input class='btn btn-default' type='button' onClick=\"move('members_box','users_box')\" value='   &lt;&lt;   ' />
                              </div>    
                          </td>
                          <td class='text-right'>
                            <select class='form-control' id='members_box' name='ingroup[]' size='15' multiple>

                            </select>
                          </td>
                        </tr>
                    </tbody>
                </table>
            </div>
      </div>
    </div>
	<div class='form-group'>
            <label for='selectcategory' class='col-sm-2 control-label'>$langCategory:</label>
            <div class='col-sm-3'>
                <select class='form-control' name='selectcategory' id='selectcategory'>
                <option value='0'>--</option>";
        if ($social_bookmarks_enabled) {
            $tool_content .= "<option value='" . getIndirectReference(-2) . "'";
            if (isset($category) and -2 == $category) {
                $tool_content .= " selected='selected'";
            }
            $tool_content .= ">$langSocialCategory</option>";
        }
        $resultcategories = Database::get()->queryArray("SELECT * FROM group_category WHERE course_id = ?d ORDER BY `order`", $course_id);
        foreach ($resultcategories as $myrow) {
            $tool_content .= "<option value='" . getIndirectReference($myrow->id) . "'";
            if (isset($category) and $myrow->id == $category) {
                $tool_content .= " selected='selected'";
            }
            $tool_content .= '>' . q($myrow->name) . "</option>";
        }
        $tool_content .= "
            </select>
            </div>
        </div>
		<div class='form-group'>

             <label class='col-sm-2 control-label'>$langGroupStudentRegistrationType:</label>
                <div class='col-xs-9'>             
                    <div class='checkbox'>
					   <label>
					    <input type='checkbox' name='self_reg'>
                        
					  </label>
					</div>
                    <div class='checkbox'>
					  <label>
                        <input type='checkbox' name='multi_reg'>
                        
                      </label>
					</div>                    
                </div>
            </div>        
		    <div class='form-group'>
                 <label class='col-sm-2 control-label'>$langPrivate_1:</label>
                <div class='col-sm-9'>            
                    <div class='radio'>
                      <label>
                        <input type='radio' name='private_forum' value='1' checked=''>
                        $langPrivate_2
                      </label>
                    </div>
                    <div class='radio'>
                      <label>
                        <input type='radio' name='private_forum' value='0'>
                        $langPrivate_3
                      </label>
                    </div>
                </div>
            </div>
            <div class='form-group'>
             <label class='col-sm-2 control-label'>$langGroupForum:</label>
                <div class='col-xs-9'>             
                    <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='forum'>
                      </label>
                    </div>                    
                </div>
            </div>   
            <div class='form-group'>
             <label class='col-sm-2 control-label'>$langDoc:</label>
                <div class='col-xs-9'>             
                    <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='documents'>
                      </label>
                    </div>                    
                </div>
            </div>  
            <div class='form-group'>
             <label class='col-sm-2 control-label'>$langWiki:</label>
                <div class='col-xs-9'>             
                    <div class='checkbox'>
                      <label>
                        <input type='checkbox' name='wiki'>
                      </label>
                    </div>                    
                </div>
            </div>
        <div class='form-group'>
        <div class='col-sm-10 col-sm-offset-2'>
            <input class='btn btn-primary' type='submit' value='$langCreate' name='creation'>
            <a class='btn btn-default' href='index.php?course=$course_code'>$langCancel</a>
        </div>
        </div>
    </fieldset>
    </form>
</div>";
}
draw($tool_content, 2);
