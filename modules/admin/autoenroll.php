<?php
/* ========================================================================
 * Open eClass 3.2
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


$require_admin = true;
require_once '../../include/baseTheme.php';
require_once 'include/lib/hierarchy.class.php';

$toolName = $langAutoEnroll;
$navigation[] = array('url' => 'index.php', 'name' => $langAdmin);

if (isset($_REQUEST['add'])) {
    $type = intval($_REQUEST['add']);
    if (!in_array($type, array(USER_STUDENT, USER_TEACHER))) {
        forbidden();
    }
}

if (isset($_POST['submit'])) {
    if (isset($_POST['id'])) {
        if (!($rule = getDirectReference($_POST['id']))) {
            forbidden();
        }
        Database::get()->query('DELETE FROM autoenroll_rule_department WHERE rule = ?d', $rule);
        Database::get()->query('DELETE FROM autoenroll_department WHERE rule = ?d', $rule);
        Database::get()->query('DELETE FROM autoenroll_course WHERE rule = ?d', $rule);
    }

    if (isset($_POST['courses']) or isset($_POST['departments'])) {
        if (!isset($rule)) {
            $rule = Database::get()->query('INSERT INTO autoenroll_rule
                SET status = ?d', $type)->lastInsertID;
        }
        if (isset($_POST['department'])) {
            multiInsert('autoenroll_rule_department',
                'rule, department', $rule, $_POST['department']);
        }
        if (isset($_POST['courses']) and !empty($_POST['courses'])) {
            $courses = explode(',', $_POST['courses']);
            multiInsert('autoenroll_course',
                'rule, course_id', $rule, $courses);
        }
        if (isset($_POST['departments'])) {
            multiInsert('autoenroll_department',
                'rule, department_id', $rule, $_POST['departments']);
        }
    }
    Session::Messages($langAutoEnrollAdded, 'alert-success');
    redirect_to_home_page('modules/admin/autoenroll.php');
} elseif (isset($_GET['add']) or isset($_GET['edit'])) {
    load_js('jstree');
    load_js('select2');

    $pageName = isset($_GET['add'])? $langAutoEnrollNew: $langEditChange;
    $navigation[] = array('url' => 'autoenroll.php', 'name' => $langAutoEnroll);

    if (isset($_GET['edit'])) {
        if (!($rule = getDirectReference($_GET['edit']))) {
            forbidden();
        }

        $q = Database::get()->querySingle('SELECT * FROM autoenroll_rule WHERE id = ?d', $rule);
        $type = $q->status;

        $department = array_map(function ($item) { return $item->department; },
            Database::get()->queryArray(
                'SELECT department FROM autoenroll_rule_department WHERE rule = ?d', $rule));

        $courses = implode(',',
            array_map(function ($course) { 
                return "{id: {$course->course_id}, text: '" .
                    js_escape($course->title . ' (' . $course->public_code . ')') .
                    "'}";
                },
                Database::get()->queryArray(
                    'SELECT course_id, title, public_code FROM autoenroll_course, course
                         WHERE autoenroll_course.course_id = course.id AND
                               rule = ?d', $rule)));
        $ruleInput = "<input type='hidden' name='id' value='$_GET[edit]'>";
    } else {
        $department = array();
        $courses = $ruleInput = '';
    }

    $tree = new Hierarchy();
    list($js, $htmlTree) = $tree->buildUserNodePicker(array('defaults' => $department));
    list($js, $htmlTreeCourse) = $tree->buildCourseNodePicker();
    $head_content .= $js . "
      <script>
        $(function () {
          $('#courses').select2({
            minimumInputLength: 2,
            tags: true,
            tokenSeparators: [', '],
            ajax: {
              url: 'coursefeed.php',
              dataType: 'json',
              data: function(term, page) {
                return { q: term };
              },
              results: function(data, page) {
                return { results: data };
              }
            }
          }).select2('data', [$courses]);
        });
      </script>";

    if ($type == USER_STUDENT) {
        $statusLabel = q(($type == USER_STUDENT)? $langStudents: $langTeachers);
    }
    $tool_content .= action_bar(array(
        array('title' => $langBack,
              'url' => 'autoenroll.php',
              'icon' => 'fa-reply',
              'level' => 'primary-label'))) . "
      <div class='form-wrapper'>
        <form role='form' class='form-horizontal' method='post' action='autoenroll.php'>
          <input type='hidden' name='add' value='$type'>$ruleInput
          <fieldset>
            <div class='form-group'>
              <label class='col-sm-3 control-label'>$langStatus:</label>   
              <div class='col-sm-9'><p class='form-control-static'>$statusLabel</p></div>
            </div>
            <div class='form-group'>
              <label for='title' class='col-sm-3 control-label'>$langFaculty:</label>   
              <div class='col-sm-9 form-control-static'>$htmlTree</div>
            </div>
            <div class='form-group'>
              <label for='title' class='col-sm-3 control-label'>$langAutoEnrollCourse:</label>   
              <div class='col-sm-9'>
                <input class='form-control' type='hidden' id='courses' name='courses' value=''>
              </div>
            </div>
            <div class='form-group'>
              <label for='title' class='col-sm-3 control-label'>$langAutoEnrollDepartment:</label>   
              <div class='col-sm-9 form-control-static'>$htmlTreeCourse</div>
            </div>
            <div class='form-group'>
              <div class='col-sm-10 col-sm-offset-2'>
                <input class='btn btn-primary' type='submit' name='submit' value='" . q($langSubmit) . "'>
                <a href='autoenroll.php' class='btn btn-default'>$langCancel</a>    
              </div>
            </div>
          </fieldset>
        </form>
      </div>";

} else {

    $tool_content .= action_bar(array(
        array('title' => "$langAutoEnrollNew ($langStudents)",
              'url' => 'autoenroll.php?add=' . USER_STUDENT,
              'icon' => 'fa-plus-circle',
              'level' => 'primary-label',
              'button-class' => 'btn-success'),
        array('title' => "$langAutoEnrollNew ($langTeachers)",
              'url' => 'autoenroll.php?add=' . USER_TEACHER,
              'icon' => 'fa-plus-circle',
              'level' => 'primary-label',
              'button-class' => 'btn-success'),
        array('title' => $langBack,
              'url' => 'index.php',
              'icon' => 'fa-reply',
              'level' => 'primary-label')));

    $rules = array();
    $i = 0;
    Database::get()->queryFunc('SELECT * FROM autoenroll_rule',
        function ($item) {
            global $rules, $i, $urlAppend, $langStudents, $langTeachers,
                $langAutoEnrollRule, $langApplyTo, $langApplyDepartments,
                $langApplyAnyDepartment, $langAutoEnrollCourse,
                $langAutoEnrollDepartment, $langDelete, $langEditChange;
            $i++;
            $rule = $item->id;
            $statusLabel = q(($item->status == USER_STUDENT)? $langStudents: $langTeachers);
            $rules[$rule] = "
          <div class='panel panel-info'>
            <div class='panel-heading'>
              $langAutoEnrollRule $i
              <div class='pull-right'>" .
              action_button(array(
                  array(
                      'title' => $langEditChange,
                      'icon' => 'fa-edit',
                      'url' => "autoenroll.php?edit=" . getIndirectReference($rule)),
                  array(
                      'title' => $langDelete,
                      'url' => '#',
                      'icon' => 'fa-times',
                      'btn_class' => 'delete_btn btn-default'),
              )) . "
              </div>
            </div>
            <div class='panel-body'>
              <div>$langApplyTo: <b>$statusLabel</b> ";

            $deps = Database::get()->queryArray('SELECT hierarchy.id, name
                FROM autoenroll_rule_department, hierarchy
                WHERE autoenroll_rule_department.department = hierarchy.id AND
                      rule = ?d', $rule);
            if ($deps) {
                $rules[$rule] .= $langApplyDepartments . ':<ul>';
                foreach ($deps as $dep) {
                    $rules[$rule] .= '<li>' . q(getSerializedMessage($dep->name)) . '</li>';
                }
                $rules[$rule] .= '</ul>';
            } else {
                $rules[$rule] .= $langApplyAnyDepartment;
            }

            $courses = Database::get()->queryArray('SELECT code, title, public_code
                FROM autoenroll_course, course
                WHERE autoenroll_course.course_id = course.id AND
                      rule = ?d', $rule);
            if ($courses) {
                $rules[$rule] .= $langAutoEnrollCourse . ':<ul>';
                foreach ($courses as $course) {
                    $rules[$rule] .= "<li><a href='{$urlAppend}courses/{$course->code}/'>" .
                        q($course->title) . '</a> (' .
                        q($course->public_code) . ')</li>';
                }
                $rules[$rule] .= '</ul>';
            }

            $deps = Database::get()->queryArray('SELECT hierarchy.id, name
                FROM autoenroll_department, hierarchy
                WHERE autoenroll_department.department_id = hierarchy.id AND
                      rule = ?d', $rule);
            if ($deps) {
                $rules[$rule] .= $langAutoEnrollDepartment . ':<ul>';
                foreach ($deps as $dep) {
                    $rules[$rule] .= "<li><a href='{$urlAppend}modules/auth/courses.php?fc={$dep->id}'>" .
                        q(getSerializedMessage($dep->name)) . '</a></li>';
                }
                $rules[$rule] .= '</ul>';
            }
            $rules[$rule] .= "</div></div></div>";
        });
    if ($i) {
        $tool_content .= implode($rules);
    } else {
        $tool_content .= "<div class='alert alert-warning text-center'>$langNoRules</div>";
    }
}

draw($tool_content, 3, null, $head_content);

function multiInsert($table, $signature, $key, $values) {
    $terms = array();
    $count = 0;
    foreach ($values as $value) {
        $count++;
        $terms[] = $key;
        $terms[] = $value;
    }
    Database::get()->query("INSERT INTO `$table` ($signature) VALUES " .
            implode(', ', array_fill(0, $count, '(?d, ?d)')),
        $terms);
}