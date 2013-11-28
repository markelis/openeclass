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


$require_current_course = true;
$require_login = true;
$require_help = true;
$helpTopic = 'For';
require_once '../../include/baseTheme.php';
require_once 'config.php';
require_once 'functions.php';
require_once 'include/lib/modalboxhelper.class.php';
require_once 'include/lib/multimediahelper.class.php';
require_once 'modules/search/indexer.class.php';
require_once 'modules/search/forumtopicindexer.class.php';
require_once 'modules/search/forumpostindexer.class.php';

ModalBoxHelper::loadModalBox();

if ($is_editor) {
    load_js('tools.js');
}

if (isset($_GET['all'])) {
    $paging = false;
} else {
    $paging = true;
}

if (isset($_GET['forum'])) {
    $forum = intval($_GET['forum']);
}
if (isset($_GET['topic'])) {
    $topic = intval($_GET['topic']);
}
$sql = "SELECT f.id, f.name FROM forum f, forum_topic t
            WHERE f.id = $forum
            AND t.id = $topic
            AND t.forum_id = f.id
            AND f.course_id = $course_id";

$result = db_query($sql);

if (!$myrow = mysql_fetch_array($result)) {
    $tool_content .= "<p class='alert1'>$langErrorTopicSelect</p>";
    draw($tool_content, 2);
    exit();
}
$forum_name = $myrow['name'];
$forum = $myrow['id'];
$total = get_total_posts($topic);

if (isset($_GET['delete']) && $is_editor) {
    $idx = new Indexer();
    $ftdx = new ForumTopicIndexer($idx);
    $fpdx = new ForumPostIndexer($idx);

    $post_id = intval($_GET['post_id']);
    $last_post_in_thread = get_last_post($topic);

    $result = db_query("SELECT post_time FROM forum_post
                            WHERE id = $post_id");

    $myrow = mysql_fetch_array($result);
    $this_post_time = $myrow["post_time"];

    db_query("DELETE FROM forum_post WHERE id = $post_id");
    $fpdx->remove($post_id);

    if ($total == 1) { // if exists one post in topic
        db_query("DELETE FROM forum_topic WHERE id = $topic AND forum_id = $forum");
        $ftdx->remove($topic);
        db_query("UPDATE forum SET num_topics = 0,
                            num_posts = 0
                            WHERE id = $forum
                            AND course_id = $course_id");
        header("Location: viewforum.php?course=$course_code&forum=$forum");
    } else {
        $sql = "SELECT MAX(id) AS last_post FROM forum_post
                                WHERE topic_id = $topic";
        $last_post = db_query_get_single_value($sql);

        db_query("UPDATE forum SET
                        `num_posts` = `num_posts`-1,
                        last_post_id = $last_post
                        WHERE id = $forum
                        AND course_id = $course_id");

        db_query("UPDATE forum_topic SET
                                `num_replies` = `num_replies`-1,
                                last_post_id = $last_post
                        WHERE id = $topic");
    }
    if ($last_post_in_thread == $this_post_time) {
        $topic_time_fixed = $last_post_in_thread;
        $sql = "UPDATE forum_topic
			SET topic_time = '$topic_time_fixed'
			WHERE id = $topic";
    }
    $tool_content .= "<p class='success'>$langDeletedMessage</p>";
}



if ($paging and $total > $posts_per_page) {
    $times = 0;
    for ($x = 0; $x < $total; $x += $posts_per_page) {
        $times++;
    }
    $pages = $times;
}

$result = db_query("SELECT title FROM forum_topic WHERE id = $topic");
$myrow = mysql_fetch_array($result);

$topic_subject = $myrow["title"];

if (!add_units_navigation(TRUE)) {
    $navigation[] = array('url' => "index.php?course=$course_code", 'name' => $langForums);
    $navigation[] = array('url' => "viewforum.php?course=$course_code&amp;forum=$forum", 'name' => $forum_name);
}
$nameTools = $topic_subject;

if (isset($_SESSION['message'])) {
    $tool_content .= $_SESSION['message'];
    unset($_SESSION['message']);
}
$tool_content .= "<div id='operations_container'>
	<ul id='opslist'>
	<li><a href='reply.php?course=$course_code&amp;topic=$topic&amp;forum=$forum'>$langReply";

$tool_content .= "</a></li></ul></div>";

if ($paging and $total > $posts_per_page) {
    $times = 1;
    $tool_content .= "
        <table width='100%' class='tbl'>
	<tr>
          <td width='50%' align='left'>
	  <span class='row'><strong class='pagination'>
	  <span>";

    if (isset($_GET['start'])) {
        $start = intval($_GET['start']);
    } else {
        $start = 0;
    }

    $last_page = $start - $posts_per_page;
    $tool_content .= "$langPages: ";

    for ($x = 0; $x < $total; $x += $posts_per_page) {
        if ($times != 1) {
            $tool_content .= "<span class='page-sep'>,</span>";
        }
        if ($start && ($start == $x)) {
            $tool_content .= "" . $times;
        } else if ($start == 0 && $x == 0) {
            $tool_content .= "1";
        } else {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$x'>$times</a>";
        }
        $times++;
    }

    $tool_content .= "</span></strong></span></td>
	<td align='right'>
	<span class='pages'>";
    if (isset($start) && $start > 0) {
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$last_page'>$langPreviousPage</a>&nbsp;|";
    } else {
        $start = 0;
    }
    if (($start + $posts_per_page) < $total) {
        $next_page = $start + $posts_per_page;
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$next_page'>$langNextPage</a>&nbsp;|";
    }
    $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;all=true'>$langAllOfThem</a></span>
	</td>
	</tr>
	</table>";
} else {
    $tool_content .= "<table width='100%' class='tbl'>
	<tr>
	<td width='60%' align='left'>
	<span class='row'><strong class='pagination'>&nbsp;</strong></span></td>
	<td align='right'>";
    if ($total > $posts_per_page) {
        $tool_content .= "<span class='pages'>
		&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=0'>$langPages</a>
		</span>";
    }
    $tool_content .= "</td></tr></table>";
}

$tool_content .= "<table width='100%' class='tbl_alt'>
    <tr>
      <th width='220'>$langAuthor</th>
      <th>$langMessage</th>";
if ($is_editor) {
    $tool_content .= "<th width='60'>$langActions</th>";
}
$tool_content .= "</tr>";

if (isset($_GET['all'])) {
    $sql = "SELECT * FROM forum_post WHERE topic_id = $topic ORDER BY id";
} elseif (isset($_GET['start'])) {
    $start = intval($_GET['start']);
    $sql = "SELECT * FROM forum_post
		WHERE topic_id = $topic
		ORDER BY id
                LIMIT $start, $posts_per_page";
} else {
    $sql = "SELECT * FROM forum_post
		WHERE topic_id = '$topic'
		ORDER BY id
                LIMIT $posts_per_page";
}
$result = db_query($sql);
$myrow = mysql_fetch_array($result);
$count = 0;
do {
    if ($count % 2 == 1) {
        $tool_content .= "<tr class='odd'>";
    } else {
        $tool_content .= "<tr class='even'>";
    }
    $tool_content .= "<td valign='top'>" . display_user($myrow['poster_id']) . "</td>";
    $message = $myrow["post_text"];
    // support for math symbols
    $message = mathfilter($message, 12, "../../courses/mathimg/");
    if ($count == 0) {
        $postTitle = "<b>$langPostTitle: </b>" . q($topic_subject);
    } else {
        $postTitle = "";
    }
    $tool_content .= "<td>
	  <div>
	    <b>$langSent: </b>" . $myrow["post_time"] . "<br>$postTitle
	  </div>
	  <br />$message<br />
	</td>";
    if ($is_editor) {
        $tool_content .= "<td width='40' valign='top'>
                    <a href='editpost.php?course=$course_code&amp;post_id=" . $myrow["id"] . "&amp;topic=$topic&amp;forum=$forum'>" .
                "<img src='$themeimg/edit.png' title='$langModify' alt='$langModify' /></a>" .
                "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;post_id=" . $myrow['id'] .
                "&amp;topic=$topic&amp;forum=$forum&amp;delete=on' onClick=\"return confirmation('$langConfirmDelete');\">" .
                "<img src='$themeimg/delete.png' title='$langDelete' alt='$langDelete'></a></td>";
    }
    $tool_content .= "</tr>";
    $count++;
} while ($myrow = mysql_fetch_array($result));

$sql = "UPDATE forum_topic SET num_views = num_views + 1
            WHERE id = $topic AND forum_id = $forum";
db_query($sql);

$tool_content .= "</table>";

if ($paging and $total > $posts_per_page) {
    $times = 1;
    $tool_content .= "<table class='tbl'>
	<tr>
	<td width='50%'>
	<span class='row'><strong class='pagination'><span>";

    $last_page = $start - $posts_per_page;
    $tool_content .= "$langPages: ";

    for ($x = 0; $x < $total; $x += $posts_per_page) {
        if ($times != 1) {
            $tool_content .= "\n<span class='page-sep'>,</span>";
        }
        if ($start && ($start == $x)) {
            $tool_content .= "" . $times;
        } else if ($start == 0 && $x == 0) {
            $tool_content .= "1";
        } else {
            $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$x'>$times</a>";
        }
        $times++;
    }
    $tool_content .= "</span></strong></span></td>
	<td><span class='pages'>";
    if (isset($start) && $start > 0) {
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$last_page'>$langPreviousPage</a>&nbsp;|";
    } else {
        $start = 0;
    }
    if (($start + $posts_per_page) < $total) {
        $next_page = $start + $posts_per_page;
        $tool_content .= "<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=$next_page'>$langNextPage</a>&nbsp;|";
    }
    $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;all=true'>$langAllOfThem</a>
	</span>
	</td></tr></table>";
} else {
    $tool_content .= "<table class='tbl'>
	<tr>
	<td width='60%' align='left'>
	<span class='row'><strong class='pagination'>&nbsp;</strong>
	</span></td>
	<td align='right'>
	<span class='pages'>";
    if ($total > $posts_per_page) {
        $tool_content .= "&nbsp;<a href='$_SERVER[SCRIPT_NAME]?course=$course_code&amp;topic=$topic&amp;forum=$forum&amp;start=0'>$langPages</a>";
    } else {
        $tool_content .= '&nbsp;';
    }
    $tool_content .= "</span></td></tr></table>";
}
draw($tool_content, 2, null, $head_content);
