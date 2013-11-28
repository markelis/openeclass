<?php

/* ========================================================================
 * Open eClass 3.0
 * E-learning and Course Management System
 * ========================================================================
 * Copyright 2003-2012  Greek Universities Network - GUnet
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


$require_current_course = TRUE;
$require_login = TRUE;
$require_help = FALSE;
$require_editor = TRUE;
require_once '../../include/baseTheme.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'modules/search/indexer.class.php';
require_once 'modules/search/forumtopicindexer.class.php';
require_once 'modules/search/forumpostindexer.class.php';

$idx = new Indexer();
$ftdx = new ForumTopicIndexer($idx);
$fpdx = new ForumPostIndexer($idx);

if (isset($_REQUEST['forum'])) {
    $forum_id = intval($_REQUEST['forum']);
}
if (isset($_REQUEST['topic'])) {
    $topic_id = intval($_REQUEST['topic']);
}
if (isset($_REQUEST['post_id'])) {
    $post_id = intval($_REQUEST['post_id']);
}
if (isset($_POST['submit'])) {
    $message = $_POST['message'];

    $sql = "UPDATE forum_post SET post_text = " . autoquote(purify($message)) . "
                        WHERE id = $post_id";
    if (!$result = db_query($sql)) {
        $tool_content .= $langUnableUpdatePost;
        draw($tool_content, 2, null, $head_content);
        exit();
    }
    $fpdx->store($post_id);

    if (isset($_POST['subject'])) {
        $subject = $_POST['subject'];
        $sql = "UPDATE forum_topic
                                SET title = " . autoquote(trim($subject)) . "
                        WHERE id = $topic_id";
        if (!$result = db_query($sql)) {
            $tool_content .= $langUnableUpdateTopic;
            draw($tool_content, 2, null, $head_content);
            exit();
        }
        $ftdx->store($topic_id);
    }
    header("Location: {$urlServer}modules/forum/viewtopic.php?course=$course_code&topic=$topic_id&forum=$forum_id");
    exit;
} else {
    $sql = "SELECT f.name, t.title
                        FROM forum f, forum_topic t
                        WHERE f.id = $forum_id
                                AND t.id = $topic_id
                                AND t.forum_id = f.id";

    if (!$result = db_query($sql)) {
        $tool_content .= $langTopicInformation;
        draw($tool_content, 2, null, $head_content);
        exit();
    }
    $myrow = mysql_fetch_array($result);

    $nameTools = $langReply;
    $navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langForums);
    $navigation[] = array('url' => "viewforum.php?course=$course_code&amp;forum=$forum_id", 'name' => $myrow['name']);
    $navigation[] = array('url' => "viewtopic.php?course=$course_code&amp;topic=$topic_id&amp;forum=$forum_id", 'name' => $myrow['title']);

    $sql = "SELECT p.post_text, p.post_time, t.title
                        FROM forum_post p, forum_topic t
                        WHERE p.id = $post_id
                        AND p.topic_id = t.id";
    $result = db_query($sql);
    $myrow = mysql_fetch_array($result);
    $message = $myrow["post_text"];
    $message = str_replace('{', '&#123;', $message);
    list($day, $time) = explode(' ', $myrow["post_time"]);
    $tool_content .= "<form action='$_SERVER[SCRIPT_NAME]?course=$course_code' method='post'>
                <fieldset>
                <legend>$langReplyEdit </legend>
                <table width='100%' class='tbl'>";
    $first_post = is_first_post($topic_id, $post_id);
    if ($first_post) {
        $tool_content .= "<tr><th>$langSubject:<br /><br />
                <input type='text' name='subject' size='53' maxlength='100' value='" . stripslashes($myrow["title"]) . "'  class='FormData_InputText' /></th>
                </tr>";
    }
    $tool_content .= "<tr><td><b>$langBodyMessage:</b><br /><br />" .
            rich_text_editor('message', 10, 50, $message, "class='FormData_InputText'")
            . "
        </td></tr>
        <tr><td class='right'>";
    $tool_content .= "<input type='hidden' name='post_id' value='$post_id'>";
    $tool_content .= "<input type='hidden' name='topic' value='$topic_id'>";
    $tool_content .= "<input type='hidden' name='forum' value='$forum_id'>";
    $tool_content .= "<input class='Login' type='submit' name='submit' value='$langSubmit' />
        </td></tr>
        </table>
        </fieldset></form>";
}
draw($tool_content, 2, null, $head_content);
