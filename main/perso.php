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
 * @file perso.php
 * @brief This component is the central controller of eclass personalised.
 * It controls personalisation and initialises several variables used by it.
 * @abstract  It is based on the diploma thesis of Evelthon Prodromou
 * @author Evelthon Prodromou <eprodromou@upnet.gr>
 */


if (!isset($_SESSION['uid'])) {
    die("Unauthorized Access!");
    exit;
}

if ($_SESSION['status'] == USER_TEACHER) {
    $extra = "AND course.visible != " . COURSE_INACTIVE;
} else {
    $extra = '';
}

$result2 = Database::get()->queryArray("SELECT course.id cid, course.code code, course.public_code,
                        course.title title, course.prof_names profs, course_user.status status
                FROM course JOIN course_user ON course.id = course_user.course_id
                WHERE course_user.user_id = ?d $extra ORDER BY status, course.title, course.prof_names", $uid);

$courses = array();
if (count($result2) > 0) {    
        foreach ($result2 as $mycours) {
        $courses[$mycours->code] = $mycours->status;
    }
}
$_SESSION['courses'] = $courses;
$subsystem = MAIN;

require_once 'include/lib/textLib.inc.php';
require_once 'include/lib/fileDisplayLib.inc.php';

//include personalised component files (announcemets.php etc.) from /modules/perso
require_once 'main/lessons.php';
require_once 'main/assignments.php';
require_once 'main/announcements.php';
require_once 'main/documents.php';
require_once 'main/agenda.php';
require_once 'main/forumPosts.php';

$_user['persoLastLogin'] = last_login($uid);
$_user['lastLogin'] = str_replace('-', ' ', $_user['persoLastLogin']);

//	BEGIN Get user's lesson info]=====================================================
$user_lesson_info = getUserLessonInfo($uid, "html");
//	END Get user's lesson info]=====================================================
//if user is registered to at least one lesson
if ($user_lesson_info[0][0] > 0) {
    // BEGIN - Get user assignments
    $param = array(
        'uid' => $uid,
        'max_repeat_val' => $user_lesson_info[0][0], //max repeat val (num of lessons)
        'lesson_titles' => $user_lesson_info[0][1],
        'lesson_code' => $user_lesson_info[0][2],
        'lesson_professor' => $user_lesson_info[0][3],
        'lesson_status' => $user_lesson_info[0][4],
        'lesson_id' => $user_lesson_info[0][8]
    );
    $user_assignments = getUserAssignments($param, "html");
    //END - Get user assignments
    // BEGIN - Get user announcements
    $param = array(
        'uid' => $uid,
        'max_repeat_val' => $user_lesson_info[0][0], //max repeat val (num of lessons)
        'lesson_titles' => $user_lesson_info[0][1],
        'lesson_code' => $user_lesson_info[0][2],
        'lesson_professor' => $user_lesson_info[0][3],
        'lesson_status' => $user_lesson_info[0][4],
        'usr_lst_login' => $_user["lastLogin"],
        'usr_memory' => $user_lesson_info[0][5],
        'lesson_id' => $user_lesson_info[0][8]
    );

    $user_announcements = getUserAnnouncements($param, 'html');
    // END - Get user announcements
    // BEGIN - Get user documents

    $param = array(
        'uid' => $uid,
        'max_repeat_val' => $user_lesson_info[0][0], //max repeat val (num of lessons)
        'lesson_titles' => $user_lesson_info[0][1],
        'lesson_code' => $user_lesson_info[0][2],
        'lesson_professor' => $user_lesson_info[0][3],
        'lesson_status' => $user_lesson_info[0][4],
        'usr_lst_login' => $_user["lastLogin"],
        'usr_memory' => $user_lesson_info[0][6]
    );

    $user_documents = getUserDocuments($param, "html");

    // END - Get user documents
    //BEGIN - Get user agenda
    $param = array(
        'uid' => $uid,
        'max_repeat_val' => $user_lesson_info[0][0], //max repeat val (num of lessons)
        'lesson_titles' => $user_lesson_info[0][1],
        'lesson_code' => $user_lesson_info[0][2],
        'lesson_professor' => $user_lesson_info[0][3],
        'lesson_status' => $user_lesson_info[0][4],
        'usr_lst_login' => $_user["lastLogin"],
        'lesson_id' => $user_lesson_info[0][8]
    );
    $user_agenda = getUserAgenda($param, "html");

    //END - Get user agenda
    //BEGIN - Get user forum posts
    $param = array(
        'uid' => $uid,
        'max_repeat_val' => $user_lesson_info[0][0], //max repeat val (num of lessons)
        'lesson_titles' => $user_lesson_info[0][1],
        'lesson_code' => $user_lesson_info[0][2],
        'lesson_professor' => $user_lesson_info[0][3],
        'lesson_status' => $user_lesson_info[0][4],
        'usr_lst_login' => $_user['lastLogin'],
        'usr_memory' => $user_lesson_info[0][7] //forum memory
    );
    $user_forumPosts = getUserForumPosts($param, "html");
    //END - Get user forum posts
} else {
    //show a "-" in all blocks if the user is not enrolled to any lessons
    // (except of the lessons block which is handled before)
    $user_assignments = "<p>-</p>";
    $user_announcements = "<p>-</p>";
    $user_documents = "<p>-</p>";
    $user_agenda = "<p>-</p>";
    $user_forumPosts = "<p>-</p>";
}

// ==  BEGIN create array with personalised content
$perso_tool_content = array(
    'lessons_content' => $user_lesson_info[1],
    'assigns_content' => $user_assignments,
    'announce_content' => $user_announcements,
    'docs_content' => $user_documents,
    'agenda_content' => $user_agenda,
    'forum_content' => $user_forumPosts
);

// == END create array with personalised content